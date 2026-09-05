<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Describes one field of the Contract form shown by the inline profile editor.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class ContractField
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $propertyName,
        public readonly string $fieldName,
        public readonly string $fieldType,
        public readonly string $renderType,
        public readonly string $optionSource,
        public readonly string $helptext,
        public readonly Validation $validation,
        public readonly int $position,
        public readonly string $autocomplete = '',
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     propertyName: string,
     *     fieldName: string,
     *     fieldType: string,
     *     renderType: string,
     *     optionSource: string,
     *     helptext: string,
     *     validation: Validation,
     *     position: int,
     *     autocomplete?: string,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            propertyName: $array['propertyName'],
            fieldName: $array['fieldName'],
            fieldType: $array['fieldType'],
            renderType: $array['renderType'],
            optionSource: $array['optionSource'],
            helptext: $array['helptext'],
            validation: $array['validation'],
            position: $array['position'],
            autocomplete: $array['autocomplete'] ?? '',
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->propertyName !== ''
            && $this->fieldName !== ''
            && $this->fieldType !== ''
            && $this->renderType !== '';
    }
}
