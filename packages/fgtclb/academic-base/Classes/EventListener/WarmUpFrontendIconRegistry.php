<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\EventListener;

use FGTCLB\AcademicBase\Imaging\FrontendIconRegistry;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;

/**
 * Builds the frontend icon registry when the system caches are warmed up, as
 * `IconRegistry::warmupCaches()` does for the icons of the backend, so the first
 * frontend request after a deployment does not build it.
 *
 * `cache:warmup` boots the whole container before it dispatches the event, so the
 * listeners of the event the registry dispatches while it is built are registered.
 *
 * @internal not part of public API.
 */
#[AsEventListener(identifier: 'academic-base/warm-up-frontend-icon-registry')]
final readonly class WarmUpFrontendIconRegistry
{
    public function __construct(
        private FrontendIconRegistry $frontendIconRegistry,
    ) {}

    public function __invoke(CacheWarmupEvent $event): void
    {
        if ($event->hasGroup('system')) {
            $this->frontendIconRegistry->warmup();
        }
    }
}
