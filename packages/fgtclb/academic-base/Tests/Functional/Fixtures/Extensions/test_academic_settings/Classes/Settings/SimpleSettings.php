<?php

declare(strict_types=1);

namespace FGTCLB\TestAcademicSettings\Settings;

use FGTCLB\AcademicBase\Settings\AcademicSettingsInterface;
use FGTCLB\AcademicBase\Settings\AsAcademicExtensionSetting;

#[AsAcademicExtensionSetting(
    settingsFileName: 'Configuration/TestAcademicSettings/simple-settings.yaml',
    // Using composer package name here
    packageKey: 'tests/test-academic-settings',
)]
final class SimpleSettings implements AcademicSettingsInterface
{
    /**
     * @param array<int|string, mixed> $someArray
     * @param bool $someFlag
     */
    public function __construct(
        public readonly array $someArray = [],
        public readonly bool $someFlag = false,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function __set_state(array $data): self
    {
        return new self(...$data);
    }

    /**
     * @return array{
     *     someFlag?: bool,
     *     someArray?: array<int|string, mixed>,
     * }
     */
    public function __serialize(): array
    {
        return [
            'someFlag' => $this->someFlag,
            'someArray' => $this->someArray,
        ];
    }

    /**
     * @param array{
     *     someFlag?: bool,
     *     someArray?: array<int|string, mixed>,
     * } $data
     */
    public function __unserialize(array $data): void
    {
        $this->someFlag = $data['someFlag'] ?? false;
        $this->someArray = $data['someArray'] ?? [];
    }
}
