<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Upgrades;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\AcademicProjects\Upgrades\FlexFormUpgradeWizard;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Coverage for the FlexForm migration of `EXT:academic_projects`.
 *
 * The wizard rewrites the plugin FlexForm of every already migrated content element, so each
 * test seeds `tt_content` rows carrying the old setting names and reads the stored XML back.
 *
 * **More than one record per test on purpose.** The wizard builds one update statement per
 * record; a defect in how that statement is parameterised shows up differently in the first
 * iteration than in the following ones, so a single-record fixture can pass while the wizard
 * is broken (ACE-356).
 */
final class FlexFormUpgradeWizardTest extends AbstractAcademicProjectsTestCase
{
    #[Test]
    public function updateIsNotNecessaryWithoutContentElements(): void
    {
        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function updateIsNecessaryForAMigratableContentElement(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.hideCompletedProjects' => '1',
        ]));

        $this->assertTrue($this->subject()->updateNecessary());
    }

    #[Test]
    public function completedProjectsFlagBecomesTheActiveState(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.hideCompletedProjects' => '1',
        ]));
        $this->createContentElement(2, 'academicprojects_projectlistsingle', $this->flexForm([
            'settings.hideCompletedProjects' => '0',
        ]));

        $this->assertTrue($this->subject()->executeUpdate());

        // '1' meant "hide completed", which is the active-only listing.
        $this->assertSame(['settings.activeState' => 'active'], $this->settingsOf(1));
        $this->assertSame(['settings.activeState' => 'all'], $this->settingsOf(2));
    }

    #[Test]
    public function filterAndSortingSettingsAreRenamedKeepingTheirValue(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.filter.options' => '1',
            'settings.sorting.options' => '0',
        ]));
        $this->createContentElement(2, 'academicprojects_projectlist', $this->flexForm([
            'settings.filter.options' => '0',
            'settings.sorting.options' => '1',
        ]));

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame(
            ['settings.hideFilter' => '1', 'settings.hideSorting' => '0'],
            $this->settingsOf(1),
        );
        $this->assertSame(
            ['settings.hideFilter' => '0', 'settings.hideSorting' => '1'],
            $this->settingsOf(2),
        );
    }

    /**
     * The third record is the one a per-record parameter defect loses: the first update may
     * still work by accident, the ones after it never do.
     */
    #[Test]
    public function everyContentElementIsMigratedNotOnlyTheFirst(): void
    {
        for ($uid = 1; $uid <= 3; $uid++) {
            $this->createContentElement($uid, 'academicprojects_projectlist', $this->flexForm([
                'settings.hideCompletedProjects' => '1',
            ]));
        }

        $this->assertTrue($this->subject()->executeUpdate());

        for ($uid = 1; $uid <= 3; $uid++) {
            $this->assertSame(
                ['settings.activeState' => 'active'],
                $this->settingsOf($uid),
                sprintf('content element %d migrated', $uid),
            );
        }
    }

    #[Test]
    public function contentElementOfAnotherPluginStaysUntouched(): void
    {
        $unrelated = $this->flexForm(['settings.hideCompletedProjects' => '1']);
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.hideCompletedProjects' => '1',
        ]));
        $this->createContentElement(2, 'unrelated_plugin', $unrelated);

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame(['settings.activeState' => 'active'], $this->settingsOf(1));
        $this->assertSame($unrelated, $this->flexFormOf(2));
    }

    #[Test]
    public function contentElementWithoutAFlexFormIsSkipped(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', '');
        $this->createContentElement(2, 'academicprojects_projectlist', $this->flexForm([
            'settings.hideCompletedProjects' => '1',
        ]));

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame('', $this->flexFormOf(1));
        $this->assertSame(['settings.activeState' => 'active'], $this->settingsOf(2));
    }

    private function subject(): FlexFormUpgradeWizard
    {
        $subject = $this->get(FlexFormUpgradeWizard::class);
        $this->assertInstanceOf(FlexFormUpgradeWizard::class, $subject);

        return $subject;
    }

    /**
     * @param array<string, string> $settings
     */
    private function flexForm(array $settings): string
    {
        $fields = '';
        foreach ($settings as $name => $value) {
            $fields .= sprintf(
                '<field index="%s"><value index="vDEF">%s</value></field>',
                $name,
                $value,
            );
        }

        return '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
            . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
            . $fields
            . '</language></sheet></data></T3FlexForms>';
    }

    private function createContentElement(int $uid, string $cType, string $flexForm): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert(
            'tt_content',
            [
                'uid' => $uid,
                'pid' => 1,
                'CType' => $cType,
                'pi_flexform' => $flexForm,
            ],
        );
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
            $settings[$name] = $field['vDEF'];
        }

        return $settings;
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
