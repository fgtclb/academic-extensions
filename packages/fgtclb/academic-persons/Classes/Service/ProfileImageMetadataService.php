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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Keeps the profile image metadata synchronized with the profile name.
 *
 * The text is written to both FAL file metadata and the profile's file reference.
 * This makes the metadata consistent for newly uploaded images, overwritten images,
 * backend updates and later changes to the profile name.
 *
 * @internal to be used only in EXT:academic_persons and not part of public API.
 */
final class ProfileImageMetadataService
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ResourceFactory $resourceFactory,
    ) {}

    /**
     * Updates metadata for the image currently assigned to the profile.
     *
     * @return array{alternative: string, title: string}|null Updated metadata, or null
     *                                                          when the profile has no image.
     */
    public function update(Profile $profile): ?array
    {
        $profileUid = $profile->getUid();
        return $profileUid === null ? null : $this->updateForProfileUid($profileUid);
    }

    /**
     * Updates the persisted image metadata for a profile record.
     *
     * This entry point is also used by the DataHandler hook, so backend changes
     * to the profile name update the same metadata as frontend changes.
     *
     * @return array{alternative: string, title: string}|null Updated metadata, or null
     *                                                          when the profile has no usable image.
     */
    public function updateForProfileUid(int $profileUid): ?array
    {
        if ($profileUid <= 0) {
            return null;
        }
        $profileRecord = $this->connectionPool
            ->getConnectionForTable('tx_academicpersons_domain_model_profile')
            ->select(
                ['sys_language_uid', 'title', 'first_name', 'middle_name', 'last_name'],
                'tx_academicpersons_domain_model_profile',
                ['uid' => $profileUid],
            )
            ->fetchAssociative();
        if ($profileRecord === false) {
            return null;
        }
        $imageReference = $this->findProfileImageReference($profileUid);
        if ($imageReference === null) {
            return null;
        }
        $imageReferenceUid = $imageReference['uid'];
        try {
            $imageFile = $this->resourceFactory
                ->getFileReferenceObject($imageReferenceUid)
                ->getOriginalFile();
        } catch (\Throwable) {
            return null;
        }
        if (!$imageFile instanceof File) {
            return null;
        }
        $metadataText = $this->buildMetadataText(
            (string)($profileRecord['title'] ?? ''),
            (string)($profileRecord['first_name'] ?? ''),
            (string)($profileRecord['middle_name'] ?? ''),
            (string)($profileRecord['last_name'] ?? ''),
        );
        $metadata = ['alternative' => $metadataText, 'title' => $metadataText];
        $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
        $fileMetadata = $metaDataRepository->findByFileUid($imageFile->getUid());
        if ($fileMetadata === []) {
            $fileMetadata = $metaDataRepository->createMetaDataRecord($imageFile->getUid());
        }
        $metaDataRepository->update($imageFile->getUid(), $metadata, $fileMetadata);
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder
            ->update('sys_file_reference')
            ->set('alternative', $metadataText)
            ->set('title', $metadataText)
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($imageReferenceUid, Connection::PARAM_INT),
                ),
            )
            ->executeStatement();
        return $metadata;
    }

    /**
     * Resolves the real FAL relation instead of interpreting the profile's `image`
     * column as a reference uid. DataHandler stores a relation count in that column,
     * while Extbase may temporarily expose a FileReference identity during uploads.
     *
     * @return array{uid: int, uid_local: int}|null
     */
    private function findProfileImageReference(int $profileUid): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $row = $queryBuilder
            ->select('uid', 'uid_local')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter('tx_academicpersons_domain_model_profile'),
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter('image'),
                ),
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy('sorting_foreign')
            ->addOrderBy('uid')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if ($row === false) {
            return null;
        }
        return [
            'uid' => (int)$row['uid'],
            'uid_local' => (int)$row['uid_local'],
        ];
    }

    private function buildMetadataText(string ...$parts): string
    {
        $parts = array_map(
            static fn(string $part): string => trim(
                (string)(preg_replace('/\s+/u', ' ', $part) ?? $part),
            ),
            $parts,
        );
        return implode(' ', array_filter($parts, static fn(string $part): bool => $part !== ''));
    }
}
