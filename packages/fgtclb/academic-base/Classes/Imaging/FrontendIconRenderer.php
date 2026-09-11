<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging;

use FGTCLB\AcademicBase\Event\ModifyFrontendIconAllowListEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders registered icons for a frontend page that composes its markup at runtime,
 * and decides which icons may leave the server that way at all.
 *
 * The backend icon API (`@typo3/backend/icons.js`) cannot be used in the frontend: it
 * fetches from a backend AJAX route that answers a logged-in backend user only. This
 * class is the server half of the frontend replacement, shared by the JSON map of
 * {@see \FGTCLB\AcademicBase\ViewHelpers\FrontendIconMapViewHelper} and the endpoint
 * below {@see self::ENDPOINT_PATH}, so both answer the same identifiers with the same
 * markup.
 *
 * An identifier is served when all of these hold:
 *
 * - it matches {@see self::IDENTIFIER_PATTERN};
 * - it starts with one of the allowed prefixes - `tx-academic` and `category_types.` by
 *   default, extended through {@see ModifyFrontendIconAllowListEvent}. Deliberately a
 *   prefix of the identifier and not a path of the source file: a project overriding an
 *   icon in its own `Configuration/Icons.php` points it at a file of its own, and a path
 *   filter would refuse exactly that override;
 * - it is registered, and not as deprecated - rendering a deprecated identifier raises
 *   `E_USER_DEPRECATED`, which a public endpoint would do once per request;
 * - its provider inlines an SVG file: a subclass of core's `AbstractSvgIconProvider`,
 *   but not the `SvgSpriteIconProvider` of the core icon set, whose markup is an
 *   `<svg><use>` into a sprite without a size of its own. A bitmap provider renders an
 *   `<img>` whose URL cannot be built correctly outside a page request.
 *
 * Everything is rendered with `render('inline')`, the markup a frontend template gets
 * from `<core:icon ... alternativeMarkupIdentifier="inline" />` - sanitised as far as
 * the provider of the icon sanitises: `CurrentColorSvgIconProvider` on both TYPO3
 * versions, core's `SvgIconProvider` on TYPO3 v14 only (v13 strips `<script>` elements
 * and nothing else). That is the exposure a server-rendered inline icon has as well; a
 * provider is registered by an integrator, never by a visitor.
 *
 * Stateless: the allow-list is built, and the event dispatched, per call.
 *
 * @internal Experimental until a consumer outside the academic extensions exists; it
 *           may change without a breaking change entry.
 */
final readonly class FrontendIconRenderer
{
    /**
     * The path of the endpoint, relative to the base of a site or a site language.
     */
    public const ENDPOINT_PATH = '_academic/icons.json';

    /**
     * `\A` and `\z` rather than `^` and `$`: `$` also matches before a trailing line
     * feed. The TypeScript icon factory of this extension carries the same pattern.
     */
    public const IDENTIFIER_PATTERN = '/\A[a-z0-9_][a-z0-9_.-]{0,99}\z/';

    /**
     * @var list<string>
     */
    public const DEFAULT_PREFIXES = ['tx-academic', 'category_types.'];

    public function __construct(
        private IconFactory $iconFactory,
        private IconRegistry $iconRegistry,
        private EventDispatcherInterface $eventDispatcher,
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
        $prefixes = $this->allowedPrefixes();
        $map = [];
        foreach ($identifiers as $identifier) {
            if (!is_string($identifier)
                || isset($map[$identifier])
                || !$this->isServableWith($prefixes, $identifier)
            ) {
                continue;
            }
            $map[$identifier] = $this->iconFactory
                ->getIcon($identifier, $size)
                ->render(AbstractSvgIconProvider::MARKUP_IDENTIFIER_INLINE);
        }

        return $map;
    }

    public function isServable(string $identifier): bool
    {
        return $this->isServableWith($this->allowedPrefixes(), $identifier);
    }

    /**
     * A token that changes whenever the markup of a servable icon can have changed. The
     * endpoint marks a response for the current token as immutable, so a page asks with
     * the token it was rendered with and a new token misses every cache. An immutable
     * response is never revalidated, so the `ETag` of the endpoint cannot make up for
     * anything the token misses: it only helps the responses cached briefly for any
     * other token.
     *
     * Built from the icon registry's public API, the modification time of each source
     * file - stat only, the files themselves are not read - the TYPO3 version, and the
     * package dependent cache identifier of core, which covers a changed provider class
     * as far as it arrives with a new `composer.lock`. Not seen is rendering code
     * changed in place without any of these.
     */
    public function getVersion(): string
    {
        $prefixes = $this->allowedPrefixes();
        $identifiers = array_values(array_filter(
            array_map('strval', $this->iconRegistry->getAllRegisteredIconIdentifiers()),
            fn(string $identifier): bool => $this->isServableWith($prefixes, $identifier),
        ));
        sort($identifiers);

        $fingerprint = [
            (new Typo3Version())->getVersion(),
            // @internal in TYPO3, like the AbstractSvgIconProvider this extension's
            // provider extends. Identical on 13.4 and 14.3: TYPO3 version, project path
            // and PackageManager::getCacheIdentifier() - a hash of composer.lock and the
            // dev mode in composer mode, of PackageStates.php otherwise. Re-read the
            // class on every core update, and replace it once core offers a public API.
            $this->packageDependentCacheIdentifier->toString(),
        ];
        foreach ($identifiers as $identifier) {
            $configuration = $this->iconRegistry->getIconConfigurationByIdentifier($identifier);
            $source = $configuration['options']['source'] ?? '';
            $path = is_string($source) ? GeneralUtility::getFileAbsFileName($source) : '';
            $modified = $path !== '' && is_file($path) ? filemtime($path) : false;
            $fingerprint[] = [$identifier, $configuration, $modified];
        }

        return hash('xxh3', serialize($fingerprint));
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
     * @return list<string>
     */
    private function allowedPrefixes(): array
    {
        $event = new ModifyFrontendIconAllowListEvent(self::DEFAULT_PREFIXES);
        $this->eventDispatcher->dispatch($event);

        return array_values(array_filter(
            $event->getPrefixes(),
            static fn(string $prefix): bool => $prefix !== '',
        ));
    }

    /**
     * @param list<string> $prefixes
     */
    private function isServableWith(array $prefixes, string $identifier): bool
    {
        if (preg_match(self::IDENTIFIER_PATTERN, $identifier) !== 1) {
            return false;
        }
        $allowed = false;
        foreach ($prefixes as $prefix) {
            if (str_starts_with($identifier, $prefix)) {
                $allowed = true;
                break;
            }
        }
        // isRegistered() first: it completes the registry's initialisation, which
        // isDeprecated() relies on without triggering it. getIconConfigurationByIdentifier()
        // raises the deprecation itself, so it is only reached for an identifier that
        // is not deprecated.
        if (!$allowed
            || !$this->iconRegistry->isRegistered($identifier)
            || $this->iconRegistry->isDeprecated($identifier)
        ) {
            return false;
        }
        $provider = $this->iconRegistry->getIconConfigurationByIdentifier($identifier)['provider'] ?? '';

        return is_string($provider)
            && is_a($provider, AbstractSvgIconProvider::class, true)
            && !is_a($provider, SvgSpriteIconProvider::class, true);
    }
}
