<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Upgrades;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\AcademicProjects\Upgrades\FlexFormUpgradeWizard;
use FGTCLB\AcademicProjects\Upgrades\PluginUpgradeWizard;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\PrerequisiteInterface;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

/**
 * Coverage for the FlexForm migration of `EXT:academic_projects`.
 *
 * The wizard rewrites the plugin FlexForm of every project list, a content element of the
 * plugin's type or an "Insert plugin" element of 1.x, so each test seeds `tt_content` rows
 * carrying the old setting names and reads the stored XML back.
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

    /**
     * Every project list created since 2.0 stores the new settings only. Such a list
     * made the wizard show up as necessary on every installation (ACE-847).
     */
    #[Test]
    public function updateIsNotNecessaryForContentElementsWithoutOldSettings(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.activeState' => 'active',
            'settings.hideFilter' => '1',
            'settings.hideSorting' => '0',
        ]));
        $this->createContentElement(2, 'academicprojects_projectlistsingle', '');
        $this->createContentElement(3, 'unrelated_plugin', $this->flexForm([
            'settings.hideCompletedProjects' => '1',
        ]));

        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function updateIsNotNecessaryAnyMoreOnceMigrated(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.sorting.options' => '1',
        ]));
        $this->assertTrue($this->subject()->updateNecessary());

        $this->subject()->executeUpdate();

        $this->assertFalse($this->subject()->updateNecessary());
    }

    #[Test]
    public function contentElementWithoutOldSettingsIsNotRewritten(): void
    {
        $current = $this->flexForm(['settings.activeState' => 'completed']);
        $this->createContentElement(1, 'academicprojects_projectlist', $current);
        $this->createContentElement(2, 'academicprojects_projectlist', $this->flexForm([
            'settings.hideCompletedProjects' => '1',
        ]));

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame($current, $this->flexFormOf(1));
        $this->assertSame(['settings.activeState' => 'active'], $this->settingsOf(2));
    }

    /**
     * 1.x stored the checkbox as `settings.hide_completed_projects`, the wizard only knew
     * the camel case name no released version stored (ACE-847).
     */
    #[Test]
    public function updateIsNecessaryForTheSettingNameOf1x(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.hide_completed_projects' => '0',
        ]));

        $this->assertTrue($this->subject()->updateNecessary());
    }

    /**
     * 1.x: a checked box (`1`) hid completed projects, which is the active-only listing.
     * `settings.filter.options` of 1.x hid the filter when checked, as `settings.hideFilter`.
     */
    #[Test]
    public function completedProjectsSettingOf1xBecomesTheActiveState(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.filter.options' => '1',
            'settings.hide_completed_projects' => '1',
        ]));
        $this->createContentElement(2, 'academicprojects_projectlistsingle', $this->flexForm([
            'settings.filter.options' => '0',
            'settings.hide_completed_projects' => '0',
        ]));

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame(['settings.activeState' => 'active', 'settings.hideFilter' => '1'], $this->settingsOf(1));
        $this->assertSame(['settings.activeState' => 'all', 'settings.hideFilter' => '0'], $this->settingsOf(2));
        $this->assertFalse($this->subject()->updateNecessary());
    }

    /**
     * A backend save keeps stored FlexForm keys the form no longer shows, so a list an
     * editor saved in 2.x carries the 1.x setting next to the 2.x one. The 2.4 run is the
     * first effective one after an upgrade from 1.x, and must not overwrite what the editor
     * chose since (ACE-847).
     */
    #[Test]
    public function aStoredNewSettingIsKeptAndTheOldOneRemoved(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.hide_completed_projects' => '1',
            'settings.activeState' => 'completed',
            'settings.filter.options' => '1',
            'settings.hideFilter' => '0',
        ]));
        $this->createContentElement(2, 'academicprojects_projectlist', $this->flexForm([
            'settings.hide_completed_projects' => '0',
            'settings.activeState' => 'active',
            'settings.sorting.options' => '1',
            'settings.hideSorting' => '0',
        ]));
        $this->assertTrue($this->subject()->updateNecessary());

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame(['settings.activeState' => 'completed', 'settings.hideFilter' => '0'], $this->settingsOf(1));
        $this->assertSame(['settings.activeState' => 'active', 'settings.hideSorting' => '0'], $this->settingsOf(2));
        $this->assertFalse($this->subject()->updateNecessary());
    }

    /**
     * Both old names of the active state in one FlexForm: the first one creates the
     * target, the second one is removed without overwriting it.
     */
    #[Test]
    public function bothOldNamesOfTheActiveStateAreRemoved(): void
    {
        $this->createContentElement(1, 'academicprojects_projectlist', $this->flexForm([
            'settings.hide_completed_projects' => '1',
            'settings.hideCompletedProjects' => '0',
        ]));

        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame(['settings.activeState' => 'active'], $this->settingsOf(1));
        $this->assertFalse($this->subject()->updateNecessary());
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

    /**
     * A 1.x project list is still an "Insert plugin" element until the plugin wizard
     * converted it, and it carries the settings of 1.x all the same (ACE-923).
     */
    #[Test]
    public function updateIsNecessaryForAnInsertPluginElementOf1x(): void
    {
        $this->createContentElement(1, 'list', $this->flexForm([
            'settings.hide_completed_projects' => '1',
        ]), 'academicprojects_projectlist');

        $this->assertTrue($this->subject()->updateNecessary());
    }

    #[Test]
    public function insertPluginElementOfAnotherPluginStaysUntouched(): void
    {
        $unrelated = $this->flexForm(['settings.hide_completed_projects' => '1']);
        $this->createContentElement(1, 'list', $unrelated, 'unrelated_plugin');

        $this->assertFalse($this->subject()->updateNecessary());
        $this->assertTrue($this->subject()->executeUpdate());

        $this->assertSame($unrelated, $this->flexFormOf(1));
    }

    /**
     * @return array<string, array{0: list<class-string<UpgradeWizardInterface>>}>
     */
    public static function upgradeRunOrders(): array
    {
        return [
            'FlexForm wizard first' => [[FlexFormUpgradeWizard::class, PluginUpgradeWizard::class]],
            'plugin wizard first' => [[PluginUpgradeWizard::class, FlexFormUpgradeWizard::class]],
        ];
    }

    /**
     * A bare `typo3 upgrade:run` asks every pending wizard `updateNecessary()` before it
     * runs the first one, and marks a wizard that needs nothing as done. The FlexForm
     * wizard therefore decides while the 1.x project lists are still "Insert plugin"
     * elements, whichever of the two wizards runs first. It named the plugin wizard as a
     * prerequisite, which TYPO3 drops, so the settings of 1.x were never migrated
     * (ACE-923). Both orders are covered, the registry decides which one a site gets.
     *
     * @param list<class-string<UpgradeWizardInterface>> $order
     */
    #[DataProvider('upgradeRunOrders')]
    #[Test]
    public function upgradeRunMigratesTheSettingsOfInsertPluginElementsOf1x(array $order): void
    {
        $this->createContentElement(1, 'list', $this->flexForm([
            'settings.hide_completed_projects' => '1',
            'settings.filter.options' => '1',
        ]), 'academicprojects_projectlist');
        $this->createContentElement(2, 'list', $this->flexForm([
            'settings.hide_completed_projects' => '0',
            'settings.sorting.options' => '1',
        ]), 'academicprojects_projectlistsingle');
        $this->createContentElement(3, 'list', $this->flexForm([
            'settings.hide_completed_projects' => '0',
        ]), 'academicprojects_projectlist');

        // What `UpgradeWizardRunCommand::runAllWizards()` does, without the registry.
        $wizardsToRun = [];
        foreach ($order as $className) {
            $wizard = $this->get($className);
            $this->assertInstanceOf(UpgradeWizardInterface::class, $wizard);
            if ($wizard->updateNecessary()) {
                $wizardsToRun[] = $wizard;
            }
        }
        foreach ($wizardsToRun as $wizard) {
            $this->assertTrue($wizard->executeUpdate());
        }

        $this->assertSame(['academicprojects_projectlist', ''], $this->typeOf(1));
        $this->assertSame(['academicprojects_projectlistsingle', ''], $this->typeOf(2));
        $this->assertSame(['academicprojects_projectlist', ''], $this->typeOf(3));
        $this->assertSame(['settings.activeState' => 'active', 'settings.hideFilter' => '1'], $this->settingsOf(1));
        $this->assertSame(['settings.activeState' => 'all', 'settings.hideSorting' => '1'], $this->settingsOf(2));
        $this->assertSame(['settings.activeState' => 'all'], $this->settingsOf(3));
        $this->assertFalse($this->subject()->updateNecessary());
        $pluginWizard = $this->get(PluginUpgradeWizard::class);
        $this->assertInstanceOf(PluginUpgradeWizard::class, $pluginWizard);
        $this->assertFalse($pluginWizard->updateNecessary());
    }

    /**
     * TYPO3 adds a prerequisite only if it implements the interface, and drops anything
     * else without a word (`PrerequisiteCollection::add()`).
     */
    #[Test]
    public function everyPrerequisiteIsOneTypo3Accepts(): void
    {
        foreach ($this->subject()->getPrerequisites() as $prerequisite) {
            $this->assertTrue(
                is_a($prerequisite, PrerequisiteInterface::class, true),
                sprintf('%s implements %s', $prerequisite, PrerequisiteInterface::class),
            );
        }
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

    /**
     * `list_type` is written only for an "Insert plugin" element, every other record keeps
     * the default of the column.
     */
    private function createContentElement(int $uid, string $cType, string $flexForm, string $listType = ''): void
    {
        $record = [
            'uid' => $uid,
            'pid' => 1,
            'CType' => $cType,
            'pi_flexform' => $flexForm,
        ];
        if ($listType !== '') {
            $record['list_type'] = $listType;
        }
        $this->getConnectionPool()->getConnectionForTable('tt_content')->insert('tt_content', $record);
    }

    /**
     * @return array{0: string, 1: string} CType and list_type
     */
    private function typeOf(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('CType', 'list_type')
            ->from('tt_content')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid)))
            ->executeQuery()
            ->fetchAssociative();
        $this->assertIsArray($row);

        return [(string)$row['CType'], (string)$row['list_type']];
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
