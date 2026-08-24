<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional\Support;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Reads the rows of the database a fresh import wrote, through the TYPO3
 * `QueryBuilder`.
 *
 * Through the query builder rather than through hand written SQL, for the
 * reason every functional test in this repository does: an unquoted identifier
 * is folded to lower case by PostgreSQL, so `SELECT CType` asks for a column
 * that does not exist there and for one that does everywhere else.
 *
 * Every restriction is removed. What is measured is what the seed wrote, not
 * what a frontend would see - a record the seed declares `hidden: 1` is part of
 * the seed and has to be part of its manifest.
 */
final class ConnectionRowReader extends SeedRowReader
{
    /** @var array<int, string>|null */
    private ?array $files = null;

    public function columnsOf(string $table): array
    {
        $columns = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->createSchemaManager()
            ->listTableColumns($table);

        return array_values(array_map(
            static fn(object $column): string => strtolower($column->getName()),
            $columns,
        ));
    }

    public function rows(string $table, array $columns, ?array $uids): array
    {
        $select = array_values(array_filter(
            $columns,
            static fn(string $column): bool => $column !== SeedDefinition::REFERENCED_FILE,
        ));
        if ($table === 'sys_file_reference') {
            $select[] = 'uid_local';
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->select(...$select)->from($table);
        if ($uids !== null) {
            $queryBuilder->where(
                $queryBuilder->expr()->in('uid', $queryBuilder->quoteArrayBasedValueListToIntegerList($uids)),
            );
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $queryBuilder->executeQuery()->fetchAllAssociative();

        return $rows;
    }

    public function fileIdentifiers(): array
    {
        if ($this->files !== null) {
            return $this->files;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file');
        $queryBuilder->getRestrictions()->removeAll();
        /** @var list<array<string, mixed>> $rows */
        $rows = $queryBuilder->select('uid', 'identifier')->from('sys_file')->executeQuery()->fetchAllAssociative();

        $files = [];
        foreach ($rows as $row) {
            $files[(int)$row['uid']] = (string)$row['identifier'];
        }

        return $this->files = $files;
    }
}
