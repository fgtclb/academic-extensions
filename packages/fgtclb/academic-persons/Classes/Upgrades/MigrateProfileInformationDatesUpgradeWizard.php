<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Upgrades;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\DatabaseUpdatedPrerequisite;
use TYPO3\CMS\Install\Updates\RepeatableInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Converts the integer year columns of profile information records into the
 * DATE columns that replaced them in 3.0.0.
 *
 * `year`, `year_start` and `year_end` held four-digit years. Their successors
 * `date`, `date_start` and `date_end` hold complete calendar dates, and the
 * `year_only` flag renders the dates of a record as years again. A stored year
 * therefore becomes the first of January of that year together with
 * `year_only = 1`: the record showed a year before and shows the same year
 * afterwards, and the original value stays recoverable. A record an editor
 * has already dated in 3.0 - one of its DATE columns is set - gets its
 * remaining years converted but keeps the flag as the editor left it.
 *
 * The wizard runs after the schema update: the new columns exist by then, and
 * the old ones still do, because the schema analyzer reports a column that
 * left `ext_tables.sql` as unused and never drops it on its own. Dropping the
 * three integer columns is the last step of the migration and is left to the
 * integrator, so an installation whose `ext_tables.sql` never carried them -
 * or that already dropped them - has nothing to do here.
 *
 * The wizard is {@see RepeatableInterface}, and that is not a convenience.
 * `UpgradeWizardRunCommand::getWizard()` asks `updateNecessary()` *before*
 * `handlePrerequisites()` runs, and marks a wizard that answers "no" as done
 * unless it is repeatable. A `composer install && typo3 upgrade:run` deploy
 * therefore reaches this wizard while the DATE columns do not exist yet, and a
 * wizard that were marked done there would never convert anything - while the
 * documented last step of the migration drops the year columns. Being
 * repeatable also matches the algorithm, which never overwrites a DATE that is
 * already set.
 */
#[UpgradeWizard('academicPersons_migrateProfileInformationDates')]
final class MigrateProfileInformationDatesUpgradeWizard implements UpgradeWizardInterface, RepeatableInterface
{
    private const TABLE = 'tx_academicpersons_domain_model_profile_information';

    /**
     * The old TCA capped the year at four digits; a larger value cannot become
     * a `YYYY-01-01` date and is left alone, in the selection and in the loop.
     */
    private const YEAR_MAX = 9999;

    /**
     * Legacy integer column => DATE column that replaced it.
     */
    private const COLUMN_MAP = [
        'year' => 'date',
        'year_start' => 'date_start',
        'year_end' => 'date_end',
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function getTitle(): string
    {
        return 'Migrate "EXT:academic_persons" profile information years to dates';
    }

    public function getDescription(): string
    {
        return 'The integer columns "year", "year_start" and "year_end" of profile information records were'
            . ' replaced by the DATE columns "date", "date_start" and "date_end". Every stored year becomes the'
            . ' first of January of that year, and the record is flagged to show the year only, so nothing changes'
            . ' in the rendered output. The old columns are kept; drop them through the database analyzer'
            . ' once the wizard has run.';
    }

    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class,
        ];
    }

    public function updateNecessary(): bool
    {
        $columnNames = $this->columnNames();
        $columnPairs = $this->availableColumnPairs($columnNames);
        if ($columnPairs === []) {
            // The DATE columns are missing while legacy years are still stored: the
            // schema update has not run yet. Answering "necessary" is what puts the
            // wizard into the instance list of `upgrade:run`, which is the only way
            // its `DatabaseUpdatedPrerequisite` is ever handled.
            return $this->hasUnmigratedLegacyYears($columnNames);
        }
        return (int)$this->createRecordsToMigrateQuery($columnPairs)
            ->count('uid')
            ->executeQuery()
            ->fetchOne() > 0;
    }

    public function executeUpdate(): bool
    {
        $columnPairs = $this->availableColumnPairs($this->columnNames());
        if ($columnPairs === []) {
            return true;
        }
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE);
        $result = $this->createRecordsToMigrateQuery($columnPairs)
            ->select('uid', ...array_keys($columnPairs), ...array_values($columnPairs))
            ->orderBy('uid')
            ->executeQuery();
        while ($record = $result->fetchAssociative()) {
            $values = [];
            $alreadyDated = false;
            foreach ($columnPairs as $legacyColumn => $dateColumn) {
                if ($record[$dateColumn] !== null) {
                    $alreadyDated = true;
                    continue;
                }
                $year = (int)($record[$legacyColumn] ?? 0);
                if ($year > 0 && $year <= self::YEAR_MAX) {
                    $values[$dateColumn] = sprintf('%04d-01-01', $year);
                }
            }
            if (!$alreadyDated) {
                $values['year_only'] = 1;
            }
            $connection->update(
                self::TABLE,
                $values,
                ['uid' => (int)$record['uid']],
                ['year_only' => Connection::PARAM_INT, 'uid' => Connection::PARAM_INT],
            );
        }
        return true;
    }

    /**
     * Records with at least one four-digit legacy year whose DATE column is
     * still empty. A legacy value of 0 or NULL was "no year" and is left
     * alone, and a DATE that is already set - by a previous run, or by an
     * editor - is never overwritten. Deleted and hidden records are migrated
     * as well.
     *
     * @param non-empty-array<string, string> $columnPairs
     */
    private function createRecordsToMigrateQuery(array $columnPairs): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $constraints = [];
        foreach ($columnPairs as $legacyColumn => $dateColumn) {
            $constraints[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->isNotNull($legacyColumn),
                $queryBuilder->expr()->gt($legacyColumn, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte($legacyColumn, $queryBuilder->createNamedParameter(self::YEAR_MAX, Connection::PARAM_INT)),
                $queryBuilder->expr()->isNull($dateColumn),
            );
        }
        return $queryBuilder
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->or(...$constraints));
    }

    /**
     * Whether a legacy column still holds a year that has to be converted. Asked
     * only while the DATE columns are missing, so it looks at the legacy columns
     * alone - which is exactly the state a schema update repairs.
     *
     * @param list<string> $columnNames the lower-cased columns the table has
     */
    private function hasUnmigratedLegacyYears(array $columnNames): bool
    {
        $legacyColumns = array_values(array_filter(
            array_keys(self::COLUMN_MAP),
            static fn(string $legacyColumn): bool => in_array($legacyColumn, $columnNames, true),
        ));
        if ($legacyColumns === []) {
            return false;
        }
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $constraints = [];
        foreach ($legacyColumns as $legacyColumn) {
            $constraints[] = $queryBuilder->expr()->and(
                $queryBuilder->expr()->isNotNull($legacyColumn),
                $queryBuilder->expr()->gt($legacyColumn, $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->lte($legacyColumn, $queryBuilder->createNamedParameter(self::YEAR_MAX, Connection::PARAM_INT)),
            );
        }
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where($queryBuilder->expr()->or(...$constraints))
            ->executeQuery()
            ->fetchOne() > 0;
    }

    /**
     * The column pairs the table actually has: a legacy column that was dropped
     * already, or a DATE column the schema update has not created yet, takes
     * its pair out of the migration.
     *
     * @param list<string> $columnNames the lower-cased columns the table has
     * @return array<string, string> legacy column => DATE column
     */
    private function availableColumnPairs(array $columnNames): array
    {
        $columnPairs = [];
        foreach (self::COLUMN_MAP as $legacyColumn => $dateColumn) {
            if (in_array($legacyColumn, $columnNames, true) && in_array($dateColumn, $columnNames, true)) {
                $columnPairs[$legacyColumn] = $dateColumn;
            }
        }
        return $columnPairs;
    }

    /**
     * The lower-cased column names of the table, empty when the table itself is
     * missing - which is the state of an installation that never had it.
     *
     * @return list<string>
     */
    private function columnNames(): array
    {
        $schemaManager = $this->connectionPool
            ->getConnectionForTable(self::TABLE)
            ->createSchemaManager();
        if (!$schemaManager->tablesExist([self::TABLE])) {
            return [];
        }
        // Keyed by the lower-cased column name on every platform.
        return array_values(array_map('strtolower', array_keys($schemaManager->listTableColumns(self::TABLE))));
    }
}
