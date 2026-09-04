<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Unit\Settings\Fixtures;

/**
 * The smallest settings object a normaliser can hand to `SettingsFileLoader`: it has
 * to survive `var_export()`/`require`, so it carries the `__set_state()` the loader
 * relies on.
 */
final class TestSettings
{
    /**
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly array $raw,
    ) {}

    /**
     * @param array{raw: array<string, mixed>} $array
     */
    public static function __set_state(array $array): self
    {
        return new self(raw: $array['raw']);
    }
}
