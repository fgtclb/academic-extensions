<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\Tca;

use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\Table;
use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Pins that all three record tables of this extension are workspace aware.
 *
 * Semester and module are inline children of the workspace aware "tt_content" and
 * TYPO3 v14 auto-migrates that case, so a missing flag would surface there as a
 * deprecation. The category table is related through
 * "tx_academicstudyplan_module_category_mm" instead, which the auto-migration does
 * not cover - it silently stayed non versionable until ACE-475, and nothing in the
 * suite noticed. Hence this test.
 */
final class WorkspaceAwarenessTest extends AbstractAcademicStudyPlanTestCase
{
    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordTableDataProvider(): \Generator
    {
        yield 'semester' => ['tx_academicstudyplan_domain_model_semester'];
        yield 'module' => ['tx_academicstudyplan_domain_model_module'];
        yield 'category' => ['tx_academicstudyplan_domain_model_category'];
    }

    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function recordTableIsDeclaredWorkspaceAware(string $table): void
    {
        $this->assertTrue(
            $GLOBALS['TCA'][$table]['ctrl']['versioningWS'] ?? false,
            sprintf('Table "%s" is not declared workspace aware in its TCA "ctrl" section.', $table),
        );
    }

    /**
     * What "DefaultTcaSchema" derives from the declaration above. The functional
     * schema is rebuilt from the current TCA on every run, so this cannot fail while
     * the declaration holds - it is here to name the four columns an installation has
     * to gain, which is what the "Important" changelog entry sends integrators to the
     * database analyzer for.
     */
    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function recordTableCarriesTheWorkspaceColumns(string $table): void
    {
        $schema = $this->introspectTable($table);

        foreach (['t3ver_oid', 't3ver_wsid', 't3ver_state', 't3ver_stage'] as $column) {
            $this->assertTrue(
                $schema->hasColumn($column),
                sprintf('Table "%s" does not carry the workspace column "%s".', $table, $column),
            );
        }
    }

    /**
     * The index over ("t3ver_oid", "t3ver_wsid") is derived from the same declaration
     * and is the part of it that is easy to lose: "DefaultTcaSchema" adds it only when
     * the table does not already define an index of the name "t3ver_oid" itself, and
     * every workspace lookup joins on those two columns, so losing it costs a table
     * scan that nothing reports.
     *
     * Asserted by its columns rather than by its name on purpose - SQLite index names
     * are unique per database rather than per table, so the name the schema manager
     * reports back carries a generated suffix ("t3ver_oid_93953e74") and differs per
     * DBMS and per table.
     */
    #[Test]
    #[DataProvider('recordTableDataProvider')]
    public function recordTableCarriesTheWorkspaceIndex(string $table): void
    {
        $indexedColumnSets = array_map(
            static fn(Index $index): array => $index->getColumns(),
            array_values($this->introspectTable($table)->getIndexes()),
        );

        $this->assertContains(
            ['t3ver_oid', 't3ver_wsid'],
            $indexedColumnSets,
            sprintf(
                'Table "%s" carries no index over "t3ver_oid" and "t3ver_wsid". Present indexes: %s.',
                $table,
                json_encode($indexedColumnSets, JSON_THROW_ON_ERROR),
            ),
        );
    }

    private function introspectTable(string $table): Table
    {
        return GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($table)
            ->createSchemaManager()
            ->introspectTable($table);
    }
}
