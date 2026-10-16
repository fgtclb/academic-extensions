<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Factory;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Factory\DemandFactory;
use FGTCLB\CategoryTypes\Domain\Repository\CategoryRepository;
use FGTCLB\CategoryTypes\Filter\CategoryFilterNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The sorting and the active state a project list starts with, before a visitor chose
 * one, come from `settings.sorting` and `settings.activeState`. The backend selects store
 * a string, but a setting also arrives through an import, a hand-written FlexForm or
 * TypoScript, and is an array as soon as TypoScript gives it a child,
 * `settings.sorting.field = title` for example. Extbase does not replace such an array
 * with the value an element stores.
 *
 * A demand of a request is just as little bound to strings: Extbase hands the demand
 * argument over as it came, so `demand[sortingField][]=title` reaches the factory as an
 * array.
 *
 * `Functional\Factory\DemandFactorySettingsTest` covers the strings.
 */
final class DemandFactorySortingTest extends UnitTestCase
{
    #[Test]
    public function aKnownSortingSettingIsApplied(): void
    {
        $this->assertSame('title desc', $this->createDemand(['sorting' => 'title desc'])->getSorting());
    }

    /**
     * Everything that is not one of the sorting options keeps the default of the demand,
     * title ascending, instead of raising a `TypeError` of `ProjectDemand::setSorting()`.
     *
     * @return \Generator<string, array{0: mixed}>
     */
    public static function unusableSortingSettingDataProvider(): \Generator
    {
        yield 'empty' => [''];
        yield 'a field only' => ['title'];
        yield 'unknown' => ['uid desc'];
        yield 'not a string' => [['field' => 'title']];
    }

    #[DataProvider('unusableSortingSettingDataProvider')]
    #[Test]
    public function anUnusableSortingSettingKeepsTheDefault(mixed $sorting): void
    {
        $this->assertSame('title asc', $this->createDemand(['sorting' => $sorting])->getSorting());
    }

    #[Test]
    public function noSortingSettingKeepsTheDefault(): void
    {
        $this->assertSame('title asc', $this->createDemand([])->getSorting());
    }

    /**
     * An active state setting that is not a string keeps the default of the demand, all
     * projects, as an unknown one does, instead of raising an "Array to string
     * conversion" warning.
     *
     * The assertion alone cannot fail: the cast turned an array into "Array", which
     * counts as all projects as well. The warning is what fails the unchanged code,
     * through `failOnWarning` of the unit suite.
     */
    #[Test]
    public function anActiveStateSettingThatIsNotAStringKeepsTheDefault(): void
    {
        $this->assertSame('all', $this->createDemand(['activeState' => ['value' => 'active']])->getActiveState());
    }

    /**
     * A value of a request that is not a string is ignored like an unknown option, so the
     * demand keeps its default sorting and active state instead of raising a `TypeError`.
     *
     * @return \Generator<string, array{0: array<string, mixed>}>
     */
    public static function requestValueNotAStringDataProvider(): \Generator
    {
        yield 'sorting' => [['sorting' => ['title desc']]];
        yield 'sorting field' => [['sortingField' => ['lastUpdated']]];
        yield 'sorting direction' => [['sortingDirection' => ['desc']]];
        yield 'active state' => [['activeState' => ['completed']]];
    }

    /**
     * @param array<string, mixed> $demandFromRequest
     */
    #[DataProvider('requestValueNotAStringDataProvider')]
    #[Test]
    public function aRequestValueThatIsNotAStringIsIgnored(array $demandFromRequest): void
    {
        $demand = $this->createDemand(['sorting' => 'title desc', 'activeState' => 'active'], $demandFromRequest);

        $this->assertSame('title asc', $demand->getSorting());
        $this->assertSame('all', $demand->getActiveState());
    }

    /**
     * Only the value that is not a string is ignored, the other values of the request
     * still apply.
     */
    #[Test]
    public function theOtherRequestValuesStillApply(): void
    {
        $demand = $this->createDemand([], [
            'sortingField' => ['lastUpdated'],
            'sortingDirection' => 'desc',
            'activeState' => 'completed',
        ]);

        $this->assertSame('title desc', $demand->getSorting());
        $this->assertSame('completed', $demand->getActiveState());
    }

    /**
     * @param array<string, mixed> $settings
     * @param array<string, mixed>|null $demandFromRequest
     */
    private function createDemand(array $settings, ?array $demandFromRequest = null): ProjectDemand
    {
        $subject = new DemandFactory(
            $this->createStub(CategoryRepository::class),
            $this->createStub(CategoryFilterNormalizer::class),
        );

        return $subject->createDemandObject($demandFromRequest, $settings, ['uid' => 1]);
    }
}
