<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Unit\Domain\Model\Dto;

use FGTCLB\AcademicProjects\Domain\Model\Dto\ActiveState;
use FGTCLB\AcademicProjects\Domain\Model\Dto\ProjectDemand;
use FGTCLB\AcademicProjects\Enumeration\SortingOptions;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `ProjectDemand` is assembled from FlexForm settings and request arguments and then
 * handed to `ProjectRepository::findByDemand()`. Everything it accepts reaches a query,
 * so what it rejects matters as much as what it stores.
 *
 * The sorting handling is the interesting part: `setSorting()` validates against
 * `SortingOptions::getConstants()` and keeps the previous value when the given one is
 * unknown, while `setSortingField()` and `setSortingDirection()` reassemble a full
 * option and go through the same validation.
 */
final class ProjectDemandTest extends UnitTestCase
{
    #[Test]
    public function aFreshDemandSortsByTheDefaultOption(): void
    {
        $subject = new ProjectDemand();

        $this->assertSame(SortingOptions::__default, $subject->getSorting());
        $this->assertSame('title', $subject->getSortingField());
        $this->assertSame('asc', $subject->getSortingDirection());
    }

    #[Test]
    public function aFreshDemandShowsEveryProjectOfNoPage(): void
    {
        $subject = new ProjectDemand();

        $this->assertSame([], $subject->getPages());
        $this->assertSame(ActiveState::ALL->value, $subject->getActiveState());
        $this->assertFalse($subject->getShowSelected());
        $this->assertFalse($subject->getShowHiddenRecords());
        $this->assertNull($subject->getFilterCollection());
    }

    #[Test]
    #[DataProvider('knownSortingOptions')]
    public function aKnownOptionIsSplitIntoFieldAndDirection(string $option, string $field, string $direction): void
    {
        $subject = new ProjectDemand();
        $subject->setSorting($option);

        $this->assertSame($option, $subject->getSorting());
        $this->assertSame($field, $subject->getSortingField());
        $this->assertSame($direction, $subject->getSortingDirection());
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function knownSortingOptions(): \Generator
    {
        yield 'title descending' => [SortingOptions::SORT_BY_TITLE_DESC, 'title', 'desc'];
        yield 'last updated ascending' => [SortingOptions::SORT_BY_LASTUPDATED_ASC, 'lastUpdated', 'asc'];
        yield 'backend sorting' => [SortingOptions::SORT_BY_SORTING_ASC, 'sorting', 'asc'];
        yield 'a prefixed column' => [SortingOptions::SORT_BY_BUDGET_DESC, 'tx_academicprojects_budget', 'desc'];
    }

    /**
     * An unknown option leaves the demand as it was rather than clearing the ordering.
     * A stored FlexForm value that refers to a since-renamed option therefore falls back
     * to the previous sorting instead of reaching Extbase as an empty `ORDER BY`.
     */
    #[Test]
    #[DataProvider('unusableSortingValues')]
    public function anUnknownOptionIsIgnored(string $value): void
    {
        $subject = new ProjectDemand();
        $subject->setSorting(SortingOptions::SORT_BY_BUDGET_ASC);

        $subject->setSorting($value);

        $this->assertSame(SortingOptions::SORT_BY_BUDGET_ASC, $subject->getSorting());
        $this->assertSame('tx_academicprojects_budget', $subject->getSortingField());
        $this->assertSame('asc', $subject->getSortingDirection());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function unusableSortingValues(): \Generator
    {
        yield 'empty' => [''];
        yield 'field without direction' => ['title'];
        yield 'unknown field' => ['uid asc'];
        yield 'unknown direction' => ['title sideways'];
        yield 'wrong case' => ['TITLE ASC'];
        yield 'constant name instead of value' => ['SORT_BY_TITLE_ASC'];
        yield 'sql injected into the ordering' => ['title asc; DROP TABLE pages'];
    }

    /**
     * The direction is kept, so switching the column in a list view does not silently
     * flip the order back to ascending.
     */
    #[Test]
    public function changingTheFieldKeepsTheDirection(): void
    {
        $subject = new ProjectDemand();
        $subject->setSorting(SortingOptions::SORT_BY_TITLE_DESC);

        $subject->setSortingField('lastUpdated');

        $this->assertSame(SortingOptions::SORT_BY_LASTUPDATED_DESC, $subject->getSorting());
        $this->assertSame('lastUpdated', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    #[Test]
    public function changingTheDirectionKeepsTheField(): void
    {
        $subject = new ProjectDemand();
        $subject->setSorting(SortingOptions::SORT_BY_BUDGET_ASC);

        $subject->setSortingDirection('desc');

        $this->assertSame(SortingOptions::SORT_BY_BUDGET_DESC, $subject->getSorting());
        $this->assertSame('tx_academicprojects_budget', $subject->getSortingField());
        $this->assertSame('desc', $subject->getSortingDirection());
    }

    /**
     * Both setters reassemble a full option and hand it to `setSorting()`, so a
     * combination that is not offered is rejected there. `tx_academicprojects_start_date`
     * exists in both directions, `budget` only in the two it declares - asking for a
     * field the current direction has no option for changes nothing.
     */
    #[Test]
    public function aFieldWithoutAnOptionForTheCurrentDirectionIsIgnored(): void
    {
        $subject = new ProjectDemand();
        $subject->setSorting(SortingOptions::SORT_BY_TITLE_ASC);

        $subject->setSortingField('nonexistentColumn');

        $this->assertSame(SortingOptions::SORT_BY_TITLE_ASC, $subject->getSorting());
        $this->assertSame('title', $subject->getSortingField());
    }

    /**
     * There is no option without a direction, so the direction cannot be cleared. Worth
     * pinning: it means `getSortingDirection()` never returns an empty string once the
     * constructor has run.
     */
    #[Test]
    public function theDirectionCannotBeCleared(): void
    {
        $subject = new ProjectDemand();

        $subject->setSortingDirection('');

        $this->assertSame('asc', $subject->getSortingDirection());
        $this->assertSame(SortingOptions::SORT_BY_TITLE_ASC, $subject->getSorting());
    }

    #[Test]
    #[DataProvider('activeStates')]
    public function aKnownActiveStateIsStored(string $value): void
    {
        $subject = new ProjectDemand();
        $subject->setActiveState($value);

        $this->assertSame($value, $subject->getActiveState());
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function activeStates(): \Generator
    {
        foreach (ActiveState::values() as $value) {
            yield $value => [$value];
        }
    }

    /**
     * Unlike the sorting, an unusable active state throws rather than falling back -
     * it is set from the controller, not from a stored FlexForm value, so a wrong value
     * there is a programming error and has to be loud.
     */
    #[Test]
    public function anUnknownActiveStateIsRejected(): void
    {
        $subject = new ProjectDemand();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1743627158);

        $subject->setActiveState('pending');
    }

    #[Test]
    public function theRejectedStateIsNamedInTheMessage(): void
    {
        $subject = new ProjectDemand();

        try {
            $subject->setActiveState('pending');
            $this->fail('Expected an \InvalidArgumentException');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString('pending', $exception->getMessage());
            $this->assertStringContainsString('all, active, completed', $exception->getMessage());
        }
    }

    #[Test]
    public function theOfferedActiveStatesAreTheOnesTheSetterAccepts(): void
    {
        $this->assertSame(ActiveState::values(), (new ProjectDemand())->getPossibleActiveStates());
    }

    /**
     * The pages list reaches `findByDemand()` as an uid list for an `IN` constraint, and
     * an empty one is valid there since ACE-349 - so the demand must not invent a value.
     */
    #[Test]
    public function thePageListIsStoredAsGiven(): void
    {
        $subject = new ProjectDemand();
        $subject->setPages([12, 34]);

        $this->assertSame([12, 34], $subject->getPages());
    }
}
