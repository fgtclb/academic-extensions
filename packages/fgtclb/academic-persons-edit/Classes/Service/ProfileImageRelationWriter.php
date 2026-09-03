<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Service;

use FGTCLB\AcademicPersons\Service\DataHandlerExecutionContext;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Writes profile image relations exclusively through the TYPO3 DataHandler.
 *
 * @internal
 */
final readonly class ProfileImageRelationWriter
{
    private const PROFILE_TABLE = 'tx_academicpersons_domain_model_profile';
    private const REFERENCE_TABLE = 'sys_file_reference';
    private const FIELD_NAME = 'image';

    public function __construct(
        private ConnectionPool $connectionPool,
        private DataHandlerExecutionContext $executionContext,
    ) {}

    /**
     * @return list<int> File uids previously assigned to the profile.
     */
    public function replace(int $profileUid, File $file): array
    {
        $profile = $this->getProfileRecord($profileUid);
        $oldReferences = $this->getImageReferences($profileUid);
        if (count($oldReferences) === 1 && $oldReferences[0]['l10n_parent'] === 0) {
            $referenceUid = $oldReferences[0]['uid'];
            $this->executionContext->runAsBackendUser(function (BackendUserAuthentication $backendUser) use (
                $profile,
                $profileUid,
                $referenceUid,
                $file,
            ): void {
                $dataMap = [
                    self::REFERENCE_TABLE => [
                        $referenceUid => ['uid_local' => $file->getUid()],
                    ],
                ];
                if ((int)$profile['sys_language_uid'] > 0) {
                    $dataMap[self::PROFILE_TABLE] = [
                        $profileUid => $this->buildProfileImageData(
                            (int)$profile['sys_language_uid'],
                            self::REFERENCE_TABLE . '_' . $referenceUid,
                        ),
                    ];
                }
                $this->executeDataHandler($backendUser, $dataMap);
            });
            return [$oldReferences[0]['uid_local']];
        }
        $newReferenceId = 'NEW' . bin2hex(random_bytes(16));
        $this->executionContext->runAsBackendUser(function (BackendUserAuthentication $backendUser) use (
            $profile,
            $profileUid,
            $file,
            $newReferenceId,
        ): void {
            $dataHandler = $this->executeDataHandler($backendUser, [
                self::REFERENCE_TABLE => [
                    $newReferenceId => [
                        'pid' => (int)$profile['pid'],
                        'uid_local' => $file->getUid(),
                        'uid_foreign' => $profileUid,
                        'tablenames' => self::PROFILE_TABLE,
                        'fieldname' => self::FIELD_NAME,
                        'sorting_foreign' => 1,
                        'sys_language_uid' => (int)$profile['sys_language_uid'],
                        'l10n_parent' => 0,
                    ],
                ],
            ]);
            $referenceUid = (int)($dataHandler->substNEWwithIDs[$newReferenceId] ?? 0);
            if ($referenceUid <= 0) {
                throw new \UnexpectedValueException('The profile image reference could not be created.');
            }
            $this->executeDataHandler($backendUser, [
                self::PROFILE_TABLE => [
                    $profileUid => $this->buildProfileImageData(
                        (int)$profile['sys_language_uid'],
                        self::REFERENCE_TABLE . '_' . $referenceUid,
                    ),
                ],
            ]);
        });
        $this->deleteReferences($oldReferences);
        return array_values(array_unique(array_column($oldReferences, 'uid_local')));
    }

    /**
     * @return list<int> File uids previously assigned to the profile.
     */
    public function remove(int $profileUid): array
    {
        $profile = $this->getProfileRecord($profileUid);
        $oldReferences = $this->getImageReferences($profileUid);
        $this->executionContext->runAsBackendUser(
            function (BackendUserAuthentication $backendUser) use ($profile, $profileUid): void {
                $this->executeDataHandler($backendUser, [
                    self::PROFILE_TABLE => [
                        $profileUid => $this->buildProfileImageData(
                            (int)$profile['sys_language_uid'],
                            '',
                        ),
                    ],
                ]);
            },
        );
        $this->deleteReferences($oldReferences);
        return array_values(array_unique(array_column($oldReferences, 'uid_local')));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildProfileImageData(int $languageUid, string $imageValue): array
    {
        $data = [self::FIELD_NAME => $imageValue];
        if ($languageUid > 0) {
            $data['l10n_state'] = [self::FIELD_NAME => 'custom'];
        }
        return $data;
    }

    /**
     * @return array{pid: int|string, sys_language_uid: int|string}
     */
    private function getProfileRecord(int $profileUid): array
    {
        $record = $this->connectionPool
            ->getConnectionForTable(self::PROFILE_TABLE)
            ->select(['pid', 'sys_language_uid'], self::PROFILE_TABLE, ['uid' => $profileUid])
            ->fetchAssociative();
        if ($record === false) {
            throw new \UnexpectedValueException('The persisted profile is unavailable.');
        }
        return [
            'pid' => (int)$record['pid'],
            'sys_language_uid' => (int)$record['sys_language_uid'],
        ];
    }

    /**
     * @return list<array{uid: int, uid_local: int, l10n_parent: int}>
     */
    private function getImageReferences(int $profileUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::REFERENCE_TABLE);
        $rows = $queryBuilder
            ->select('uid', 'uid_local', 'l10n_parent')
            ->from(self::REFERENCE_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid_foreign',
                    $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'tablenames',
                    $queryBuilder->createNamedParameter(self::PROFILE_TABLE),
                ),
                $queryBuilder->expr()->eq(
                    'fieldname',
                    $queryBuilder->createNamedParameter(self::FIELD_NAME),
                ),
            )
            ->orderBy('sorting_foreign')
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => [
                'uid' => (int)$row['uid'],
                'uid_local' => (int)$row['uid_local'],
                'l10n_parent' => (int)$row['l10n_parent'],
            ],
            $rows,
        );
    }

    /**
     * @param list<array{uid: int, uid_local: int, l10n_parent: int}> $references
     */
    private function deleteReferences(array $references): void
    {
        if ($references === []) {
            return;
        }
        $this->executionContext->runAsBackendUser(function (BackendUserAuthentication $backendUser) use ($references): void {
            $cmdmap = [];
            foreach ($references as $reference) {
                $cmdmap[self::REFERENCE_TABLE][$reference['uid']]['delete'] = 1;
            }
            $this->executeDataHandler($backendUser, cmdmap: $cmdmap);
        });
    }

    /**
     * @param array<string, array<int|string, array<string, mixed>>> $datamap
     * @param array<string, array<int|string, array<string, mixed>>> $cmdmap
     */
    private function executeDataHandler(
        BackendUserAuthentication $backendUser,
        array $datamap = [],
        array $cmdmap = [],
    ): DataHandler {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($datamap, $cmdmap, $backendUser);
        if ($datamap !== []) {
            $dataHandler->process_datamap();
        }
        if ($cmdmap !== []) {
            $dataHandler->process_cmdmap();
        }
        if ($dataHandler->errorLog !== []) {
            throw new \RuntimeException(implode(' ', $dataHandler->errorLog));
        }
        return $dataHandler;
    }
}
