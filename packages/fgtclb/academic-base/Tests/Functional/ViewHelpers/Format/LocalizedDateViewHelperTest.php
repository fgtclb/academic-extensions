<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\ViewHelpers\Format;

use FGTCLB\AcademicBase\Tests\Functional\ViewHelpers\AbstractViewHelperTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * `<b:format.localizedDate>` renders a date in the notation of the site language,
 * showing the parts the template asks for.
 *
 * The three part arguments are the half that had no test at all: a `resolveDisplay()`
 * answering a bare `DateDisplay()` renders every date whole, which is what a template
 * that switched a part off never wanted. They are registered as `bool`, and the
 * documented way to switch one off is the literal `0` of the view helper's own
 * example - the spelling is pinned here rather than assumed, because a `bool`
 * argument written as a literal is decided by Fluid and not by the view helper.
 */
final class LocalizedDateViewHelperTest extends AbstractViewHelperTestCase
{
    private const DATE = '2019-03-14 00:00:00';

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function partArgumentDataSets(): \Generator
    {
        yield 'all three parts by default, German' => ['LocalizedDate', 'de-DE', '14.03.2019'];
        yield 'all three parts by default, US English' => ['LocalizedDate', 'en-US', 'Mar 14, 2019'];
        yield 'month="0" day="0" is the year alone' => ['LocalizedDateYearOnly', 'de-DE', '2019'];
        yield 'year="0" day="0" is the month alone, German' => ['LocalizedDateMonthOnly', 'de-DE', 'März'];
        yield 'year="0" day="0" is the month alone, US English' => ['LocalizedDateMonthOnly', 'en-US', 'March'];
        yield 'day="0" is the year and the month, German' => ['LocalizedDateWithoutDay', 'de-DE', 'März 2019'];
        yield 'day="0" is the year and the month, US English' => ['LocalizedDateWithoutDay', 'en-US', 'Mar 2019'];
        yield 'year="0" is the month and the day, German' => ['LocalizedDateWithoutYear', 'de-DE', '14. März'];
        yield 'year="0" is the month and the day, US English' => ['LocalizedDateWithoutYear', 'en-US', 'Mar 14'];
        yield 'every part switched off renders nothing' => ['LocalizedDateNothingAtAll', 'de-DE', ''];
    }

    #[DataProvider('partArgumentDataSets')]
    #[Test]
    public function thePartArgumentsSelectWhatIsRendered(
        string $template,
        string $locale,
        string $expected,
    ): void {
        $output = $this->render($template, ['date' => new \DateTimeImmutable(self::DATE)], $locale);

        $this->assertSame($expected, $output);
    }

    /**
     * @return \Generator<string, array{0: mixed, 1: mixed, 2: mixed, 3: string}>
     */
    public static function boundArgumentDataSets(): \Generator
    {
        yield 'booleans' => [true, false, false, '2019'];
        yield 'the integers a settings file carries' => [1, 0, 0, '2019'];
        yield 'the strings a JSON payload carries' => ['1', '0', '0', '2019'];
        yield 'everything on' => [true, true, true, '14.03.2019'];
    }

    /**
     * The same three arguments bound to variables rather than written as literals -
     * which is how a partial that is handed a field's own configuration uses them.
     * A `"0"` arriving as a string is the case that decides it: PHP reads it as
     * `false` and every other non-empty string as `true`, so a template that passed
     * `"false"` would publish the part it meant to hide.
     */
    #[DataProvider('boundArgumentDataSets')]
    #[Test]
    public function thePartArgumentsAreAlsoReadFromVariables(
        mixed $year,
        mixed $month,
        mixed $day,
        string $expected,
    ): void {
        $output = $this->render(
            'LocalizedDateFromVariables',
            [
                'date' => new \DateTimeImmutable(self::DATE),
                'year' => $year,
                'month' => $month,
                'day' => $day,
            ],
            'de-DE',
        );

        $this->assertSame($expected, $output);
    }

    /**
     * `date` is the content argument, so the date may be the child of the tag -
     * and the children are not escaped, because a `\DateTimeInterface` cast to a
     * string before the view helper sees it is not one any more.
     */
    #[Test]
    public function theDateIsAlsoReadFromTheChildren(): void
    {
        $output = $this->render(
            'LocalizedDateFromChildren',
            ['date' => new \DateTimeImmutable(self::DATE)],
            'de-DE',
        );

        $this->assertSame('2019', $output);
    }

    /**
     * @return \Generator<string, array{0: mixed}>
     */
    public static function nonDateDataSets(): \Generator
    {
        yield 'a string' => ['2019-03-14'];
        yield 'an integer' => [1552521600];
        yield 'null' => [null];
        yield 'an array' => [['2019-03-14']];
    }

    #[DataProvider('nonDateDataSets')]
    #[Test]
    public function anythingThatIsNotADateRendersNothing(mixed $date): void
    {
        $this->assertSame('', $this->render('LocalizedDate', ['date' => $date], 'de-DE'));
        $this->assertSame('', $this->render('LocalizedDateYearOnly', ['date' => $date], 'de-DE'));
    }

    #[Test]
    public function aTemplateThatAssignsNoDateAtAllRendersNothing(): void
    {
        $this->assertSame('', $this->render('LocalizedDate', [], 'de-DE'));
        $this->assertSame('', $this->render('LocalizedDateYearOnly', [], 'de-DE'));
    }

    /**
     * Without a matched site language there is no locale to borrow, and the system
     * default is the honest answer rather than the last request's one. It is `en`,
     * which is what a scheduler run or a test rendering gets.
     */
    #[Test]
    public function aRenderingWithoutASiteLanguageFallsBackToTheDefaultLocale(): void
    {
        $output = $this->render('LocalizedDate', ['date' => new \DateTimeImmutable(self::DATE)]);

        $this->assertSame('Mar 14, 2019', $output);
    }
}
