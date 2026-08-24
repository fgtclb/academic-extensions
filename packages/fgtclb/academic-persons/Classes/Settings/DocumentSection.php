<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal not part of public API.
 */
#[Exclude]
final class DocumentSection
{
    public function __construct(
        public readonly string $identifier,
        public readonly string $label,
        public readonly string $type,
        public readonly string $fieldName,
        public readonly bool $readOnly,
        public readonly ValidationSet $validationSet,
        public readonly int $position,
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     label: string,
     *     type: string,
     *     fieldName: string,
     *     readOnly: bool,
     *     validationSet: ValidationSet,
     *     position: int,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            label: $array['label'],
            type: $array['type'],
            fieldName: $array['fieldName'],
            readOnly: $array['readOnly'],
            validationSet: $array['validationSet'],
            position: $array['position'],
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->label !== ''
            && $this->type !== ''
            && $this->fieldName !== '';
    }

    public function isContractSection(): bool
    {
        return $this->identifier === 'contracts'
            || in_array($this->type, ['contract', 'contracts'], true);
    }

    /**
     * @return array<string, Validation>
     */
    public function getValidations(): array
    {
        return $this->validationSet->validations;
    }
}
