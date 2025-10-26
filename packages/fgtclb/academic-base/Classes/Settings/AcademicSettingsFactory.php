<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Settings;

use FGTCLB\AcademicBase\DependencyInjection\AcademicSettingsCompilerPass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Property\PropertyMapper;

/**
 * Provides the factory class to create and warmup academic extension settings.
 *
 * @internal to be used only within academic extensions and not part of public API.
 */
final class AcademicSettingsFactory
{
    /**
     * @template T of AcademicSettingsInterface
     * @var array<string, array{settingsFileName: string, packageKey: string}>
     */
    private array $settingDefinitions = [];

    public function __construct(
        #[Autowire('@cache.academic_settings')]
        private readonly FrontendInterface $settingsCache,
        private readonly PackageDependentCacheIdentifier $packageDependentCacheIdentifier,
        private readonly PackageManager $packageManager,
        private readonly PropertyMapper $propertyMapper,
    ) {}

    /**
     * @internal only used to register settings definition in {@see AcademicSettingsCompilerPass::process()}.
     */
    public function addSettingDefinition(string $academicSettingsClassName, string $settingsFileName, string $packageKey): void
    {
        $this->settingDefinitions[$academicSettingsClassName] = [
            'settingsFileName' => $settingsFileName,
            'packageKey' => $packageKey,
        ];
    }

    /**
     * @template T of AcademicSettingsInterface
     * @param class-string<T> $academicSettingsClassName
     * @return T
     */
    public function get(string $academicSettingsClassName): AcademicSettingsInterface
    {
        $info = $this->validate($academicSettingsClassName);
        $package = $info['package'];
        $settingsFileName = $info['settingsFileName'];
        $cacheIdentifier = $this->createCacheIdentifier($settingsFileName);
        $settings = $this->getCache($cacheIdentifier);
        if ($settings instanceof AcademicSettingsInterface
            && $settings instanceof $academicSettingsClassName
        ) {
            return $settings;
        }
        return $this->buildSettingsInstance($academicSettingsClassName, $settingsFileName, $package, $cacheIdentifier);
    }

    #[AsEventListener]
    public function warmup(CacheWarmupEvent $event): void
    {
        $groups = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['academic_settings']['groups'] ?? ['system'];
        $shouldWarmup = false;
        foreach ($groups as $group) {
            if ($event->hasGroup($group)) {
                $shouldWarmup = true;
                break;
            }
        }
        if (!$shouldWarmup) {
            // Cache group not selected for cache warmup, skip.
            return;
        }
        foreach (array_keys($this->settingDefinitions) as $academicSettingsClassName) {
            /** @var class-string<AcademicSettingsInterface> $academicSettingsClassName */
            $this->build($academicSettingsClassName);
        }
    }

    /**
     * @template T of AcademicSettingsInterface
     * @param class-string<T> $academicSettingsClassName
     */
    private function build(string $academicSettingsClassName): void
    {
        $info = $this->validate($academicSettingsClassName);
        $package = $info['package'];
        $settingsFileName = $info['settingsFileName'];
        $cacheIdentifier = $this->createCacheIdentifier($settingsFileName);
        $this->buildSettingsInstance($academicSettingsClassName, $settingsFileName, $package, $cacheIdentifier);
    }

    /**
     * @template T of AcademicSettingsInterface
     * @param class-string<T> $academicSettingsClassName
     * @return T
     */
    private function buildSettingsInstance(
        string $academicSettingsClassName,
        string $settingsFileName,
        PackageInterface $package,
        string $cacheIdentifier,
    ): AcademicSettingsInterface {
        $settingsArray = $this->loadRawConfigurationData($settingsFileName, $package);
        $settings = $this->propertyMapper->convert(
            $settingsArray,
            $academicSettingsClassName,
        );
        $isValidInstance = (
            is_object($settings)
            && $settings instanceof AcademicSettingsInterface
            && $settings instanceof $academicSettingsClassName
        );
        if (!$isValidInstance) {
            throw new \RuntimeException(
                sprintf(
                    'Could not map configuration for "%s" to "%s".',
                    $package->getPackageKey(),
                    $academicSettingsClassName,
                ),
                1760963714,
            );
        }
        $this->setCache($cacheIdentifier, $settings);
        return $settings;
    }

    /**
     * @param PackageInterface $package
     * @return array<int|string, mixed>
     */
    private function loadRawConfigurationData(string $settingsFileName, PackageInterface $package): array
    {
        /** @var array<int|string, mixed> $settings */
        $settings = [];
        // Load root package configuration first.
        $settings = $this->loadRawConfiguration(
            $settings,
            $package->getPackagePath() . $settingsFileName,
        );
        // Iterate all active packages and load configuration from package if it exists, merging it together.
        foreach ($this->packageManager->getActivePackages() as $activePackage) {
            $settings = $this->loadRawConfiguration(
                $settings,
                $activePackage->getPackagePath() . $settingsFileName,
            );
        }
        return $settings;
    }

    /**
     * @param array<int|string, mixed> $settings
     * @param string $filePath
     * @return array<int|string, mixed>
     */
    private function loadRawConfiguration(array $settings, string $filePath): array
    {
        if (!file_exists($filePath)) {
            return $settings;
        }
        $settingsArray = Yaml::parseFile($filePath);
        if ($settingsArray === null) {
            return $settings;
        }
        return array_merge(
            $settings,
            $settingsArray,
        );
    }

    private function createCacheIdentifier(string $extensionKey): string
    {
        return $this->packageDependentCacheIdentifier
            ->withPrefix('academic-settings')
            ->withAdditionalHashedIdentifier($extensionKey)
            ->toString();
    }

    /**
     * @param string $academicSettingsClassName
     * @return array{
     *     settingsFileName: string,
     *     package: PackageInterface,
     * }
     */
    private function validate(string $academicSettingsClassName): array
    {
        if (class_exists($academicSettingsClassName) === false) {
            throw new \RuntimeException(
                sprintf(
                    'Class "%s" does not exist and cannot be created.',
                    $academicSettingsClassName,
                ),
                1760954044,
            );
        }
        if (is_a($academicSettingsClassName, AcademicSettingsInterface::class, true) === false) {
            throw new \RuntimeException(
                sprintf(
                    'Class "%s" does not implement "%s".',
                    $academicSettingsClassName,
                    AcademicSettingsInterface::class,
                ),
                1760954084,
            );
        }
        if (isset($this->settingDefinitions[$academicSettingsClassName]) === false) {
            throw new \RuntimeException(
                'No settings definition registered for "%s". Please use "%s" attribute and configure all settings.',
                1760976877,
            );
        }
        $package = $this->getPackageInfo($this->settingDefinitions[$academicSettingsClassName]['packageKey']);
        if ($package === null) {
            throw new \RuntimeException(
                sprintf(
                    'Class "%s" specifies composer package "%s", which is not active.',
                    $academicSettingsClassName,
                    $this->settingDefinitions[$academicSettingsClassName]['packageKey'],
                ),
                1760954340,
            );
        }
        return [
            'settingsFileName' => $this->settingDefinitions[$academicSettingsClassName]['settingsFileName'],
            'package' => $package,
        ];
    }

    private function getPackageInfo(string $packageKey): ?PackageInterface
    {
        foreach ($this->packageManager->getActivePackages() as $activePackage) {
            if ($activePackage->getPackageKey() === $packageKey
                || $activePackage->getValueFromComposerManifest('name') === $packageKey
            ) {
                return $activePackage;
            }
        }
        return null;
    }

    private function setCache(string $identifier, mixed $value): void
    {
        $this->settingsCache->set(
            $identifier,
            $this->settingsCache instanceof PhpFrontend
                ? 'return ' . var_export($value, true) . ';'
                : $value
        );
    }

    private function getCache(string $identifier): mixed
    {
        if ($this->settingsCache instanceof PhpFrontend) {
            return $this->settingsCache->has($identifier) ? $this->settingsCache->require($identifier) : null;
        }
        return $this->settingsCache->get($identifier);
    }
}
