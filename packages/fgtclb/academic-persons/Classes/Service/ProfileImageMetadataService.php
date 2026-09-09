<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Service;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

/**
 * Keeps the title and alternative text of a profile's image reference equal to the
 * profile's name, per profile record: a translation carries its own `title`, so its
 * reference - localized or independent - gets the text composed from the translation
 * row, while the default-language reference gets the default-language name.
 *
 * The `sys_file_reference` row is what makes that text language-correct: it overrides
 * the file metadata for this one reference, so each language of a profile describes
 * its own image with its own name.
 *
 * The `sys_file_metadata` record of the file is rewritten alongside it, with the same
 * text, on every save that reaches this service. Two consequences follow and are
 * intended. A file is shared between the languages of a profile until one of them
 * uploads its own, so that record ends up carrying the name of whichever language was
 * saved last - the reference row is where the language-correct text lives, and it is
 * what the frontend renders. And a value a backend editor typed there is replaced on
 * the next save of the profile; a listener of `ModifyProfileImageMetadataEvent` is the
 * way to keep one.
 *
 * {@see initializeFileMetadata()} is the separate, gentler write of that same record,
 * for a file the frontend editing has just uploaded: the indexer creates it empty, and
 * `alternative`, `title` and `copyright` are what an installation running
 * `EXT:filemetadata` or `fgtclb/file-required-attributes` reports as missing required
 * attributes. It fills only what the record has nothing in, and `copyright` is written
 * there and nowhere else - it is not a name, so no later profile save touches it.
 *
 * Both writers of the profile name reach this service: the DataHandler hook for
 * backend saves and localizations, and the `AfterProfileUpdateEvent` listener for
 * the frontend editing flow, which persists through Extbase and never sees a hook.
 *
 * Every write announces itself with `ModifyProfileImageMetadataEvent` first, so a
 * project can change the fields or add the ones its own installation requires.
 *
 * @internal owned by EXT:academic_persons, no public API.
 */
final readonly class ProfileImageMetadataService
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_FILE_METADATA = 'sys_file_metadata';
    private const TABLE_REFERENCE = 'sys_file_reference';

    /**
     * Columns no listener of `ModifyProfileImageMetadataEvent` may write: the identity
     * of the record, the relation it is part of, and the localization state of a
     * translated reference. They are not `ctrl` configuration and therefore literal -
     * the configurable system columns are read from the schema next to this list,
     * exactly as the writer of the relation reads them.
     *
     * @var list<string>
     */
    private const STRUCTURAL_FIELDS = [
        'uid',
        'pid',
        'l10n_state',
        't3ver_oid',
        't3ver_wsid',
        't3ver_state',
        't3ver_stage',
        // `sys_file_metadata`: the file the record describes.
        'file',
        // `sys_file_reference`: the relation itself.
        'uid_local',
        'uid_foreign',
        'tablenames',
        'fieldname',
        'sorting_foreign',
    ];

    /**
     * The `ctrl` keys whose value is a single column name that a listener must not
     * write either - the soft delete flag, the timestamps, the sorting, the
     * localization columns and the editing lock.
     *
     * @var list<string>
     */
    private const SYSTEM_FIELD_CTRL_KEYS = [
        'delete',
        'tstamp',
        'crdate',
        'cruser_id',
        'sortby',
        'origUid',
        'languageField',
        'transOrigPointerField',
        'transOrigDiffSourceField',
        'translationSource',
        'editlock',
    ];

    /**
     * The `sys_file_metadata` columns an upload fills, all three with the composed
     * name. `title` and `alternative` are core columns and always exist; `copyright`
     * is one `EXT:filemetadata` adds, and is skipped where the schema does not have
     * it. Everything beyond these three is for a listener of
     * {@see \FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent} to fill -
     * `right_of_use` of `fgtclb/file-required-attributes`, for one.
     *
     * @var list<string>
     */
    private const UPLOAD_METADATA_FIELDS = ['title', 'alternative', 'copyright'];

    /**
     * The `sys_file_metadata` columns that carry the composed name and are therefore
     * rewritten on every profile save, not only by the upload. `copyright` is not one
     * of them: it is not a name, and the upload stays its only writer.
     *
     * @var list<string>
     */
    private const NAME_METADATA_FIELDS = ['title', 'alternative'];

    public function __construct(
        private ConnectionPool $connectionPool,
        private ProfileImageRelationWriter $profileImageRelationWriter,
        private LoggerInterface $logger,
        private TcaSchemaFactory $tcaSchemaFactory,
        private MetaDataRepository $metaDataRepository,
        private ResourceFactory $resourceFactory,
        private EventDispatcherInterface $eventDispatcher,
        private DataHandlerExecutionContext $dataHandlerExecutionContext,
    ) {}

    /**
     * @return array<string, string>|null The fields written, or null when the profile
     *                                    is unpersisted or has no image reference.
     */
    public function update(Profile $profile, ?ServerRequestInterface $request = null): ?array
    {
        $profileUid = $profile->getUid();
        return $profileUid === null ? null : $this->updateForProfileUid($profileUid, $request);
    }

    /**
     * @return array<string, string>|null The fields written, or null when the profile
     *                                    does not exist, has no image reference, or a
     *                                    listener emptied the field map.
     */
    public function updateForProfileUid(int $profileUid, ?ServerRequestInterface $request = null): ?array
    {
        $metadataText = $this->composeMetadataText($profileUid);
        if ($metadataText === null) {
            return null;
        }
        $imageReference = $this->profileImageRelationWriter->findImageReference($profileUid);
        if ($imageReference === null) {
            return null;
        }
        // Announced and written on its own, before the reference: the two records are
        // independent, and a listener that empties the field map of one must not
        // decide anything about the other.
        $this->refreshFileMetadata(
            $imageReference['uid_local'],
            $imageReference['uid'],
            $profileUid,
            $metadataText,
            $request,
        );
        $metadata = ['title' => $metadataText, 'alternative' => $metadataText];
        try {
            $metadata = $this->dispatchModification(
                self::TABLE_REFERENCE,
                $imageReference['uid_local'],
                $imageReference['uid'],
                $profileUid,
                $metadata,
                $request,
            );
            if ($metadata === []) {
                return null;
            }
            $this->profileImageRelationWriter->updateReferenceMetadata($imageReference['uid'], $metadata);
        } catch (\Throwable $exception) {
            // Called from inside a DataHandler hook: a failed metadata write must not
            // turn an otherwise successful profile save into an exception - and the
            // nested DataHandler run can surface more than the writer's own
            // RuntimeException.
            $this->logger->error(
                'The image metadata of profile {profileUid} could not be written: {reason}',
                ['profileUid' => $profileUid, 'reason' => $exception->getMessage()],
            );
            return null;
        }
        return $metadata;
    }

    /**
     * Fills the `sys_file_metadata` record of a file the frontend profile editing has
     * just uploaded, and only there: the record the indexer created for it is empty,
     * nothing else in this extension writes it, and its `alternative`, `title` and
     * `copyright` are what an installation running `EXT:filemetadata` or
     * `fgtclb/file-required-attributes` reports as missing required attributes. A
     * value the record already carries is kept here.
     *
     * That last part decides `copyright` and nothing else in practice. The upload
     * calls this first and {@see updateForProfileUid()} immediately after, and that
     * one rewrites `title` and `alternative` whatever they hold - so for those two
     * columns the "keep what is there" of this method is overwritten within the same
     * request. `copyright` is the column it really protects: no later profile save
     * touches it, so a value found here survives.
     *
     * The text is the one {@see updateForProfileUid()} writes on the reference: the
     * composed name of the profile that uploaded the file.
     *
     * @return array<string, string>|null The fields written, or null when there is
     *                                    nothing to write - an unknown profile, a
     *                                    profile without a name, or a record that
     *                                    carries both values already.
     */
    public function initializeFileMetadata(
        File $file,
        int $profileUid,
        ?ServerRequestInterface $request = null,
    ): ?array {
        $fileUid = $file->getUid();
        if ($fileUid <= 0) {
            return null;
        }
        $metadataText = $this->composeMetadataText($profileUid);
        if ($metadataText === null || $metadataText === '') {
            return null;
        }
        try {
            $fileMetadata = $this->metaDataRepository->findByFileUid($fileUid);
            $schema = $this->tcaSchemaFactory->get(self::TABLE_FILE_METADATA);
            $metadata = [];
            foreach (self::UPLOAD_METADATA_FIELDS as $fieldName) {
                if (!$schema->hasField($fieldName)) {
                    continue;
                }
                if (trim((string)($fileMetadata[$fieldName] ?? '')) === '') {
                    $metadata[$fieldName] = $metadataText;
                }
            }
            // Dispatched even when both fields are filled already: adding a column the
            // installation requires is exactly what a listener is here for, and it must
            // not depend on whether the core fields happened to be empty.
            $metadata = $this->dispatchModification(
                self::TABLE_FILE_METADATA,
                $fileUid,
                $this->profileImageRelationWriter->findImageReference($profileUid)['uid'] ?? null,
                $profileUid,
                $metadata,
                $request,
            );
            if ($metadata === []) {
                return null;
            }
            if ($fileMetadata === []) {
                $this->metaDataRepository->createMetaDataRecord($fileUid, $metadata);
            } else {
                $this->metaDataRepository->update($fileUid, $metadata, $fileMetadata);
            }
        } catch (\Throwable $exception) {
            // The upload itself succeeded and the image is assigned by now: metadata
            // that could not be written is a defect to log, not a reason to refuse the
            // request and delete the file the person just uploaded.
            $this->logger->error(
                'The file metadata of the image of profile {profileUid} could not be written: {reason}',
                ['profileUid' => $profileUid, 'reason' => $exception->getMessage()],
            );
            return null;
        }
        return $metadata;
    }

    /**
     * Rewrites the `sys_file_metadata` record of the profile image with the composed
     * name, on every save that reaches this service: a frontend edit, a backend save
     * and a localization alike (ACE-559).
     *
     * This overwrites what the record carries, unlike {@see initializeFileMetadata()},
     * which fills only the fields an upload found empty. Two consequences are
     * deliberate. A file is shared between the languages of a profile, so the record
     * ends up carrying the name of whichever language was saved last - the
     * language-correct text is on the reference row, which is what the frontend
     * renders. And a value a backend editor typed into the file record is replaced on
     * the next save of the profile; a listener of
     * {@see \FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent} is the way
     * to keep one.
     *
     * A profile without a name is skipped rather than blanking the record. Failures
     * are logged and swallowed: the profile save itself has succeeded by now.
     */
    private function refreshFileMetadata(
        int $fileUid,
        ?int $fileReferenceUid,
        int $profileUid,
        string $metadataText,
        ?ServerRequestInterface $request,
    ): void {
        if ($fileUid <= 0 || $metadataText === '') {
            return;
        }
        // Unlike the reference row, this record is written through `MetaDataRepository`
        // and never sees the DataHandler, so there is no workspace version of it: a
        // frontend request previewing a workspace would change live data. It is
        // refused here for the same reason the record synchronization refuses it
        // (ACE-492). Backend and CLI act in the workspace of their own user.
        if ($this->dataHandlerExecutionContext->isFrontendRequestInWorkspace()) {
            return;
        }
        try {
            $fileMetadata = $this->metaDataRepository->findByFileUid($fileUid);
            $schema = $this->tcaSchemaFactory->get(self::TABLE_FILE_METADATA);
            $metadata = [];
            foreach (self::NAME_METADATA_FIELDS as $fieldName) {
                if ($schema->hasField($fieldName)) {
                    $metadata[$fieldName] = $metadataText;
                }
            }
            $metadata = $this->dispatchModification(
                self::TABLE_FILE_METADATA,
                $fileUid,
                $fileReferenceUid,
                $profileUid,
                $metadata,
                $request,
            );
            if ($metadata === []) {
                return;
            }
            if ($fileMetadata === []) {
                $this->metaDataRepository->createMetaDataRecord($fileUid, $metadata);
            } else {
                $this->metaDataRepository->update($fileUid, $metadata, $fileMetadata);
            }
        } catch (\Throwable $exception) {
            $this->logger->error(
                'The file metadata of the image of profile {profileUid} could not be refreshed: {reason}',
                ['profileUid' => $profileUid, 'reason' => $exception->getMessage()],
            );
        }
    }

    /**
     * Hands the fields about to be written to
     * {@see \FGTCLB\AcademicPersons\Event\ModifyProfileImageMetadataEvent} and returns
     * what the listeners left, minus the system fields of the target table.
     *
     * The file and the image reference are resolved for the event and only for it; a
     * file that cannot be resolved any more skips the dispatch rather than the write,
     * because the metadata of a record whose file went missing is still the metadata
     * of that record.
     *
     * @param array<string, string> $metadata
     * @return array<string, string>
     */
    private function dispatchModification(
        string $targetTable,
        int $fileUid,
        ?int $fileReferenceUid,
        int $profileUid,
        array $metadata,
        ?ServerRequestInterface $request,
    ): array {
        try {
            $file = $this->resourceFactory->getFileObject($fileUid);
            $fileReference = $fileReferenceUid === null
                ? null
                : $this->resourceFactory->getFileReferenceObject($fileReferenceUid);
        } catch (\Throwable $exception) {
            $this->logger->warning(
                'The image metadata of profile {profileUid} is written without dispatching {event}: {reason}',
                [
                    'profileUid' => $profileUid,
                    'event' => ModifyProfileImageMetadataEvent::class,
                    'reason' => $exception->getMessage(),
                ],
            );
            return $metadata;
        }
        $event = new ModifyProfileImageMetadataEvent(
            $targetTable,
            $file,
            $fileReference,
            $profileUid,
            $metadata,
            $request,
        );
        $this->eventDispatcher->dispatch($event);
        return $this->withoutSystemFields($targetTable, $event->getMetadata(), $profileUid);
    }

    /**
     * Drops the system fields of the table from what a listener left behind. This
     * event writes metadata: the identity of a record, the relation it belongs to, its
     * localization, its workspace and its enable columns are the DataHandler's, and a
     * listener setting `uid_local` would repoint the profile image rather than
     * describe it. A refusal is logged, so a listener that tried does not fail
     * silently.
     *
     * @param array<string, string> $metadata
     * @return array<string, string>
     */
    private function withoutSystemFields(string $tableName, array $metadata, int $profileUid): array
    {
        $protected = $this->getSystemFieldNames($tableName);
        $refused = array_intersect(array_keys($metadata), $protected);
        if ($refused === []) {
            return $metadata;
        }
        $this->logger->warning(
            'A listener of {event} tried to write the system fields {fields} of {table} for profile'
            . ' {profileUid}. They are refused; only metadata columns are written.',
            [
                'event' => ModifyProfileImageMetadataEvent::class,
                'fields' => implode(', ', $refused),
                'table' => $tableName,
                'profileUid' => $profileUid,
            ],
        );
        return array_diff_key($metadata, array_flip($protected));
    }

    /**
     * The system columns of a table: the literal ones a table cannot configure away,
     * plus every `ctrl` key that names one - the soft delete flag, the timestamps, the
     * sorting, the localization columns, the editing lock - and the enable columns.
     *
     * @return list<string>
     */
    private function getSystemFieldNames(string $tableName): array
    {
        $configuration = $this->tcaSchemaFactory->get($tableName)->getRawConfiguration();
        $names = self::STRUCTURAL_FIELDS;
        foreach (self::SYSTEM_FIELD_CTRL_KEYS as $key) {
            $name = $configuration[$key] ?? null;
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }
        foreach ((array)($configuration['enablecolumns'] ?? []) as $name) {
            if (is_string($name) && $name !== '') {
                $names[] = $name;
            }
        }
        return array_values(array_unique($names));
    }

    /**
     * The text both writers use: the name of the profile record, composed from its
     * title and its name columns. Null when no such record exists.
     */
    private function composeMetadataText(int $profileUid): ?string
    {
        if ($profileUid <= 0) {
            return null;
        }
        $profileRecord = $this->connectionPool
            ->getConnectionForTable(self::PROFILE_TABLE)
            ->select(
                ['title', 'first_name', 'middle_name', 'last_name'],
                self::PROFILE_TABLE,
                ['uid' => $profileUid, $this->getDeletedColumnName() => 0],
            )
            ->fetchAssociative();
        if ($profileRecord === false) {
            return null;
        }
        return $this->buildMetadataText(
            (string)($profileRecord['title'] ?? ''),
            (string)($profileRecord['first_name'] ?? ''),
            (string)($profileRecord['middle_name'] ?? ''),
            (string)($profileRecord['last_name'] ?? ''),
        );
    }

    /**
     * The configured soft-delete column of the profile table. It is TCA `ctrl`
     * configuration, not a constant, so it is read from the schema.
     */
    private function getDeletedColumnName(): string
    {
        return $this->tcaSchemaFactory->get(self::PROFILE_TABLE)
            ->getCapability(TcaSchemaCapability::SoftDelete)
            ->getFieldName();
    }

    private function buildMetadataText(string ...$parts): string
    {
        $parts = array_map(
            static fn(string $part): string => trim((string)preg_replace('/\s+/u', ' ', $part)),
            $parts,
        );
        return implode(' ', array_filter($parts, static fn(string $part): bool => $part !== ''));
    }
}
