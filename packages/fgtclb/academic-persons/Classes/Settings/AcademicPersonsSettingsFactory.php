<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * @internal not part of public API.
 * @todo Integrate a basic configuration builder factory in `EXT:academic_base` and adopt this implementation.
 */
class AcademicPersonsSettingsFactory
{
    private const DOCUMENT_PROPERTY_ALIASES = [
        'from' => 'yearStart',
        'to' => 'yearEnd',
        'description' => 'bodytext',
    ];

    public function __construct(
        #[Autowire(service: 'cache.core')]
        protected readonly PhpFrontend $cache,
        protected readonly PackageManager $packageManager,
    ) {}

    public function get(): AcademicPersonsSettings
    {
        return $this->getFromCache() ?? $this->loadUncached();
    }

    private function loadUncached(): AcademicPersonsSettings
    {
        $loadedSettings = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $settingsFile = $package->getPackagePath() . 'Configuration/AcademicPersons/Settings.yaml';
            if (file_exists($settingsFile)) {
                $settingsArray = Yaml::parseFile($settingsFile);
                if ($settingsArray === null) {
                    continue;
                }
                $loadedSettings = array_merge($loadedSettings, $settingsArray);
            }
        }
        $settings = $this->normalize($loadedSettings);
        $this->setCache($settings);
        return $settings;
    }

    private function getFromCache(): ?AcademicPersonsSettings
    {
        $settings = $this->cache->require($this->academicPersonsSettingsIdentifier());
        return $settings instanceof AcademicPersonsSettings ? $settings : null;
    }

    private function setCache(AcademicPersonsSettings $settings): void
    {
        $this->cache->set($this->academicPersonsSettingsIdentifier(), 'return ' . var_export($settings, true) . ';');
    }

    /**
     * @return non-empty-string
     */
    private function academicPersonsSettingsIdentifier(): string
    {
        return 'AcademicPersons_Settings_SectionSchema_v2';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function normalize(array $settings): AcademicPersonsSettings
    {
        return new AcademicPersonsSettings(
            profileSections: $this->normalizeProfileSections($settings),
            specialFields: $this->normalizeSpecialFields($settings),
            contractContactSections: $this->normalizeContractContactSections($settings),
            documentSections: $this->normalizeDocumentSections($settings),
            raw: $settings,
        );
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, ProfileSection>
     */
    private function normalizeProfileSections(array $settings): array
    {
        $profile = $settings['profile'] ?? null;
        if (!is_array($profile)) {
            return [];
        }
        $groupedFields = [];
        $sectionPositions = [];
        foreach ($profile as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $sectionIdentifier = (string)($options['section'] ?? '');
            $fieldType = (string)($options['fieldType'] ?? '');
            $renderType = (string)($options['renderType'] ?? '');
            $propertyName = (string)($options['propertyName'] ?? $identifier);
            $fieldName = (string)($options['fieldName'] ?? GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName));
            $validators = is_array($options['validators'] ?? null) ? $options['validators'] : [];
            $field = new ProfileField(
                identifier: (string)$identifier,
                section: $sectionIdentifier,
                propertyName: $propertyName,
                fieldName: $fieldName,
                fieldType: $fieldType,
                renderType: $renderType,
                validation: $this->normalizeValidation(
                    identifier: $propertyName,
                    fieldName: $fieldName,
                    validators: $validators,
                    fieldType: $fieldType,
                    renderType: $renderType,
                ),
                position: count($groupedFields[$sectionIdentifier] ?? []),
            );
            if (!$field->isValid()) {
                continue;
            }
            if (!array_key_exists($sectionIdentifier, $sectionPositions)) {
                $sectionPositions[$sectionIdentifier] = count($sectionPositions);
            }
            $groupedFields[$sectionIdentifier][$field->identifier] = $field;
        }
        $sections = [];
        foreach ($groupedFields as $identifier => $fields) {
            $validations = [];
            foreach ($fields as $field) {
                $validations[$field->propertyName] = $field->validation;
            }
            $sections[$identifier] = new ProfileSection(
                identifier: $identifier,
                fields: $fields,
                validationSet: new ValidationSet(identifier: $identifier, validations: $validations),
                position: $sectionPositions[$identifier],
            );
        }
        return $sections;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, SpecialField>
     */
    private function normalizeSpecialFields(array $settings): array
    {
        $configuredFields = $settings['special'] ?? null;
        if (!is_array($configuredFields)) {
            return [];
        }
        $fields = [];
        foreach ($configuredFields as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $fieldIdentifiers = [];
            foreach (is_array($options['fields'] ?? null) ? $options['fields'] : [] as $fieldIdentifier) {
                if (is_string($fieldIdentifier) && $fieldIdentifier !== '' && !in_array($fieldIdentifier, $fieldIdentifiers, true)) {
                    $fieldIdentifiers[] = $fieldIdentifier;
                }
            }
            $fieldType = (string)($options['fieldType'] ?? '');
            $renderType = (string)($options['renderType'] ?? '');
            $validators = is_array($options['validators'] ?? null) ? $options['validators'] : [];
            $fieldName = GeneralUtility::camelCaseToLowerCaseUnderscored((string)$identifier);
            $field = new SpecialField(
                identifier: (string)$identifier,
                type: strtolower((string)($options['type'] ?? '')),
                fieldType: $fieldType,
                renderType: $renderType,
                fieldIdentifiers: $fieldIdentifiers,
                validation: $this->normalizeValidation(
                    identifier: (string)$identifier,
                    fieldName: $fieldName,
                    validators: $validators,
                    fieldType: $fieldType,
                    renderType: $renderType,
                ),
                position: count($fields),
            );
            if ($field->isValid()) {
                $fields[$field->identifier] = $field;
            }
        }
        return $fields;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, ContractContactSection>
     */
    private function normalizeContractContactSections(array $settings): array
    {
        $configuredFields = $settings['contractContact'] ?? null;
        if (!is_array($configuredFields)) {
            return [];
        }
        $groupedFields = [];
        $sectionPositions = [];
        foreach ($configuredFields as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $sectionIdentifier = (string)($options['section'] ?? '');
            $propertyName = (string)($options['propertyName'] ?? $identifier);
            $fieldName = (string)($options['fieldName'] ?? GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName));
            $fieldType = (string)($options['fieldType'] ?? '');
            $renderType = (string)($options['renderType'] ?? '');
            $validators = is_array($options['validators'] ?? null) ? $options['validators'] : [];
            $field = new ContractContactField(
                identifier: (string)$identifier,
                section: $sectionIdentifier,
                propertyName: $propertyName,
                fieldName: $fieldName,
                fieldType: $fieldType,
                renderType: $renderType,
                validation: $this->normalizeValidation(
                    identifier: $propertyName,
                    fieldName: $fieldName,
                    validators: $validators,
                    fieldType: $fieldType,
                    renderType: $renderType,
                ),
                position: count($groupedFields[$sectionIdentifier] ?? []),
            );
            if (!$field->isValid()) {
                continue;
            }
            if (!array_key_exists($sectionIdentifier, $sectionPositions)) {
                $sectionPositions[$sectionIdentifier] = count($sectionPositions);
            }
            $groupedFields[$sectionIdentifier][$field->identifier] = $field;
        }
        $sections = [];
        foreach ($groupedFields as $identifier => $fields) {
            $validations = [];
            foreach ($fields as $field) {
                $validations[$field->propertyName] = $field->validation;
            }
            $sections[$identifier] = new ContractContactSection(
                identifier: $identifier,
                fields: $fields,
                validationSet: new ValidationSet(identifier: $identifier, validations: $validations),
                position: $sectionPositions[$identifier],
            );
        }
        return $sections;
    }

    /**
     * @param array<string, mixed> $settings
     * @return array<string, DocumentSection>
     */
    private function normalizeDocumentSections(array $settings): array
    {
        $configuredSections = $settings['documentSections'] ?? null;
        if (!is_array($configuredSections)) {
            return [];
        }
        $sections = [];
        foreach ($configuredSections as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $validations = [];
            $configuredValidations = is_array($options['validators'] ?? null) ? $options['validators'] : [];
            foreach ($configuredValidations as $fieldIdentifier => $validators) {
                if (!is_array($validators)) {
                    continue;
                }
                $propertyName = self::DOCUMENT_PROPERTY_ALIASES[(string)$fieldIdentifier]
                    ?? (string)$fieldIdentifier;
                $fieldName = GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);
                $validations[$propertyName] = $this->normalizeValidation(
                    identifier: $propertyName,
                    fieldName: $fieldName,
                    validators: $validators,
                );
            }
            $section = new DocumentSection(
                identifier: (string)$identifier,
                label: (string)($options['label'] ?? ''),
                type: (string)($options['type'] ?? ''),
                fieldName: (string)($options['fieldName'] ?? ''),
                readOnly: (bool)($options['readonly'] ?? false),
                validationSet: new ValidationSet(identifier: (string)$identifier, validations: $validations),
                position: count($sections),
            );
            if ($section->isValid()) {
                $sections[$section->identifier] = $section;
            }
        }
        return $sections;
    }

    /**
     * @param array<int, mixed> $validators
     */
    private function normalizeValidation(
        string $identifier,
        string $fieldName,
        array $validators,
        string $fieldType = '',
        string $renderType = '',
    ): Validation {
        $flags = [];
        foreach ($validators as $validator) {
            if (!is_string($validator)) {
                continue;
            }
            $flag = strtolower(trim($validator));
            if ($flag !== '' && !in_array($flag, $flags, true)) {
                $flags[] = $flag;
            }
        }
        $disabled = in_array('disabled', $flags, true);
        $readOnly = $disabled || in_array('readonly', $flags, true);
        $required = !$readOnly && in_array('required', $flags, true);
        $inputType = match (strtolower($renderType)) {
            'select' => 'select',
            'checkbox' => 'checkbox',
            'phone' => 'tel',
            'email' => 'email',
            'number' => 'number',
            'combinedlink' => 'url',
            'ckeditor' => 'textarea',
            default => 'text',
        };
        $tcaConfig = [
            'readOnly' => $readOnly,
            'required' => false,
        ];
        if ($fieldType === 'check') {
            $tcaConfig['type'] = 'check';
        } elseif ($fieldType === 'textarea') {
            $tcaConfig['type'] = 'text';
        } elseif (in_array($fieldType, ['input', 'select'], true)) {
            $tcaConfig['type'] = $fieldType;
        }
        /** @var class-string<ValidatorInterface>[] $validatorClassNames */
        $validatorClassNames = [];
        if ($required) {
            $validatorClassNames[] = NotEmptyValidator::class;
            $tcaConfig['required'] = true;
            $tcaConfig['minitems'] = 1;
        }
        if (in_array('email', $flags, true)) {
            $validatorClassNames[] = EmailAddressValidator::class;
            $tcaConfig['type'] = 'email';
            $inputType = 'email';
        }
        if (in_array('number', $flags, true)) {
            $tcaConfig['type'] = 'number';
            $inputType = 'number';
        }
        if (in_array('url', $flags, true)) {
            $validatorClassNames[] = UrlValidator::class;
            $inputType = 'url';
        }
        if (in_array('tel', $flags, true)) {
            $inputType = 'tel';
        }
        if (in_array('date', $flags, true)) {
            $inputType = 'date';
        }
        if (in_array('textarea', $flags, true) || in_array('html', $flags, true)) {
            $inputType = 'textarea';
            $tcaConfig['type'] = 'text';
        }
        return new Validation(
            identifier: $identifier,
            fieldName: $fieldName,
            required: $required,
            disabled: $disabled,
            readOnly: $readOnly,
            validatorClassNames: $validatorClassNames,
            tcaConfig: $tcaConfig,
            inputType: $inputType,
            flags: $flags,
        );
    }
}
