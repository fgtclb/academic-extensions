<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Date;

use FGTCLB\AcademicBase\Date\DateDisplay;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Which parts a field publishes, and the ICU skeleton they become. The
 * skeleton is what keeps the notation the locale's own, so every combination
 * is pinned rather than the two that happen to be shipped.
 */
final class DateDisplayTest extends UnitTestCase
{
    #[Test]
    public function allThreePartsAreShownByDefault(): void
    {
        $display = new DateDisplay();

        $this->assertTrue($display->year);
        $this->assertTrue($display->month);
        $this->assertTrue($display->day);
        $this->assertTrue($display->isComplete());
        $this->assertFalse($display->isEmpty());
    }

    #[Test]
    public function aDisplayWithoutAnyPartIsEmpty(): void
    {
        $display = new DateDisplay(year: false, month: false, day: false);

        $this->assertTrue($display->isEmpty());
        $this->assertFalse($display->isComplete());
    }

    /**
     * @return \Generator<string, array{0: DateDisplay, 1: string}>
     */
    public static function skeletonDataSets(): \Generator
    {
        yield 'year, month and day' => [new DateDisplay(), 'yMMMd'];
        yield 'year and month' => [new DateDisplay(day: false), 'yMMM'];
        yield 'year alone' => [new DateDisplay(month: false, day: false), 'y'];
        yield 'month and day' => [new DateDisplay(year: false), 'MMMd'];
        yield 'a month standing alone is spelled out' => [new DateDisplay(year: false, day: false), 'MMMM'];
        yield 'day alone' => [new DateDisplay(year: false, month: false), 'd'];
        yield 'nothing at all' => [new DateDisplay(year: false, month: false, day: false), ''];
    }

    #[DataProvider('skeletonDataSets')]
    #[Test]
    public function partsBecomeAnIcuSkeleton(DateDisplay $display, string $expected): void
    {
        $this->assertSame($expected, $display->skeleton());
    }

    /**
     * @return \Generator<string, array{0: bool, 1: bool, 2: bool}>
     */
    public static function cacheRoundTripDataSets(): \Generator
    {
        foreach ([true, false] as $year) {
            foreach ([true, false] as $month) {
                foreach ([true, false] as $day) {
                    yield sprintf(
                        'year %s, month %s, day %s',
                        $year ? 'on' : 'off',
                        $month ? 'on' : 'off',
                        $day ? 'on' : 'off',
                    ) => [$year, $month, $day];
                }
            }
        }
    }

    /**
     * `SettingsFileLoader` caches the settings as `return <var_export>;` and restores
     * them with `require`, so the round trip is exercised with the real
     * `var_export()` rather than with a hand written array.
     *
     * All eight combinations are pinned rather than one: three booleans always
     * repeat a value, so any single tuple is symmetric under one of the three
     * possible swaps inside `__set_state()` and would restore unchanged from a
     * `month` that reads the `day` key.
     */
    #[DataProvider('cacheRoundTripDataSets')]
    #[Test]
    public function theCacheRoundTripKeepsEveryPart(bool $year, bool $month, bool $day): void
    {
        $display = new DateDisplay(year: $year, month: $month, day: $day);

        $restored = eval('return ' . var_export($display, true) . ';');

        $this->assertInstanceOf(DateDisplay::class, $restored);
        $this->assertSame($year, $restored->year);
        $this->assertSame($month, $restored->month);
        $this->assertSame($day, $restored->day);
    }

    #[Test]
    public function anIncompleteCacheEntryFallsBackToShowingEverything(): void
    {
        $restored = DateDisplay::__set_state([]);

        $this->assertTrue($restored->isComplete());
    }
}
