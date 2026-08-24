<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * @internal not part of public API.
 */
#[Exclude]
final class ProfileSection
{
    /**
     * @param array<string, ProfileField> $fields
     */
    public function __construct(
        public readonly string $identifier,
        public readonly array $fields,
        public readonly ValidationSet $validationSet,
        public readonly int $position,
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     fields: array<string, ProfileField>,
     *     validationSet: ValidationSet,
     *     position: int,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            fields: $array['fields'],
            validationSet: $array['validationSet'],
            position: $array['position'],
        );
    }

    public function getField(string $identifier): ?ProfileField
    {
        return $this->fields[$identifier] ?? null;
    }

    /**
     * Fluid-facing shortcut which keeps the validation set attached to this section.
     *
     * @return array<string, Validation>
     */
    public function getValidations(): array
    {
        return $this->validationSet->validations;
    }
}
