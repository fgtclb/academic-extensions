<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Functional\TestCase;

use FGTCLB\TestingHelper\TestCase\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;
use TYPO3\CMS\Core\Cache\CacheManager;

/**
 * A test instance built by the shared base class keeps the Extbase class schema cache in
 * memory, see {@see FunctionalTestCase}.
 */
final class FunctionalTestCaseTest extends FunctionalTestCase
{
    #[Test]
    public function theExtbaseCacheOfTheInstanceIsKeptInMemory(): void
    {
        $this->assertSame(
            TransientMemoryBackend::class,
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase']['backend'] ?? null,
        );
        $this->assertInstanceOf(
            TransientMemoryBackend::class,
            $this->get(CacheManager::class)->getCache('extbase')->getBackend(),
        );
    }
}
