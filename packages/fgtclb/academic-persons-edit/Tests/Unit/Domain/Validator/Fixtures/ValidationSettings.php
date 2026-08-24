<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Tests\Unit\Domain\Validator\Fixtures;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ContractContactField;
use FGTCLB\AcademicPersons\Settings\ContractContactSection;
use FGTCLB\AcademicPersons\Settings\DocumentSection;
use FGTCLB\AcademicPersons\Settings\ProfileField;
use FGTCLB\AcademicPersons\Settings\ProfileSection;
use FGTCLB\AcademicPersons\Settings\SpecialField;
use FGTCLB\AcademicPersons\Settings\Validation;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

final class ValidationSettings
{
    /**
     * @param array<string, array<int, class-string>> $propertyValidators
     * @param array<string, string> $fieldIdentifiers
     */
    public static function forContractContactSection(
        string $sectionIdentifier,
        array $propertyValidators,
        array $fieldIdentifiers = [],
    ): AcademicPersonsSettings {
        $fields = [];
        $validations = [];
        foreach ($propertyValidators as $property => $validatorClassNames) {
            $fieldIdentifier = $fieldIdentifiers[$property] ?? $property;
            $validation = self::validation($property, $validatorClassNames);
            $fields[$fieldIdentifier] = new ContractContactField(
                identifier: $fieldIdentifier,
                section: $sectionIdentifier,
                propertyName: $property,
                fieldName: $validation->fieldName,
                fieldType: 'input',
                renderType: 'text',
                validation: $validation,
                position: count($fields),
            );
            $validations[$property] = $validation;
        }
        return new AcademicPersonsSettings(
            contractContactSections: [
                $sectionIdentifier => new ContractContactSection(
                    identifier: $sectionIdentifier,
                    fields: $fields,
                    validationSet: new ValidationSet(
                        identifier: $sectionIdentifier,
                        validations: $validations,
                    ),
                    position: 0,
                ),
            ],
        );
    }

    /**
     * @param array<string, array<int, class-string>> $propertyValidators
     * @param array<string, string> $fieldIdentifiers
     */
    public static function forProfileSection(
        string $sectionIdentifier,
        array $propertyValidators,
        array $fieldIdentifiers = [],
    ): AcademicPersonsSettings {
        $fields = [];
        $validations = [];
        foreach ($propertyValidators as $property => $validatorClassNames) {
            $fieldIdentifier = $fieldIdentifiers[$property] ?? $property;
            $validation = self::validation($property, $validatorClassNames);
            $fields[$fieldIdentifier] = new ProfileField(
                identifier: $fieldIdentifier,
                section: $sectionIdentifier,
                propertyName: $property,
                fieldName: $validation->fieldName,
                fieldType: 'input',
                renderType: 'text',
                validation: $validation,
                position: count($fields),
            );
            $validations[$property] = $validation;
        }
        return new AcademicPersonsSettings(
            profileSections: [
                $sectionIdentifier => new ProfileSection(
                    identifier: $sectionIdentifier,
                    fields: $fields,
                    validationSet: new ValidationSet(identifier: $sectionIdentifier, validations: $validations),
                    position: 0,
                ),
            ],
            specialFields: [
                'skipSync' => new SpecialField(
                    identifier: 'skipSync',
                    type: 'special',
                    fieldType: 'check',
                    renderType: 'checkbox',
                    fieldIdentifiers: [],
                    validation: new Validation(
                        identifier: 'skipSync',
                        fieldName: 'skip_sync',
                        required: false,
                        disabled: false,
                        readOnly: false,
                        validatorClassNames: [],
                        tcaConfig: ['type' => 'check'],
                        inputType: 'checkbox',
                    ),
                    position: 0,
                ),
            ],
        );
    }

    /**
     * @param array<string, array<int, class-string>> $propertyValidators
     */
    public static function forDocumentSection(
        string $sectionIdentifier,
        string $type,
        array $propertyValidators,
        bool $readOnly = false,
    ): AcademicPersonsSettings {
        $validations = [];
        foreach ($propertyValidators as $property => $validatorClassNames) {
            $validations[$property] = self::validation($property, $validatorClassNames);
        }
        return new AcademicPersonsSettings(
            documentSections: [
                $sectionIdentifier => new DocumentSection(
                    identifier: $sectionIdentifier,
                    label: ucfirst($sectionIdentifier),
                    type: $type,
                    fieldName: $sectionIdentifier,
                    readOnly: $readOnly,
                    validationSet: new ValidationSet(identifier: $sectionIdentifier, validations: $validations),
                    position: 0,
                ),
            ],
        );
    }

    /**
     * @param array<string, string> $fieldRenderTypes
     * @param list<string> $readOnlyProperties
     */
    public static function forProfileFields(
        array $fieldRenderTypes,
        array $readOnlyProperties = [],
    ): AcademicPersonsSettings {
        $fields = [];
        $validations = [];
        foreach ($fieldRenderTypes as $property => $renderType) {
            $readOnly = in_array($property, $readOnlyProperties, true);
            $validation = new Validation(
                identifier: $property,
                fieldName: GeneralUtility::camelCaseToLowerCaseUnderscored($property),
                required: false,
                disabled: false,
                readOnly: $readOnly,
                validatorClassNames: [],
                tcaConfig: [],
            );
            $fields[$property] = new ProfileField(
                identifier: $property,
                section: 'information',
                propertyName: $property,
                fieldName: $validation->fieldName,
                fieldType: $renderType === 'ckeditor' ? 'textarea' : 'input',
                renderType: $renderType,
                validation: $validation,
                position: count($fields),
            );
            $validations[$property] = $validation;
        }
        return new AcademicPersonsSettings(
            profileSections: [
                'information' => new ProfileSection(
                    identifier: 'information',
                    fields: $fields,
                    validationSet: new ValidationSet(identifier: 'information', validations: $validations),
                    position: 0,
                ),
            ],
            specialFields: [
                'skipSync' => new SpecialField(
                    identifier: 'skipSync',
                    type: 'special',
                    fieldType: 'check',
                    renderType: 'checkbox',
                    fieldIdentifiers: [],
                    validation: new Validation(
                        identifier: 'skipSync',
                        fieldName: 'skip_sync',
                        required: false,
                        disabled: false,
                        readOnly: false,
                        validatorClassNames: [],
                        tcaConfig: ['type' => 'check'],
                        inputType: 'checkbox',
                    ),
                    position: 0,
                ),
            ],
        );
    }

    /**
     * @param array<int, class-string> $validatorClassNames
     */
    private static function validation(string $property, array $validatorClassNames): Validation
    {
        /** @var array<int, class-string<ValidatorInterface>> $validatorClassNames */
        return new Validation(
            identifier: $property,
            fieldName: GeneralUtility::camelCaseToLowerCaseUnderscored($property),
            required: true,
            disabled: false,
            readOnly: false,
            validatorClassNames: $validatorClassNames,
            tcaConfig: [],
        );
    }
}
