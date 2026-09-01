<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Tests\Unit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SectionSettingsTest extends UnitTestCase
{
    #[Test]
    public function profileValidationIsAggregatedWithoutLosingItsSection(): void
    {
        $validation = $this->validation('firstName', 'first_name', ['required']);
        $field = new ProfileField(
            identifier: 'firstName',
            section: 'information',
            propertyName: 'firstName',
            fieldName: 'first_name',
            fieldType: 'input',
            renderType: 'text',
            validation: $validation,
            position: 0,
        );
        $sectionSet = new ValidationSet(identifier: 'information', validations: ['firstName' => $validation]);
        $settings = new AcademicPersonsSettings(
            profileSections: [
                'information' => new ProfileSection(
                    identifier: 'information',
                    fields: ['firstName' => $field],
                    validationSet: $sectionSet,
                    position: 0,
                ),
            ],
            raw: [],
        );
        $this->assertSame($sectionSet, $settings->getProfileSection('information')?->validationSet);
        $this->assertSame($validation, $settings->getProfileValidationSet()->get('firstName'));
        $this->assertSame($field, $settings->getProfileField('firstName'));
    }

    #[Test]
    public function documentTcaValidationIsAttachedOnlyToItsRecordType(): void
    {
        $validation = $this->validation('yearStart', 'year_start', ['required', 'date']);
        $settings = new AcademicPersonsSettings(
            documentSections: [
                'cooperation' => new DocumentSection(
                    identifier: 'cooperation',
                    label: 'Cooperation',
                    type: 'cooperation',
                    fieldName: 'cooperation',
                    readOnly: false,
                    validationSet: new ValidationSet(
                        identifier: 'cooperation',
                        validations: ['yearStart' => $validation],
                    ),
                    position: 0,
                    rowFields: ['from', 'title'],
                    actions: ['view', 'down', 'up', 'edit'],
                ),
            ],
            raw: [],
        );
        $this->assertSame(
            [
                'types' => [
                    'cooperation' => [
                        'columnsOverrides' => [
                            'year_start' => ['config' => ['required' => true]],
                        ],
                    ],
                ],
            ],
            $settings->getDocumentValidationTcaTypesConfig(),
        );
        $this->assertSame('cooperation', $settings->getDocumentSectionByType('cooperation')?->identifier);
        $this->assertSame(['from', 'title'], $settings->getDocumentSection('cooperation')?->rowFields);
        $this->assertTrue($settings->getDocumentSection('cooperation')->allowsAction('edit'));
        $this->assertTrue($settings->getDocumentSection('cooperation')->allowsDragSorting());
    }

    #[Test]
    public function readonlyDocumentSectionKeepsOnlyItsViewCapability(): void
    {
        $section = new DocumentSection(
            identifier: 'contracts',
            label: 'Contracts',
            type: 'contracts',
            fieldName: 'contracts',
            readOnly: true,
            validationSet: new ValidationSet(identifier: 'contracts', validations: []),
            position: 0,
            rowFields: ['from', 'position'],
            actions: ['view', 'down', 'up', 'delete', 'edit'],
        );
        $this->assertSame(['view'], $section->getAllowedActions());
        $this->assertFalse($section->allowsCreate());
        $this->assertFalse($section->allowsDragSorting());
        $this->assertFalse($section->allowsAction('edit'));
    }

    #[Test]
    public function completeSectionGraphSurvivesTheCacheRoundTrip(): void
    {
        $validation = $this->validation('miscellaneous', 'miscellaneous', ['html']);
        $field = new ProfileField(
            identifier: 'miscellaneous',
            section: 'aboutme',
            propertyName: 'miscellaneous',
            fieldName: 'miscellaneous',
            fieldType: 'textarea',
            renderType: 'ckeditor',
            validation: $validation,
            position: 0,
        );
        $subject = new AcademicPersonsSettings(
            profileSections: [
                'aboutme' => new ProfileSection(
                    identifier: 'aboutme',
                    fields: ['miscellaneous' => $field],
                    validationSet: new ValidationSet(
                        identifier: 'aboutme',
                        validations: ['miscellaneous' => $validation],
                    ),
                    position: 0,
                ),
            ],
            documentSections: [
                'publications' => new DocumentSection(
                    identifier: 'publications',
                    label: 'Publications',
                    type: 'publication',
                    fieldName: 'publications',
                    readOnly: false,
                    validationSet: new ValidationSet(identifier: 'publications', validations: []),
                    position: 0,
                    rowFields: ['year', 'title'],
                    actions: ['view', 'down', 'up', 'delete', 'edit'],
                ),
            ],
            raw: ['profile' => ['miscellaneous' => ['section' => 'aboutme']]],
        );
        $restored = eval('return ' . var_export($subject, true) . ';');
        $this->assertInstanceOf(AcademicPersonsSettings::class, $restored);
        $this->assertEquals($subject, $restored);
        $this->assertSame('ckeditor', $restored->getProfileField('miscellaneous')?->renderType);
        $this->assertSame(['year', 'title'], $restored->getDocumentSection('publications')?->rowFields);
        $this->assertSame(
            ['view', 'down', 'up', 'delete', 'edit'],
            $restored->getDocumentSection('publications')->actions,
        );
    }

    /**
     * @param list<string> $flags
     */
    private function validation(string $identifier, string $fieldName, array $flags): Validation
    {
        return new Validation(
            identifier: $identifier,
            fieldName: $fieldName,
            required: in_array('required', $flags, true),
            disabled: false,
            readOnly: false,
            validatorClassNames: [],
            tcaConfig: in_array('required', $flags, true) ? ['required' => true] : [],
            flags: $flags,
        );
    }
}
