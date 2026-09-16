<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\FunctionalTestCase;

use Doctrine\DBAL\Schema\Column;
use TYPO3\CMS\Core\Cache\CacheManager;
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

        // Adding the column is not enough: `Connection::getSchemaInformation()` caches
        // table information twice - in the `runtime` cache and in the persistent
        // `database_schema` cache - so a column added here stays invisible to every
        // caller reading through that API, for the rest of the process. Both layers have
        // to be dropped, and the level 1 cache is the reason the level 2 flush alone does
        // not do it.
        //
        // The column is added late enough for that to matter: a test calling this from a
        // test method rather than from `setUp()` has usually read the `tt_content` schema
        // already. `typo3/testing-framework` resolves the column types of a data set
        // through that cache since 9.7.0 - it used live schema introspection before - so a
        // stale entry ends the import with `getType()` on null instead of a readable
        // error.
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->getCache('database_schema')->flush();
        $cacheManager->getCache('runtime')->flush();
    }
}
