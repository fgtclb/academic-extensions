<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Date;

use FGTCLB\AcademicBase\Date\DateCompletion;
use FGTCLB\AcademicBase\Date\DateCompletionEdge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The four rules an integrator can name - the first or the last month of the
 * year, the first or the last day of the month - and the trap between them:
 * the last day is the last day of the *resulting* month, so a last day of a
 * last month is the 31st of December and never the 31st of January.
 */
final class DateCompletionTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: DateCompletion, 1: int, 2: ?int, 3: ?int, 4: string}>
     */
    public static function completionDataSets(): \Generator
    {
        yield 'a year alone, both edges first' => [
            new DateCompletion(), 2019, null, null, '2019-01-01',
        ];
        yield 'a year alone, both edges last' => [
            new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST), 2019, null, null, '2019-12-31',
        ];
        yield 'a year alone, last month and first day' => [
            new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::FIRST), 2019, null, null, '2019-12-01',
        ];
        yield 'a year alone, first month and last day' => [
            new DateCompletion(DateCompletionEdge::FIRST, DateCompletionEdge::LAST), 2019, null, null, '2019-01-31',
        ];
        yield 'a year and a month, last day of a short month' => [
            new DateCompletion(day: DateCompletionEdge::LAST), 2019, 2, null, '2019-02-28',
        ];
        yield 'a year and a month, last day of a leap February' => [
            new DateCompletion(day: DateCompletionEdge::LAST), 2020, 2, null, '2020-02-29',
        ];
        yield 'a complete date is not completed' => [
            new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST), 2019, 3, 14, '2019-03-14',
        ];
    }

    #[DataProvider('completionDataSets')]
    #[Test]
    public function partsAreCompletedByTheirEdge(
        DateCompletion $completion,
        int $year,
        ?int $month,
        ?int $day,
        string $expected,
    ): void {
        $this->assertSame($expected, $completion->complete($year, $month, $day)->format('Y-m-d'));
    }

    #[Test]
    public function aCompletedDateCarriesNoTimeOfDay(): void
    {
        $date = (new DateCompletion())->complete(2019, null, null);

        $this->assertSame('00:00:00', $date->format('H:i:s'));
    }

    /**
     * @return \Generator<string, array{0: DateCompletionEdge, 1: DateCompletionEdge}>
     */
    public static function cacheRoundTripDataSets(): \Generator
    {
        yield 'both edges first' => [DateCompletionEdge::FIRST, DateCompletionEdge::FIRST];
        yield 'both edges last' => [DateCompletionEdge::LAST, DateCompletionEdge::LAST];
        yield 'a last month and a first day' => [DateCompletionEdge::LAST, DateCompletionEdge::FIRST];
        yield 'a first month and a last day' => [DateCompletionEdge::FIRST, DateCompletionEdge::LAST];
    }

    /**
     * `SettingsFileLoader` caches the settings as `return <var_export>;` and restores
     * them with `require`, so the round trip is exercised with the real
     * `var_export()` rather than with a hand written array - a property the export
     * does not write is lost on every request but the first.
     *
     * The two mixed data sets are what makes this fail rather than decorate it: a
     * `__set_state()` that swapped `month` and `day` restores a symmetric pair
     * unchanged.
     */
    #[DataProvider('cacheRoundTripDataSets')]
    #[Test]
    public function theCacheRoundTripKeepsBothEdges(
        DateCompletionEdge $month,
        DateCompletionEdge $day,
    ): void {
        $completion = new DateCompletion($month, $day);

        $restored = eval('return ' . var_export($completion, true) . ';');

        $this->assertInstanceOf(DateCompletion::class, $restored);
        $this->assertSame($month, $restored->month);
        $this->assertSame($day, $restored->day);
    }

    /**
     * An entry cached before the two edges were configurable restores to the
     * default rule rather than to nothing.
     */
    #[Test]
    public function anIncompleteCacheEntryFallsBackToTheFirstEdgeOfBoth(): void
    {
        $restored = DateCompletion::__set_state([]);

        $this->assertSame(DateCompletionEdge::FIRST, $restored->month);
        $this->assertSame(DateCompletionEdge::FIRST, $restored->day);
    }
}
