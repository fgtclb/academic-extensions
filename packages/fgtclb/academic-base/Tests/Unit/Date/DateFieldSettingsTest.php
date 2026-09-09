<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Date;

use FGTCLB\AcademicBase\Date\DateCompletion;
use FGTCLB\AcademicBase\Date\DateCompletionEdge;
use FGTCLB\AcademicBase\Date\DateDisplay;
use FGTCLB\AcademicBase\Date\DateFieldSettings;
use FGTCLB\AcademicBase\Date\DateGranularity;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * One field's date configuration, and the question the editor's hint is drawn
 * from: does a visitor see less than the editor is asked for?
 */
final class DateFieldSettingsTest extends UnitTestCase
{
    #[Test]
    public function theDefaultAsksForAFullDateAndPublishesAllOfIt(): void
    {
        $settings = DateFieldSettings::default();

        $this->assertSame(DateGranularity::DATE, $settings->granularity);
        $this->assertTrue($settings->display->isComplete());
        $this->assertSame(DateCompletionEdge::FIRST, $settings->completion->month);
        $this->assertSame(DateCompletionEdge::FIRST, $settings->completion->day);
        $this->assertFalse($settings->publishesLessThanItAsks());
    }

    /**
     * @return \Generator<string, array{0: DateFieldSettings, 1: bool}>
     */
    public static function hintDataSets(): \Generator
    {
        yield 'a full date entered and published' => [
            new DateFieldSettings(), false,
        ];
        yield 'a full date entered, the year alone published' => [
            new DateFieldSettings(new DateDisplay(month: false, day: false)), true,
        ];
        yield 'a year entered and the year published' => [
            new DateFieldSettings(new DateDisplay(month: false, day: false), DateGranularity::YEAR), false,
        ];
        yield 'a year and a month entered, the year alone published' => [
            new DateFieldSettings(new DateDisplay(month: false, day: false), DateGranularity::MONTH), true,
        ];
        yield 'a year entered, the day published as well changes nothing' => [
            new DateFieldSettings(new DateDisplay(month: false), DateGranularity::YEAR), false,
        ];
    }

    #[DataProvider('hintDataSets')]
    #[Test]
    public function theHintIsShownWhenLessIsPublishedThanAskedFor(DateFieldSettings $settings, bool $expected): void
    {
        $this->assertSame($expected, $settings->publishesLessThanItAsks());
    }

    #[Test]
    public function theCacheRoundTripKeepsTheWholeConfiguration(): void
    {
        $settings = new DateFieldSettings(
            new DateDisplay(month: false, day: false),
            DateGranularity::YEAR,
            new DateCompletion(DateCompletionEdge::LAST, DateCompletionEdge::LAST),
        );

        $restored = DateFieldSettings::__set_state([
            'display' => $settings->display,
            'granularity' => $settings->granularity,
            'completion' => $settings->completion,
        ]);

        $this->assertEquals($settings, $restored);
    }
}
