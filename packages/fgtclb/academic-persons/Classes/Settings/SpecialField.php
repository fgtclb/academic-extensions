<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersons\Settings;

use Symfony\Component\DependencyInjection\Attribute\Exclude;

/**
 * Describes an inline component which is not rendered as one regular profile
 * field. Placement remains the responsibility of the Fluid composition root.
 *
 * @internal not part of public API.
 */
#[Exclude]
final class SpecialField
{
    /**
     * @param list<string> $fieldIdentifiers
     */
    public function __construct(
        public readonly string $identifier,
        public readonly string $type,
        public readonly string $fieldType,
        public readonly string $renderType,
        public readonly array $fieldIdentifiers,
        public readonly Validation $validation,
        public readonly int $position,
    ) {}

    /**
     * @param array{
     *     identifier: string,
     *     type: string,
     *     fieldType: string,
     *     renderType: string,
     *     fieldIdentifiers: list<string>,
     *     validation: Validation,
     *     position: int,
     * } $array
     */
    public static function __set_state(array $array): self
    {
        return new self(
            identifier: $array['identifier'],
            type: $array['type'],
            fieldType: $array['fieldType'],
            renderType: $array['renderType'],
            fieldIdentifiers: $array['fieldIdentifiers'],
            validation: $array['validation'],
            position: $array['position'],
        );
    }

    public function isValid(): bool
    {
        return $this->identifier !== ''
            && $this->type === 'special'
            && $this->renderType !== '';
    }

    public function hasDirectProfileProperty(): bool
    {
        return $this->fieldType !== '' && $this->fieldIdentifiers === [];
    }
}
