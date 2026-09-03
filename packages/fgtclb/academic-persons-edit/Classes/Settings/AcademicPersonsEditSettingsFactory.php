<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;

/**
 * Compatibility facade for integrations still requesting the former factory.
 * The canonical settings are loaded by academic_persons from one file.
 *
 * @internal not part of public API.
 */
final class AcademicPersonsEditSettingsFactory
{
    public function __construct(
        private readonly AcademicPersonsSettingsFactory $academicPersonsSettingsFactory,
    ) {}

    public function get(): AcademicPersonsSettings
    {
        return $this->academicPersonsSettingsFactory->get();
    }
}
