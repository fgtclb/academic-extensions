<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

interface AcademicSettingsInterface
{
    /**
     * @param array<string, mixed> $data
     * @return self
     */
    public static function __set_state(array $data): self;

    /**
     * @return array<string, mixed>
     */
    public function __serialize(): array;

    /**
     * @param array<string, mixed> $data
     */
    public function __unserialize(array $data): void;
}
