<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Upgrades;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\AcademicProjects\Upgrades\PluginUpgradeWizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class PluginUpgradeWizardTest extends AbstractAcademicProjectsTestCase
{
    #[Test]
    public function updateNecessaryReturnsFalseWhenListTypeRecordsAreAvailable(): void
    {
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertFalse($subject->updateNecessary());
    }

    public static function ttContentPluginDataSets(): \Generator
    {
        yield 'only projectlist - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyProjectList_notDeletedOrHidden.csv',
        ];
        yield 'only projectlist - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyProjectList_notDeletedButHidden.csv',
        ];
        yield 'only projectlist - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyProjectList_deletedButNotHidden.csv',
        ];
        yield 'only projectlistsingle - not deleted and hidden' => [
            'fixtureDataSetFile' => 'onlyProjectListSingle_notDeletedOrHidden.csv',
        ];
        yield 'only projectlistsingle - not deleted and but hidden' => [
            'fixtureDataSetFile' => 'onlyProjectListSingle_notDeletedButHidden.csv',
        ];
        yield 'only projectlistsingle - deleted but not hidden' => [
            'fixtureDataSetFile' => 'onlyProjectListSingle_deletedButNotHidden.csv',
        ];
    }

    #[DataProvider('ttContentPluginDataSets')]
    #[Test]
    public function updateNecessaryReturnsTrueWhenUpgradablePluginsExists(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertTrue($subject->updateNecessary(), 'updateNecessary() returns true');
    }

    #[DataProvider('ttContentPluginDataSets')]
    #[Test]
    public function executeUpdateMigratesContentElementsAndReturnsTrue(
        string $fixtureDataSetFile,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataSets/' . $fixtureDataSetFile);
        $subject = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $subject);
        $this->assertTrue($subject->executeUpdate(), 'updateNecessary() returns true');
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Upgraded/' . $fixtureDataSetFile);
    }
}
