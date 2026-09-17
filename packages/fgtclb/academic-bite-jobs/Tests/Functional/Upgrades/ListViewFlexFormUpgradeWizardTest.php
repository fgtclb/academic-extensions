<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Upgrades;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\AcademicBiteJobs\Upgrades\ListViewFlexFormUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Coverage for the plugin settings migration of `EXT:academic_bite_jobs`.
 *
 * `Fixtures/ListViewValues.csv` stores one job list content element per case:
 *
 * | uid | stored view value          | expected afterwards            |
 * |-----|----------------------------|--------------------------------|
 * | 1   | `ListView`                 | `List`                         |
 * | 2   | `CardView`                 | `Card`                         |
 * | 3   | `TableView`                | `Table`                        |
 * | 4   | empty                      | `List`                         |
 * | 5   | `Grid` (unknown)           | `List`                         |
 * | 6   | `Table`                    | unchanged                      |
 * | 7   | `CardView`, hidden         | `Card`                         |
 * | 8   | no FlexForm at all         | unchanged                      |
 * | 9   | `ListView`, CType `text`   | unchanged                      |
 * | 10  | no view field              | `List`                         |
 * | 11  | XML that does not parse    | unchanged                      |
 * | 12  | `CardView` plus `groupBy`  | `Card`, `groupBy` removed      |
 * | 13  | `Table` plus both removed settings | only the removed settings gone |
 *
 * Several records are migrated in one run on purpose: the wizard builds one update
 * statement per record, and a parameter defect shows up only after the first (ACE-356).
 */
final class ListViewFlexFormUpgradeWizardTest extends AbstractAcademicBiteJobsTestCase
{
    #[Test]
    public function updateIsNotNecessaryWithoutContentElements(): void
    {
        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryWhenEveryContentElementStoresACurrentValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ListViewValues.csv');
        // What remains are a current value, no FlexForm, another CType and unparsable XML.
        foreach ([1, 2, 3, 4, 5, 7, 10, 12, 13] as $uid) {
            $this->getConnectionPool()->getConnectionForTable('tt_content')->delete('tt_content', ['uid' => $uid]);
        }

        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function updateIsNecessaryForAStoredPre21Value(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ListViewValues.csv');

        $this->assertTrue($this->subject()->updateNecessary());
    }

    #[Test]
    public function storedViewValuesAreRewrittenToAView(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ListViewValues.csv');

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame('List', $this->viewOf(1));
        $this->assertSame('Card', $this->viewOf(2));
        $this->assertSame('Table', $this->viewOf(3));
        $this->assertSame('List', $this->viewOf(4));
        $this->assertSame('List', $this->viewOf(5));
        $this->assertSame('Card', $this->viewOf(7), 'hidden content element migrated');
        $this->assertSame('List', $this->viewOf(10));
        $this->assertSame('Card', $this->viewOf(12));
        $this->assertSame('Table', $this->viewOf(13), 'a current view value stays');
        $this->assertFalse($this->subject()->updateNecessary());
    }

    /**
     * Version 2.1 removed these two settings from the data structure without removing them
     * from the stored FlexForms, and `settings.jobs.groupBy` still reaches the plugin.
     */
    #[Test]
    public function settingsRemovedInVersion21AreDropped(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ListViewValues.csv');
        $this->assertArrayHasKey('settings.jobs.groupBy', $this->settingsOf(12));

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertArrayNotHasKey('settings.jobs.groupBy', $this->settingsOf(12));
        $this->assertArrayNotHasKey('settings.jobs.groupBy', $this->settingsOf(13));
        $this->assertArrayNotHasKey('settings.jobs.custom.zuordnung', $this->settingsOf(13));
        $this->assertSame('key-13', $this->settingsOf(13)['settings.jobs.jobListingKey'] ?? null);
    }

    #[Test]
    public function otherFieldsKeepTheirValue(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ListViewValues.csv');
        $migrated = [1, 2, 3, 4, 5, 7, 10, 12];
        $before = [];
        foreach ($migrated as $uid) {
            $settings = $this->settingsOf($uid);
            unset($settings['settings.jobs.view'], $settings['settings.jobs.groupBy']);
            $before[$uid] = $settings;
            $this->assertSame('key-' . $uid, $settings['settings.jobs.jobListingKey'] ?? null);
            $this->assertArrayHasKey('settings.jobs.limit', $settings);
        }

        $this->assertTrue($this->subject()->executeUpdate());

        foreach ($migrated as $uid) {
            $settings = $this->settingsOf($uid);
            unset($settings['settings.jobs.view']);
            $this->assertSame($before[$uid], $settings, sprintf('other settings of content element %d', $uid));
            $this->assertSame('Element ' . $uid, $this->headerOf($uid));
        }
    }

    #[Test]
    public function contentElementsNotNeedingAMigrationStayByteIdentical(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ListViewValues.csv');
        $before = [];
        foreach ([6, 8, 9, 11] as $uid) {
            $before[$uid] = $this->flexFormOf($uid);
        }

        $this->assertTrue($this->subject()->executeUpdate());

        foreach ($before as $uid => $flexForm) {
            $this->assertSame($flexForm, $this->flexFormOf($uid), sprintf('content element %d untouched', $uid));
        }
    }

    private function subject(): ListViewFlexFormUpgradeWizard
    {
        $subject = $this->get(ListViewFlexFormUpgradeWizard::class);
        $this->assertInstanceOf(ListViewFlexFormUpgradeWizard::class, $subject);

        return $subject;
    }

    private function viewOf(int $uid): ?string
    {
        return $this->settingsOf($uid)['settings.jobs.view'] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function settingsOf(int $uid): array
    {
        $flexForm = GeneralUtility::xml2array($this->flexFormOf($uid));
        $this->assertIsArray($flexForm);

        $settings = [];
        foreach ($flexForm['data']['sDEF']['lDEF'] ?? [] as $name => $field) {
            $settings[$name] = (string)($field['vDEF'] ?? '');
        }

        return $settings;
    }

    private function headerOf(int $uid): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        return (string)$queryBuilder
            ->select('header')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchOne();
    }

    private function flexFormOf(int $uid): string
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();

        return (string)$queryBuilder
            ->select('pi_flexform')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchOne();
    }
}
