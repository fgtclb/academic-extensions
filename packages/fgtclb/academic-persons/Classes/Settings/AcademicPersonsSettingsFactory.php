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
        return 'AcademicPersons_Settings_PublicProfileSchema_v5';
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function normalize(array $settings): AcademicPersonsSettings
    {
        return new AcademicPersonsSettings(
            publicProfile: $this->normalizePublicProfile($settings),
            raw: $settings,
        );
    }

    /**
     * Normalizes the editor settings without mixing them into the public
     * profile configuration loaded by this factory.
     *
     * The value objects live in academic_persons because they describe its
     * domain records. Loading, caching and injecting this graph is owned by
     * academic_persons_edit.
     *
     * @param array<string, mixed> $settings
     */
    public function normalizeEditConfiguration(array $settings): AcademicPersonsSettings
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
     */
    private function normalizePublicProfile(array $settings): PublicProfileSettings
    {
        $configuredPublicProfile = $settings['profile'] ?? null;
        if (!is_array($configuredPublicProfile)) {
            return new PublicProfileSettings();
        }
        $structure = [];
        $configuredStructure = is_array($configuredPublicProfile['structure'] ?? null)
            ? $configuredPublicProfile['structure']
            : [];
        foreach ($configuredStructure as $columnIdentifier => $elementIdentifiers) {
            if (!is_string($columnIdentifier) || $columnIdentifier === '' || !is_array($elementIdentifiers)) {
                continue;
            }
            $structure[$columnIdentifier] = $this->normalizePublicProfileList($elementIdentifiers);
        }
        $details = [];
        $configuredDetails = is_array($configuredPublicProfile['details'] ?? null)
            ? $configuredPublicProfile['details']
            : [];
        foreach ($configuredDetails as $detailIdentifier => $detailConfiguration) {
            if (!is_string($detailIdentifier) || $detailIdentifier === '') {
                continue;
            }
            if (is_string($detailConfiguration) && $detailConfiguration !== '') {
                $details[$detailIdentifier] = $detailConfiguration;
                continue;
            }
            if (!is_array($detailConfiguration)) {
                continue;
            }
            $details[$detailIdentifier] = array_is_list($detailConfiguration)
                ? $this->normalizePublicProfileList($detailConfiguration)
                : $this->normalizePublicProfileMap($detailConfiguration);
        }
        return new PublicProfileSettings(structure: $structure, details: $details);
    }

    /**
     * @param array<int, mixed> $configuredValues
     * @return list<string>
     */
    private function normalizePublicProfileList(array $configuredValues): array
    {
        $values = [];
        foreach ($configuredValues as $configuredValue) {
            if (!is_string($configuredValue)) {
                continue;
            }
            $value = trim($configuredValue);
            if ($value !== '' && !in_array($value, $values, true)) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * @param array<string|int, mixed> $configuredValues
     * @return array<string, string>
     */
    private function normalizePublicProfileMap(array $configuredValues): array
    {
        $values = [];
        foreach ($configuredValues as $configuredIdentifier => $configuredValue) {
            if (!is_string($configuredIdentifier)
                || $configuredIdentifier === ''
                || !is_string($configuredValue)
            ) {
                continue;
            }
            $value = trim($configuredValue);
            if ($value !== '') {
                $values[$configuredIdentifier] = $value;
            }
        }
        return $values;
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
                    characterLimit: $this->normalizeProfileCharacterLimit(
                        $options,
                        $renderType,
                    ),
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
            $sectionIdentifier = (string)$identifier;
            $sectionType = (string)($options['type'] ?? '');
            $contractSection = $sectionIdentifier === 'contracts'
                || in_array($sectionType, ['contract', 'contracts'], true);
            $validations = [];
            $configuredValidations = is_array($options['validators'] ?? null) ? $options['validators'] : [];
            foreach ($configuredValidations as $fieldIdentifier => $validationConfiguration) {
                if (!is_array($validationConfiguration)) {
                    continue;
                }
                $propertyName = self::DOCUMENT_PROPERTY_ALIASES[(string)$fieldIdentifier]
                    ?? (string)$fieldIdentifier;
                $fieldName = GeneralUtility::camelCaseToLowerCaseUnderscored($propertyName);
                $validations[$propertyName] = $this->normalizeValidation(
                    identifier: $propertyName,
                    fieldName: $fieldName,
                    validators: $this->normalizeDocumentValidationFlags($validationConfiguration),
                    characterLimit: $this->normalizeDocumentCharacterLimit($validationConfiguration),
                );
            }
            $section = new DocumentSection(
                identifier: $sectionIdentifier,
                label: (string)($options['label'] ?? ''),
                type: $sectionType,
                fieldName: (string)($options['fieldName'] ?? ''),
                readOnly: (bool)($options['readonly'] ?? false),
                validationSet: new ValidationSet(identifier: $sectionIdentifier, validations: $validations),
                position: count($sections),
                rowFields: $this->normalizeDocumentOptionList(
                    $options['rowFields'] ?? null,
                    $contractSection
                        ? DocumentSection::SUPPORTED_CONTRACT_ROW_FIELDS
                        : DocumentSection::SUPPORTED_PROFILE_INFORMATION_ROW_FIELDS,
                ),
                actions: $this->normalizeDocumentOptionList(
                    $options['actions'] ?? null,
                    DocumentSection::SUPPORTED_ACTIONS,
                ),
            );
            if ($section->isValid()) {
                $sections[$section->identifier] = $section;
            }
        }
        return $sections;
    }

    /**
     * @param mixed $configuredValues
     * @param list<string> $allowedValues
     * @return list<string>
     */
    private function normalizeDocumentOptionList(mixed $configuredValues, array $allowedValues): array
    {
        if (!is_array($configuredValues)) {
            return [];
        }
        $values = [];
        foreach ($configuredValues as $configuredValue) {
            if (!is_string($configuredValue)) {
                continue;
            }
            $value = strtolower(trim($configuredValue));
            if ($value !== '' && in_array($value, $allowedValues, true) && !in_array($value, $values, true)) {
                $values[] = $value;
            }
        }
        return $values;
    }

    /**
     * Document fields support the regular validator flag list and the richer
     * editor map used for descriptions. Keeping this conversion here makes
     * the resulting Validation object the single source for Fluid, JSON and
     * Extbase validation metadata. Backend TCA is configured independently.
     *
     * @param array<int|string, mixed> $configuration
     * @return list<mixed>
     */
    private function normalizeDocumentValidationFlags(array $configuration): array
    {
        if (array_is_list($configuration)) {
            return array_values($configuration);
        }
        $flags = is_array($configuration['validators'] ?? null)
            ? array_values($configuration['validators'])
            : [];
        $supportedFlags = [
            'required',
            'readonly',
            'disabled',
            'email',
            'url',
            'number',
            'tel',
            'date',
            'textarea',
            'html',
        ];
        foreach ($supportedFlags as $flag) {
            if (($configuration[$flag] ?? false) === true) {
                $flags[] = $flag;
            }
        }
        $editor = is_array($configuration['editor'] ?? null) ? $configuration['editor'] : [];
        $editorType = strtolower(trim((string)($editor['type'] ?? '')));
        if ($editorType === 'ckeditor') {
            $flags[] = 'html';
        } elseif ($editorType === 'textarea') {
            $flags[] = 'textarea';
        }
        return $flags;
    }

    /**
     * @param array<int|string, mixed> $configuration
     */
    private function normalizeDocumentCharacterLimit(array $configuration): int
    {
        $editor = is_array($configuration['editor'] ?? null) ? $configuration['editor'] : [];
        if (strtolower(trim((string)($editor['type'] ?? ''))) !== 'ckeditor') {
            return 0;
        }
        return $this->normalizeCharacterLimit($editor['limit'] ?? 0);
    }

    /**
     * @param array<string, mixed> $configuration
     */
    private function normalizeProfileCharacterLimit(array $configuration, string $renderType): int
    {
        if (strtolower(trim($renderType)) !== 'ckeditor') {
            return 0;
        }
        return $this->normalizeCharacterLimit($configuration['characterLimit'] ?? 0);
    }

    private function normalizeCharacterLimit(mixed $limit): int
    {
        if (is_int($limit)) {
            return max(0, $limit);
        }
        if (is_string($limit) && preg_match('/^\d+$/', trim($limit)) === 1) {
            return (int)trim($limit);
        }
        return 0;
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
        int $characterLimit = 0,
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
            if ($fieldType === 'select') {
                $tcaConfig['minitems'] = 1;
            }
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
            characterLimit: max(0, $characterLimit),
        );
    }
}
