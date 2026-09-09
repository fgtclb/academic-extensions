<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Date;

use FGTCLB\AcademicBase\Date\DateDisplay;
use FGTCLB\AcademicBase\Date\LocalizedDateFormatter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The site locale decides the notation, never the browser. The complete date
 * is pinned separately from the narrower ones: it deliberately keeps the
 * `MEDIUMDATE` notation the editor and the contract rows already render, and a
 * change to the skeleton would move it on every installation.
 */
final class LocalizedDateFormatterTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{0: DateDisplay, 1: string, 2: string}>
     */
    public static function displayDataSets(): \Generator
    {
        yield 'a complete German date keeps the medium notation' => [
            new DateDisplay(), 'de-DE', '14.03.2019',
        ];
        yield 'a complete US English date keeps the medium notation' => [
            new DateDisplay(), 'en-US', 'Mar 14, 2019',
        ];
        // `en-US` alone cannot carry the argument: its `MEDIUMDATE` and its `yMMMd`
        // skeleton are both `Mar 14, 2019`, so a formatter that built the complete
        // date from the skeleton would pass that case unchanged. German and
        // Japanese are the two shipped cases where the two notations differ, and
        // Japanese differs in the separators as well as in the month name.
        yield 'a complete Japanese date keeps the medium notation' => [
            new DateDisplay(), 'ja-JP', '2019/03/14',
        ];
        yield 'a German year and month' => [
            new DateDisplay(day: false), 'de-DE', 'März 2019',
        ];
        yield 'a US English year and month' => [
            new DateDisplay(day: false), 'en-US', 'Mar 2019',
        ];
        yield 'a year alone is the same everywhere' => [
            new DateDisplay(month: false, day: false), 'de-DE', '2019',
        ];
        yield 'a German month and day' => [
            new DateDisplay(year: false), 'de-DE', '14. März',
        ];
        yield 'a US English month and day' => [
            new DateDisplay(year: false), 'en-US', 'Mar 14',
        ];
        // A month standing on its own is spelled out rather than abbreviated -
        // `MMMM` instead of the `MMM` it carries next to another part - because
        // "Mar" alone reads as a truncation rather than as a month.
        yield 'a German month standing alone is spelled out' => [
            new DateDisplay(year: false, day: false), 'de-DE', 'März',
        ];
        yield 'a US English month standing alone is spelled out' => [
            new DateDisplay(year: false, day: false), 'en-US', 'March',
        ];
        yield 'a day alone is the same everywhere' => [
            new DateDisplay(year: false, month: false), 'en-US', '14',
        ];
    }

    #[DataProvider('displayDataSets')]
    #[Test]
    public function aDateIsFormattedForTheLocaleAndTheShownParts(
        DateDisplay $display,
        string $locale,
        string $expected,
    ): void {
        $formatted = (new LocalizedDateFormatter())->format(
            new \DateTimeImmutable('2019-03-14'),
            $display,
            new Locale($locale),
        );

        $this->assertSame($expected, $formatted);
    }

    #[Test]
    public function aDisplayThatShowsNothingProducesNothing(): void
    {
        $formatted = (new LocalizedDateFormatter())->format(
            new \DateTimeImmutable('2019-03-14'),
            new DateDisplay(year: false, month: false, day: false),
            new Locale('de-DE'),
        );

        $this->assertSame('', $formatted);
    }

    #[Test]
    public function aLocaleIsAcceptedAsAPlainString(): void
    {
        $formatted = (new LocalizedDateFormatter())->format(
            new \DateTimeImmutable('2019-03-14'),
            new DateDisplay(),
            'de-DE',
        );

        $this->assertSame('14.03.2019', $formatted);
    }

    /**
     * Every combination of published parts answers something a visitor can read.
     *
     * The formatter falls back to `MEDIUMDATE` when ICU cannot serve a skeleton,
     * because an empty cell is worse than the whole date. That arm cannot be
     * reached through this API - `DateDisplay::skeleton()` only ever produces one
     * of six plain ASCII skeletons, and `IntlDatePatternGenerator::getBestPattern()`
     * answers `false` only for a skeleton that is not valid UTF-8 - so what is
     * pinned here is the premise the arm rests on: none of the seven displays
     * renders nothing, in any of the shipped locales.
     */
    #[Test]
    public function everyPublishedCombinationRendersSomething(): void
    {
        $subject = new LocalizedDateFormatter();
        $date = new \DateTimeImmutable('2019-03-14');

        foreach (['de-DE', 'en-US', 'ja-JP'] as $locale) {
            foreach ([true, false] as $year) {
                foreach ([true, false] as $month) {
                    foreach ([true, false] as $day) {
                        $display = new DateDisplay(year: $year, month: $month, day: $day);
                        $message = sprintf('%s: %s', $locale, $display->skeleton());
                        $formatted = $subject->format($date, $display, new Locale($locale));
                        if ($display->isEmpty()) {
                            $this->assertSame('', $formatted, $message);
                            continue;
                        }
                        $this->assertNotSame('', $formatted, $message);
                    }
                }
            }
        }
    }
}
