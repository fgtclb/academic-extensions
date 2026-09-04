<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Loads one settings file from every active package and caches the
 * normalised result.
 *
 * The file is read from every active package that ships it, in package
 * loading order, and the arrays are folded together with a top-level
 * `array_merge()`: the last package wins per top-level key, there is no deep
 * merge. The merged array is handed to the normaliser of the calling
 * extension, and the object it returns is written to the core cache as
 * `return <var_export>;` - which is why every object in that graph needs a
 * `__set_state()`.
 *
 * @internal not part of public API.
 */
final class SettingsFileLoader
{
    public function __construct(
        #[Autowire(service: 'cache.core')]
        private readonly PhpFrontend $cache,
        private readonly PackageManager $packageManager,
    ) {}

    /**
     * @template T of object
     * @param non-empty-string $relativeFilePath Path of the settings file relative to the package root
     * @param non-empty-string $cacheIdentifier Entry identifier in the core cache
     * @param class-string<T> $settingsClassName Class of the normalised settings object
     * @param \Closure(array<string, mixed>): T $normalize Builds the settings object from the merged array
     * @return T
     */
    public function load(
        string $relativeFilePath,
        string $cacheIdentifier,
        string $settingsClassName,
        \Closure $normalize,
    ): object {
        $cached = $this->cache->require($cacheIdentifier);
        if ($cached instanceof $settingsClassName) {
            return $cached;
        }
        $settings = $normalize($this->loadMergedArray($relativeFilePath));
        $this->cache->set($cacheIdentifier, 'return ' . var_export($settings, true) . ';');
        return $settings;
    }

    /**
     * @param non-empty-string $relativeFilePath
     * @return array<string, mixed>
     */
    public function loadMergedArray(string $relativeFilePath): array
    {
        $loadedSettings = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $settingsFile = $package->getPackagePath() . $relativeFilePath;
            if (!file_exists($settingsFile)) {
                continue;
            }
            $settingsArray = Yaml::parseFile($settingsFile);
            if (!is_array($settingsArray)) {
                continue;
            }
            $loadedSettings = array_merge($loadedSettings, $settingsArray);
        }
        return $loadedSettings;
    }
}
