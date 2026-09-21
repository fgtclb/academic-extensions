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
 * loading order, and the arrays are folded together recursively by
 * {@see merge()}: a later package changes only the keys it names, at any
 * depth, a value that is not a map replaces what the earlier package had, and
 * `null` removes a key. The merged array is handed to the normaliser of the
 * calling extension, and the object it returns is written to the core cache as
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
        foreach ($this->loadPackageArrays($relativeFilePath) as $settingsArray) {
            $loadedSettings = $this->merge($loadedSettings, $settingsArray);
        }
        return $loadedSettings;
    }

    /**
     * Folds one settings array onto the one the packages before it produced.
     *
     * Public because a consumer that folds the package arrays itself - the
     * settings migration command of academic_persons, which needs the state
     * per package rather than the end result - has to fold them the way the
     * runtime does.
     *
     * Two maps are merged key by key, so a package states only what it changes.
     * Everything else is a value and is replaced: a list has no key to merge by -
     * merging `[required]` with `[readonly]` would make dropping `required`
     * impossible - and a pair of different types has nothing to merge either. A
     * PHP list is what `array_is_list()` says it is, so a map whose keys happen to
     * be `0 ... n-1` is one as well - an empty array is a list too, which is how
     * a flag list is cleared and a map is emptied. `null` is not a value but the
     * instruction to drop the key, at any depth and whether or not an earlier
     * package named it: a `null` always means "not configured". A map that holds
     * nothing but removed keys stays as an empty map; only the keys named `null`
     * are dropped, never their parent.
     *
     * The key order of the result is the later order when the later map names
     * every key of the earlier one, which keeps a reordered full copy - the shape
     * an override had while the merge was shallow - in its own order. A map that
     * names only some keys must not move them, so the earlier order is kept and
     * the keys only the later map names are appended.
     *
     * @param array<string, mixed> $earlier
     * @param array<string, mixed> $later
     * @return array<string, mixed>
     */
    public function merge(array $earlier, array $later): array
    {
        $merged = array_diff_key($earlier, $later) === [] ? [] : $earlier;
        foreach ($later as $key => $value) {
            if ($value === null) {
                unset($merged[$key]);
                continue;
            }
            $earlierValue = $earlier[$key] ?? null;
            if (is_array($value) && !array_is_list($value)) {
                $merged[$key] = $this->merge(
                    is_array($earlierValue) && !array_is_list($earlierValue) ? $earlierValue : [],
                    $value,
                );
                continue;
            }
            $merged[$key] = $value;
        }
        return $merged;
    }

    /**
     * The file of every active package that ships it, keyed by package key
     * and in package loading order - the order the merge folds them in. A
     * consumer that needs to know which package contributed what, such as a
     * migration report, walks this instead of the merged array.
     *
     * @param non-empty-string $relativeFilePath
     * @return array<string, array<string, mixed>>
     */
    public function loadPackageArrays(string $relativeFilePath): array
    {
        $packageArrays = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            $settingsFile = $package->getPackagePath() . $relativeFilePath;
            if (!file_exists($settingsFile)) {
                continue;
            }
            $settingsArray = Yaml::parseFile($settingsFile);
            if (!is_array($settingsArray)) {
                continue;
            }
            $packageArrays[$package->getPackageKey()] = $settingsArray;
        }
        return $packageArrays;
    }
}
