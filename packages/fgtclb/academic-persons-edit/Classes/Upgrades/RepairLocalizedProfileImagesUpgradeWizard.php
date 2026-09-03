<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Upgrades;

use FGTCLB\AcademicPersonsEdit\Service\ProfileImageRelationWriter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Rebuilds legacy shared or duplicate profile image relations through DataHandler.
 */
#[UpgradeWizard('academicPersonsEdit_repairLocalizedProfileImages')]
final readonly class RepairLocalizedProfileImagesUpgradeWizard implements UpgradeWizardInterface
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';

    public function __construct(
        private ConnectionPool $connectionPool,
        private ResourceFactory $resourceFactory,
        private ProfileImageRelationWriter $profileImageRelationWriter,
    ) {}

    public function getTitle(): string
    {
        return 'Repair language-specific academic profile images';
    }

    public function getDescription(): string
    {
        return 'Rebuilds shared, localized or duplicate profile image references through the TYPO3 DataHandler.';
    }

    public function executeUpdate(): bool
    {
        foreach ($this->getAffectedProfiles() as $profile) {
            if ($profile['uid_local'] <= 0) {
                $this->profileImageRelationWriter->remove($profile['uid']);
                continue;
            }
            try {
                $sourceFile = $this->resourceFactory->getFileObject($profile['uid_local']);
            } catch (FileDoesNotExistException) {
                $this->profileImageRelationWriter->remove($profile['uid']);
                continue;
            }
            $assignedFile = $sourceFile;
            if ($profile['copy_file']) {
                $targetFolder = $sourceFile->getStorage()->getFolder(
                    $sourceFile->getParentFolder()->getIdentifier(),
                );
                if (!$targetFolder instanceof Folder) {
                    throw new \UnexpectedValueException('The profile image target folder is unavailable.');
                }
                $assignedFile = $sourceFile->getStorage()->copyFile($sourceFile, $targetFolder);
            }
            $replacedFileUids = $this->profileImageRelationWriter->replace($profile['uid'], $assignedFile);
            $this->deleteUnreferencedFiles($replacedFileUids, $assignedFile->getUid());
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
     * @return list<array{uid: int, sys_language_uid: int, uid_local: int, copy_file: bool}>
     */
    private function getAffectedProfiles(): array
    {
        $rows = $this->connectionPool
            ->getConnectionForTable('sys_file_reference')
            ->executeQuery(
                'SELECT p.uid, p.sys_language_uid, p.l10n_parent AS profile_l10n_parent, p.image,'
                . ' r.uid AS reference_uid, r.uid_local, r.l10n_parent'
                . ' FROM ' . self::PROFILE_TABLE . ' p'
                . ' LEFT JOIN sys_file_reference r ON r.uid_foreign = p.uid'
                . ' AND r.tablenames = ? AND r.fieldname = ? AND r.deleted = 0'
                . ' WHERE p.deleted = 0 AND (p.image <> 0 OR r.uid IS NOT NULL)'
                . ' ORDER BY p.uid, r.sorting_foreign DESC, r.uid DESC',
                [self::PROFILE_TABLE, 'image'],
            )
            ->fetchAllAssociative();
        $profiles = [];
        foreach ($rows as $row) {
            $profileUid = (int)$row['uid'];
            $profiles[$profileUid] ??= [
                'uid' => $profileUid,
                'sys_language_uid' => (int)$row['sys_language_uid'],
                'l10n_parent' => (int)$row['profile_l10n_parent'],
                'image' => (int)$row['image'],
                'references' => [],
            ];
            if ((int)$row['reference_uid'] > 0) {
                $profiles[$profileUid]['references'][] = $row;
            }
        }
        $fileOwnersByProfileFamily = [];
        foreach ($profiles as $profile) {
            $profileFamilyUid = $profile['sys_language_uid'] > 0 ? $profile['l10n_parent'] : $profile['uid'];
            foreach ($profile['references'] as $reference) {
                $fileOwnersByProfileFamily[$profileFamilyUid][(int)$reference['uid_local']][$profile['uid']] = true;
            }
        }
        $sharedFileProfileUids = [];
        foreach ($fileOwnersByProfileFamily as $fileOwners) {
            foreach ($fileOwners as $profileOwners) {
                if (count($profileOwners) <= 1) {
                    continue;
                }
                foreach (array_keys($profileOwners) as $profileUid) {
                    $sharedFileProfileUids[$profileUid] = true;
                }
            }
        }
        $affectedProfiles = [];
        foreach ($profiles as $profile) {
            $references = $profile['references'];
            $retainedReference = $references[0] ?? null;
            $retainedReferenceLocalizationParent = $retainedReference !== null
                ? (int)$retainedReference['l10n_parent']
                : 0;
            $hasSharedFile = isset($sharedFileProfileUids[$profile['uid']]);
            if (
                count($references) === 1
                && $retainedReferenceLocalizationParent === 0
                && $profile['image'] === 1
                && !$hasSharedFile
            ) {
                continue;
            }
            $affectedProfiles[] = [
                'uid' => $profile['uid'],
                'sys_language_uid' => $profile['sys_language_uid'],
                'uid_local' => (int)($retainedReference['uid_local'] ?? 0),
                'copy_file' => $profile['sys_language_uid'] > 0
                    && ($hasSharedFile || $retainedReferenceLocalizationParent > 0),
            ];
        }
        return $affectedProfiles;
    }

    /**
     * @param list<int> $fileUids
     */
    private function deleteUnreferencedFiles(array $fileUids, int $retainedFileUid): void
    {
        foreach (array_unique($fileUids) as $fileUid) {
            if ($fileUid <= 0 || $fileUid === $retainedFileUid) {
                continue;
            }
            try {
                $file = $this->resourceFactory->getFileObject($fileUid);
            } catch (FileDoesNotExistException) {
                continue;
            }
            if ($this->countFileReferences($file) === 0) {
                $file->getStorage()->deleteFile($file);
            }
        }
    }

    private function countFileReferences(File $file): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_reference');
        return (int)$queryBuilder
            ->count('uid')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_local',
                    $queryBuilder->createNamedParameter($file->getUid()),
                ),
            )
            ->executeQuery()
            ->fetchOne();
    }
}
