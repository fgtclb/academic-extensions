<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
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
}
