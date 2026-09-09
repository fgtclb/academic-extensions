<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Date;

use FGTCLB\AcademicBase\Date\DateCompletionEdge;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Which end of a period completes a part the editor was not asked for.
 *
 * The default is the one an integrator never writes down, so it is the one that
 * decides what an unconfigured `dates` block does: the first month of the year
 * and the first day of the month, which is what the dropped integer years were
 * read as everywhere.
 */
final class DateCompletionEdgeTest extends UnitTestCase
{
    #[Test]
    public function theTwoEndsOfAPeriodAreTheOnlyCases(): void
    {
        $this->assertSame(
            ['first', 'last'],
            array_map(
                static fn(DateCompletionEdge $edge): string => $edge->value,
                DateCompletionEdge::cases(),
            ),
        );
    }

    /**
     * The default is `FIRST` and nothing else: a `LAST` default would silently
     * move every unconfigured year to the thirty first of December.
     */
    #[Test]
    public function anUnconfiguredEdgeIsTheFirstOne(): void
    {
        $this->assertSame(DateCompletionEdge::FIRST, DateCompletionEdge::default());
    }

    /**
     * @return \Generator<string, array{0: string, 1: DateCompletionEdge}>
     */
    public static function configuredValueDataSets(): \Generator
    {
        yield 'the value as written' => ['last', DateCompletionEdge::LAST];
        yield 'upper case' => ['LAST', DateCompletionEdge::LAST];
        yield 'mixed case' => ['LaSt', DateCompletionEdge::LAST];
        yield 'surrounded by whitespace' => ["  last\n", DateCompletionEdge::LAST];
        yield 'upper case and whitespace' => [' FIRST ', DateCompletionEdge::FIRST];
        yield 'an empty value falls back' => ['', DateCompletionEdge::FIRST];
        yield 'an unknown value falls back' => ['middle', DateCompletionEdge::FIRST];
        yield 'a value that only contains the name falls back' => ['lastly', DateCompletionEdge::FIRST];
    }

    #[DataProvider('configuredValueDataSets')]
    #[Test]
    public function aConfiguredValueIsReadCaseInsensitivelyAndTrimmed(
        string $configured,
        DateCompletionEdge $expected,
    ): void {
        $this->assertSame($expected, DateCompletionEdge::tryFromDefault($configured));
    }
}
