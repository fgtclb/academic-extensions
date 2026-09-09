<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use Doctrine\DBAL\Schema\Column;
use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Tests\Functional\Upgrades\Fixtures\DefaultEdgesProfileInformationDatesUpgradeWizard;
use FGTCLB\AcademicPersons\Tests\Functional\Upgrades\Fixtures\LastEdgesEndDateProfileInformationDatesUpgradeWizard;
use FGTCLB\AcademicPersons\Upgrades\AbstractMigrateProfileInformationDatesUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * {@see AbstractMigrateProfileInformationDatesUpgradeWizard} is abstract on purpose
 * and is never registered, so this test exercises it the way a project does: through
 * a concrete subclass that picks the completion rules.
 *
 * The three integer year columns the wizard reads are gone from `ext_tables.sql`,
 * which is the whole reason the wizard exists - so the test adds them back for its
 * own run and drops them again afterwards. That is the same technique
 * `EnsureTtContentListTypeColumnTrait` uses for the removed `tt_content.list_type`;
 * both operations are guarded by a schema look-up, so a re-used test database does
 * not turn them into a duplicate-column error.
 */
final class MigrateProfileInformationDatesUpgradeWizardTest extends AbstractAcademicPersonsTestCase
{
    private const TABLE_NAME = 'tx_academicpersons_domain_model_profile_information';
    private const LEGACY_COLUMNS = ['year', 'year_start', 'year_end'];
    private const FIXTURE_PATH = __DIR__ . '/Fixtures/MigrateProfileInformationDates/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->addLegacyYearColumns();
    }

    protected function tearDown(): void
    {
        $this->removeLegacyYearColumns();
        parent::tearDown();
    }

    #[Test]
    public function updateNecessaryReturnsFalseWithoutAnyRecord(): void
    {
        $this->assertFalse($this->getDefaultEdgesWizard()->updateNecessary());
    }

    #[Test]
    public function updateNecessaryReturnsTrueWhenAYearIsWaitingForItsDate(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears.csv');
        $this->assertTrue($this->getDefaultEdgesWizard()->updateNecessary());
    }

    #[Test]
    public function defaultEdgesCompleteAYearToTheFirstDayOfTheFirstMonth(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears.csv');

        $this->assertTrue($this->getDefaultEdgesWizard()->executeUpdate());

        $this->assertCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears_defaultEdges.csv');
    }

    #[Test]
    public function lastEdgesCompleteAnEndYearToTheThirtyFirstOfDecember(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears.csv');

        $this->assertTrue($this->getLastEdgesEndDateWizard()->executeUpdate());

        $this->assertCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears_lastEdgesEndDate.csv');
    }

    /**
     * Record 3 carries a year *and* a date. The date is what an editor or an earlier,
     * hand-written migration decided, and the wizard has nothing better to offer - so
     * it stays.
     */
    #[Test]
    public function aTargetDateThatIsAlreadySetIsNeverOverwritten(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears.csv');

        $this->getLastEdgesEndDateWizard()->executeUpdate();

        $this->assertSame('2001-05-06', $this->getDateValue(3, 'date'));
    }

    #[Test]
    public function updateIsNotNecessaryAnyMoreAfterARunAndASecondRunChangesNothing(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears.csv');
        $subject = $this->getLastEdgesEndDateWizard();
        $subject->executeUpdate();

        $this->assertFalse($subject->updateNecessary(), 'updateNecessary() is false after the run');

        $this->assertTrue($subject->executeUpdate(), 'the second run reports success');
        $this->assertCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears_lastEdgesEndDate.csv');
    }

    /**
     * A deleted and a hidden record carry a year like every other one, and an editor
     * restores or unhides either at any time. Migrating only what a default query
     * sees would leave those rows with an empty date forever, so every query of the
     * wizard drops its restrictions - which is what the two extra records of the
     * fixture pin. The `deleted` and `hidden` flags themselves are untouched.
     */
    #[Test]
    public function deletedAndHiddenRecordsAreMigratedAsWell(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears.csv');

        $this->assertTrue($this->getLastEdgesEndDateWizard()->executeUpdate());

        $this->assertSame('2019-01-01', $this->getDateValue(6, 'date'), 'the deleted record');
        $this->assertSame('2015-01-01', $this->getDateValue(7, 'date_start'), 'the hidden record');
        $this->assertSame('2019-12-31', $this->getDateValue(7, 'date_end'), 'the hidden record');
    }

    /**
     * The same for the question the install tool asks first: a database whose only
     * unmigrated rows are deleted or hidden still has work to do, and reports it.
     */
    #[Test]
    public function aDatabaseOfOnlyDeletedAndHiddenRecordsStillNeedsTheUpdate(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYearsRestrictedOnly.csv');
        $subject = $this->getLastEdgesEndDateWizard();

        $this->assertTrue($subject->updateNecessary());

        $this->assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(self::FIXTURE_PATH . 'profileInformationYearsRestrictedOnly_lastEdgesEndDate.csv');
        $this->assertFalse($subject->updateNecessary(), 'updateNecessary() is false after the run');
    }

    /**
     * An installation that has run the database analyser after upgrading no longer
     * has the legacy columns. Asking for a migration there is a completed migration,
     * not a query against a column that does not exist.
     */
    #[Test]
    public function aDatabaseWithoutTheLegacyColumnsReportsNothingToDo(): void
    {
        $this->importCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears.csv');
        $this->removeLegacyYearColumns();
        $subject = $this->getDefaultEdgesWizard();

        $this->assertFalse($subject->updateNecessary());
        $this->assertTrue($subject->executeUpdate());

        $this->addLegacyYearColumns();
        $this->assertCSVDataSet(self::FIXTURE_PATH . 'profileInformationYears_untouched.csv');
    }

    private function getDefaultEdgesWizard(): DefaultEdgesProfileInformationDatesUpgradeWizard
    {
        return new DefaultEdgesProfileInformationDatesUpgradeWizard($this->get(ConnectionPool::class));
    }

    private function getLastEdgesEndDateWizard(): LastEdgesEndDateProfileInformationDatesUpgradeWizard
    {
        return new LastEdgesEndDateProfileInformationDatesUpgradeWizard($this->get(ConnectionPool::class));
    }

    private function getDateValue(int $uid, string $column): ?string
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $value = $queryBuilder
            ->select($column)
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchOne();
        return $value === false || $value === null ? null : (string)$value;
    }

    /**
     * The pre-3.0 declaration of the three columns, as `ext_tables.sql` carried it
     * until the date columns replaced them.
     */
    private function addLegacyYearColumns(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $existingColumns = $this->getColumnNames();
        foreach (self::LEGACY_COLUMNS as $columnName) {
            if (in_array($columnName, $existingColumns, true)) {
                continue;
            }
            $connection->executeStatement(
                sprintf(
                    'ALTER TABLE %s ADD COLUMN %s INT DEFAULT NULL',
                    $connection->quoteIdentifier(self::TABLE_NAME),
                    $connection->quoteIdentifier($columnName),
                ),
            );
        }
    }

    private function removeLegacyYearColumns(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $existingColumns = $this->getColumnNames();
        foreach (self::LEGACY_COLUMNS as $columnName) {
            if (!in_array($columnName, $existingColumns, true)) {
                continue;
            }
            $connection->executeStatement(
                sprintf(
                    'ALTER TABLE %s DROP COLUMN %s',
                    $connection->quoteIdentifier(self::TABLE_NAME),
                    $connection->quoteIdentifier($columnName),
                ),
            );
        }
    }

    /**
     * @return list<string>
     */
    private function getColumnNames(): array
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        return array_values(array_map(
            static fn(Column $column): string => strtolower($column->getName()),
            $connection->createSchemaManager()->listTableColumns(self::TABLE_NAME),
        ));
    }
}
