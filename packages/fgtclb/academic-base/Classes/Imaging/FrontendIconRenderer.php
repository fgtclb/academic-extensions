<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders icons of the {@see FrontendIconRegistry} for a frontend page that composes
 * its markup at runtime, and decides which of them may leave the server that way.
 *
 * The backend icon API (`@typo3/backend/icons.js`) cannot be used in the frontend: it
 * fetches from a backend AJAX route that answers a logged-in backend user only, and it
 * serves the icon registry of TYPO3, which is a backend registry. This class is the
 * server half of the frontend replacement, shared by the JSON map of
 * {@see \FGTCLB\AcademicBase\ViewHelpers\FrontendIconMapViewHelper} and the endpoint
 * below {@see self::ENDPOINT_PATH}, so both answer the same identifiers with the same
 * markup. It reads the frontend icon registry of this extension only, never the icon
 * registry of TYPO3.
 *
 * The frontend registry is the allow-list. It holds what the `Configuration/FrontendIcons.php`
 * files and the listeners of `CollectFrontendIconsEvent` put there for visitors, so an
 * identifier is served when all of these hold:
 *
 * - it matches {@see self::IDENTIFIER_PATTERN};
 * - it is registered in the frontend registry, and it is not
 *   {@see FrontendIconFactory::NOT_FOUND_IDENTIFIER}: the placeholder is what a template
 *   renders for an unknown identifier, and a script gets no answer for one instead;
 * - its provider inlines an SVG file: a subclass of core's `AbstractSvgIconProvider`,
 *   but not the `SvgSpriteIconProvider`, whose markup is an `<svg><use>` into a sprite
 *   without a size of its own. A bitmap provider renders an `<img>` whose URL cannot be
 *   built correctly outside a page request, and a font provider of a third-party
 *   extension inlines nothing either.
 *
 * Everything is rendered with `render('inline')`, the markup a frontend template gets
 * from `<ab:icon ... alternativeMarkupIdentifier="inline" />` - sanitised as far as the
 * provider of the icon sanitises: `CurrentColorSvgIconProvider` on both TYPO3 versions,
 * core's `SvgIconProvider` on TYPO3 v14 only (v13 strips `<script>` elements and nothing
 * else). That is the exposure a server-rendered inline icon has as well, since a provider
 * is registered by an integrator, never by a visitor.
 *
 * Stateless: every call asks the registry, whose entry is a cached file.
 *
 * @internal not part of public API. It is the implementation behind the frontend icon
 *           API, whose contract the extension points page of the manual lists.
 */
#[Autoconfigure(public: true)]
final readonly class FrontendIconRenderer
{
    /**
     * The path of the endpoint, relative to the base of a site or a site language.
     */
    public const ENDPOINT_PATH = '_academic/icons.json';

    /**
     * `\A` and `\z` rather than `^` and `$`: `$` also matches before a trailing line
     * feed.
     */
    public const IDENTIFIER_PATTERN = '/\A[a-z0-9_][a-z0-9_.-]{0,99}\z/';

    public function __construct(
        private FrontendIconFactory $frontendIconFactory,
        private FrontendIconRegistry $frontendIconRegistry,
        private PackageDependentCacheIdentifier $packageDependentCacheIdentifier,
    ) {}

    /**
     * The markup of every identifier that may be served, keyed by identifier, in the
     * order asked for. An identifier that may not be served is left out rather than
     * answered with the `default-not-found` placeholder.
     *
     * @param iterable<mixed> $identifiers
     * @return array<string, string>
     */
    public function render(iterable $identifiers, IconSize $size = IconSize::SMALL): array
    {
        $map = [];
        foreach ($identifiers as $identifier) {
            if (!is_string($identifier) || isset($map[$identifier]) || !$this->isServable($identifier)) {
                continue;
            }
            $map[$identifier] = $this->frontendIconFactory
                ->getIcon($identifier, $size)
                ->render(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE);
        }

        return $map;
    }

    public function isServable(string $identifier): bool
    {
        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1
            || $identifier === FrontendIconFactory::NOT_FOUND_IDENTIFIER
        ) {
            return false;
        }
        $provider = $this->frontendIconRegistry->getIconConfiguration($identifier)['provider'] ?? null;

        return $provider !== null
            && is_a($provider, AbstractSvgIconProvider::class, true)
            && !is_a($provider, SvgSpriteIconProvider::class, true);
    }

    /**
     * A token that changes whenever the markup of a served icon can have changed. The
     * endpoint marks a response for the current token as immutable, so a page asks with
     * the token it was rendered with and a new token misses every cache. An immutable
     * response is never revalidated, so the `ETag` of the endpoint cannot make up for
     * anything the token misses: it only helps the responses cached briefly for any
     * other token.
     *
     * Built from the frontend icon registry, for every served identifier: the
     * identifier, its registration and the modification time of its source file, and
     * for every provider of them the modification time of the files of the provider
     * class and of each of its parents - stat only, no file is read. Plus the package
     * dependent cache identifier of TYPO3, which covers a TYPO3 update, another project
     * path and, in composer mode, every change of `composer.lock`. Not seen is other
     * rendering code edited in place without any of these: the icon wrapper of TYPO3,
     * the SVG sanitiser library.
     */
    public function getVersion(): string
    {
        $icons = [];
        $providers = [];
        foreach ($this->frontendIconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            $configuration = $this->frontendIconRegistry->getIconConfiguration($identifier);
            if ($configuration === null || !$this->isServable($identifier)) {
                continue;
            }
            $source = $configuration['options']['source'] ?? '';
            $path = is_string($source) ? GeneralUtility::getFileAbsFileName($source) : '';
            $icons[$identifier] = [$configuration, $path !== '' && is_file($path) ? filemtime($path) : false];
            $providers[$configuration['provider']] ??= $this->classFileTimes($configuration['provider']);
        }
        // The order of the packages decides the order of the registry, not the markup.
        ksort($icons);
        ksort($providers);

        return hash('xxh3', serialize([
            // @internal in TYPO3, like the AbstractSvgIconProvider the providers extend, and
            // the key the frontend registry caches itself under. Identical on 13.4 and 14.3:
            // TYPO3 version, project path and PackageManager::getCacheIdentifier(), a hash
            // of the lock data and the dev mode in composer mode, of PackageStates.php
            // otherwise. Re-read the class on every core update.
            $this->packageDependentCacheIdentifier->toString(),
            $icons,
            $providers,
        ]));
    }

    /**
     * The size named by a string, the way a template or a query parameter spells it,
     * or `null` for anything else - `IconSize::OVERLAY` included, which is core's size
     * for an overlay icon and not one of a standalone icon.
     */
    public static function sizeFrom(string $value): ?IconSize
    {
        $size = IconSize::tryFrom($value);

        return $size === null || $size === IconSize::OVERLAY ? null : $size;
    }

    /**
     * The modification time of the file of a class and of each of its parents, so an
     * icon provider changed in place, or the core class it extends, changes the token.
     *
     * @param class-string $class
     * @return array<class-string, int|false>
     */
    private function classFileTimes(string $class): array
    {
        $times = [];
        for ($reflection = new \ReflectionClass($class); $reflection !== false; $reflection = $reflection->getParentClass()) {
            $file = $reflection->getFileName();
            $times[$reflection->getName()] = $file !== false && is_file($file) ? filemtime($file) : false;
        }

        return $times;
    }
}
