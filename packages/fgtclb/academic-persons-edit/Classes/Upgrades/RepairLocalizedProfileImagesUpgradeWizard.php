<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Upgrades;

use FGTCLB\AcademicPersons\Service\ProfileImageRelationWriter;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Brings the profile image relations written before 3.0 into the shape the
 * translatable image column expects.
 *
 * Until 3.0 the column was `l10n_mode=exclude`, and the pre-3.0 upload and
 * synchronisation paths of this extension wrote the relation rows and the relation
 * counter of the profile by hand. Three shapes of that data are defects under the
 * `l10n_state` model of the column and are repaired here:
 *
 * - **Duplicate references** on one profile: the reference the frontend renders -
 *   the first by `sorting_foreign`, then `uid` - is kept, every other one is deleted,
 *   and a file no reference uses any more goes with it.
 * - **A relation counter** that disagrees with the number of references of a
 *   default-language profile or of a translation with an image of its own.
 * - **A translation carrying its own, non-localized reference** without the
 *   `custom` localization state: the next synchronisation would replace that
 *   reference with a localization of the default-language one. The state is set.
 *
 * A translation whose image follows the default-language profile (the `parent`
 * state, with a localized reference or none at all) is the model's regular shape and
 * is left alone; only a stale relation counter on such a translation is corrected -
 * by re-submitting the default-language relation, so the core carries it into the
 * translation the way every later synchronisation will. That correction needs a
 * usable language parent: a translation whose `l10n_parent` is missing, deleted or
 * itself a translation is repaired as an independent record instead, and the orphan
 * is logged - a wizard that throws halfway through has already applied part of its
 * repairs, and this is exactly the broken data it exists for.
 *
 * **No file is deleted.** The wizard rewrites relations only. Deciding that a file is
 * unused would mean asking `sys_file_reference`, which records FAL relations and knows
 * nothing about an RTE `t3://file` link, a typolink or a file collection - and an
 * unattended bulk delete on that basis is unrecoverable. Files left without a relation
 * are for the "unused files" tooling of the install tool to report, with a human
 * looking at the list.
 *
 * Every write goes through {@see ProfileImageRelationWriter}, and so through the
 * DataHandler: reference index, history and the localization state are the core's.
 */
#[UpgradeWizard('academicPersonsEdit_repairLocalizedProfileImages')]
final readonly class RepairLocalizedProfileImagesUpgradeWizard implements UpgradeWizardInterface
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const REFERENCE_TABLE = 'sys_file_reference';
    private const FIELD_NAME = 'image';

    public function __construct(
        private ConnectionPool $connectionPool,
        private ResourceFactory $resourceFactory,
        private ProfileImageRelationWriter $profileImageRelationWriter,
        private LoggerInterface $logger,
    ) {}

    public function getTitle(): string
    {
        return 'Repair the profile image relations of academic profiles';
    }

    public function getDescription(): string
    {
        return 'Reduces duplicate profile image references to the rendered one, corrects relation counters,'
            . ' and marks translations that carry an image of their own as independent from the'
            . ' default-language image - through the TYPO3 DataHandler. No file is deleted.';
    }

    public function executeUpdate(): bool
    {
        $familiesToPropagate = [];
        foreach ($this->getAffectedProfiles() as $profile) {
            if ($profile['repair'] === 'propagate') {
                $familiesToPropagate[$profile['l10n_parent']] = true;
                continue;
            }
            $retainedFile = $this->findRetainedFile($profile['references']);
            if ($retainedFile === null) {
                $this->profileImageRelationWriter->remove($profile['uid']);
                continue;
            }
            $this->profileImageRelationWriter->replace($profile['uid'], $retainedFile);
        }
        foreach (array_keys($familiesToPropagate) as $defaultProfileUid) {
            $this->profileImageRelationWriter->propagateToTranslations($defaultProfileUid);
        }
        return true;
    }

    public function updateNecessary(): bool
    {
        return $this->getAffectedProfiles() !== [];
    }

    /**
     * @return list<class-string>
     */
    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    /**
     * @param list<array{uid: int, uid_local: int, l10n_parent: int}> $references
     */
    private function findRetainedFile(array $references): ?File
    {
        $fileUid = $references[0]['uid_local'] ?? 0;
        if ($fileUid <= 0) {
            return null;
        }
        try {
            return $this->resourceFactory->getFileObject($fileUid);
        } catch (FileDoesNotExistException) {
            return null;
        }
    }

    /**
     * Every non-deleted profile that has a relation counter or a reference, joined
     * with its non-deleted image references. The `deleted` condition of the
     * reference belongs to the join, not to the where clause: in the where clause it
     * would drop the profiles without any reference, and a stale counter on exactly
     * those is one of the defects to find.
     *
     * @return list<array{uid: int, sys_language_uid: int, l10n_parent: int, repair: 'rewrite'|'propagate', references: list<array{uid: int, uid_local: int, l10n_parent: int}>}>
     */
    private function getAffectedProfiles(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PROFILE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select(
                'p.uid',
                'p.sys_language_uid',
                'p.l10n_parent',
                'p.image',
                'p.l10n_state',
                'r.uid AS reference_uid',
                'r.uid_local AS reference_uid_local',
                'r.l10n_parent AS reference_l10n_parent',
            )
            ->from(self::PROFILE_TABLE, 'p')
            ->leftJoin(
                'p',
                self::REFERENCE_TABLE,
                'r',
                (string)$queryBuilder->expr()->and(
                    $queryBuilder->expr()->eq('r.uid_foreign', $queryBuilder->quoteIdentifier('p.uid')),
                    $queryBuilder->expr()->eq(
                        'r.tablenames',
                        $queryBuilder->createNamedParameter(self::PROFILE_TABLE),
                    ),
                    $queryBuilder->expr()->eq(
                        'r.fieldname',
                        $queryBuilder->createNamedParameter(self::FIELD_NAME),
                    ),
                    $queryBuilder->expr()->eq('r.deleted', 0),
                ),
            )
            ->where(
                $queryBuilder->expr()->eq('p.deleted', 0),
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->neq('p.image', 0),
                    $queryBuilder->expr()->isNotNull('r.uid'),
                ),
            )
            ->orderBy('p.uid')
            ->addOrderBy('r.sorting_foreign')
            ->addOrderBy('r.uid')
            ->executeQuery()
            ->fetchAllAssociative();

        $profiles = [];
        foreach ($rows as $row) {
            $profileUid = (int)$row['uid'];
            $profiles[$profileUid] ??= [
                'uid' => $profileUid,
                'sys_language_uid' => (int)$row['sys_language_uid'],
                'l10n_parent' => (int)$row['l10n_parent'],
                'image' => (int)$row['image'],
                'custom' => $this->hasCustomImageState((string)($row['l10n_state'] ?? '')),
                'references' => [],
            ];
            if ((int)$row['reference_uid'] > 0) {
                $profiles[$profileUid]['references'][] = [
                    'uid' => (int)$row['reference_uid'],
                    'uid_local' => (int)$row['reference_uid_local'],
                    'l10n_parent' => (int)$row['reference_l10n_parent'],
                ];
            }
        }

        $usableParentUids = $this->findUsableLanguageParentUids($profiles);
        $affectedProfiles = [];
        foreach ($profiles as $profile) {
            $repair = $this->determineRepair($profile, $usableParentUids);
            if ($repair === null) {
                continue;
            }
            $affectedProfiles[] = [
                'uid' => $profile['uid'],
                'sys_language_uid' => $profile['sys_language_uid'],
                'l10n_parent' => $profile['l10n_parent'],
                'repair' => $repair,
                'references' => $profile['references'],
            ];
        }
        return $affectedProfiles;
    }

    /**
     * @param array{uid: int, sys_language_uid: int, l10n_parent: int, image: int, custom: bool, references: list<array{uid: int, uid_local: int, l10n_parent: int}>} $profile
     * @param array<int, true> $usableParentUids
     * @return 'rewrite'|'propagate'|null
     */
    private function determineRepair(array $profile, array $usableParentUids): ?string
    {
        $referenceCount = count($profile['references']);
        $counterIsStale = $profile['image'] !== $referenceCount;
        $isTranslation = $profile['sys_language_uid'] > 0 && $profile['l10n_parent'] > 0;
        if ($isTranslation && !isset($usableParentUids[$profile['l10n_parent']])) {
            // The language parent is gone, deleted, or is itself a translation. Nothing
            // can be propagated into this record, so it is repaired as what it de facto
            // is: an independent profile owning whatever references it holds.
            $this->logger->warning(
                'Profile {profileUid} points at language parent {parentUid}, which is missing, deleted or'
                    . ' not a default-language record. Its image relation is repaired as an independent one.',
                ['profileUid' => $profile['uid'], 'parentUid' => $profile['l10n_parent']],
            );
            return $referenceCount > 1 || $counterIsStale ? 'rewrite' : null;
        }
        $followsDefaultLanguage = $isTranslation && !$profile['custom'];
        if (!$followsDefaultLanguage) {
            // A default-language profile, a translation with an image of its own, or a
            // free-mode translation without a default-language record to follow.
            return $referenceCount > 1 || $counterIsStale ? 'rewrite' : null;
        }
        $hasOwnReference = array_filter(
            $profile['references'],
            static fn(array $reference): bool => $reference['l10n_parent'] === 0,
        ) !== [];
        if ($hasOwnReference || $referenceCount > 1) {
            return 'rewrite';
        }
        return $counterIsStale ? 'propagate' : null;
    }

    /**
     * The subset of the collected `l10n_parent` values that can actually be propagated
     * into: an existing, undeleted, default-language, live profile record. Everything
     * else makes its translations orphans - and calling the writer with such a uid
     * would throw halfway through a wizard that is not transactional.
     *
     * @param array<int, array{uid: int, sys_language_uid: int, l10n_parent: int, image: int, custom: bool, references: list<array{uid: int, uid_local: int, l10n_parent: int}>}> $profiles
     * @return array<int, true>
     */
    private function findUsableLanguageParentUids(array $profiles): array
    {
        $parentUids = [];
        foreach ($profiles as $profile) {
            if ($profile['sys_language_uid'] > 0 && $profile['l10n_parent'] > 0) {
                $parentUids[$profile['l10n_parent']] = true;
            }
        }
        if ($parentUids === []) {
            return [];
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::PROFILE_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid')
            ->from(self::PROFILE_TABLE)
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->quoteArrayBasedValueListToIntegerList(array_keys($parentUids)),
                ),
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('sys_language_uid', 0),
                $queryBuilder->expr()->eq('t3ver_oid', 0),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        $usableParentUids = [];
        foreach ($rows as $uid) {
            $usableParentUids[(int)$uid] = true;
        }
        return $usableParentUids;
    }

    private function hasCustomImageState(string $localizationState): bool
    {
        if ($localizationState === '') {
            return false;
        }
        try {
            $states = json_decode($localizationState, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }
        return is_array($states) && ($states[self::FIELD_NAME] ?? null) === 'custom';
    }
}
