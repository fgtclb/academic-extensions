<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging;

use FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * The icons of the frontend, separate from the icon registry of TYPO3, which is built
 * for the backend and loads every core icon, the record icons and the flags with it.
 * Neither registry reads the other.
 *
 * Every active package may ship `Configuration/FrontendIcons.php`, in the format of
 * `Configuration/Icons.php`. The listeners of {@see CollectFrontendIconsEvent} come
 * first, then the files in package loading order, each merged with `array_merge()`:
 * a later package replaces the whole configuration of an identifier, and a file
 * replaces a contributed icon whatever the order of the two packages. The result is
 * normalised the way core normalises `Icons.php` in
 * `ServiceProvider::configureIconRegistry()`.
 *
 * The normalised registry is written to `cache.core` as `return <var_export>;`, under
 * a key that core's {@see PackageDependentCacheIdentifier} derives from the TYPO3
 * version, the project path and the package set, the way core keys its own `Icons_`
 * entry. A changed package set therefore reads a new entry without a flush, and an
 * edited file is read after the system caches are flushed. `cache:warmup` builds it
 * through {@see \FGTCLB\AcademicBase\EventListener\WarmUpFrontendIconRegistry}.
 *
 * Stateless: every lookup reads the cache entry, an OPcache'd file whose array is
 * immutable, and {@see FrontendIconFactory} asks once per icon and request.
 *
 * @internal not part of public API. The file format and the event are.
 */
#[Autoconfigure(public: true)]
final readonly class FrontendIconRegistry
{
    private const FILE = 'Configuration/FrontendIcons.php';
    private const CACHE_IDENTIFIER_PREFIX = 'AcademicFrontendIcons';

    public function __construct(
        #[Autowire(service: 'cache.core')]
        private PhpFrontend $cache,
        private PackageManager $packageManager,
        private EventDispatcherInterface $eventDispatcher,
        private PackageDependentCacheIdentifier $packageDependentCacheIdentifier,
    ) {}

    public function isRegistered(string $identifier): bool
    {
        return isset($this->getIcons()[$identifier]);
    }

    /**
     * @return array{provider: class-string<IconProviderInterface>, options: array<string, mixed>}|null
     */
    public function getIconConfiguration(string $identifier): ?array
    {
        return $this->getIcons()[$identifier] ?? null;
    }

    /**
     * Every identifier the registry knows, in the order the registry was built:
     * contributed icons first, then the files in package loading order. An identifier
     * a later package replaced keeps the position of its first registration, which
     * is how `array_merge()` treats a string key.
     *
     * @return list<string>
     */
    public function getAllRegisteredIconIdentifiers(): array
    {
        return array_keys($this->getIcons());
    }

    /**
     * Builds the registry and writes it to the cache, whether or not an entry exists.
     */
    public function warmup(): void
    {
        $this->cache->set($this->getCacheIdentifier(), $this->export($this->build()));
    }

    /**
     * @return array<string, array{provider: class-string<IconProviderInterface>, options: array<string, mixed>}>
     */
    private function getIcons(): array
    {
        $cacheIdentifier = $this->getCacheIdentifier();
        $icons = $this->cache->require($cacheIdentifier);
        if (is_array($icons)) {
            /** @var array<string, array{provider: class-string<IconProviderInterface>, options: array<string, mixed>}> $icons */
            return $icons;
        }
        $icons = $this->build();
        $this->cache->set($cacheIdentifier, $this->export($icons));
        return $icons;
    }

    /**
     * @return array<string, array{provider: class-string<IconProviderInterface>, options: array<string, mixed>}>
     */
    private function build(): array
    {
        $event = new CollectFrontendIconsEvent();
        $this->eventDispatcher->dispatch($event);
        $icons = $event->getIcons();
        $requireFile = static fn(string $file): mixed => require $file;
        foreach ($this->packageManager->getActivePackages() as $package) {
            $file = $package->getPackagePath() . self::FILE;
            if (!file_exists($file)) {
                continue;
            }
            $packageIcons = $requireFile($file);
            if (!is_array($packageIcons)) {
                continue;
            }
            $icons = array_merge($icons, $packageIcons);
        }
        return $this->normalize($icons);
    }

    /**
     * @param array<array-key, mixed> $icons
     * @return array<string, array{provider: class-string<IconProviderInterface>, options: array<string, mixed>}>
     */
    private function normalize(array $icons): array
    {
        $normalized = [];
        foreach ($icons as $identifier => $options) {
            if (!is_array($options)) {
                continue;
            }
            $provider = $options['provider'] ?? null;
            unset($options['provider']);
            if ($provider === null && is_string($options['source'] ?? null) && $options['source'] !== '') {
                $provider = $this->detectProvider($options['source']);
            }
            if ($provider === null) {
                continue;
            }
            if (!is_string($provider) || !is_a($provider, IconProviderInterface::class, true)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'The frontend icon "%s" names the provider "%s", which does not implement %s.',
                        $identifier,
                        is_string($provider) ? $provider : get_debug_type($provider),
                        IconProviderInterface::class,
                    ),
                    1791061901,
                );
            }
            /** @var array<string, mixed> $options */
            $normalized[(string)$identifier] = ['provider' => $provider, 'options' => $options];
        }
        return $normalized;
    }

    /**
     * The rule of {@see IconRegistry::detectIconProvider()}, copied so that building the
     * frontend registry never builds the backend one.
     *
     * @return class-string<IconProviderInterface>
     */
    private function detectProvider(string $source): string
    {
        if (str_ends_with(strtolower($source), 'svg')) {
            return SvgIconProvider::class;
        }
        return BitmapIconProvider::class;
    }

    private function getCacheIdentifier(): string
    {
        return $this->packageDependentCacheIdentifier->withPrefix(self::CACHE_IDENTIFIER_PREFIX)->toString();
    }

    /**
     * @param array<string, array{provider: class-string<IconProviderInterface>, options: array<string, mixed>}> $icons
     */
    private function export(array $icons): string
    {
        return 'return ' . var_export($icons, true) . ';';
    }
}
