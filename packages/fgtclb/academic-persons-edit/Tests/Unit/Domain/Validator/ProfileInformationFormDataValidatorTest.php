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
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\EmailFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use FGTCLB\AcademicPersonsEdit\Domain\Validator\ProfileInformationFormDataValidator;
use FGTCLB\AcademicPersonsEdit\Exception\UnsuitableValidatorException;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\RecordingValidator;
use FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures\ValidationSettings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\Error\Result;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Profile-information validation is selected by the document section's record type.
 */
final class ProfileInformationFormDataValidatorTest extends UnitTestCase
{
    private const VALIDATION_SET = 'publications';

    #[Test]
    public function theMatchingDocumentSectionIsProcessed(): void
    {
        $result = $this->validate(
            ValidationSettings::forDocumentSection(
                self::VALIDATION_SET,
                'publication',
                ['title' => [RecordingValidator::class]],
            ),
            new ProfileInformationFormData(type: 'publication', title: 'A publication')
        );

        $this->assertSame(['string(A publication)'], $this->messagesFor($result, 'title'));
    }

    #[Test]
    public function aDocumentSectionWithAnotherRecordTypeIsIgnored(): void
    {
        $result = $this->validate(
            ValidationSettings::forDocumentSection(
                'lectures',
                'lecture',
                ['title' => [RecordingValidator::class]],
            ),
            new ProfileInformationFormData(type: 'publication', title: 'A publication')
        );

        $this->assertFalse($result->hasErrors());
    }

    #[Test]
    public function theContractSectionIsNeverAppliedToAProfileInformationRecord(): void
    {
        $result = $this->validate(
            ValidationSettings::forDocumentSection(
                'contracts',
                'contracts',
                ['title' => [RecordingValidator::class]],
                true,
            ),
            new ProfileInformationFormData(type: 'contracts', title: 'Not a contract'),
        );
        $this->assertFalse($result->hasErrors());
    }

    #[Test]
    public function validatorsFromSiblingDocumentSectionsNeverLeakIntoTheSelectedType(): void
    {
        $publications = ValidationSettings::forDocumentSection(
            'publications',
            'publication',
            ['title' => [RecordingValidator::class]],
        );
        $lectures = ValidationSettings::forDocumentSection(
            'lectures',
            'lecture',
            ['link' => [RecordingValidator::class]],
        );
        $settings = new AcademicPersonsSettings(
            documentSections: array_replace($publications->documentSections, $lectures->documentSections),
        );
        $result = $this->validate(
            $settings,
            new ProfileInformationFormData(
                type: 'publication',
                title: 'A publication',
                link: 'https://example.org',
            ),
        );
        $this->assertSame(['string(A publication)'], $this->messagesFor($result, 'title'));
        $this->assertSame([], $this->messagesFor($result, 'link'));
    }

    /**
     * All normalized document properties have to resolve off the DTO, because an
     * unreadable property silently becomes `null`.
     */
    #[Test]
    #[DataProvider('configuredProperties')]
    public function aConfiguredPropertyResolvesToTheSubmittedValue(string $property, string $expectedDescription): void
    {
        $result = $this->validate(
            ValidationSettings::forDocumentSection(
                self::VALIDATION_SET,
                'publication',
                [$property => [RecordingValidator::class]],
            ),
            new ProfileInformationFormData(
                type: 'publication',
                title: 'A publication',
                bodytext: 'Some text',
                link: 'https://example.org',
                year: 2024,
                yearStart: 2020,
                yearEnd: 2023
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
            // Validator properties used by the shipped document sections.
            'title' => ['title', 'string(A publication)'],
            'year' => ['year', 'int(2024)'],
            'bodytext' => ['bodytext', 'string(Some text)'],
            'link' => ['link', 'string(https://example.org)'],
            'yearStart' => ['yearStart', 'int(2020)'],
            'yearEnd' => ['yearEnd', 'int(2023)'],
            // Section-selection metadata is readable for project-specific rules.
            'type' => ['type', 'string(publication)'],
        ];
    }

    /**
     * A `?int` property has no empty-string state: an omitted or non numeric `year`
     * arrives as `null`, which `NotEmptyValidator` reports with its *null* message
     * and code 1221560910 rather than the empty one. That distinction is what a
     * template branching on the error code sees.
     */
    #[Test]
    public function anOmittedNullableIntIsHandedOverAsNull(): void
    {
        $result = $this->validate(
            ValidationSettings::forDocumentSection(
                self::VALIDATION_SET,
                'publication',
                ['year' => [RecordingValidator::class]],
            ),
            new ProfileInformationFormData(type: 'publication', title: 'A publication')
        );

        $this->assertSame(['null'], $this->messagesFor($result, 'year'));
    }

    /**
     * A wrong argument type is a wiring mistake and must surface instead of letting
     * an unvalidated object through.
     */
    #[Test]
    #[DataProvider('unsuitableSubjects')]
    public function anythingButProfileInformationFormDataIsRejected(mixed $subject): void
    {
        $validator = new ProfileInformationFormDataValidator();
        $validator->injectAcademicPersonsSettings(
            ValidationSettings::forDocumentSection(self::VALIDATION_SET, 'publication', []),
        );

        $this->expectException(UnsuitableValidatorException::class);
        $this->expectExceptionCode(1297418975);
        $this->expectExceptionMessage('Not a valid profile information object.');

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
        $validator = new ProfileInformationFormDataValidator();
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
