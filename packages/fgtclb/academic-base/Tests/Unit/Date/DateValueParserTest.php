<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Date;

use FGTCLB\AcademicBase\Date\DateCompletion;
use FGTCLB\AcademicBase\Date\DateCompletionEdge;
use FGTCLB\AcademicBase\Date\DateGranularity;
use FGTCLB\AcademicBase\Date\DateValueParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Reading a submitted value. The strictness is the point: `createFromFormat()`
 * reads `2019-02-31` as the third of March and `32.01.2026` as the first of
 * February, so anything that does not format back to what came in is refused.
 */
final class DateValueParserTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: string, 1: DateGranularity, 2: DateCompletion, 3: string}>
     */
    public static function acceptedDataSets(): \Generator
    {
        yield 'a full date in the format the browser control submits' => [
            '2019-03-14', DateGranularity::DATE, new DateCompletion(), '2019-03-14',
        ];
        yield 'a full date in the German notation the endpoint accepted before' => [
            '14.03.2019', DateGranularity::DATE, new DateCompletion(), '2019-03-14',
        ];
        yield 'a month completed to its first day' => [
            '2019-03', DateGranularity::MONTH, new DateCompletion(), '2019-03-01',
        ];
        yield 'a month completed to its last day' => [
            '2019-02', DateGranularity::MONTH, new DateCompletion(day: DateCompletionEdge::LAST), '2019-02-28',
        ];
        yield 'a year completed to the start of the year' => [
            '2019', DateGranularity::YEAR, new DateCompletion(), '2019-01-01',
        ];
        yield 'a year completed to the end of the year' => [
            '2019', DateGranularity::YEAR, new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST), '2019-12-31',
        ];
        yield 'a full date is accepted at the month granularity and not completed' => [
            '2019-03-14', DateGranularity::MONTH, new DateCompletion(day: DateCompletionEdge::LAST), '2019-03-14',
        ];
        yield 'a full date is accepted at the year granularity and not completed' => [
            '2019-03-14', DateGranularity::YEAR, new DateCompletion(DateCompletionEdge::LAST), '2019-03-14',
        ];
    }

    #[DataProvider('acceptedDataSets')]
    #[Test]
    public function anAcceptedValueBecomesTheExpectedDay(
        string $value,
        DateGranularity $granularity,
        DateCompletion $completion,
        string $expected,
    ): void {
        $date = (new DateValueParser())->parse($value, $granularity, $completion);

        $this->assertInstanceOf(\DateTimeImmutable::class, $date);
        $this->assertSame($expected, $date->format('Y-m-d'));
        $this->assertSame('00:00:00', $date->format('H:i:s'));
    }

    /**
     * @return \Generator<string, array{0: string, 1: DateGranularity}>
     */
    public static function refusedDataSets(): \Generator
    {
        yield 'an empty value is not a date' => ['', DateGranularity::DATE];
        yield 'a day the month does not have' => ['2019-02-31', DateGranularity::DATE];
        yield 'a day no month has' => ['32.01.2026', DateGranularity::DATE];
        yield 'a month no year has' => ['2019-13', DateGranularity::MONTH];
        yield 'a notation of neither format' => ['15/01/2026', DateGranularity::DATE];
        yield 'an unpadded month' => ['2019-3', DateGranularity::MONTH];
        yield 'a two digit year' => ['19', DateGranularity::YEAR];
        yield 'the German notation at the year granularity' => ['14.03.2019', DateGranularity::YEAR];
        yield 'a month value where a full date is asked for' => ['2019-03', DateGranularity::DATE];
        yield 'a year value where a month is asked for' => ['2019', DateGranularity::MONTH];
    }

    #[DataProvider('refusedDataSets')]
    #[Test]
    public function aValueThatIsNotADateOfThatGranularityIsRefused(string $value, DateGranularity $granularity): void
    {
        $this->assertNull((new DateValueParser())->parse($value, $granularity, new DateCompletion()));
    }
}
