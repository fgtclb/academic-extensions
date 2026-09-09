<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Date;

use FGTCLB\AcademicBase\Date\DateGranularity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The control an editor meets, and the format it exchanges values in. A year
 * is a number control because no browser has a year input, which is the one
 * mapping that is not obvious from the name.
 */
final class DateGranularityTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: DateGranularity, 1: string, 2: string}>
     */
    public static function granularityDataSets(): \Generator
    {
        yield 'a full date is the browser date control' => [DateGranularity::DATE, 'date', 'Y-m-d'];
        yield 'a year and a month is the browser month control' => [DateGranularity::MONTH, 'month', 'Y-m'];
        yield 'a year alone is a number control' => [DateGranularity::YEAR, 'number', 'Y'];
    }

    #[DataProvider('granularityDataSets')]
    #[Test]
    public function eachGranularityNamesItsControlAndItsFormat(
        DateGranularity $granularity,
        string $inputType,
        string $format,
    ): void {
        $this->assertSame($inputType, $granularity->inputType());
        $this->assertSame($format, $granularity->format());
    }

    #[Test]
    public function anUnconfiguredFieldAsksForAFullDate(): void
    {
        $this->assertSame(DateGranularity::DATE, DateGranularity::default());
        $this->assertSame(DateGranularity::DATE, DateGranularity::tryFromDefault(''));
        $this->assertSame(DateGranularity::DATE, DateGranularity::tryFromDefault('quarter'));
    }

    /**
     * @return \Generator<string, array{0: string, 1: DateGranularity}>
     */
    public static function configuredValueDataSets(): \Generator
    {
        yield 'the value as written' => ['month', DateGranularity::MONTH];
        yield 'upper case' => ['MONTH', DateGranularity::MONTH];
        yield 'mixed case' => ['MoNtH', DateGranularity::MONTH];
        yield 'surrounded by whitespace' => ["  month\n", DateGranularity::MONTH];
        yield 'upper case and whitespace' => ['  Year ', DateGranularity::YEAR];
    }

    #[DataProvider('configuredValueDataSets')]
    #[Test]
    public function aConfiguredValueIsReadCaseInsensitivelyAndTrimmed(
        string $configured,
        DateGranularity $expected,
    ): void {
        $this->assertSame($expected, DateGranularity::tryFromDefault($configured));
    }
}
