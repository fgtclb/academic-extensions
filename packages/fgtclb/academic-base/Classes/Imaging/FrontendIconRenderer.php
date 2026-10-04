<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Imaging\IconProvider\AbstractSvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Imaging\IconSize;

/**
 * Renders icons of the {@see FrontendIconRegistry} for a frontend page that composes
 * its markup at runtime, and decides which of them may leave the server that way.
 *
 * The backend icon API (`@typo3/backend/icons.js`) cannot be used in the frontend: it
 * fetches from a backend AJAX route that answers a logged-in backend user only, and it
 * serves the icon registry of TYPO3, which is a backend registry. This class is the
 * server half of the frontend replacement, used by the JSON map of
 * {@see \FGTCLB\AcademicBase\ViewHelpers\FrontendIconMapViewHelper}. It reads the
 * frontend icon registry of this extension only, never the icon registry of TYPO3.
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
     * `\A` and `\z` rather than `^` and `$`: `$` also matches before a trailing line
     * feed.
     */
    public const IDENTIFIER_PATTERN = '/\A[a-z0-9_][a-z0-9_.-]{0,99}\z/';

    public function __construct(
        private FrontendIconFactory $frontendIconFactory,
        private FrontendIconRegistry $frontendIconRegistry,
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
     * The size named by a string, the way a template or a query parameter spells it,
     * or `null` for anything else - `IconSize::OVERLAY` included, which is core's size
     * for an overlay icon and not one of a standalone icon.
     */
    public static function sizeFrom(string $value): ?IconSize
    {
        $size = IconSize::tryFrom($value);

        return $size === null || $size === IconSize::OVERLAY ? null : $size;
    }
}
