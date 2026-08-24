<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

/**
 * @internal not part of public API.
 * @todo Integrate basic interface(s) and traits into `EXT:academic_base` and adopt along with basic/shared factory.
 */
final class AcademicPersonsSettings
{
    /** @var array<string, ProfileSection> */
    public readonly array $profileSections;
    /** @var array<string, SpecialField> */
    public readonly array $specialFields;
    /** @var array<string, ContractContactSection> */
    public readonly array $contractContactSections;
    /** @var array<string, DocumentSection> */
    public readonly array $documentSections;
    /** @var array<string, mixed> */
    public readonly array $raw;

    /**
     * @param array<string, ProfileSection> $profileSections
     * @param array<string, SpecialField> $specialFields
     * @param array<string, ContractContactSection> $contractContactSections
     * @param array<string, DocumentSection> $documentSections
     * @param array<string, mixed> $raw
     */
    public function __construct(
        array $profileSections = [],
        array $specialFields = [],
        array $contractContactSections = [],
        array $documentSections = [],
        array $raw = [],
    ) {
        $this->profileSections = $profileSections;
        $this->specialFields = $specialFields;
        $this->contractContactSections = $contractContactSections;
        $this->documentSections = $documentSections;
        $this->raw = $raw;
    }

    /**
     * @param array{
     *     profileSections?: array<string, ProfileSection>,
     *     specialFields?: array<string, SpecialField>,
     *     contractContactSections?: array<string, ContractContactSection>,
     *     documentSections?: array<string, DocumentSection>,
     *     raw: array<string, mixed>,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            profileSections: $array['profileSections'] ?? [],
            specialFields: $array['specialFields'] ?? [],
            contractContactSections: $array['contractContactSections'] ?? [],
            documentSections: $array['documentSections'] ?? [],
            raw: $array['raw'] ?? [],
        );
    }

    public function getProfileSection(string $identifier): ?ProfileSection
    {
        return $this->profileSections[$identifier] ?? null;
    }

    public function getProfileSectionOrEmpty(string $identifier): ProfileSection
    {
        return $this->getProfileSection($identifier) ?? new ProfileSection(
            identifier: $identifier,
            fields: [],
            validationSet: new ValidationSet(identifier: $identifier, validations: []),
            position: count($this->profileSections),
        );
    }

    public function getProfileField(string $identifier): ?ProfileField
    {
        foreach ($this->profileSections as $section) {
            $field = $section->getField($identifier);
            if ($field !== null) {
                return $field;
            }
            foreach ($section->fields as $candidate) {
                if ($candidate->propertyName === $identifier) {
                    return $candidate;
                }
            }
        }
        return null;
    }

    public function getSpecialField(string $identifier): ?SpecialField
    {
        return $this->specialFields[$identifier] ?? null;
    }

    public function getContractContactSection(string $identifier): ?ContractContactSection
    {
        return $this->contractContactSections[$identifier] ?? null;
    }

    public function getContractContactField(string $identifier): ?ContractContactField
    {
        foreach ($this->contractContactSections as $section) {
            $field = $section->getField($identifier);
            if ($field !== null) {
                return $field;
            }
            foreach ($section->fields as $candidate) {
                if ($candidate->propertyName === $identifier) {
                    return $candidate;
                }
            }
        }
        return null;
    }

    public function getDocumentSection(string $identifier): ?DocumentSection
    {
        return $this->documentSections[$identifier] ?? null;
    }

    public function getDocumentSectionByType(string $type): ?DocumentSection
    {
        foreach ($this->documentSections as $section) {
            if ($section->type === $type) {
                return $section;
            }
        }
        return null;
    }

    public function getProfileValidationSet(?string $sectionIdentifier = null): ValidationSet
    {
        $identifier = $sectionIdentifier ?? 'profile';
        if ($sectionIdentifier !== null) {
            return $this->getProfileSection($sectionIdentifier)?->validationSet
                ?? new ValidationSet(identifier: $identifier, validations: []);
        }
        $validations = [];
        foreach ($this->profileSections as $section) {
            $validations = array_replace($validations, $section->validationSet->validations);
        }
        return new ValidationSet(identifier: 'profile', validations: $validations);
    }

    /**
     * Returns every validation that may participate in the inline Profile JSON
     * update, including direct special properties such as `skipSync`.
     */
    public function getProfileUpdateValidationSet(): ValidationSet
    {
        $validations = $this->getProfileValidationSet()->validations;
        foreach ($this->specialFields as $field) {
            if ($field->hasDirectProfileProperty()) {
                $validations[$field->identifier] = $field->validation;
            }
        }
        return new ValidationSet(identifier: 'profileUpdate', validations: $validations);
    }

    /**
     * @param list<string> $fieldIdentifiers
     */
    public function getProfileValidationSetForFields(array $fieldIdentifiers, string $sectionIdentifier): ValidationSet
    {
        $section = $this->getProfileSection($sectionIdentifier);
        return new ValidationSet(
            identifier: $sectionIdentifier,
            validations: $section === null
                ? []
                : $this->collectProfileValidations($fieldIdentifiers, $section),
        );
    }

    public function getContractContactValidationSet(string $sectionIdentifier): ValidationSet
    {
        return $this->getContractContactSection($sectionIdentifier)?->validationSet
            ?? new ValidationSet(identifier: $sectionIdentifier, validations: []);
    }

    /**
     * @param list<string> $fieldIdentifiers
     */
    public function getContractContactValidationSetForFields(
        array $fieldIdentifiers,
        string $sectionIdentifier,
    ): ValidationSet {
        $section = $this->getContractContactSection($sectionIdentifier);
        return new ValidationSet(
            identifier: $sectionIdentifier,
            validations: $section === null
                ? []
                : $this->collectContractContactValidations($fieldIdentifiers, $section),
        );
    }

    public function getDocumentValidationSet(string $sectionIdentifier): ValidationSet
    {
        return $this->getDocumentSection($sectionIdentifier)?->validationSet
            ?? new ValidationSet(identifier: $sectionIdentifier, validations: []);
    }

    public function getDocumentValidationSetByType(string $type): ValidationSet
    {
        return $this->getDocumentSectionByType($type)?->validationSet
            ?? new ValidationSet(identifier: $type, validations: []);
    }

    /**
     * @param list<string> $fieldIdentifiers
     * @param non-empty-string|null $sectionIdentifier
     * @return array<string, mixed>
     */
    public function getProfileValidationTcaTableConfig(
        array $fieldIdentifiers = [],
        ?string $sectionIdentifier = null,
    ): array
    {
        $validationSet = match (true) {
            $fieldIdentifiers === [] => $this->getProfileValidationSet($sectionIdentifier),
            $sectionIdentifier !== null => $this->getProfileValidationSetForFields(
                $fieldIdentifiers,
                $sectionIdentifier,
            ),
            default => new ValidationSet(
                identifier: 'profile',
                validations: $this->collectProfileValidations($fieldIdentifiers),
            ),
        };
        return $this->buildValidationTcaTableConfig($validationSet);
    }

    /**
     * @param list<string> $fieldIdentifiers
     * @return array<string, mixed>
     */
    public function getContractContactValidationTcaTableConfig(
        array $fieldIdentifiers,
        string $sectionIdentifier,
    ): array {
        return $this->buildValidationTcaTableConfig(
            $this->getContractContactValidationSetForFields($fieldIdentifiers, $sectionIdentifier),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getDocumentValidationTcaTypesConfig(): array
    {
        $configuration = [];
        foreach ($this->documentSections as $section) {
            if ($section->isContractSection()) {
                continue;
            }
            foreach ($section->validationSet->validations as $validation) {
                if ($validation->tcaConfig !== []) {
                    $configuration['types'][$section->type]['columnsOverrides'][$validation->fieldName]['config']
                        = $validation->tcaConfig;
                }
            }
        }
        return $configuration;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDocumentValidationTcaTableConfig(string $sectionIdentifier): array
    {
        return $this->buildValidationTcaTableConfig($this->getDocumentValidationSet($sectionIdentifier));
    }

    /**
     * @param list<string> $fieldIdentifiers
     * @return array<string, Validation>
     */
    private function collectProfileValidations(
        array $fieldIdentifiers,
        ?ProfileSection $section = null,
    ): array
    {
        $validations = [];
        $sections = $section === null ? $this->profileSections : [$section];
        foreach ($fieldIdentifiers as $fieldIdentifier) {
            foreach ($sections as $candidateSection) {
                $field = $candidateSection->getField($fieldIdentifier);
                if ($field === null) {
                    foreach ($candidateSection->fields as $candidate) {
                        if ($candidate->propertyName === $fieldIdentifier) {
                            $field = $candidate;
                            break;
                        }
                    }
                }
                if ($field !== null) {
                    $validations[$field->propertyName] = $field->validation;
                    break;
                }
            }
        }
        return $validations;
    }

    /**
     * @param list<string> $fieldIdentifiers
     * @return array<string, Validation>
     */
    private function collectContractContactValidations(
        array $fieldIdentifiers,
        ContractContactSection $section,
    ): array {
        $validations = [];
        foreach ($fieldIdentifiers as $fieldIdentifier) {
            $field = $section->getField($fieldIdentifier);
            if ($field === null) {
                foreach ($section->fields as $candidate) {
                    if ($candidate->propertyName === $fieldIdentifier) {
                        $field = $candidate;
                        break;
                    }
                }
            }
            if ($field !== null) {
                $validations[$field->propertyName] = $field->validation;
            }
        }
        return $validations;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildValidationTcaTableConfig(ValidationSet $validationSet): array
    {
        $tableTca = [];
        foreach ($validationSet->validations as $validation) {
            if ($validation->tcaConfig !== []) {
                $tableTca['columns'][$validation->fieldName]['config'] = $validation->tcaConfig;
            }
        }
        return $tableTca;
    }
}
