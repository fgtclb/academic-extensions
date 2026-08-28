<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator;

use DateTime;
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
                year: new DateTime('2024-05-10'),
                yearStart: new DateTime('2020-02-03'),
                yearEnd: new DateTime('2023-11-29'),
                yearOnly: true,
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
            'year' => ['year', 'datetime(2024-05-10)'],
            'bodytext' => ['bodytext', 'string(Some text)'],
            'link' => ['link', 'string(https://example.org)'],
            'yearStart' => ['yearStart', 'datetime(2020-02-03)'],
            'yearEnd' => ['yearEnd', 'datetime(2023-11-29)'],
            'yearOnly' => ['yearOnly', 'bool(true)'],
            // Section-selection metadata is readable for project-specific rules.
            'type' => ['type', 'string(publication)'],
        ];
    }

    /**
     * A nullable date property has no empty-string state: an omitted or invalid
     * ``year`` arrives as ``null``, which ``NotEmptyValidator`` reports with its null message
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

    #[Test]
    public function configuredRichTextCharacterLimitCountsVisibleTextWithoutMarkup(): void
    {
        $settings = ValidationSettings::forDocumentSection(
            self::VALIDATION_SET,
            'publication',
            [],
            characterLimits: ['bodytext' => 5],
        );
        $validResult = $this->validate(
            $settings,
            new ProfileInformationFormData(
                type: 'publication',
                bodytext: '<p><strong>12345</strong></p>',
            ),
        );
        $this->assertSame([], $this->messagesFor($validResult, 'bodytext'));

        $invalidResult = $this->validate(
            $settings,
            new ProfileInformationFormData(
                type: 'publication',
                bodytext: '<p><strong>123456</strong></p>',
            ),
        );
        $this->assertSame(
            ['The text must not exceed 5 characters.'],
            $this->messagesFor($invalidResult, 'bodytext'),
        );
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
