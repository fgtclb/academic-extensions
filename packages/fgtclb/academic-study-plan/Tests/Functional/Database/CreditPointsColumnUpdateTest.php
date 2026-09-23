<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Database;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Types\DecimalType;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\SqlReader;

/**
 * An installation updated from a release with integer credit points still has the
 * integer column until the database compare runs. This test gives the table that
 * column back, stores a value in it, and runs the change the compare offers for it:
 * the value survives, and the column is a decimal column afterwards.
 *
 * The functional schema is built from the current definition, so without the step
 * that gives the column back this would only prove that nothing needs changing.
 */
final class CreditPointsColumnUpdateTest extends AbstractAcademicStudyPlanTestCase
{
    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordTableDataProvider(): \Generator
    {
        yield 'semester' => ['tx_academicstudyplan_domain_model_semester'];
        yield 'module' => ['tx_academicstudyplan_domain_model_module'];
    }

    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function databaseCompareKeepsIntegerCreditPoints(string $table): void
    {
        $indexes = $this->indexesOf($table);
        $connection = $this->getConnectionPool()->getConnectionForTable($table);
        $schemaManager = $connection->createSchemaManager();
        $current = $schemaManager->introspectTable($table);
        $previous = clone $current;
        // The column as `ext_tables.sql` declared it before: `int(11) NOT NULL DEFAULT '0'`.
        $previous->modifyColumn('credit_points', [
            'type' => Type::getType(Types::INTEGER),
            'default' => 0,
            'notnull' => true,
            'unsigned' => false,
        ]);
        $schemaManager->alterTable($schemaManager->createComparator()->compareTables($current, $previous));
        // Doctrine's own table rebuild on SQLite loses the secondary indexes with DBAL 4
        // (TYPO3 v13), which an installation has - and without them the compare would add
        // them in the same run as the column change, and on SQLite both rebuild the table.
        // Put them back directly, so the compare finds what an installation has. DBAL 3
        // (TYPO3 v12) keeps them.
        $altered = $schemaManager->introspectTable($table)->getIndexes();
        foreach (array_diff_key($current->getIndexes(), $altered) as $index) {
            $schemaManager->createIndex($index, $table);
        }
        $this->assertSame($indexes, $this->indexesOf($table), 'The former table lacks an index.');
        $connection->insert($table, ['pid' => 1, 'label' => 'Before the update', 'credit_points' => 5]);
        // Read back by label: without a table name, "lastInsertId()" of TYPO3 v12 asks
        // PostgreSQL for a sequence named "uid_seq".
        $uid = (int)$connection->select(['uid'], $table, ['label' => 'Before the update'])->fetchOne();

        // What `extension:setup` runs - added and changed fields and indexes together -
        // and every statement of the table rather than the one naming the column: SQLite
        // cannot alter a column and rebuilds the table, indexes included.
        $changes = $this->suggestionsFor($table);
        $this->assertNotSame([], $changes, 'The database compare offers no change of the credit points column.');
        $sqlReader = $this->get(SqlReader::class);
        $this->assertSame([], $this->get(SchemaMigrator::class)->migrate(
            $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString()),
            $changes,
        ));
        // Afterwards the table keeps its indexes and the compare offers nothing further,
        // or the install tool would keep suggesting a change after every update.
        $this->assertSame($indexes, $this->indexesOf($table), 'The database compare lost an index.');
        $this->assertSame([], $this->suggestionsFor($table), 'The database compare still offers something after running it.');

        $this->assertSame(5.0, (float)$this->creditPointsOf($table, $uid));
        $this->assertInstanceOf(
            DecimalType::class,
            $connection->createSchemaManager()->introspectTable($table)->getColumn('credit_points')->getType(),
        );
        $connection->update($table, ['credit_points' => '2.50'], ['uid' => $uid]);
        $this->assertSame(2.5, (float)$this->creditPointsOf($table, $uid));
    }

    /**
     * @return array<string, string> The added and changed fields and indexes of the table.
     */
    private function suggestionsFor(string $table): array
    {
        $sqlReader = $this->get(SqlReader::class);
        $suggestions = $this->get(SchemaMigrator::class)->getUpdateSuggestions(
            $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString()),
        )['Default'];
        $statements = [];
        foreach (['add', 'change'] as $category) {
            $statements += array_filter(
                $suggestions[$category] ?? [],
                static fn(string $statement): bool => str_contains($statement, $table),
            );
        }

        return $statements;
    }

    /**
     * @return list<string> The columns of every index, sorted.
     */
    private function indexesOf(string $table): array
    {
        $indexes = array_map(
            static fn(Index $index): string => implode(',', $index->getColumns()),
            array_values($this->getConnectionPool()->getConnectionForTable($table)->createSchemaManager()->introspectTable($table)->getIndexes()),
        );
        sort($indexes);

        return $indexes;
    }

    private function creditPointsOf(string $table, int $uid): mixed
    {
        return $this->getConnectionPool()
            ->getConnectionForTable($table)
            ->select(['credit_points'], $table, ['uid' => $uid])
            ->fetchOne();
    }
}
