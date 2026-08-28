<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Settings;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettingsFactory;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Loads only the frontend-editor configuration. Public-profile settings are
 * loaded and cached independently by academic_persons.
 *
 * @internal not part of public API.
 */
final class AcademicPersonsEditSettingsFactory
{
    private const SETTINGS_FILE = 'Configuration/AcademicsPersonsEdit/Settings.yaml';

    public function __construct(
        #[Autowire(service: 'cache.core')]
        private readonly PhpFrontend $cache,
        private readonly PackageManager $packageManager,
        private readonly AcademicPersonsSettingsFactory $academicPersonsSettingsFactory,
    ) {}

    public function get(): AcademicPersonsSettings
    {
        return $this->getFromCache() ?? $this->loadUncached();
    }

    private function loadUncached(): AcademicPersonsSettings
    {
        $loadedSettings = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $settingsFile = $package->getPackagePath() . self::SETTINGS_FILE;
            if (!file_exists($settingsFile)) {
                continue;
            }
            $settingsArray = Yaml::parseFile($settingsFile);
            if (!is_array($settingsArray)) {
                continue;
            }
            $loadedSettings = array_merge($loadedSettings, $settingsArray);
        }
        $settings = $this->academicPersonsSettingsFactory->normalizeEditConfiguration($loadedSettings);
        $this->setCache($settings);
        return $settings;
    }

    private function getFromCache(): ?AcademicPersonsSettings
    {
        $settings = $this->cache->require($this->settingsIdentifier());
        return $settings instanceof AcademicPersonsSettings ? $settings : null;
    }

    private function setCache(AcademicPersonsSettings $settings): void
    {
        $this->cache->set($this->settingsIdentifier(), 'return ' . var_export($settings, true) . ';');
    }

    /**
     * @return non-empty-string
     */
    private function settingsIdentifier(): string
    {
        return 'AcademicPersonsEdit_Settings_SectionSchema_v3';
    }
}
