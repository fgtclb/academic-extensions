<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * Turns the flag lists of a settings file into {@see ValidationSet} objects.
 *
 * The expected shape is one map per set, each property naming a list of flags:
 *
 *     <set identifier>:
 *       <propertyName>:
 *         - required
 *         - email
 *
 * Flags are matched case-insensitively. `disabled` and `readonly` cancel
 * `required`, and `disabled` is expressed to the backend as `readOnly`,
 * because FormEngine has no per-field notion matching the HTML attribute.
 *
 * @internal not part of public API.
 */
final class ValidationNormalizer
{
    /**
     * @param array<string, array<string, list<string>>> $validationSets
     * @return array<string, ValidationSet>
     */
    public function normalizeValidationSets(array $validationSets): array
    {
        $normalized = [];
        foreach ($validationSets as $identifier => $properties) {
            $validations = [];
            foreach ($properties as $propertyName => $flags) {
                $validations[$propertyName] = $this->normalizeValidation((string)$propertyName, $flags);
            }
            $normalized[$identifier] = new ValidationSet(
                identifier: (string)$identifier,
                validations: $validations,
            );
        }
        return $normalized;
    }

    /**
     * @param list<string> $flags
     */
    public function normalizeValidation(string $identifier, array $flags): Validation
    {
        $tcaConfig = [];
        $flags = array_map('strtolower', $flags);
        $readOnly = in_array('readonly', $flags, true);
        $disabled = in_array('disabled', $flags, true);
        $required = !$disabled && !$readOnly && in_array('required', $flags, true);
        $inputType = 'text';
        /** @var class-string<ValidatorInterface>[] $validatorClassNames */
        $validatorClassNames = [];
        if ($disabled) {
            // @todo Investigate how to handle that for the backend / TCA FormEngine, therefore switch to
            //       readOnly for now
            $readOnly = true;
        }
        $tcaConfig['readOnly'] = $readOnly;
        $tcaConfig['required'] = false;
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
            // @todo Investigate if we want to use NumberValidator for the frontend
            $tcaConfig['type'] = 'number';
            $inputType = 'number';
        }
        // @todo url validation ?
        return new Validation(
            identifier: $identifier,
            fieldName: GeneralUtility::camelCaseToLowerCaseUnderscored($identifier),
            required: $required,
            disabled: $disabled,
            readOnly: $readOnly,
            validatorClassNames: $validatorClassNames,
            tcaConfig: $tcaConfig,
            inputType: $inputType,
        );
    }
}
