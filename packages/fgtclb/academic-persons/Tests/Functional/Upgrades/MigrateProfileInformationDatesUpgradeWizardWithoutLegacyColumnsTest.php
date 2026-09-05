<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Functional\Upgrades;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\AcademicPersons\Upgrades\MigrateProfileInformationDatesUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;

/**
 * The schema of a fresh 3.0.0 installation, or of one that dropped the integer
 * columns after migrating: without the `tests/test-legacy-year-columns` fixture
 * the table has no `year`, `year_start` or `year_end` column, and the wizard
 * has to recognise that instead of querying columns that do not exist.
 */
final class MigrateProfileInformationDatesUpgradeWizardWithoutLegacyColumnsTest extends AbstractAcademicPersonsTestCase
{
    #[Test]
    public function updateIsNotNecessaryWithoutTheLegacyColumns(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/profileInformationDates.csv');
        $subject = $this->get(MigrateProfileInformationDatesUpgradeWizard::class);
        $this->assertInstanceOf(MigrateProfileInformationDatesUpgradeWizard::class, $subject);

        $this->assertFalse($subject->updateNecessary());
        $this->assertTrue($subject->executeUpdate());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/DataSets/profileInformationDates.csv');
    }
}
