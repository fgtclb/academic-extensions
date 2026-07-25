<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 v14 removed the `tt_content.list_type` column together with the plugin
 * sub-type feature. The `list_type` -> `CType` upgrade-wizard tests still need
 * the column to seed legacy fixtures and exercise the migration, so this trait
 * re-creates it when it is missing. On TYPO3 v13 the column already exists and
 * the method is a no-op.
 *
 * @see https://docs.typo3.org/permalink/changelog:important-105538-1730752784
 */
trait EnsureTtContentListTypeColumnTrait
{
    protected function ensureTtContentListTypeColumnExists(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tt_content');

        $columnNames = array_map(
            static fn(Column $column): string => strtolower($column->getName()),
            $connection->createSchemaManager()->listTableColumns('tt_content')
        );
        if (in_array('list_type', $columnNames, true)) {
            return;
        }

        $connection->executeStatement(
            "ALTER TABLE tt_content ADD COLUMN list_type VARCHAR(255) DEFAULT '' NOT NULL"
        );
    }
}
