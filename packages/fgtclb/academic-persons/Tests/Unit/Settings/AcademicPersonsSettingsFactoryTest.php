<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use ReflectionMethod;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AcademicPersonsSettingsFactoryTest extends UnitTestCase
{
    #[Test]
    public function shippedConfigurationUsesOnlyTheSectionBasedTopLevelMaps(): void
    {
        $this->assertSame(
            ['profile', 'special', 'contractContact', 'documentSections'],
            array_keys($this->getShippedConfiguration()),
        );
    }

    #[Test]
    public function sectionSchemaDoesNotReuseTheIncompatibleLegacyCacheEntry(): void
    {
        $factory = (new ReflectionClass(AcademicPersonsSettingsFactory::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AcademicPersonsSettingsFactory::class, 'academicPersonsSettingsIdentifier');
        $method->setAccessible(true);
        $this->assertSame('AcademicPersons_Settings_SectionSchema_v3', $method->invoke($factory));
    }

    #[Test]
    public function shippedProfileFieldsAreGroupedByTheirConfiguredSection(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame(['information', 'aboutme'], array_keys($settings->profileSections));
        $this->assertSame(
            [
                'gender',
                'title',
                'firstName',
                'middleName',
                'lastName',
                'website',
                'publicationsLink',
                'coreCompetences',
                'supervisedThesis',
                'supervisedDoctoralThesis',
                'teachingArea',
            ],
            array_keys($settings->getProfileSection('information')?->fields ?? []),
        );
        $this->assertSame(['miscellaneous'], array_keys($settings->getProfileSection('aboutme')?->fields ?? []));
        $firstName = $settings->getProfileField('firstName');
        $this->assertNotNull($firstName);
        $this->assertSame('information', $firstName->section);
        $this->assertTrue($firstName->validation->readOnly);
        $this->assertSame('input', $firstName->fieldType);
        $this->assertSame('text', $firstName->renderType);
        $this->assertSame('website', $settings->getProfileField('website')?->propertyName);
        $this->assertSame(0, $settings->getProfileField('gender')?->position);
        $this->assertSame(0, $settings->getProfileField('miscellaneous')?->position);
    }

    #[Test]
    public function shippedSpecialFieldsDescribeNameImageAndSynchronizationComponents(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame(['title', 'image', 'skipSync'], array_keys($settings->specialFields));
        $this->assertSame(
            ['title', 'firstName', 'middleName', 'lastName'],
            $settings->getSpecialField('title')?->fieldIdentifiers,
        );
        $this->assertSame('title', $settings->getSpecialField('title')?->renderType);
        $this->assertSame('image', $settings->getSpecialField('image')?->renderType);
        $this->assertTrue($settings->getSpecialField('skipSync')?->hasDirectProfileProperty());
        $this->assertSame('checkbox', $settings->getSpecialField('skipSync')?->validation->inputType);
    }

    #[Test]
    public function shippedContractContactsAreSeparateAndSectionLocal(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame(
            ['phoneNumbers', 'emailAddresses', 'physicalAddresses'],
            array_keys($settings->contractContactSections),
        );
        $email = $settings->getContractContactField('emailAddress');
        $this->assertNotNull($email);
        $this->assertSame('email', $email->propertyName);
        $this->assertSame('emailAddresses', $email->section);
        $this->assertSame(
            [NotEmptyValidator::class, EmailAddressValidator::class],
            $email->validation->validatorClassNames,
        );
        $this->assertNull($settings->getProfileField('street'));
        $this->assertNull($settings->getContractContactValidationSet('phoneNumbers')->get('email'));
        $this->assertNotNull($settings->getContractContactValidationSet('emailAddresses')->get('email'));
    }

    #[Test]
    public function shippedDocumentSectionsKeepOrderMetadataAndSectionLocalValidation(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame(
            [
                'contracts',
                'cooperation',
                'lectures',
                'memberships',
                'pressMedia',
                'publications',
                'scientificResearch',
                'vita',
            ],
            array_keys($settings->documentSections),
        );
        $contracts = $settings->getDocumentSection('contracts');
        $this->assertNotNull($contracts);
        $this->assertTrue($contracts->readOnly);
        $this->assertTrue($contracts->isContractSection());
        $this->assertSame('contracts', $contracts->type);
        $this->assertSame('contracts', $contracts->fieldName);
        $this->assertSame(['from', 'position'], $contracts->rowFields);
        $this->assertSame(['view'], $contracts->actions);
        $this->assertSame(['view'], $contracts->getAllowedActions());
        $this->assertFalse($contracts->allowsCreate());
        $this->assertFalse($contracts->allowsDragSorting());
        $cooperation = $settings->getDocumentSection('cooperation');
        $this->assertNotNull($cooperation);
        $this->assertSame(['from', 'to', 'title'], $cooperation->rowFields);
        $this->assertSame(['view', 'down', 'up', 'delete', 'edit'], $cooperation->actions);
        $this->assertTrue($cooperation->allowsCreate());
        $this->assertTrue($cooperation->allowsDragSorting());
        $this->assertSame(
            ['title', 'yearStart', 'yearEnd', 'year', 'bodytext'],
            array_keys($cooperation->validationSet->validations),
        );
        $this->assertSame('year_start', $cooperation->validationSet->get('yearStart')?->fieldName);
        $this->assertSame('date', $cooperation->validationSet->get('yearStart')?->inputType);
        $this->assertSame('textarea', $cooperation->validationSet->get('bodytext')?->inputType);
        $this->assertSame(['html'], $cooperation->validationSet->get('bodytext')?->flags);
        $this->assertTrue($cooperation->validationSet->get('bodytext')?->isRichText());
        $this->assertTrue($cooperation->validationSet->get('title')?->required);
    }

    #[Test]
    public function unsupportedOrDuplicateDocumentPresentationValuesAreIgnored(): void
    {
        $settings = $this->normalize([
            'documentSections' => [
                'publications' => [
                    'label' => 'Publications',
                    'type' => 'publication',
                    'fieldName' => 'publications',
                    'rowFields' => ['year', 'unsupported', 'year', 123, ' title ', 'position'],
                    'actions' => ['view', 'unsupported', 'VIEW', null, 'edit'],
                ],
            ],
        ]);
        $section = $settings->getDocumentSection('publications');
        $this->assertNotNull($section);
        $this->assertSame(['year', 'title'], $section->rowFields);
        $this->assertSame(['view', 'edit'], $section->actions);
    }

    #[Test]
    public function shippedUrlFlagProducesFrontendAndServerMetadata(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $website = $settings->getProfileField('website')?->validation;
        $this->assertNotNull($website);
        $this->assertSame('url', $website->inputType);
        $this->assertSame([UrlValidator::class], $website->validatorClassNames);
    }

    #[Test]
    public function documentValidatorsRemainLocalToTheirConfiguredSection(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $cooperation = $settings->getDocumentValidationSet('cooperation');
        $lectures = $settings->getDocumentValidationSet('lectures');
        $this->assertNull($cooperation->get('link'));
        $this->assertSame([UrlValidator::class], $lectures->get('link')?->validatorClassNames);
        $this->assertSame('cooperation', $cooperation->identifier);
        $this->assertSame('lectures', $lectures->identifier);
    }

    #[Test]
    public function disabledOrReadOnlyProfileFieldsCanNeverRemainRequired(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'locked' => [
                    'section' => 'information',
                    'fieldType' => 'input',
                    'renderType' => 'text',
                    'validators' => ['required', 'disabled'],
                ],
            ],
        ]);
        $validation = $settings->getProfileField('locked')?->validation;
        $this->assertNotNull($validation);
        $this->assertTrue($validation->disabled);
        $this->assertTrue($validation->readOnly);
        $this->assertFalse($validation->required);
        $this->assertSame([], $validation->validatorClassNames);
    }

    #[Test]
    public function invalidEntriesAndNonStringValidatorFlagsAreIgnored(): void
    {
        $settings = $this->normalize([
            'profile' => [
                'valid' => [
                    'section' => 'information',
                    'fieldType' => 'input',
                    'renderType' => 'text',
                    'validators' => ['required', '', ['invalid']],
                ],
                'missingSection' => ['fieldType' => 'input', 'renderType' => 'text'],
            ],
            'documentSections' => [
                'missingType' => ['label' => 'Missing type', 'fieldName' => 'missing_type'],
            ],
        ]);
        $this->assertSame(['information'], array_keys($settings->profileSections));
        $this->assertSame(['required'], $settings->getProfileField('valid')?->validation->flags);
        $this->assertSame([], $settings->documentSections);
    }

    /**
     * @return array<string, mixed>
     */
    private function getShippedConfiguration(): array
    {
        $configuration = Yaml::parseFile(__DIR__ . '/../../../Configuration/AcademicPersons/Settings.yaml');
        $this->assertIsArray($configuration);
        return $configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function normalize(array $configuration): AcademicPersonsSettings
    {
        $factory = (new ReflectionClass(AcademicPersonsSettingsFactory::class))->newInstanceWithoutConstructor();
        $method = new ReflectionMethod(AcademicPersonsSettingsFactory::class, 'normalize');
        $method->setAccessible(true);
        $settings = $method->invoke($factory, $configuration);
        $this->assertInstanceOf(AcademicPersonsSettings::class, $settings);
        return $settings;
    }
}
