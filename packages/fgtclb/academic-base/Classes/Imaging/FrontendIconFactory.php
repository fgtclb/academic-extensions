<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Imaging;

use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Imaging\IconState;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Creates an icon of the {@see FrontendIconRegistry}, the way {@see IconFactory::getIcon()}
 * creates one of the backend registry, so the markup is the same.
 *
 * An identifier the registry does not know renders {@see self::NOT_FOUND_IDENTIFIER},
 * which academic_base registers in its own `Configuration/FrontendIcons.php`. There is
 * no fallback to the backend registry.
 *
 * The provider is taken from the container when it is a service there, as core does.
 * TYPO3 v14 publishes every icon provider and calls the `inject*()` setters of
 * `AbstractSvgIconProvider`, so a provider created with `new` fails on its first inline
 * render. TYPO3 v13 has no such services, and `GeneralUtility::makeInstance()` is
 * enough there.
 *
 * A prepared icon is kept in the runtime cache, so an icon repeated on a page reads and
 * sanitises its file once. Unlike core, every call returns a copy: a title set on one
 * icon never reaches the next rendering of the same icon.
 *
 * @internal not part of public API.
 */
#[Autoconfigure(public: true)]
final readonly class FrontendIconFactory
{
    public const NOT_FOUND_IDENTIFIER = 'default-not-found';

    public function __construct(
        private FrontendIconRegistry $frontendIconRegistry,
        private ContainerInterface $container,
        #[Autowire(service: 'cache.runtime')]
        private FrontendInterface $runtimeCache,
    ) {}

    public function getIcon(
        string $identifier,
        IconSize $size = IconSize::MEDIUM,
        ?string $overlayIdentifier = null,
        ?IconState $state = null,
    ): FrontendIcon {
        $cacheIdentifier = 'academic-frontend-icon-' . hash('xxh3', $identifier . $size->value . $overlayIdentifier . ($state->value ?? ''));
        $icon = $this->runtimeCache->get($cacheIdentifier);
        if ($icon instanceof FrontendIcon) {
            return clone $icon;
        }

        $iconConfiguration = $this->frontendIconRegistry->getIconConfiguration($identifier);
        if ($iconConfiguration === null) {
            $identifier = self::NOT_FOUND_IDENTIFIER;
            $iconConfiguration = $this->frontendIconRegistry->getIconConfiguration($identifier);
        }
        if ($iconConfiguration === null) {
            throw new \LogicException(
                sprintf(
                    'The frontend icon "%s" is not registered. academic_base registers it in its'
                    . ' Configuration/FrontendIcons.php, so a package that replaces it has to name a provider or a source.',
                    self::NOT_FOUND_IDENTIFIER,
                ),
                1791061903,
            );
        }

        $icon = new FrontendIcon();
        $icon->setIdentifier($identifier);
        $icon->setSize($size);
        $icon->setState($state ?? IconState::STATE_DEFAULT);
        if (!empty($overlayIdentifier)) {
            $icon->setOverlayIcon($this->getIcon($overlayIdentifier, IconSize::OVERLAY));
        }
        if (!empty($iconConfiguration['options']['spinning'])) {
            $icon->setSpinning(true);
        }
        if (!empty($iconConfiguration['options']['bidi'])) {
            $icon->setBidi(true);
        }

        /** @var IconProviderInterface $iconProvider */
        $iconProvider = $this->container->has($iconConfiguration['provider'])
            ? $this->container->get($iconConfiguration['provider'])
            : GeneralUtility::makeInstance($iconConfiguration['provider']);
        $iconProvider->prepareIconMarkup($icon, $iconConfiguration['options']);

        $this->runtimeCache->set($cacheIdentifier, $icon);

        return clone $icon;
    }
}
