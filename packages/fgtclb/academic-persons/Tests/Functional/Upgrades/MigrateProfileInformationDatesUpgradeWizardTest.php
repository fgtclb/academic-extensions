<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateProfileInformationDatesUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * Runs the wizard against the schema of an updated installation: the fixture
 * extension re-declares the integer columns `year`, `year_start` and `year_end`
 * next to the `date`, `date_start` and `date_end` columns of 3.0.0, which is
 * what the schema analyzer leaves behind - it never drops a column on its own.
 *
 * The record set covers every shape the wizard has to decide on: a single year,
 * a start and an end year, no year, a stored 0, a five-digit value, records an
 * editor already dated in 3.0, hidden, deleted and translated records.
 */
final class MigrateProfileInformationDatesUpgradeWizardTest extends AbstractAcademicPersonsTestCase
{
    protected function setUp(): void
    {
        $this->addTestExtension('tests/test-legacy-year-columns');
        parent::setUp();
    }

    #[Test]
    public function updateIsNecessaryWhileLegacyYearsHaveNoDate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/legacyYears.csv');

        $this->assertTrue($this->subject()->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryOnceEveryLegacyYearHasItsDate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Upgraded/legacyYears.csv');

        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function executeUpdateWritesTheFirstOfJanuaryAndFlagsTheRecordAsYearOnly(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/legacyYears.csv');
        $subject = $this->subject();

        $this->assertTrue($subject->executeUpdate());

        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/legacyYears.csv');
        $this->assertFalse($subject->updateNecessary(), 'nothing is left to migrate after one run');
    }

    private function subject(): MigrateProfileInformationDatesUpgradeWizard
    {
        $subject = $this->get(MigrateProfileInformationDatesUpgradeWizard::class);
        $this->assertInstanceOf(MigrateProfileInformationDatesUpgradeWizard::class, $subject);
        return $subject;
    }
}
