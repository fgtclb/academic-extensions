<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Factory\DemandFactory;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * `DemandFactoryCategoryFilterTest` covers the category filter submitted by the list form.
 * This one covers the rest of `createDemandObject()`: the branch taken when there is no
 * form data and the plugin settings decide, and the tail that applies to both branches.
 *
 * The three arguments come from `ProjectController::listAction()` and are of very
 * different trustworthiness - `$demandFromForm` is the unvalidated request array,
 * `$settings` is the plugin FlexForm and `$contentElementData` is the `tt_content` row of
 * the plugin. The factory is the only place where that is sorted out, so what is asserted
 * here is which input is allowed to reach which property, and what happens to a value that
 * makes no sense.
 */
final class DemandFactorySettingsTest extends AbstractAcademicProjectsTestCase
{
    /**
     * The plugin FlexForm offers a fixed select, so an unknown state can only arrive from a
     * hand-edited record or a TypoScript override. `ProjectDemand::setActiveState()` throws
     * on one (code 1743627158); the factory normalizes through `tryFromDefault()` first, so
     * the plugin degrades to "all" instead of taking the page down.
     */
    #[Test]
    public function anUnknownActiveStateInThePluginSettingsFallsBackToAll(): void
    {
        $demand = $this->createDemandFromSettings(['activeState' => 'nonsense']);

        $this->assertSame('all', $demand->getActiveState());
    }

    #[Test]
    public function thePluginSettingsProvideTheActiveState(): void
    {
        $demand = $this->createDemandFromSettings(['activeState' => 'completed']);

        $this->assertSame('completed', $demand->getActiveState());
    }

    #[Test]
    public function thePluginSettingsProvideTheSorting(): void
    {
        $demand = $this->createDemandFromSettings(['sorting' => 'title desc']);

        $this->assertSame('title desc', $demand->getSorting());
        $this->assertSame('title', $demand->getSortingField());
        $this->assertSame('desc', $demand->getSortingDirection());
    }

    /**
     * A sorting outside `SortingOptions` is dropped by the setter rather than reported, so
     * the demand keeps the default. That is what stops an arbitrary string from reaching
     * `ProjectRepository::findByDemand()`, which puts the value into `setOrderings()`
     * unquoted.
     */
    #[Test]
    public function anUnknownSortingInThePluginSettingsIsIgnored(): void
    {
        $demand = $this->createDemandFromSettings(['sorting' => 'nonsense desc']);

        $this->assertSame('title asc', $demand->getSorting());
    }

    /**
     * The `categories` setting is a checkbox: it does not carry the category uids, it only
     * says that the ones related to the plugin record itself are to be used. They are read
     * from the `sys_category_record_mm` rows of that `tt_content` uid - which is why this
     * needs a database and the form branch does not.
     */
    #[Test]
    public function theCategoriesRelatedToTheContentElementBecomeTheFilter(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DemandFactorySettings/pluginCategories.csv');

        $demand = $this->createDemandFromSettings(['categories' => '1'], ['uid' => 1]);

        $this->assertSame([1, 2], $this->filteredUids($demand));
    }

    /**
     * Category uid 3 in the fixture is of type `program_type`, which belongs to
     * `EXT:academic_programs`. The group `projects` is passed explicitly, so a relation on
     * the same content element to a category of a foreign group is not part of the filter -
     * asserted by the test above returning two of three relations, and here by the demand
     * of a content element without relations carrying no filter at all.
     */
    #[Test]
    public function aContentElementWithoutRelatedCategoriesGetsAnEmptyFilter(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DemandFactorySettings/pluginCategories.csv');

        $demand = $this->createDemandFromSettings(['categories' => '1'], ['uid' => 2]);

        $this->assertSame([], $this->filteredUids($demand));
    }

    /**
     * Without the setting the relations are not even looked at, and no `FilterCollection`
     * is built - `null` rather than an empty one, which is what
     * `ProjectRepository::findByDemand()` checks for before adding a `contains` constraint
     * per category.
     */
    #[Test]
    public function noFilterIsBuiltWhenTheContentElementSelectsNoCategories(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DemandFactorySettings/pluginCategories.csv');

        $demand = $this->createDemandFromSettings(['categories' => '0'], ['uid' => 1]);

        $this->assertNull($demand->getFilterCollection());
    }

    /**
     * The branch is chosen on `=== null`, not on emptiness. An empty array is form data,
     * so the plugin's own active state and sorting are discarded rather than used as the
     * starting point the form overrides - which is what the comment in the factory
     * describes.
     */
    #[Test]
    public function anEmptyFormArrayDiscardsThePluginSettings(): void
    {
        $demand = $this->createDemand([], ['activeState' => 'completed', 'sorting' => 'title desc']);

        $this->assertSame('all', $demand->getActiveState());
        $this->assertSame('title asc', $demand->getSorting());
    }

    /**
     * The form submits field and direction separately, while the demand stores one string
     * and validates it as a whole. Each of the two setters therefore rebuilds that string
     * from the other half, and only the combination has to be a known `SortingOptions`
     * value - so the two halves have to arrive together for either to take effect.
     */
    #[Test]
    public function theFormSetsSortingFieldAndDirectionSeparately(): void
    {
        $demand = $this->createDemand(['sortingField' => 'title', 'sortingDirection' => 'desc']);

        $this->assertSame('title desc', $demand->getSorting());
    }

    /**
     * The page restriction is never taken from the request: it is the plugin's `pages`
     * field, a comma separated list of uids as FormEngine stores a group field.
     */
    #[Test]
    public function theSelectedPagesComeFromTheContentElementRecord(): void
    {
        $demand = $this->createDemand(null, [], ['pages' => '10,11,12']);

        $this->assertSame([10, 11, 12], $demand->getPages());
    }

    /**
     * A plugin without a page selection restricts nothing, and the guard is on the type as
     * well as on the value - `pages` is `null` on a record read with a schema that has the
     * column, and absent on the `[]` that `listAction()` falls back to when there is no
     * content object.
     */
    #[Test]
    public function anEmptyOrAbsentPageSelectionRestrictsNothing(): void
    {
        $this->assertSame([], $this->createDemand(null, [], ['pages' => ''])->getPages());
        $this->assertSame([], $this->createDemand(null, [], ['pages' => null])->getPages());
        $this->assertSame([], $this->createDemand(null, [], [])->getPages());
    }

    /**
     * `showSelected` flips the page restriction of the repository from "these are the
     * storage pages" to "these are the records", so it decides what the `pages` field
     * means. It is derived from the CType alone, not from a setting.
     */
    #[Test]
    public function onlyTheSingleViewPluginTreatsTheSelectedPagesAsRecords(): void
    {
        $single = $this->createDemand(null, [], ['CType' => 'academicprojects_projectlistsingle']);
        $list = $this->createDemand(null, [], ['CType' => 'academicprojects_projectlist']);
        $none = $this->createDemand(null, [], []);

        $this->assertTrue($single->getShowSelected());
        $this->assertFalse($list->getShowSelected());
        $this->assertFalse($none->getShowSelected());
    }

    /**
     * Unlike active state and sorting, `showHiddenRecords` is applied outside the branch,
     * so it comes from the plugin settings even when the form was submitted. A visitor
     * cannot switch it on through the request.
     */
    #[Test]
    public function hiddenRecordsAreShownOnPluginSettingsEvenWithFormData(): void
    {
        $withForm = $this->createDemand(['activeState' => 'active'], ['showHiddenRecords' => '1']);
        $withoutForm = $this->createDemand(null, ['showHiddenRecords' => '1']);
        $unset = $this->createDemand(null, []);

        $this->assertTrue($withForm->getShowHiddenRecords());
        $this->assertTrue($withoutForm->getShowHiddenRecords());
        $this->assertFalse($unset->getShowHiddenRecords());
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $contentElementData
     */
    private function createDemandFromSettings(array $settings, array $contentElementData = []): ProjectDemand
    {
        return $this->createDemand(null, $settings, $contentElementData);
    }

    /**
     * @param ?array<string, mixed> $demandFromForm
     * @param array<string, mixed> $settings
     * @param array<string, mixed> $contentElementData
     */
    private function createDemand(
        ?array $demandFromForm,
        array $settings = [],
        array $contentElementData = [],
    ): ProjectDemand {
        return $this->get(DemandFactory::class)->createDemandObject(
            $demandFromForm,
            $settings,
            $contentElementData,
        );
    }

    /**
     * @return array<int, int>
     */
    private function filteredUids(ProjectDemand $demand): array
    {
        $uids = [];
        foreach ($demand->getFilterCollection()?->getFilterCategories() ?? [] as $category) {
            $uids[] = $category->getUid();
        }
        sort($uids);

        return $uids;
    }
}
