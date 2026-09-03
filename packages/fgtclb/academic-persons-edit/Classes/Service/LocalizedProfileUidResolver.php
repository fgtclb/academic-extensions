<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Resolves a public/default profile uid to the persisted record of a site language.
 *
 * @internal
 */
final readonly class LocalizedProfileUidResolver
{
    private const TABLE = 'tx_academicpersons_domain_model_profile';

    public function __construct(private ConnectionPool $connectionPool) {}

    public function resolve(int $profileUid, int $languageId): int
    {
        if ($profileUid <= 0 || $languageId <= 0) {
            return $profileUid;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $record = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($profileUid, Connection::PARAM_INT),
                ),
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();
        if ($record === false) {
            return 0;
        }
        if ((int)$record['sys_language_uid'] === $languageId) {
            return (int)$record['uid'];
        }
        $defaultProfileUid = (int)$record['l10n_parent'] > 0
            ? (int)$record['l10n_parent']
            : (int)$record['uid'];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        return (int)$queryBuilder
            ->select('uid')
            ->from(self::TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'l10n_parent',
                    $queryBuilder->createNamedParameter($defaultProfileUid, Connection::PARAM_INT),
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT),
                ),
            )
            ->orderBy('uid')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();
    }
}
