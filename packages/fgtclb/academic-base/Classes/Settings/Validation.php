<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * One field of a validation set: the flags an editor sees on a form control,
 * the Extbase validators to run on the submitted value and the TCA column
 * configuration fragment the backend FormEngine merges in.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class Validation
{
    /**
     * @param string $identifier
     * @param class-string<ValidatorInterface>[] $validatorClassNames
     * @param array<string, mixed> $tcaConfig
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $fieldName,
        public readonly bool $required,
        public readonly bool $disabled,
        public readonly bool $readOnly,
        public readonly array $validatorClassNames,
        public readonly array $tcaConfig,
        public readonly string $inputType = '',
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     fieldName: string,
     *     required: bool,
     *     disabled: bool,
     *     readOnly: bool,
     *     validatorClassNames: class-string<ValidatorInterface>[],
     *     tcaConfig: array<string, mixed>,
     *     inputType: string,
     * } $array
     * @return self
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            fieldName: $array['fieldName'],
            required: $array['required'],
            disabled: $array['disabled'],
            readOnly: $array['readOnly'],
            validatorClassNames: $array['validatorClassNames'],
            tcaConfig: $array['tcaConfig'],
            inputType: $array['inputType'],
        );
    }
}
