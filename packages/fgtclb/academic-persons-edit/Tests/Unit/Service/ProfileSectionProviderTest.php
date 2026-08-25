<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Service;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\SpecialField;
use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use FGTCLB\AcademicPersonsEdit\Service\ProfileSectionProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ProfileSectionProviderTest extends UnitTestCase
{
    #[Test]
    public function configuredOrderAndSpecialTitleGroupBecomeTheFluidViewModel(): void
    {
        $gender = $this->field('gender', 'information', 'select', 'select', 0);
        $title = $this->field('title', 'information', 'input', 'text', 1);
        $firstName = $this->field('firstName', 'information', 'input', 'text', 2, true);
        $lastName = $this->field('lastName', 'information', 'input', 'text', 3, true);
        $website = $this->field('website', 'information', 'input', 'url', 4);
        $about = $this->field('miscellaneous', 'aboutme', 'textarea', 'ckeditor', 0);
        $settings = new AcademicPersonsSettings(
            profileSections: [
                'information' => $this->section(
                    'information',
                    [$gender, $title, $firstName, $lastName, $website],
                    0,
                ),
                'aboutme' => $this->section('aboutme', [$about], 1),
            ],
            specialFields: [
                'title' => new SpecialField(
                    identifier: 'title',
                    type: 'special',
                    fieldType: '',
                    renderType: 'title',
                    fieldIdentifiers: ['title', 'firstName', 'lastName'],
                    validation: $this->validation('title', 'title'),
                    position: 0,
                ),
                'image' => new SpecialField(
                    identifier: 'image',
                    type: 'special',
                    fieldType: '',
                    renderType: 'image',
                    fieldIdentifiers: [],
                    validation: $this->validation('image', 'image'),
                    position: 1,
                ),
            ],
        );

        $subject = new ProfileSectionProvider($settings);
        $sections = $subject->getSections();

        $this->assertSame(['information', 'aboutme'], array_keys($sections));
        $this->assertSame(
            ['field', 'special', 'field'],
            array_column($sections['information']['items'], 'kind'),
        );
        $this->assertSame(
            'gender',
            $sections['information']['items'][0]['field']['identifier'],
        );
        $this->assertSame(
            'title firstName lastName',
            $sections['information']['items'][1]['special']['fieldIdentifiers'],
        );
        $this->assertSame(
            'website',
            $sections['information']['items'][2]['field']['identifier'],
        );
        $this->assertSame(
            'ckeditor',
            $sections['aboutme']['items'][0]['field']['renderType'],
        );
        $this->assertTrue(
            $sections['information']['items'][1]['special']['fields'][1]['validation']->readOnly,
        );
        $this->assertSame(
            'col-12 col-sm-4',
            $sections['information']['items'][1]['special']['fields'][0]['columnClass'],
        );
        $this->assertSame(
            'col-12 col-sm-8',
            $sections['information']['items'][1]['special']['fields'][1]['columnClass'],
        );
        $this->assertSame(
            'honorific-prefix',
            $sections['information']['items'][1]['special']['fields'][0]['autocomplete'],
        );
        $this->assertSame(
            'url',
            $sections['information']['items'][2]['field']['autocomplete'],
        );
        $this->assertTrue($subject->getSpecialFields()['image']['writable']);
        $this->assertSame(['title', 'image'], array_keys($subject->getSpecialFields()));
    }

    #[Test]
    public function readOnlySpecialComponentIsNotWritable(): void
    {
        $settings = new AcademicPersonsSettings(
            specialFields: [
                'image' => new SpecialField(
                    identifier: 'image',
                    type: 'special',
                    fieldType: '',
                    renderType: 'image',
                    fieldIdentifiers: [],
                    validation: $this->validation('image', 'image', true),
                    position: 0,
                ),
            ],
        );

        $this->assertFalse(
            (new ProfileSectionProvider($settings))->getSpecialFields()['image']['writable'],
        );
    }

    private function field(
        string $identifier,
        string $section,
        string $fieldType,
        string $renderType,
        int $position,
        bool $readOnly = false,
    ): ProfileField {
        return new ProfileField(
            identifier: $identifier,
            section: $section,
            propertyName: $identifier,
            fieldName: strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $identifier)),
            fieldType: $fieldType,
            renderType: $renderType,
            validation: $this->validation(
                $identifier,
                strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $identifier)),
                $readOnly,
            ),
            position: $position,
        );
    }

    /**
     * @param list<ProfileField> $fields
     */
    private function section(string $identifier, array $fields, int $position): ProfileSection
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
            position: $position,
        );
    }

    private function validation(
        string $identifier,
        string $fieldName,
        bool $readOnly = false,
    ): Validation {
        return new Validation(
            identifier: $identifier,
            fieldName: $fieldName,
            required: false,
            disabled: false,
            readOnly: $readOnly,
            validatorClassNames: [],
            tcaConfig: [],
            inputType: 'text',
        );
    }
}
