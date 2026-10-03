<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\EventListener;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;

/**
 * Warming up the system caches builds the frontend icon registry, with the icons the
 * listeners of the collect event contribute, under the key core derives from the
 * package set. Warming up another group leaves it alone.
 *
 * The listener is called as the event dispatcher calls it, from its registration in the
 * listener provider, and not by dispatching the event: that would run every warmup
 * listener of core as well, and on TYPO3 v14 the warmup of the language files reads
 * files core itself deprecates.
 */
final class WarmUpFrontendIconRegistryTest extends AbstractAcademicBaseTestCase
{
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'fgtclb/academic-base',
        'tests/frontend-icons',
    ];

    #[Test]
    public function warmingUpTheSystemCachesBuildsTheRegistry(): void
    {
        $cache = $this->emptiedCoreCache();

        $this->callListener(new CacheWarmupEvent(['system']));

        $this->assertTrue($cache->has($this->cacheIdentifier()));
        $icons = $cache->require($this->cacheIdentifier());
        $this->assertIsArray($icons);
        $this->assertArrayHasKey('test-frontend-contributed', $icons);
        $this->assertArrayHasKey('default-not-found', $icons);
    }

    #[Test]
    public function warmingUpAnotherGroupLeavesTheRegistryAlone(): void
    {
        $cache = $this->emptiedCoreCache();

        $this->callListener(new CacheWarmupEvent(['pages']));

        $this->assertFalse($cache->has($this->cacheIdentifier()));
    }

    private function callListener(CacheWarmupEvent $event): void
    {
        $definitions = $this->get(ListenerProvider::class)->getAllListenerDefinitions()[CacheWarmupEvent::class] ?? [];
        $this->assertArrayHasKey('academic-base/warm-up-frontend-icon-registry', $definitions);
        $definition = $definitions['academic-base/warm-up-frontend-icon-registry'];
        $this->get($definition['service'])->{$definition['method'] ?? '__invoke'}($event);
    }

    private function emptiedCoreCache(): PhpFrontend
    {
        $cache = $this->get('cache.core');
        $this->assertInstanceOf(PhpFrontend::class, $cache);
        $cache->remove($this->cacheIdentifier());
        $this->assertFalse($cache->has($this->cacheIdentifier()));
        return $cache;
    }

    private function cacheIdentifier(): string
    {
        return $this->get(PackageDependentCacheIdentifier::class)->withPrefix('AcademicFrontendIcons')->toString();
    }
}
