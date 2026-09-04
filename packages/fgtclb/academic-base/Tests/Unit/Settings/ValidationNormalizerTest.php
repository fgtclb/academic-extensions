<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Settings;

use FGTCLB\AcademicBase\Settings\Validation;
use FGTCLB\AcademicBase\Settings\ValidationNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
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
            ),
        ];
        yield 'an unknown flag is ignored' => [
            'flags' => ['url'],
            'expected' => new Validation(
                identifier: 'firstName',
                fieldName: 'first_name',
                required: false,
                disabled: false,
                readOnly: false,
                validatorClassNames: [],
                tcaConfig: ['readOnly' => false, 'required' => false],
                inputType: 'text',
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
