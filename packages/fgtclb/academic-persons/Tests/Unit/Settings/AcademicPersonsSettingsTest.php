<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ContractContactField;
use FGTCLB\AcademicPersons\Settings\ContractContactSection;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\PublicProfileSettings;
use FGTCLB\AcademicPersons\Settings\SpecialField;
use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AcademicPersonsSettingsTest extends UnitTestCase
{
    #[Test]
    public function profileSectionsAndFieldsAreResolvedByTheirIdentifiers(): void
    {
        $field = $this->profileField('profileWebsite', 'website', 'website');
        $section = new ProfileSection(
            identifier: 'information',
            fields: ['profileWebsite' => $field],
            validationSet: new ValidationSet(identifier: 'information', validations: ['website' => $field->validation]),
            position: 0,
        );
        $subject = new AcademicPersonsSettings(profileSections: ['information' => $section]);
        $this->assertSame($section, $subject->getProfileSection('information'));
        $this->assertSame($field, $subject->getProfileField('profileWebsite'));
        $this->assertSame($field, $subject->getProfileField('website'));
        $this->assertNull($subject->getProfileField('unknown'));
    }

    #[Test]
    public function unknownProfileSectionReturnsAnEmptySectionSpecificObject(): void
    {
        $subject = new AcademicPersonsSettings();
        $section = $subject->getProfileSectionOrEmpty('aboutme');
        $this->assertSame('aboutme', $section->identifier);
        $this->assertSame([], $section->fields);
        $this->assertSame('aboutme', $section->validationSet->identifier);
        $this->assertSame([], $section->validationSet->validations);
    }

    #[Test]
    public function publicProfileDefaultsToEmptySettingsAndIsExposedByGetter(): void
    {
        $defaultSettings = new AcademicPersonsSettings();
        $this->assertSame([], $defaultSettings->publicProfile->structure);
        $this->assertSame([], $defaultSettings->publicProfile->details);
        $this->assertSame($defaultSettings->publicProfile, $defaultSettings->getPublicProfile());
        $publicProfile = new PublicProfileSettings(
            structure: ['left' => ['menuSections'], 'right' => ['headline']],
            details: ['headline' => ['firstName', 'lastName']],
        );
        $subject = new AcademicPersonsSettings(publicProfile: $publicProfile);
        $this->assertSame($publicProfile, $subject->publicProfile);
        $this->assertSame(['menuSections'], $subject->publicProfile->getColumn('left'));
        $this->assertSame([], $subject->publicProfile->getColumn('unknown'));
    }

    #[Test]
    public function profileValidationCanBeReadPerSectionOrAggregated(): void
    {
        $informationField = $this->profileField('gender', 'gender', 'gender');
        $aboutField = $this->profileField('miscellaneous', 'miscellaneous', 'miscellaneous', 'aboutme');
        $information = $this->profileSection('information', [$informationField]);
        $about = $this->profileSection('aboutme', [$aboutField]);
        $subject = new AcademicPersonsSettings(profileSections: ['information' => $information, 'aboutme' => $about]);
        $this->assertSame($information->validationSet, $subject->getProfileValidationSet('information'));
        $this->assertSame(['gender', 'miscellaneous'], array_keys($subject->getProfileValidationSet()->validations));
        $this->assertSame([], $subject->getProfileValidationSet('unknown')->validations);
    }

    #[Test]
    public function profileValidationSubsetMapsConfiguredIdentifiersToDtoProperties(): void
    {
        $website = $this->profileField('website', 'website', 'website');
        $title = $this->profileField('title', 'title', 'title');
        $subject = new AcademicPersonsSettings(
            profileSections: ['information' => $this->profileSection('information', [$website, $title])],
        );
        $subset = $subject->getProfileValidationSetForFields(['website'], 'information');
        $this->assertSame('information', $subset->identifier);
        $this->assertSame(['website'], array_keys($subset->validations));
        $this->assertSame($website->validation, $subset->get('website'));
        $this->assertSame([], $subject->getProfileValidationSetForFields(['website'], 'aboutme')->validations);
    }

    #[Test]
    public function contractContactsNeverFallBackToProfileFieldsOrAnotherContactSection(): void
    {
        $profileStreet = $this->profileField('street', 'street', 'street');
        $contractStreet = $this->contractContactField(
            'street',
            'physicalAddresses',
            'street',
            'street',
        );
        $subject = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->profileSection('information', [$profileStreet]),
            ],
            contractContactSections: [
                'physicalAddresses' => $this->contractContactSection('physicalAddresses', [$contractStreet]),
            ],
        );
        $this->assertSame($profileStreet, $subject->getProfileField('street'));
        $this->assertSame($contractStreet, $subject->getContractContactField('street'));
        $this->assertSame(['street'], array_keys(
            $subject->getContractContactValidationSet('physicalAddresses')->validations,
        ));
        $this->assertSame([], $subject->getContractContactValidationSet('emailAddresses')->validations);
        $this->assertSame(
            [],
            $subject->getContractContactValidationSetForFields(
                ['street'],
                'emailAddresses',
            )->validations,
        );
    }

    #[Test]
    public function profileUpdateValidationAddsOnlyDirectSpecialProperties(): void
    {
        $profileField = $this->profileField('gender', 'gender', 'gender');
        $title = new SpecialField(
            identifier: 'title',
            type: 'special',
            fieldType: '',
            renderType: 'title',
            fieldIdentifiers: ['firstName', 'lastName'],
            validation: $this->validation('title', 'title', []),
            position: 0,
        );
        $skipSync = new SpecialField(
            identifier: 'skipSync',
            type: 'special',
            fieldType: 'check',
            renderType: 'checkbox',
            fieldIdentifiers: [],
            validation: $this->validation('skipSync', 'skip_sync', ['type' => 'check']),
            position: 1,
        );
        $subject = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->profileSection('information', [$profileField]),
            ],
            specialFields: ['title' => $title, 'skipSync' => $skipSync],
        );
        $this->assertSame(
            ['gender', 'skipSync'],
            array_keys($subject->getProfileUpdateValidationSet()->validations),
        );
    }

    #[Test]
    public function documentSectionsAreResolvedByIdentifierAndRecordType(): void
    {
        $section = $this->documentSection('publications', 'publication');
        $subject = new AcademicPersonsSettings(documentSections: ['publications' => $section]);
        $this->assertSame($section, $subject->getDocumentSection('publications'));
        $this->assertSame($section, $subject->getDocumentSectionByType('publication'));
        $this->assertNull($subject->getDocumentSection('unknown'));
        $this->assertNull($subject->getDocumentSectionByType('unknown'));
    }

    #[Test]
    public function documentValidationNeverFallsBackToAnotherSection(): void
    {
        $publication = $this->documentSection('publications', 'publication');
        $subject = new AcademicPersonsSettings(documentSections: ['publications' => $publication]);
        $this->assertSame($publication->validationSet, $subject->getDocumentValidationSet('publications'));
        $this->assertSame($publication->validationSet, $subject->getDocumentValidationSetByType('publication'));
        $this->assertSame([], $subject->getDocumentValidationSet('lectures')->validations);
        $this->assertSame([], $subject->getDocumentValidationSetByType('lecture')->validations);
    }

    #[Test]
    public function profileTcaConfigurationUsesDatabaseFieldNamesAndSkipsEmptyConfiguration(): void
    {
        $firstName = $this->profileField('firstName', 'firstName', 'first_name', 'information', ['required' => true]);
        $middleName = $this->profileField('middleName', 'middleName', 'middle_name');
        $subject = new AcademicPersonsSettings(
            profileSections: ['information' => $this->profileSection('information', [$firstName, $middleName])],
        );
        $this->assertSame(
            ['columns' => ['first_name' => ['config' => ['required' => true]]]],
            $subject->getProfileValidationTcaTableConfig(),
        );
        $this->assertSame(
            [],
            $subject->getProfileValidationTcaTableConfig(['firstName'], 'aboutme'),
        );
    }

    #[Test]
    public function documentTcaConfigurationStaysAttachedToItsRecordType(): void
    {
        $publication = $this->documentSection('publications', 'publication', ['required' => true]);
        $lecture = $this->documentSection('lectures', 'lecture', ['readOnly' => true]);
        $contracts = $this->documentSection('contracts', 'contracts', ['required' => true]);
        $subject = new AcademicPersonsSettings(
            documentSections: [
                'contracts' => $contracts,
                'publications' => $publication,
                'lectures' => $lecture,
            ],
        );
        $this->assertSame(
            [
                'types' => [
                    'publication' => ['columnsOverrides' => ['title' => ['config' => ['required' => true]]]],
                    'lecture' => ['columnsOverrides' => ['title' => ['config' => ['readOnly' => true]]]],
                ],
            ],
            $subject->getDocumentValidationTcaTypesConfig(),
        );
    }

    #[Test]
    public function completeSectionGraphSurvivesTheSettingsCacheRoundTrip(): void
    {
        $field = $this->profileField('miscellaneous', 'miscellaneous', 'miscellaneous', 'aboutme');
        $subject = new AcademicPersonsSettings(
            profileSections: ['aboutme' => $this->profileSection('aboutme', [$field])],
            specialFields: [
                'image' => new SpecialField(
                    identifier: 'image',
                    type: 'special',
                    fieldType: '',
                    renderType: 'image',
                    fieldIdentifiers: [],
                    validation: $this->validation('image', 'image', []),
                    position: 0,
                ),
            ],
            contractContactSections: [
                'emailAddresses' => $this->contractContactSection(
                    'emailAddresses',
                    [$this->contractContactField('emailAddress', 'emailAddresses', 'email', 'email')],
                ),
            ],
            documentSections: ['publications' => $this->documentSection('publications', 'publication')],
            publicProfile: new PublicProfileSettings(
                structure: ['left' => ['menuSections'], 'right' => ['headline', 'profileEntries']],
                details: [
                    'headline' => ['title', 'firstName', 'lastName'],
                    'subline' => 'LLL:EXT:academic_persons/Resources/Private/Language/locallang.xlf:detail.subline',
                ],
            ),
            raw: ['profile' => ['miscellaneous' => ['section' => 'aboutme']]],
        );
        $restored = eval('return ' . var_export($subject, true) . ';');
        $this->assertInstanceOf(AcademicPersonsSettings::class, $restored);
        $this->assertEquals($subject, $restored);
        $this->assertNotSame($subject, $restored);
        $this->assertSame(['headline', 'profileEntries'], $restored->publicProfile->getColumn('right'));
        $this->assertSame(['title', 'firstName', 'lastName'], $restored->publicProfile->details['headline']);
    }

    private function contractContactField(
        string $identifier,
        string $section,
        string $propertyName,
        string $fieldName,
    ): ContractContactField {
        return new ContractContactField(
            identifier: $identifier,
            section: $section,
            propertyName: $propertyName,
            fieldName: $fieldName,
            fieldType: 'input',
            renderType: 'text',
            validation: $this->validation($propertyName, $fieldName, []),
            position: 0,
        );
    }

    /**
     * @param list<ContractContactField> $fields
     */
    private function contractContactSection(string $identifier, array $fields): ContractContactSection
    {
        $indexedFields = [];
        $validations = [];
        foreach ($fields as $field) {
            $indexedFields[$field->identifier] = $field;
            $validations[$field->propertyName] = $field->validation;
        }
        return new ContractContactSection(
            identifier: $identifier,
            fields: $indexedFields,
            validationSet: new ValidationSet(identifier: $identifier, validations: $validations),
            position: 0,
        );
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function profileField(
        string $identifier,
        string $propertyName,
        string $fieldName,
        string $section = 'information',
        array $tcaConfig = [],
    ): ProfileField {
        $validation = $this->validation($propertyName, $fieldName, $tcaConfig);
        return new ProfileField(
            identifier: $identifier,
            section: $section,
            propertyName: $propertyName,
            fieldName: $fieldName,
            fieldType: 'input',
            renderType: 'text',
            validation: $validation,
            position: 0,
        );
    }

    /**
     * @param list<ProfileField> $fields
     */
    private function profileSection(string $identifier, array $fields): ProfileSection
    {
        $indexedFields = [];
        $validations = [];
        foreach ($fields as $field) {
            $indexedFields[$field->identifier] = $field;
            $validations[$field->propertyName] = $field->validation;
        }
        return new ProfileSection(
            identifier: $identifier,
            fields: $indexedFields,
            validationSet: new ValidationSet(identifier: $identifier, validations: $validations),
            position: 0,
        );
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function documentSection(string $identifier, string $type, array $tcaConfig = []): DocumentSection
    {
        $validation = $this->validation('title', 'title', $tcaConfig);
        return new DocumentSection(
            identifier: $identifier,
            label: ucfirst($identifier),
            type: $type,
            fieldName: $identifier,
            readOnly: false,
            validationSet: new ValidationSet(identifier: $identifier, validations: ['title' => $validation]),
            position: 0,
        );
    }

    /**
     * @param array<string, mixed> $tcaConfig
     */
    private function validation(string $identifier, string $fieldName, array $tcaConfig): Validation
    {
        return new Validation(
            identifier: $identifier,
            fieldName: $fieldName,
            required: false,
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: $tcaConfig,
        );
    }
}
