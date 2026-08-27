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
use FGTCLB\AcademicPersonsEdit\Domain\Validator\AddressFormDataValidator;
use FGTCLB\AcademicPersonsEdit\Exception\UnsuitableValidatorException;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\RecordingValidator;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\ValidationSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Address validation is derived only from the physical-address contract-contact section.
 */
final class AddressFormDataValidatorTest extends UnitTestCase
{
    private const VALIDATION_SET = 'physicalAddresses';

    /**
     * Address fields are resolved only from the configured contract-contact section.
     */
    #[Test]
    public function configuredAddressFieldsAreProcessed(): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection(self::VALIDATION_SET, ['city' => [RecordingValidator::class]]),
            new AddressFormData(city: 'Munich')
        );

        $this->assertSame(['string(Munich)'], $this->messagesFor($result, 'city'));
    }

    #[Test]
    public function aConfiguredFieldFromAnotherContactSectionIsIgnored(): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection('emailAddresses', ['email' => [RecordingValidator::class]]),
            new AddressFormData(city: 'Munich')
        );

        $this->assertFalse($result->hasErrors());
    }

    /**
     * The section's property names are resolved off the DTO through
     * `ObjectAccess`, which yields `null` for anything it cannot read instead of
     * raising. Renaming a DTO property without touching the yaml file would
     * therefore keep validating - against `null` - and a required address field
     * would become unsaveable. Every property the shipped configuration names is
     * pinned here to prove it still resolves to the submitted value.
     */
    #[Test]
    #[DataProvider('configuredProperties')]
    public function aConfiguredPropertyResolvesToTheSubmittedValue(string $property, string $expectedDescription): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection(
                self::VALIDATION_SET,
                [$property => [RecordingValidator::class]],
                $property === 'type' ? ['type' => 'physicalAddressType'] : [],
            ),
            new AddressFormData(
                street: 'Bahnhofstrasse',
                streetNumber: '12a',
                additional: 'c/o Faculty',
                zip: '80331',
                city: 'Munich',
                state: 'Bavaria',
                country: 'DE',
                type: 'work'
            )
        );

        $this->assertSame([$expectedDescription], $this->messagesFor($result, $property));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function configuredProperties(): array
    {
        return [
            // Required in the shipped Configuration/AcademicsPersonsEdit/Settings.yaml.
            'street' => ['street', 'string(Bahnhofstrasse)'],
            'streetNumber' => ['streetNumber', 'string(12a)'],
            'zip' => ['zip', 'string(80331)'],
            'city' => ['city', 'string(Munich)'],
            'country' => ['country', 'string(DE)'],
            // Not configured by default, but supported when added to the profile section.
            'additional' => ['additional', 'string(c/o Faculty)'],
            'state' => ['state', 'string(Bavaria)'],
            'type' => ['type', 'string(work)'],
        ];
    }

    /**
     * A profile field which is not part of the address DTO mapping is not processed.
     */
    #[Test]
    public function anUnknownAddressPropertyIsIgnored(): void
    {
        $result = $this->validate(
            ValidationSettings::forContractContactSection(self::VALIDATION_SET, ['houseNumber' => [RecordingValidator::class]]),
            new AddressFormData(streetNumber: '12a')
        );

        $this->assertFalse($result->hasErrors());
    }

    /**
     * The validator is wired per controller argument, so a mismatch is a programming
     * error and has to surface instead of passing an unvalidated object on.
     */
    #[Test]
    #[DataProvider('unsuitableSubjects')]
    public function anythingButAddressFormDataIsRejected(mixed $subject): void
    {
        $validator = new AddressFormDataValidator();
        $validator->injectAcademicPersonsSettings(
            ValidationSettings::forContractContactSection(self::VALIDATION_SET, []),
        );

        $this->expectException(UnsuitableValidatorException::class);
        $this->expectExceptionCode(1297418975);
        $this->expectExceptionMessage('Not a valid address object.');

        $validator->validate($subject);
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function unsuitableSubjects(): array
    {
        return [
            'a sibling form data object' => [new EmailFormData()],
            'an arbitrary object' => [new \stdClass()],
            'a non empty string' => ['not an object'],
        ];
    }

    private function validate(AcademicPersonsSettings $settings, mixed $subject): Result
    {
        $validator = new AddressFormDataValidator();
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
