<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\AddressFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\EmailFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\EmailFormDataValidator;
use FGTCLB\AcademicPersonsEdit\Exception\UnsuitableValidatorException;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\RecordingValidator;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\SilentValidator;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\ValidationSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Email validation is derived from the email-address contract-contact section.
 */
final class EmailFormDataValidatorTest extends UnitTestCase
{
    private const VALIDATION_SET = 'emailAddresses';

    #[Test]
    public function theConfiguredEmailAddressFieldIsProcessed(): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection(
                self::VALIDATION_SET,
                ['email' => [RecordingValidator::class]],
                ['email' => 'emailAddress'],
            ),
            new EmailFormData(email: 'jane.doe@example.org')
        );

        $this->assertSame(['string(jane.doe@example.org)'], $this->messagesFor($result, 'email'));
    }

    #[Test]
    public function aConfiguredFieldFromAnotherContactSectionIsIgnored(): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection('phoneNumbers', ['phoneNumber' => [RecordingValidator::class]]),
            new EmailFormData(email: 'jane.doe@example.org')
        );

        $this->assertFalse($result->hasErrors());
    }

    /**
     * The aliased YAML identifier `emailAddress` must resolve to the DTO's `email` property.
     */
    #[Test]
    #[DataProvider('configuredProperties')]
    public function aConfiguredPropertyResolvesToTheSubmittedValue(string $property, string $expectedDescription): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection(
                self::VALIDATION_SET,
                [$property => [RecordingValidator::class]],
                match ($property) {
                    'email' => ['email' => 'emailAddress'],
                    'type' => ['type' => 'emailAddressType'],
                    default => [],
                },
            ),
            new EmailFormData(email: 'jane.doe@example.org', type: 'work')
        );

        $this->assertSame([$expectedDescription], $this->messagesFor($result, $property));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function configuredProperties(): array
    {
        return [
            'email' => ['email', 'string(jane.doe@example.org)'],
            'type' => ['type', 'string(work)'],
        ];
    }

    /**
     * `email` is configured as `required` plus `email`, which normalizes to
     * `NotEmptyValidator` and `EmailAddressValidator`. Both run and both may
     * contribute, so an empty submit produces two errors on one field - dropping the
     * second entry would leave any string acceptable.
     */
    #[Test]
    public function bothValidatorsOfTheEmailPropertyContributeSeparateErrors(): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection(
                self::VALIDATION_SET,
                ['email' => [RecordingValidator::class, SilentValidator::class, RecordingValidator::class]],
                ['email' => 'emailAddress'],
            ),
            new EmailFormData()
        );

        $this->assertSame(['string()', 'string()'], $this->messagesFor($result, 'email'));
    }

    /**
     * A wrong argument type is a wiring mistake and must surface instead of letting
     * an unvalidated object through.
     */
    #[Test]
    #[DataProvider('unsuitableSubjects')]
    public function anythingButEmailFormDataIsRejected(mixed $subject): void
    {
        $validator = new EmailFormDataValidator();
        $validator->injectAcademicPersonsSettings(
            ValidationSettings::forContractContactSection(self::VALIDATION_SET, []),
        );

        $this->expectException(UnsuitableValidatorException::class);
        $this->expectExceptionCode(1297418975);
        $this->expectExceptionMessage('Not a valid email object.');

        $validator->validate($subject);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unsuitableSubjects(): array
    {
        return [
            'a sibling form data object' => [new AddressFormData()],
            'an arbitrary object' => [new \stdClass()],
            'a non empty string' => ['jane.doe@example.org'],
        ];
    }

    private function validate(AcademicPersonsSettings $settings, mixed $subject): Result
    {
        $validator = new EmailFormDataValidator();
        $validator->injectAcademicPersonsSettings($settings);
        return $validator->validate($subject);
    }

    /**
     * @return array<int, string>
     */
    private function messagesFor(Result $result, string $property): array
    {
        return array_map(
            static fn($error): string => $error->getMessage(),
            $result->forProperty($property)->getErrors()
        );
    }
}
