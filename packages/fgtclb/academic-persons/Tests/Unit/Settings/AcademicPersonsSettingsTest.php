<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ContractContactField;
use FGTCLB\AcademicPersons\Settings\ContractContactSection;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
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
        $field = $this->profileField('emailAddress', 'email', 'email');
        $section = new ProfileSection(
            identifier: 'information',
            fields: ['emailAddress' => $field],
            validationSet: new ValidationSet(identifier: 'information', validations: ['email' => $field->validation]),
            position: 0,
        );
        $subject = new AcademicPersonsSettings(profileSections: ['information' => $section]);
        self::assertSame($section, $subject->getProfileSection('information'));
        self::assertSame($field, $subject->getProfileField('emailAddress'));
        self::assertSame($field, $subject->getProfileField('email'));
        self::assertNull($subject->getProfileField('unknown'));
    }

    #[Test]
    public function unknownProfileSectionReturnsAnEmptySectionSpecificObject(): void
    {
        $subject = new AcademicPersonsSettings();
        $section = $subject->getProfileSectionOrEmpty('aboutme');
        self::assertSame('aboutme', $section->identifier);
        self::assertSame([], $section->fields);
        self::assertSame('aboutme', $section->validationSet->identifier);
        self::assertSame([], $section->validationSet->validations);
    }

    #[Test]
    public function profileValidationCanBeReadPerSectionOrAggregated(): void
    {
        $informationField = $this->profileField('gender', 'gender', 'gender');
        $aboutField = $this->profileField('miscellaneous', 'miscellaneous', 'miscellaneous', 'aboutme');
        $information = $this->profileSection('information', [$informationField]);
        $about = $this->profileSection('aboutme', [$aboutField]);
        $subject = new AcademicPersonsSettings(profileSections: ['information' => $information, 'aboutme' => $about]);
        self::assertSame($information->validationSet, $subject->getProfileValidationSet('information'));
        self::assertSame(['gender', 'miscellaneous'], array_keys($subject->getProfileValidationSet()->validations));
        self::assertSame([], $subject->getProfileValidationSet('unknown')->validations);
    }

    #[Test]
    public function profileValidationSubsetMapsConfiguredIdentifiersToDtoProperties(): void
    {
        $email = $this->profileField('emailAddress', 'email', 'email');
        $phone = $this->profileField('phoneNumber', 'phoneNumber', 'phone_number');
        $subject = new AcademicPersonsSettings(
            profileSections: ['information' => $this->profileSection('information', [$email, $phone])],
        );
        $subset = $subject->getProfileValidationSetForFields(['emailAddress'], 'information');
        self::assertSame('information', $subset->identifier);
        self::assertSame(['email'], array_keys($subset->validations));
        self::assertSame($email->validation, $subset->get('email'));
        self::assertSame([], $subject->getProfileValidationSetForFields(['emailAddress'], 'aboutme')->validations);
    }

    #[Test]
    public function contractContactsNeverFallBackToProfileFieldsOrAnotherContactSection(): void
    {
        $profileEmail = $this->profileField('emailAddress', 'emailAddress', 'email_address');
        $contractEmail = $this->contractContactField(
            'emailAddress',
            'emailAddresses',
            'email',
            'email',
        );
        $subject = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->profileSection('information', [$profileEmail]),
            ],
            contractContactSections: [
                'emailAddresses' => $this->contractContactSection('emailAddresses', [$contractEmail]),
            ],
        );

        self::assertSame($profileEmail, $subject->getProfileField('emailAddress'));
        self::assertSame($contractEmail, $subject->getContractContactField('emailAddress'));
        self::assertSame(['email'], array_keys(
            $subject->getContractContactValidationSet('emailAddresses')->validations,
        ));
        self::assertSame([], $subject->getContractContactValidationSet('phoneNumbers')->validations);
        self::assertSame(
            [],
            $subject->getContractContactValidationSetForFields(
                ['emailAddress'],
                'phoneNumbers',
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

        self::assertSame(
            ['gender', 'skipSync'],
            array_keys($subject->getProfileUpdateValidationSet()->validations),
        );
    }

    #[Test]
    public function documentSectionsAreResolvedByIdentifierAndRecordType(): void
    {
        $section = $this->documentSection('publications', 'publication');
        $subject = new AcademicPersonsSettings(documentSections: ['publications' => $section]);
        self::assertSame($section, $subject->getDocumentSection('publications'));
        self::assertSame($section, $subject->getDocumentSectionByType('publication'));
        self::assertNull($subject->getDocumentSection('unknown'));
        self::assertNull($subject->getDocumentSectionByType('unknown'));
    }

    #[Test]
    public function documentValidationNeverFallsBackToAnotherSection(): void
    {
        $publication = $this->documentSection('publications', 'publication');
        $subject = new AcademicPersonsSettings(documentSections: ['publications' => $publication]);
        self::assertSame($publication->validationSet, $subject->getDocumentValidationSet('publications'));
        self::assertSame($publication->validationSet, $subject->getDocumentValidationSetByType('publication'));
        self::assertSame([], $subject->getDocumentValidationSet('lectures')->validations);
        self::assertSame([], $subject->getDocumentValidationSetByType('lecture')->validations);
    }

    #[Test]
    public function profileTcaConfigurationUsesDatabaseFieldNamesAndSkipsEmptyConfiguration(): void
    {
        $firstName = $this->profileField('firstName', 'firstName', 'first_name', 'information', ['required' => true]);
        $middleName = $this->profileField('middleName', 'middleName', 'middle_name');
        $subject = new AcademicPersonsSettings(
            profileSections: ['information' => $this->profileSection('information', [$firstName, $middleName])],
        );
        self::assertSame(
            ['columns' => ['first_name' => ['config' => ['required' => true]]]],
            $subject->getProfileValidationTcaTableConfig(),
        );
        self::assertSame(
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
        self::assertSame(
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
            raw: ['profile' => ['miscellaneous' => ['section' => 'aboutme']]],
        );
        $restored = eval('return ' . var_export($subject, true) . ';');
        self::assertInstanceOf(AcademicPersonsSettings::class, $restored);
        self::assertEquals($subject, $restored);
        self::assertNotSame($subject, $restored);
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
