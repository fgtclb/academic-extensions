<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Database;

use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Index\IndexedColumn;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;

/**
 * An installation updated from 2.x still has the tables that release declared until
 * the database compare runs: the former credit points column, and no workspace
 * columns, which 3.0 adds to both tables. These tests give the table that shape back,
 * store a value in it, and run what the compare offers for it: the value survives,
 * the column takes two decimals afterwards, and the table is complete.
 *
 * The functional schema is built from the current definition, so without the step
 * that gives the table its former shape this would only prove that nothing needs
 * changing.
 */
final class CreditPointsColumnUpdateTest extends AbstractAcademicStudyPlanTestCase
{
    /**
     * The indexes the current definition declares, read before the table is changed.
     *
     * @var list<string>
     */
    private array $declaredIndexes = [];

    /**
     * @return \Generator<string, array{0: non-empty-string}>
     */
    public static function recordTableDataProvider(): \Generator
    {
        yield 'semester' => ['tx_academicstudyplan_domain_model_semester'];
        yield 'module' => ['tx_academicstudyplan_domain_model_module'];
    }

    /**
     * @param non-empty-string $table
     */
    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function databaseCompareKeepsIntegerCreditPoints(string $table): void
    {
        // The column as `ext_tables.sql` of 2.3 declared it: `int(11) NOT NULL DEFAULT '0'`.
        $uid = $this->storeInFormerTable($table, [
            'type' => Type::getType(Types::INTEGER),
            'default' => 0,
            'notnull' => true,
            'unsigned' => false,
        ], 5);

        $this->assertNotSame([], $this->statementsNaming($this->runDatabaseCompare($table), 'credit_points'), 'The database compare offers no change of the credit points column.');

        $this->assertSame(5.0, (float)$this->creditPointsOf($table, $uid));
        $this->getConnectionPool()->getConnectionForTable($table)->update($table, ['credit_points' => '2.50'], ['uid' => $uid]);
        $this->assertSame('2.50', (string)$this->creditPointsOf($table, $uid));
    }

    /**
     * Branch `2` declares the column in `ext_tables.sql`, because TYPO3 v12 derives
     * none: `decimal(10,2) unsigned NOT NULL DEFAULT '0.00'`. That is the column
     * derived here on MariaDB, MySQL and PostgreSQL, so the compare leaves it alone
     * there and only adds the workspace columns; on SQLite it turns the numeric column
     * into the derived text column. Either way a value stored by 2.4 survives the update
     * to 3.0.
     *
     * @param non-empty-string $table
     */
    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function databaseCompareKeepsCreditPointsOfBranchTwo(string $table): void
    {
        $uid = $this->storeInFormerTable($table, [
            'type' => Type::getType(Types::DECIMAL),
            'precision' => 10,
            'scale' => 2,
            'default' => '0.00',
            'notnull' => true,
            'unsigned' => true,
        ], '2.50');

        $creditPointsStatements = $this->statementsNaming($this->runDatabaseCompare($table), 'credit_points');

        if ($this->isSqlite($table)) {
            $this->assertNotSame([], $creditPointsStatements, 'SQLite keeps the numeric column of branch 2.');
        } else {
            $this->assertSame([], $creditPointsStatements, 'The compare changes the column of branch 2.');
        }
        $this->assertSame(2.5, (float)$this->creditPointsOf($table, $uid));
        $this->getConnectionPool()->getConnectionForTable($table)->update($table, ['credit_points' => '12.75'], ['uid' => $uid]);
        $this->assertSame('12.75', (string)$this->creditPointsOf($table, $uid));
    }

    /**
     * Gives the table the shape 2.x declared - the given credit points column, and no
     * workspace columns with their index - and stores one row in it.
     *
     * @param non-empty-string $table
     * @param array<string, mixed> $column
     */
    private function storeInFormerTable(string $table, array $column, int|string $creditPoints): int
    {
        $connection = $this->getConnectionPool()->getConnectionForTable($table);
        $schemaManager = $connection->createSchemaManager();
        $current = $schemaManager->introspectTableByUnquotedName($table);
        $this->declaredIndexes = $this->indexesOf($current->getIndexes());
        $previous = clone $current;
        foreach ($previous->getIndexes() as $name => $index) {
            if (in_array('t3ver_oid', $this->columnsOf($index), true)) {
                $previous->dropIndex($name);
            }
        }
        foreach (['t3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_stage'] as $workspaceColumn) {
            $previous->dropColumn($workspaceColumn);
        }
        $previous->modifyColumn('credit_points', $column);
        $schemaManager->alterTable($schemaManager->createComparator()->compareTables($current, $previous));
        // Doctrine's own table rebuild on SQLite loses the secondary indexes, which an
        // installation has. Put them back, so the compare finds what an installation has.
        $altered = $schemaManager->introspectTableByUnquotedName($table)->getIndexes();
        foreach (array_diff_key($previous->getIndexes(), $altered) as $index) {
            $schemaManager->createIndex($index, $table);
        }
        $this->assertSame($this->indexesOf($previous->getIndexes()), $this->indexesOf($this->introspect($table)->getIndexes()), 'The former table lacks an index.');
        $connection->insert($table, ['pid' => 1, 'label' => 'Before the update', 'credit_points' => $creditPoints]);

        return (int)$connection->select(['uid'], $table, ['label' => 'Before the update'])->fetchOne();
    }

    /**
     * Runs what the database compare offers for the table the way `extension:setup`
     * does - added and changed fields and indexes together, once - and returns the
     * changed statements: on SQLite the added workspace columns rebuild the table as
     * well, and that rebuild names every column.
     * Afterwards the table has every column and index the current definition declares,
     * and the compare offers nothing further, or the install tool would keep suggesting
     * a change after every update.
     *
     * On SQLite the workspace columns and the column change each rebuild the table in
     * the same run, and the run reports two errors of the second rebuild ("table …
     * already exists", "no such table: __temp__…") although the table ends up complete:
     * the statements both rebuilds share run once. `extension:setup` does not show
     * them; the database analyzer does, and the changelog says so. Elsewhere the run
     * reports none.
     *
     * @param non-empty-string $table
     * @return array<string, string>
     */
    private function runDatabaseCompare(string $table): array
    {
        $changed = $this->suggestionsFor($table, ['change']);
        $offered = $this->suggestionsFor($table, ['add', 'change']);
        $sqlReader = $this->get(SqlReader::class);
        $errors = $this->get(SchemaMigrator::class)->migrate(
            $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString()),
            $offered,
        );

        if ($this->isSqlite($table)) {
            // The two errors the changelog names. Should the core stop reporting them,
            // the changelog paragraph goes, and this with it.
            $this->assertCount(2, $errors);
            $this->assertStringContainsString('already exists', implode(' ', $errors));
            $this->assertStringContainsString('no such table: __temp__', implode(' ', $errors));
        } else {
            $this->assertSame([], $errors);
        }
        $this->assertSame([], $this->suggestionsFor($table, ['add', 'change']), 'The database compare still offers something after running it.');
        $this->assertSame($this->declaredIndexes, $this->indexesOf($this->introspect($table)->getIndexes()), 'The table lacks an index after the compare.');
        $this->assertTrue($this->introspect($table)->hasColumn('t3ver_oid'), 'The table lacks the workspace columns after the compare.');

        return $changed;
    }

    /**
     * @param array<string, string> $statements
     * @return array<string, string>
     */
    private function statementsNaming(array $statements, string $column): array
    {
        return array_filter($statements, static fn(string $statement): bool => str_contains($statement, $column));
    }

    /**
     * @param list<'add'|'change'> $categories
     * @return array<string, string> The statements of the table in these categories.
     */
    private function suggestionsFor(string $table, array $categories): array
    {
        $sqlReader = $this->get(SqlReader::class);
        $suggestions = $this->get(SchemaMigrator::class)->getUpdateSuggestions(
            $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString()),
        )['Default'];
        $statements = [];
        foreach ($categories as $category) {
            $statements += array_filter(
                $suggestions[$category] ?? [],
                static fn(string $statement): bool => str_contains($statement, $table),
            );
        }

        return $statements;
    }

    /**
     * @param non-empty-string $table
     */
    private function introspect(string $table): Table
    {
        return $this->getConnectionPool()->getConnectionForTable($table)->createSchemaManager()->introspectTableByUnquotedName($table);
    }

    private function isSqlite(string $table): bool
    {
        return $this->getConnectionPool()->getConnectionForTable($table)->getDatabasePlatform() instanceof SQLitePlatform;
    }

    /**
     * @return list<string>
     */
    private function columnsOf(Index $index): array
    {
        return array_map(
            static fn(IndexedColumn $column): string => trim($column->getColumnName()->toString(), '"'),
            $index->getIndexedColumns(),
        );
    }

    /**
     * @param array<Index> $indexes
     * @return list<string> The columns of every index, sorted.
     */
    private function indexesOf(array $indexes): array
    {
        $columns = array_map(fn(Index $index): string => implode(',', $this->columnsOf($index)), array_values($indexes));
        sort($columns);

        return $columns;
    }

    private function creditPointsOf(string $table, int $uid): mixed
    {
        return $this->getConnectionPool()
            ->getConnectionForTable($table)
            ->select(['credit_points'], $table, ['uid' => $uid])
            ->fetchOne();
    }
}
