<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * The normaliser is the one place the flag vocabulary of a settings file is given a
 * meaning, for both consumers at once: the Extbase validators the frontend form runs,
 * and the TCA fragment the backend FormEngine merges. Every rule below is observable
 * by an editor, which is why each one is pinned on its own.
 */
final class ValidationNormalizerTest extends UnitTestCase
{
    /**
     * @return \Generator<string, array{flags: list<string>, expected: Validation}>
     */
    public static function flagsDataSets(): \Generator
    {
        yield 'no flags: an optional text field with a neutral TCA fragment' => [
            'flags' => [],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'text',
                flags: [],
            ),
        ];
        yield 'required: NotEmptyValidator, and required plus minitems in TCA' => [
            'flags' => ['required'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: true,
                disabled: false,
                readOnly: false,
                validatorClassNames: [NotEmptyValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => true, 'minitems' => 1],
                inputType: 'text',
                flags: ['required'],
            ),
        ];
        yield 'readonly cancels required: no validator, TCA readOnly' => [
            'flags' => ['required', 'readonly'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: true,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => true, 'required' => false],
                inputType: 'text',
                flags: ['required', 'readonly'],
            ),
        ];
        yield 'disabled cancels required and is expressed to TCA as readOnly' => [
            'flags' => ['disabled', 'required'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: true,
                readOnly: true,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => true, 'required' => false],
                inputType: 'text',
                flags: ['disabled', 'required'],
            ),
        ];
        yield 'email: EmailAddressValidator, TCA type and input type' => [
            'flags' => ['email'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [EmailAddressValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => false, 'type' => 'email'],
                inputType: 'email',
                flags: ['email'],
            ),
        ];
        yield 'number: TCA type and input type, no validator' => [
            'flags' => ['number'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false, 'type' => 'number'],
                inputType: 'number',
                flags: ['number'],
            ),
        ];
        yield 'date: input type only, the TCA column keeps its own type' => [
            'flags' => ['date'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'date',
                flags: ['date'],
            ),
        ];
        yield 'required email: both validators, in flag order' => [
            'flags' => ['email', 'required'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: true,
                disabled: false,
                readOnly: false,
                validatorClassNames: [NotEmptyValidator::class, EmailAddressValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => true, 'minitems' => 1, 'type' => 'email'],
                inputType: 'email',
                flags: ['email', 'required'],
            ),
        ];
        yield 'flags are matched case-insensitively' => [
            'flags' => ['Required', 'EMAIL'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: true,
                disabled: false,
                readOnly: false,
                validatorClassNames: [NotEmptyValidator::class, EmailAddressValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => true, 'minitems' => 1, 'type' => 'email'],
                inputType: 'email',
                flags: ['required', 'email'],
            ),
        ];
        yield 'url: UrlValidator and input type, the TCA column keeps its own type' => [
            'flags' => ['url'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [UrlValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'url',
                flags: ['url'],
            ),
        ];
        yield 'tel: input type only, no validator, the TCA column keeps its own type' => [
            'flags' => ['tel'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'tel',
                flags: ['tel'],
            ),
        ];
        yield 'textarea: input type only, the TCA column keeps its own type' => [
            'flags' => ['textarea'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'textarea',
                flags: ['textarea'],
            ),
        ];
        yield 'html: input type textarea and the rich text marker, the TCA column keeps its own type' => [
            'flags' => ['html'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'textarea',
                flags: ['html'],
            ),
        ];
        yield 'an unknown flag is kept in the list and has no other effect' => [
            'flags' => ['whatever'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'text',
                flags: ['whatever'],
            ),
        ];
        yield 'flags are trimmed, de-duplicated and kept in configured order' => [
            'flags' => [' required ', 'REQUIRED', 'email', '', 'required'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: true,
                disabled: false,
                readOnly: false,
                validatorClassNames: [NotEmptyValidator::class, EmailAddressValidator::class],
                tcaConfig: ['readOnly' => false, 'required' => true, 'minitems' => 1, 'type' => 'email'],
                inputType: 'email',
                flags: ['required', 'email'],
            ),
        ];
    }

    /**
     * @param list<string> $flags
     */
    #[DataProvider('flagsDataSets')]
    #[Test]
    public function theFlagsOfAFieldAreNormalized(array $flags, Validation $expected): void
    {
        $this->assertEquals($expected, (new ValidationNormalizer())->normalizeValidation('firstName', $flags));
    }

    /**
     * A render type names the frontend control and therefore the input type the flags
     * start from. A flag that implies a type still wins - `email` on a `text` control
     * is an email input - and the render type never reaches the TCA fragment.
     *
     * @return \Generator<string, array{renderType: string, flags: list<string>, expectedInputType: string}>
     */
    public static function renderTypeDataSets(): \Generator
    {
        yield 'select' => ['renderType' => 'select', 'flags' => [], 'expectedInputType' => 'select'];
        yield 'checkbox' => ['renderType' => 'checkbox', 'flags' => [], 'expectedInputType' => 'checkbox'];
        yield 'phone is a tel input' => ['renderType' => 'phone', 'flags' => [], 'expectedInputType' => 'tel'];
        yield 'email' => ['renderType' => 'email', 'flags' => [], 'expectedInputType' => 'email'];
        yield 'number' => ['renderType' => 'number', 'flags' => [], 'expectedInputType' => 'number'];
        yield 'date' => ['renderType' => 'date', 'flags' => [], 'expectedInputType' => 'date'];
        yield 'combinedLink is a url input' => ['renderType' => 'combinedLink', 'flags' => [], 'expectedInputType' => 'url'];
        yield 'ckeditor is a textarea' => ['renderType' => 'ckeditor', 'flags' => [], 'expectedInputType' => 'textarea'];
        yield 'text' => ['renderType' => 'text', 'flags' => [], 'expectedInputType' => 'text'];
        yield 'an unknown render type is a text input' => ['renderType' => 'cropper', 'flags' => [], 'expectedInputType' => 'text'];
        yield 'a flag implying a type wins over the render type' => ['renderType' => 'text', 'flags' => ['email'], 'expectedInputType' => 'email'];
        yield 'required does not change the render type input' => ['renderType' => 'select', 'flags' => ['required'], 'expectedInputType' => 'select'];
    }

    /**
     * @param list<string> $flags
     */
    #[DataProvider('renderTypeDataSets')]
    #[Test]
    public function theRenderTypeDecidesTheInputTypeTheFlagsStartFrom(
        string $renderType,
        array $flags,
        string $expectedInputType,
    ): void {
        $validation = (new ValidationNormalizer())->normalizeValidation('firstName', $flags, renderType: $renderType);

        $this->assertSame($expectedInputType, $validation->inputType);
        $this->assertArrayNotHasKey('renderType', $validation->tcaConfig);
    }

    /**
     * A settings file may name the column explicitly, for the one case the property
     * and the column do not share a name. It is metadata only: the TCA fragment is the
     * same either way.
     */
    #[Test]
    public function anExplicitFieldNameIsKeptInsteadOfTheDerivedOne(): void
    {
        $validation = (new ValidationNormalizer())->normalizeValidation('emailAddress', ['required'], fieldName: 'email');

        $this->assertSame('emailAddress', $validation->identifier);
        $this->assertSame('email', $validation->fieldName);
        $this->assertSame(['readOnly' => false, 'required' => true, 'minitems' => 1], $validation->tcaConfig);
    }

    /**
     * The character limit is what a rich text counter and the server side check read.
     * It stays out of the TCA on purpose - FormEngine's `max` counts markup, the limit
     * counts readable text - and a negative value means "no limit", like zero.
     */
    #[Test]
    public function theCharacterLimitIsCarriedAsMetadataAndNeverReachesTheTca(): void
    {
        $normalizer = new ValidationNormalizer();

        $limited = $normalizer->normalizeValidation('miscellaneous', ['html'], renderType: 'ckeditor', characterLimit: 1000);
        $unlimited = $normalizer->normalizeValidation('miscellaneous', ['html'], renderType: 'ckeditor');
        $negative = $normalizer->normalizeValidation('miscellaneous', ['html'], renderType: 'ckeditor', characterLimit: -5);

        $this->assertSame(1000, $limited->characterLimit);
        $this->assertTrue($limited->isRichText());
        $this->assertArrayNotHasKey('max', $limited->tcaConfig);
        $this->assertSame(0, $unlimited->characterLimit);
        $this->assertSame(0, $negative->characterLimit);
    }

    /**
     * A flag list read from YAML can carry anything; only its strings are flags.
     */
    #[Test]
    public function nonStringEntriesOfAFlagListAreDropped(): void
    {
        $validation = (new ValidationNormalizer())->normalizeValidation('firstName', ['required', 1, null, ['email'], true]);

        $this->assertSame(['required'], $validation->flags);
        $this->assertSame([NotEmptyValidator::class], $validation->validatorClassNames);
    }

    /**
     * One entry addresses both the Extbase property and the database column, which is
     * only possible because the column name is derived from the property name.
     */
    #[Test]
    public function theFieldNameIsTheLowerCaseUnderscoredIdentifier(): void
    {
        $validation = (new ValidationNormalizer())->normalizeValidation('streetNumber', []);

        $this->assertSame('streetNumber', $validation->identifier);
        $this->assertSame('street_number', $validation->fieldName);
    }

    /**
     * The sets keep the identifiers of the file, and each set keeps its validations
     * under the property names - `ValidationSet::get()` looks them up by exactly that
     * key, so a re-indexed array would make every lookup miss.
     */
    #[Test]
    public function theSetsAndTheirValidationsKeepTheirKeys(): void
    {
        $sets = (new ValidationNormalizer())->normalizeValidationSets([
            'profile' => [
                'firstName' => ['disabled'],
                'lastName' => [],
            ],
            'emailAddress' => [
                'email' => ['required', 'email'],
            ],
        ]);

        $this->assertSame(['profile', 'emailAddress'], array_keys($sets));
        $this->assertSame('profile', $sets['profile']->identifier);
        $this->assertSame(['firstName', 'lastName'], array_keys($sets['profile']->validations));
        $this->assertTrue($sets['profile']->get('firstName')?->disabled);
        $this->assertSame('emailAddress', $sets['emailAddress']->identifier);
        $this->assertSame([NotEmptyValidator::class, EmailAddressValidator::class], $sets['emailAddress']->get('email')?->validatorClassNames);
    }

    #[Test]
    public function noSetsProduceNoSets(): void
    {
        $this->assertSame([], (new ValidationNormalizer())->normalizeValidationSets([]));
    }
}
