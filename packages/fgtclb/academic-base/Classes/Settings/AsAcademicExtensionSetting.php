<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class AsAcademicExtensionSetting
{
    public const TAG_NAME = 'academic.settings';

    public function __construct(
        public string $settingsFileName,
        public string $packageKey,
    ) {}
}
