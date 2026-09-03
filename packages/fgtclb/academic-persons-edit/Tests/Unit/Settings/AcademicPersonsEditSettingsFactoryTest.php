<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use FGTCLB\AcademicPersonsEdit\Settings\AcademicPersonsEditSettingsFactory;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AcademicPersonsEditSettingsFactoryTest extends UnitTestCase
{
    #[Test]
    public function shippedConfigurationContainsTheUnifiedSettingsMaps(): void
    {
        $this->assertSame(
            ['profile', 'special', 'contracts', 'documentSections'],
            array_keys($this->getShippedConfiguration()),
        );
    }

    #[Test]
    public function unifiedGraphKeepsConfiguredSectionsAndOrder(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame(['information', 'aboutme'], array_keys($settings->profileSections));
        $this->assertSame(['title', 'image', 'skipSync'], array_keys($settings->specialFields));
        $this->assertSame(
            [
                'position',
                'organisationalUnit',
                'functionType',
                'validFrom',
                'validTo',
                'location',
                'room',
                'officeHours',
                'publish',
            ],
            array_keys($settings->contractFields),
        );
        $this->assertSame(
            ['physicalAddresses', 'emailAddresses', 'phoneNumbers'],
            array_keys($settings->contractContactSections),
        );
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
        $this->assertNotEmpty($settings->publicProfile->structure);
        $this->assertNotEmpty($settings->publicProfile->details);
    }

    #[Test]
    public function contractEditorFieldsAreNormalizedFromTheirStructuralConfiguration(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame('text', $settings->getContractField('position')?->renderType);
        $this->assertTrue($settings->getContractField('position')->validation->required);
        $this->assertSame(
            'organisationalUnits',
            $settings->getContractField('organisationalUnit')?->optionSource,
        );
        $this->assertSame('functionTypes', $settings->getContractField('functionType')?->optionSource);
        $this->assertSame('date', $settings->getContractField('validFrom')?->validation->inputType);
        $this->assertSame('date', $settings->getContractField('validTo')?->validation->inputType);
        $this->assertSame('locations', $settings->getContractField('location')?->optionSource);
        $this->assertTrue($settings->getContractField('officeHours')?->validation->isRichText());
        $this->assertSame('checkbox', $settings->getContractField('publish')?->validation->inputType);
        $this->assertSame(
            $settings->getContractField('publish')->validation,
            $settings->getDocumentValidationSet('contracts')->get('publish'),
        );
    }

    #[Test]
    public function imageCropperRatioIsPreservedWithoutDependingOnTheShippedValue(): void
    {
        $settings = $this->normalize([
            'special' => [
                'image' => [
                    'type' => 'special',
                    'renderType' => 'cropper',
                    'settings' => ['ratio' => '9x16'],
                ],
            ],
        ]);
        $this->assertSame('9x16', $settings->raw['special']['image']['settings']['ratio'] ?? null);
    }

    #[Test]
    public function profileAndContactValidatorsRemainSectionLocal(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $gender = $settings->getProfileField('gender')?->validation;
        $this->assertNotNull($gender);
        $this->assertTrue($gender->required);
        $this->assertSame([NotEmptyValidator::class], $gender->validatorClassNames);
        $firstName = $settings->getProfileField('firstName')?->validation;
        $this->assertNotNull($firstName);
        $this->assertTrue($firstName->disabled);
        $this->assertTrue($firstName->readOnly);
        $this->assertFalse($firstName->required);
        $website = $settings->getProfileField('website')?->validation;
        $this->assertNotNull($website);
        $this->assertSame('url', $website->inputType);
        $this->assertSame([UrlValidator::class], $website->validatorClassNames);
        $country = $settings->getContractContactField('country');
        $this->assertNotNull($country);
        $this->assertSame('input', $country->fieldType);
        $this->assertSame('select', $country->renderType);
        $this->assertSame('select', $country->validation->inputType);
        $this->assertSame('country', $country->autocomplete);
        $this->assertSame(
            'street-address',
            $settings->getContractContactField('street')?->autocomplete,
        );
        $this->assertSame(
            'postal-code',
            $settings->getContractContactField('zip')?->autocomplete,
        );
        $this->assertSame(
            'address-level2',
            $settings->getContractContactField('city')?->autocomplete,
        );
        $this->assertSame(
            'email',
            $settings->getContractContactField('emailAddress')?->autocomplete,
        );
        $this->assertSame(
            'tel',
            $settings->getContractContactField('phoneNumber')?->autocomplete,
        );
        $email = $settings->getContractContactField('emailAddress')->validation;
        $this->assertNotNull($email);
        $this->assertSame(
            [NotEmptyValidator::class, EmailAddressValidator::class],
            $email->validatorClassNames,
        );
        $this->assertNull($settings->getContractContactValidationSet('phoneNumbers')->get('email'));
        $this->assertNotNull($settings->getContractContactValidationSet('emailAddresses')->get('email'));
    }

    #[Test]
    public function documentDateRequirementsComeOnlyFromTheirConfiguredFrontendFlags(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $cooperation = $settings->getDocumentValidationSet('cooperation');
        $year = $cooperation->get('year');
        $from = $cooperation->get('yearStart');
        $to = $cooperation->get('yearEnd');
        $this->assertNotNull($year);
        $this->assertNotNull($from);
        $this->assertNotNull($to);
        $this->assertSame('date', $year->inputType);
        $this->assertSame('date', $from->inputType);
        $this->assertSame('date', $to->inputType);
        $this->assertTrue($year->required);
        $this->assertFalse($from->required);
        $this->assertFalse($to->required);
        $this->assertSame([NotEmptyValidator::class], $year->validatorClassNames);
        $this->assertSame([], $from->validatorClassNames);
        $this->assertSame([], $to->validatorClassNames);
    }

    #[Test]
    public function contractDocumentSectionResolvesItsReferencedTypeConfiguration(): void
    {
        $configuration = $this->getShippedConfiguration();
        $this->assertSame(
            ['type' => 'contracts'],
            $configuration['documentSections']['contracts'],
        );
        $settings = $this->normalize($configuration);
        $contracts = $settings->getDocumentSection('contracts');
        $this->assertNotNull($contracts);
        $this->assertSame('contracts', $contracts->type);
        $this->assertSame('contracts', $contracts->fieldName);
        $this->assertSame(['position'], $contracts->rowFields);
        $this->assertSame(['view', 'down', 'up', 'delete', 'edit'], $contracts->actions);
        $this->assertNotNull($contracts->validationSet->get('validFrom'));
        $this->assertNotNull($contracts->validationSet->get('publish'));
    }

    #[Test]
    public function contractDateAliasesResolveToContractDtoAndTcaProperties(): void
    {
        $settings = $this->normalize([
            'documentSections' => [
                'contracts' => [
                    'label' => 'Contracts',
                    'type' => 'contracts',
                    'fieldName' => 'contracts',
                    'validators' => [
                        'from' => ['required', 'date'],
                        'to' => ['date'],
                        'position' => ['required'],
                    ],
                ],
            ],
        ]);
        $validationSet = $settings->getDocumentValidationSet('contracts');
        $validFromValidation = $validationSet->get('validFrom');
        $validToValidation = $validationSet->get('validTo');
        $positionValidation = $validationSet->get('position');
        $this->assertNotNull($validFromValidation);
        $this->assertNotNull($validToValidation);
        $this->assertNotNull($positionValidation);
        $this->assertTrue($validFromValidation->required);
        $this->assertSame('valid_from', $validFromValidation->fieldName);
        $this->assertSame('valid_to', $validToValidation->fieldName);
        $this->assertTrue($positionValidation->required);
        $this->assertSame(
            ['columns' => [
                'valid_from' => ['config' => ['readOnly' => false, 'required' => true]],
                'valid_to' => ['config' => ['readOnly' => false, 'required' => false]],
                'position' => ['config' => ['readOnly' => false, 'required' => true]],
            ]],
            $settings->getDocumentValidationTcaTableConfig('contracts'),
        );
    }

    #[Test]
    public function configuredDescriptionEditorProducesRichTextValidationMetadata(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $description = $settings->getDocumentValidationSet('publications')->get('bodytext');
        $this->assertNotNull($description);
        $this->assertSame(['html'], $description->flags);
        $this->assertSame('textarea', $description->inputType);
        $this->assertTrue($description->isRichText());
        $this->assertSame(500, $description->characterLimit);
        $this->assertArrayNotHasKey('max', $description->tcaConfig);
    }

    #[Test]
    public function configuredProfileEditorProducesCharacterLimitMetadata(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $miscellaneous = $settings->getProfileField('miscellaneous')?->validation;
        $this->assertNotNull($miscellaneous);
        $this->assertTrue($miscellaneous->isRichText());
        $this->assertSame(1000, $miscellaneous->characterLimit);
        $this->assertArrayNotHasKey('max', $miscellaneous->tcaConfig);
        $this->assertSame(0, $settings->getProfileField('teachingArea')?->validation->characterLimit);
    }

    #[Test]
    public function documentAliasesAndValidatorsNeverLeakBetweenSections(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $cooperation = $settings->getDocumentValidationSet('cooperation');
        $lectures = $settings->getDocumentValidationSet('lectures');
        $this->assertSame('year_start', $cooperation->get('yearStart')?->fieldName);
        $this->assertSame('year_end', $cooperation->get('yearEnd')?->fieldName);
        $this->assertSame('bodytext', $cooperation->get('bodytext')?->fieldName);
        $this->assertNull($cooperation->get('link'));
        $this->assertSame([UrlValidator::class], $lectures->get('link')?->validatorClassNames);
    }

    #[Test]
    public function disabledFieldsCannotRemainRequired(): void
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
    public function unifiedSettingsSurviveThePhpCacheRoundTrip(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $restored = eval('return ' . var_export($settings, true) . ';');
        $this->assertInstanceOf(AcademicPersonsSettings::class, $restored);
        $this->assertEquals($settings, $restored);
        $this->assertSame(
            ['view', 'down', 'up', 'delete', 'edit'],
            $restored->getDocumentSection('publications')?->actions,
        );
    }

    #[Test]
    public function formerFactoryDelegatesToTheCentralFactory(): void
    {
        $settings = new AcademicPersonsSettings();
        $centralFactory = $this->createMock(AcademicPersonsSettingsFactory::class);
        $centralFactory->expects($this->once())->method('get')->willReturn($settings);
        $compatibilityFactory = new AcademicPersonsEditSettingsFactory($centralFactory);
        $this->assertSame($settings, $compatibilityFactory->get());
    }

    /**
     * @return array<string, mixed>
     */
    private function getShippedConfiguration(): array
    {
        $configuration = Yaml::parseFile(
            __DIR__ . '/../../../../academic-persons/Configuration/AcademicPersons/Settings.yaml',
        );
        $this->assertIsArray($configuration);
        return $configuration;
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function normalize(array $configuration): AcademicPersonsSettings
    {
        $factory = (new \ReflectionClass(AcademicPersonsSettingsFactory::class))->newInstanceWithoutConstructor();
        $settings = $factory->normalizeEditConfiguration($configuration);
        $this->assertInstanceOf(AcademicPersonsSettings::class, $settings);
        return $settings;
    }
}
