<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Event;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use TYPO3\CMS\Core\Imaging\IconProviderInterface;

/**
 * Dispatched once each time the frontend icon registry is built, before the
 * `Configuration/FrontendIcons.php` files of the active packages are read, so a
 * listener can contribute icons it computes from code rather than ships in a file.
 *
 * The registry is cached with the system caches, so the event is not dispatched per
 * request: a listener runs when the cache is built or warmed up, and what it
 * contributes changes only after the system caches are flushed. An entry of a
 * `Configuration/FrontendIcons.php` with the same identifier replaces the contributed
 * icon, whatever the loading order of the two packages, so a site package can always
 * replace what a listener contributes.
 *
 * Built by {@see FrontendIconRegistry}.
 *
 * @api
 */
final class CollectFrontendIconsEvent
{
    /**
     * @var array<string, array<string, mixed>>
     */
    private array $icons = [];

    /**
     * Contributes the icon `$identifier`, rendered by `$providerClass` with `$options`,
     * the options of an entry of `Configuration/FrontendIcons.php` without `provider`.
     * A later call for the same identifier replaces the earlier one.
     *
     * @param class-string<IconProviderInterface> $providerClass
     * @param array<string, mixed> $options
     * @throws \InvalidArgumentException when `$providerClass` is not an icon provider
     */
    public function addIcon(string $identifier, string $providerClass, array $options = []): void
    {
        if (!is_a($providerClass, IconProviderInterface::class, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The frontend icon "%s" was contributed with the provider "%s", which does not implement %s.',
                    $identifier,
                    $providerClass,
                    IconProviderInterface::class,
                ),
                1791061902,
            );
        }
        $this->icons[$identifier] = ['provider' => $providerClass] + $options;
    }

    /**
     * The contributed icons in the format of `Configuration/FrontendIcons.php`.
     *
     * @return array<string, array<string, mixed>>
     */
    public function getIcons(): array
    {
        return $this->icons;
    }
}
