<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateProfileInformationDatesUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\Updates\RepeatableInterface;

/**
 * The state a `composer install && vendor/bin/typo3 upgrade:run` deploy reaches
 * the wizard in: the new code is installed, the schema update has not run yet,
 * so the legacy year columns are there and the DATE columns are not.
 *
 * `UpgradeWizardRunCommand::getWizard()` asks `updateNecessary()` *before*
 * `handlePrerequisites()` gets to run `DatabaseUpdatedPrerequisite`, and marks a
 * wizard that answers "no" as done unless it implements
 * {@see RepeatableInterface}. Both halves are pinned here: the wizard reports
 * that an update is necessary, which is what puts it into the instance list the
 * prerequisites are handled for, and it is repeatable, so no run can take it out
 * of the Install Tool before it has converted anything.
 *
 * The test instance always has the DATE columns - they are in the extension's
 * own `ext_tables.sql` - so they are dropped here to produce the pre-update
 * schema.
 */
final class MigrateProfileInformationDatesUpgradeWizardBeforeSchemaUpdateTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE = 'tx_academicpersons_domain_model_profile_information';

    /**
     * @var list<string>
     */
    private const DATE_COLUMNS = ['date', 'date_start', 'date_end'];

    protected function setUp(): void
    {
        $this->addTestExtension('tests/test-legacy-year-columns');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        // Every DBMS but SQLite reuses one database for the whole test class, so
        // a dropped column would still be missing for the next test - and for
        // the CSV import that test starts with.
        $this->restoreDateColumns();
        parent::tearDown();
    }

    #[Test]
    public function updateIsNecessaryWhileTheDateColumnsAreMissing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/legacyYears.csv');
        $this->dropDateColumns();

        $this->assertTrue($this->subject()->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryWithoutTheDateColumnsAndWithoutAStoredYear(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/profileInformationDates.csv');
        $this->dropDateColumns();

        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function theWizardIsRepeatableSoAPrematureRunCannotMarkItDone(): void
    {
        $this->assertInstanceOf(RepeatableInterface::class, $this->subject());
    }

    private function dropDateColumns(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE);
        foreach (self::DATE_COLUMNS as $columnName) {
            $connection->executeStatement(sprintf(
                'ALTER TABLE %s DROP COLUMN %s',
                $connection->quoteIdentifier(self::TABLE),
                $connection->quoteIdentifier($columnName),
            ));
        }
    }

    private function restoreDateColumns(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable(self::TABLE);
        $schemaManager = $connection->createSchemaManager();
        if (!$schemaManager->tablesExist([self::TABLE])) {
            return;
        }
        $columnNames = array_map('strtolower', array_keys($schemaManager->listTableColumns(self::TABLE)));
        foreach (self::DATE_COLUMNS as $columnName) {
            if (in_array($columnName, $columnNames, true)) {
                continue;
            }
            $connection->executeStatement(sprintf(
                'ALTER TABLE %s ADD COLUMN %s DATE DEFAULT NULL',
                $connection->quoteIdentifier(self::TABLE),
                $connection->quoteIdentifier($columnName),
            ));
        }
    }

    private function subject(): MigrateProfileInformationDatesUpgradeWizard
    {
        $subject = $this->get(MigrateProfileInformationDatesUpgradeWizard::class);
        $this->assertInstanceOf(MigrateProfileInformationDatesUpgradeWizard::class, $subject);
        return $subject;
    }
}
