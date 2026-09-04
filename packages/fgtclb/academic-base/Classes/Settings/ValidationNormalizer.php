<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\EmailAddressValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;
use TYPO3\CMS\Extbase\Validation\Validator\UrlValidator;
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
 * Only `required`, `email` and `url` produce an Extbase validator. `email`
 * and `number` also set the TCA column type; every other flag - `date`,
 * `tel`, `textarea` and `html` - chooses the frontend input type only and
 * leaves the TCA column to the TCA file that declares it.
 *
 * A render type, when a settings file names one, decides the input type the
 * flags start from, so a `select` stays a select even though no flag says
 * so. A character limit is carried through as metadata for the frontend and
 * the server side validation; it is never copied into the TCA.
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
     * @param array<int|string, mixed> $flags
     * @param string $fieldName Database column, derived from the identifier when empty
     * @param string $renderType Frontend control the flags refine, e.g. `select` or `ckeditor`
     * @param int $characterLimit Readable-text limit of a rich text field, 0 for none
     */
    public function normalizeValidation(
        string $identifier,
        array $flags,
        string $fieldName = '',
        string $renderType = '',
        int $characterLimit = 0,
    ): Validation {
        $flags = $this->normalizeFlags($flags);
        $tcaConfig = [];
        $readOnly = in_array('readonly', $flags, true);
        $disabled = in_array('disabled', $flags, true);
        $required = !$disabled && !$readOnly && in_array('required', $flags, true);
        $inputType = match (strtolower(trim($renderType))) {
            'select' => 'select',
            'checkbox' => 'checkbox',
            'phone' => 'tel',
            'email' => 'email',
            'number' => 'number',
            'date' => 'date',
            'combinedlink' => 'url',
            'ckeditor' => 'textarea',
            default => 'text',
        };
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
        if (in_array('url', $flags, true)) {
            $validatorClassNames[] = UrlValidator::class;
            $inputType = 'url';
        }
        if (in_array('tel', $flags, true)) {
            // The input type only: there is no phone number format to enforce.
            $inputType = 'tel';
        }
        if (in_array('date', $flags, true)) {
            // The input type only: the TCA column declares its own datetime type,
            // format and dbType, which a flag list has no business overriding.
            $inputType = 'date';
        }
        if (in_array('textarea', $flags, true) || in_array('html', $flags, true)) {
            // The input type only, for the same reason: the text columns carrying
            // these flags declare their own RTE configuration.
            $inputType = 'textarea';
        }
        return new Validation(
            identifier: $identifier,
            fieldName: $fieldName !== '' ? $fieldName : GeneralUtility::camelCaseToLowerCaseUnderscored($identifier),
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

    /**
     * Keeps the strings of a flag list, lower-cased, trimmed and without
     * duplicates, in the order they were configured.
     *
     * @param array<int|string, mixed> $flags
     * @return list<string>
     */
    private function normalizeFlags(array $flags): array
    {
        $normalized = [];
        foreach ($flags as $flag) {
            if (!is_string($flag)) {
                continue;
            }
            $flag = strtolower(trim($flag));
            if ($flag !== '' && !in_array($flag, $normalized, true)) {
                $normalized[] = $flag;
            }
        }
        return $normalized;
    }
}
