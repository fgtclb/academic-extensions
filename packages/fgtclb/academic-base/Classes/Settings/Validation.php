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
 * `flags` keeps the normalised flag list the object was built from, so a
 * consumer can ask for a flag the object has no dedicated property for -
 * `isRichText()` is the one such question asked today. `characterLimit` is
 * the readable-text limit of a rich text field, 0 for no limit; it is
 * frontend and server side metadata only and is never copied into the TCA.
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
     * @param list<string> $flags
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
        public readonly array $flags = [],
        public readonly int $characterLimit = 0,
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
     *     flags?: list<string>,
     *     characterLimit?: int,
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
            flags: $array['flags'] ?? [],
            characterLimit: $array['characterLimit'] ?? 0,
        );
    }

    public function isRichText(): bool
    {
        return in_array('html', $this->flags, true);
    }
}
