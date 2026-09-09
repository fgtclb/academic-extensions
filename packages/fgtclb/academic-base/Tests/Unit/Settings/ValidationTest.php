<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Date\DateFieldSettings;
use FGTCLB\AcademicBase\Date\DateGranularity;
use FGTCLB\AcademicBase\Settings\Validation;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\StringLengthValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * `Validation` carries no behaviour beyond `__set_state()` - it is a readonly data
 * object whose properties are read directly. That one method is not decoration though:
 * `SettingsFileLoader` caches the settings as `return <var_export>;` and
 * restores them with `require`, so a constructor property that `__set_state()` does not
 * pass on is lost on every request but the first, where nothing points at it.
 */
final class ValidationTest extends UnitTestCase
{
    #[Test]
    public function everyPropertySurvivesTheVarExportRoundTrip(): void
    {
        $subject = new Validation(
            identifier: 'firstName',
            fieldName: 'first_name',
            required: true,
            disabled: false,
            readOnly: true,
            validatorClassNames: [NotEmptyValidator::class, StringLengthValidator::class],
            tcaConfig: ['type' => 'input', 'max' => 60, 'eval' => 'trim'],
            inputType: 'text',
            flags: ['required', 'readonly', 'html'],
            characterLimit: 100,
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(Validation::class, $restored);
        $this->assertEquals($subject, $restored);
        $this->assertSame('firstName', $restored->identifier);
        $this->assertSame('first_name', $restored->fieldName);
        $this->assertTrue($restored->required);
        $this->assertFalse($restored->disabled);
        $this->assertTrue($restored->readOnly);
        $this->assertSame(
            [NotEmptyValidator::class, StringLengthValidator::class],
            $restored->validatorClassNames,
        );
        $this->assertSame(['type' => 'input', 'max' => 60, 'eval' => 'trim'], $restored->tcaConfig);
        $this->assertSame('text', $restored->inputType);
        $this->assertSame(['required', 'readonly', 'html'], $restored->flags);
        $this->assertSame(100, $restored->characterLimit);
        $this->assertTrue($restored->isRichText());
    }

    /**
     * `inputType`, `flags` and `characterLimit` are the constructor arguments with a
     * default. `__set_state()` reads the first as a required array key and the other
     * two with a fallback, so an entry cached before they existed still restores.
     * That holds as long as the array comes from `var_export()` of a real instance -
     * this pins that it does, because a validation built without them is the common
     * case.
     */
    #[Test]
    public function aDefaultedInputTypeIsStillExportedAndRestored(): void
    {
        $subject = new Validation(
            identifier: 'firstName',
            fieldName: 'first_name',
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: [],
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(Validation::class, $restored);
        $this->assertSame('', $restored->inputType);
        $this->assertSame([], $restored->flags);
        $this->assertSame(0, $restored->characterLimit);
        $this->assertFalse($restored->isRichText());
    }

    /**
     * The rich text question is answered from the flag list and from nothing else -
     * not from the input type, which a `textarea` flag sets to the same value.
     */
    #[Test]
    public function onlyTheHtmlFlagMakesAValidationRichText(): void
    {
        $arguments = [
            'identifier' => 'miscellaneous',
            'fieldName' => 'miscellaneous',
            'required' => false,
            'disabled' => false,
            'readOnly' => false,
            'validatorClassNames' => [],
            'tcaConfig' => [],
            'inputType' => 'textarea',
        ];

        $this->assertTrue((new Validation(...[...$arguments, 'flags' => ['html']]))->isRichText());
        $this->assertFalse((new Validation(...[...$arguments, 'flags' => ['textarea']]))->isRichText());
        $this->assertFalse((new Validation(...$arguments))->isRichText());
    }

    /**
     * @return \Generator<string, array{0: string, 1: DateFieldSettings, 2: string}>
     */
    public static function controlInputTypeDataSets(): \Generator
    {
        yield 'a text field is its own input type' => [
            'text', new DateFieldSettings(), 'text',
        ];
        yield 'a textarea is its own input type' => [
            'textarea', new DateFieldSettings(), 'textarea',
        ];
        yield 'a select is its own input type' => [
            'select', new DateFieldSettings(), 'select',
        ];
        yield 'a field with no input type at all stays empty' => [
            '', new DateFieldSettings(), '',
        ];
        yield 'a date asking for a whole date is the browser date control' => [
            'date', new DateFieldSettings(granularity: DateGranularity::DATE), 'date',
        ];
        yield 'a date asking for a year and a month is the browser month control' => [
            'date', new DateFieldSettings(granularity: DateGranularity::MONTH), 'month',
        ];
        yield 'a date asking for a year alone is a number control' => [
            'date', new DateFieldSettings(granularity: DateGranularity::YEAR), 'number',
        ];
    }

    /**
     * The control an editor meets. It is the input type for everything but a date,
     * whose control follows the granularity the field asks for - and a year is a
     * `number` control, because no browser has a year input.
     *
     * The granularity of a field that is not a date is never consulted: a `text`
     * field carrying the default `DateFieldSettings` would answer `date` if it were.
     */
    #[DataProvider('controlInputTypeDataSets')]
    #[Test]
    public function theControlInputTypeFollowsTheGranularityOfADateOnly(
        string $inputType,
        DateFieldSettings $dateSettings,
        string $expected,
    ): void {
        $validation = new Validation(
            identifier: 'date',
            fieldName: 'date',
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: [],
            inputType: $inputType,
            dateSettings: $dateSettings,
        );

        $this->assertSame($expected, $validation->getControlInputType());
    }

    /**
     * A granularity that is not the default survives the cache round trip and is
     * still the one the control follows afterwards - which is the only way the
     * request that rebuilds the settings differs from every later one.
     */
    #[Test]
    public function theControlInputTypeSurvivesTheVarExportRoundTrip(): void
    {
        $subject = new Validation(
            identifier: 'dateStart',
            fieldName: 'date_start',
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: [],
            inputType: 'date',
            dateSettings: new DateFieldSettings(granularity: DateGranularity::YEAR),
        );

        $restored = eval('return ' . var_export($subject, true) . ';');

        $this->assertInstanceOf(Validation::class, $restored);
        $this->assertSame(DateGranularity::YEAR, $restored->dateSettings->granularity);
        $this->assertSame('number', $restored->getControlInputType());
    }
}
