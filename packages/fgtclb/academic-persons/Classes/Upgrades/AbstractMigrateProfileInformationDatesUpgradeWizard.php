<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Upgrades;

use FGTCLB\AcademicBase\Date\DateCompletion;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * A starting point for migrating the pre-3.0 integer year columns of
 * `tx_academicpersons_domain_model_profile_information` into the date columns that
 * replaced them: `year` into `date`, `year_start` into `date_start` and `year_end`
 * into `date_end`.
 *
 * **This class is deliberately abstract and carries no `#[UpgradeWizard]`
 * attribute, so nothing registers it and no installation runs it by accident.**
 * A year knows neither a month nor a day, so there is no migration this extension
 * could perform that is right for every installation: whether the 2019 of a
 * "Studies" entry means the first of January, the end of the academic year or the
 * day the entry was actually about is a question only the project can answer. The
 * extension therefore ships the mechanism and leaves the rule to the project, which
 * subclasses this wizard, picks its completion rules and adds the attribute:
 *
 * ```php
 * #[UpgradeWizard('myProject_migrateProfileInformationDates')]
 * final readonly class MigrateProfileInformationDatesUpgradeWizard
 *     extends AbstractMigrateProfileInformationDatesUpgradeWizard
 * {
 *     public function __construct(ConnectionPool $connectionPool)
 *     {
 *         parent::__construct(
 *             $connectionPool,
 *             // A start year becomes the 1st of January.
 *             new DateCompletion(),
 *             // An end year becomes the 31st of December.
 *             new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST),
 *         );
 *     }
 * }
 * ```
 *
 * A `readonly` class can only be extended by a `readonly` class, so the subclass
 * has to repeat the keyword.
 *
 * What the migration does: for every row that carries a non-null, non-zero integer
 * in one of the three old columns **while the matching date column is still
 * empty**, the completed date is written. A date column that already carries a
 * value is never overwritten — an installation that migrated by hand, or that
 * edited a record after the upgrade, keeps what it has. That guard is also what
 * makes the wizard {@see RepeatableInterface}: the second run finds nothing left
 * to do and changes nothing.
 *
 * The old columns are not part of `ext_tables.sql` any more, so a database that
 * has been analysed and cleaned no longer has them. That is not an error: the
 * wizard asks the schema which of the three old columns are still present and
 * reports "nothing to do" for the ones that are gone, rather than failing on a
 * query against a column that does not exist.
 *
 * Deleted and hidden rows are migrated as well — restoring a record after the
 * upgrade must not resurrect a row with an empty date — so every query runs
 * without restrictions.
 */
abstract readonly class AbstractMigrateProfileInformationDatesUpgradeWizard implements UpgradeWizardInterface, RepeatableInterface
{
    protected const TABLE_NAME = 'tx_academicpersons_domain_model_profile_information';

    /**
     * The three migrations, as `old integer column => new date column`.
     */
    protected const COLUMN_MAP = [
        'year' => 'date',
        'year_start' => 'date_start',
        'year_end' => 'date_end',
    ];

    /**
     * @param DateCompletion $dateCompletion Completes `year` into `date` and `year_start` into `date_start`.
     * @param DateCompletion $endDateCompletion Completes `year_end` into `date_end`. An end year usually wants
     *                                          `DateCompletionEdge::LAST` for both the month and the day.
     */
    public function __construct(
        protected ConnectionPool $connectionPool,
        protected DateCompletion $dateCompletion = new DateCompletion(),
        protected DateCompletion $endDateCompletion = new DateCompletion(),
    ) {}

    public function getTitle(): string
    {
        return 'Migrate the profile information years into the date columns that replaced them';
    }

    public function getDescription(): string
    {
        return 'Writes a date into "date", "date_start" and "date_end" of every profile information'
            . ' record that still carries the pre-3.0 integer year in "year", "year_start" or'
            . ' "year_end" while the matching date column is empty. A year has no month and no day,'
            . ' so both are completed by the rule this wizard was configured with. A date column that'
            . ' already carries a value is never overwritten, and columns that no longer exist in the'
            . ' database are skipped.';
    }

    public function updateNecessary(): bool
    {
        foreach ($this->getMigratableColumns() as $oldColumn => $newColumn) {
            if ($this->countRowsToMigrate($oldColumn, $newColumn) > 0) {
                return true;
            }
        }
        return false;
    }

    public function executeUpdate(): bool
    {
        foreach ($this->getMigratableColumns() as $oldColumn => $newColumn) {
            $completion = $this->getCompletionForColumn($newColumn);
            foreach ($this->getRowsToMigrate($oldColumn, $newColumn) as $row) {
                $date = $completion->complete($row['year'], null, null);
                // The constraint is built on the very query builder that executes the
                // statement: a named parameter is bound to its own builder, and set()
                // creates one of its own that a foreign WHERE would collide with.
                $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
                $queryBuilder
                    ->update(self::TABLE_NAME)
                    ->set($newColumn, $date->format('Y-m-d'))
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($row['uid'], Connection::PARAM_INT),
                        ),
                    )
                    ->executeStatement();
            }
        }
        return true;
    }

    /**
     * @return list<class-string>
     */
    public function getPrerequisites(): array
    {
        return [DatabaseUpdatedPrerequisite::class];
    }

    /**
     * The completion rule for one target column. `date` and `date_start` share one,
     * `date_end` has its own because an end year is usually completed to the other
     * edge of the year.
     */
    protected function getCompletionForColumn(string $newColumn): DateCompletion
    {
        return $newColumn === 'date_end' ? $this->endDateCompletion : $this->dateCompletion;
    }

    /**
     * The subset of {@see self::COLUMN_MAP} that can be migrated in this database:
     * both the old integer column and the new date column have to exist. The old
     * ones are gone from `ext_tables.sql`, so a cleaned database has none of them
     * left and this returns an empty array — which is a completed migration, not an
     * error.
     *
     * @return array<string, string>
     */
    protected function getMigratableColumns(): array
    {
        $existingColumns = $this->getExistingColumnNames();
        $migratableColumns = [];
        foreach (self::COLUMN_MAP as $oldColumn => $newColumn) {
            if (isset($existingColumns[$oldColumn], $existingColumns[$newColumn])) {
                $migratableColumns[$oldColumn] = $newColumn;
            }
        }
        return $migratableColumns;
    }

    /**
     * @return array<string, true> The lower-cased column names of the table, as a lookup.
     */
    protected function getExistingColumnNames(): array
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $schemaManager = $connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::TABLE_NAME])) {
            return [];
        }
        $columnNames = [];
        foreach ($schemaManager->listTableColumns(self::TABLE_NAME) as $column) {
            $columnNames[strtolower($column->getName())] = true;
        }
        return $columnNames;
    }

    protected function countRowsToMigrate(string $oldColumn, string $newColumn): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_NAME)
            ->where(...$this->buildMigrationConstraints($queryBuilder, $oldColumn, $newColumn))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return list<array{uid: int, year: int}>
     */
    protected function getRowsToMigrate(string $oldColumn, string $newColumn): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $result = $queryBuilder
            ->select('uid', $oldColumn)
            ->from(self::TABLE_NAME)
            ->where(...$this->buildMigrationConstraints($queryBuilder, $oldColumn, $newColumn))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $rows = [];
        foreach ($result as $row) {
            $rows[] = [
                'uid' => (int)$row['uid'],
                'year' => (int)$row[$oldColumn],
            ];
        }
        return $rows;
    }

    /**
     * A row is migratable when the old column carries a usable year and the new
     * column is still empty. `0` is not a usable year — the column was written as
     * `0` rather than `NULL` by some editors — and an already filled date column is
     * left alone, which is what makes a second run a no-op.
     *
     * The expressions are created on the passed builder, and only that builder may
     * execute them.
     *
     * @return list<string>
     */
    protected function buildMigrationConstraints(
        QueryBuilder $queryBuilder,
        string $oldColumn,
        string $newColumn,
    ): array {
        // The expression builder quotes the field name itself - `year` is a keyword
        // on more than one DBMS, and quoting it a second time here turns it into a
        // string literal that SQLite compares without complaining.
        return [
            (string)$queryBuilder->expr()->isNotNull($oldColumn),
            (string)$queryBuilder->expr()->neq(
                $oldColumn,
                $queryBuilder->createNamedParameter(0, Connection::PARAM_INT),
            ),
            (string)$queryBuilder->expr()->isNull($newColumn),
        ];
    }
}
