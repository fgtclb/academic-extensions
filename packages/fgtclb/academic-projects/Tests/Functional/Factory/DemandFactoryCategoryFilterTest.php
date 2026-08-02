<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Factory\DemandFactory;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The category filter of the list plugin reaches `createDemandObject()` as the raw request
 * array of `listAction(?array $demand = null)`, which validates nothing. Reading it is
 * `EXT:category_types` `Filter\CategoryFilterNormalizer`, covered on its own in
 * `Tests/Unit/Filter/CategoryFilterNormalizerTest` - what is asserted here is that this
 * factory hands the filter over to it and turns the result into a demand.
 */
final class DemandFactoryCategoryFilterTest extends AbstractAcademicProjectsTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DemandFactory/categories.csv');
    }

    #[Test]
    public function aSingleSelectionIsAccepted(): void
    {
        $demand = $this->createDemand(['competence_field' => '1']);

        $this->assertSame([1], $this->filteredUids($demand));
    }

    /**
     * What a filter select with `multiple` submits. It was a `TypeError` before, because
     * the value was handed to `GeneralUtility::intExplode()`, which takes a string.
     */
    #[Test]
    public function aMultipleSelectionIsAccepted(): void
    {
        $demand = $this->createDemand(['competence_field' => ['1', '2']]);

        $this->assertSame([1, 2], $this->filteredUids($demand));
    }

    #[Test]
    public function filtersOfSeveralCategoryTypesAreCombined(): void
    {
        $demand = $this->createDemand(['competence_field' => '1', 'cooperation' => ['3']]);

        $this->assertSame([1, 3], $this->filteredUids($demand));
    }

    /**
     * The prepended "all options" entry carries an empty value, so every unselected filter
     * used to add uid 0 to the list.
     */
    #[Test]
    public function unselectedFiltersSelectNoCategory(): void
    {
        $demand = $this->createDemand(['competence_field' => '', 'cooperation' => '']);

        $this->assertSame([], $this->filteredUids($demand));
    }

    /**
     * A crafted request must drop the filter rather than take the plugin down.
     */
    #[Test]
    public function anUnreadableFilterSelectsNoCategory(): void
    {
        $demand = $this->createDemand('nonsense');

        $this->assertSame([], $this->filteredUids($demand));
    }

    private function createDemand(mixed $filterCollection): ProjectDemand
    {
        return $this->get(DemandFactory::class)->createDemandObject(
            ['filterCollection' => $filterCollection],
            [],
            [],
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
