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
    public function shippedConfigurationContainsOnlyEditorMaps(): void
    {
        $this->assertSame(
            ['profile', 'special', 'contractContact', 'documentSections'],
            array_keys($this->getShippedConfiguration()),
        );
    }

    #[Test]
    public function factoryUsesTheSeparatedPathAndCacheEntry(): void
    {
        $reflection = new \ReflectionClass(AcademicPersonsEditSettingsFactory::class);
        $this->assertSame(
            'Configuration/AcademicsPersonsEdit/Settings.yaml',
            $reflection->getConstant('SETTINGS_FILE'),
        );
        $factory = $reflection->newInstanceWithoutConstructor();
        $identifier = new \ReflectionMethod(AcademicPersonsEditSettingsFactory::class, 'settingsIdentifier');
        $this->assertSame('AcademicPersonsEdit_Settings_SectionSchema_v3', $identifier->invoke($factory));
    }

    #[Test]
    public function editorGraphKeepsConfiguredSectionsAndOrder(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $this->assertSame(['information', 'aboutme'], array_keys($settings->profileSections));
        $this->assertSame(['title', 'image', 'skipSync'], array_keys($settings->specialFields));
        $this->assertSame(
            ['phoneNumbers', 'emailAddresses', 'physicalAddresses'],
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
        $this->assertSame([], $settings->publicProfile->structure);
        $this->assertSame([], $settings->publicProfile->details);
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
        $email = $settings->getContractContactField('emailAddress')?->validation;
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
    public function configuredDescriptionEditorProducesRichTextValidationMetadata(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $description = $settings->getDocumentValidationSet('publications')->get('bodytext');
        $this->assertNotNull($description);
        $this->assertSame(['html'], $description->flags);
        $this->assertSame('textarea', $description->inputType);
        $this->assertTrue($description->isRichText());
        $this->assertSame(100, $description->characterLimit);
        $this->assertArrayNotHasKey('max', $description->tcaConfig);
    }

    #[Test]
    public function configuredProfileEditorProducesCharacterLimitMetadata(): void
    {
        $settings = $this->normalize($this->getShippedConfiguration());
        $miscellaneous = $settings->getProfileField('miscellaneous')?->validation;
        $this->assertNotNull($miscellaneous);
        $this->assertTrue($miscellaneous->isRichText());
        $this->assertSame(500, $miscellaneous->characterLimit);
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
    public function editorSettingsSurviveThePhpCacheRoundTrip(): void
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

    /**
     * @return array<string, mixed>
     */
    private function getShippedConfiguration(): array
    {
        $configuration = Yaml::parseFile(
            __DIR__ . '/../../../Configuration/AcademicsPersonsEdit/Settings.yaml',
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
