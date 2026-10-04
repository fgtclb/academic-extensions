<?php

declare(strict_types=1);

namespace FGTCLB\TestingHelper\Tests\Functional\TestCase;

use FGTCLB\TestingHelper\TestCase\FunctionalTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\NullBackend;
use TYPO3\CMS\Core\Cache\CacheManager;

/**
 * What a test class configures for its instance wins over the defaults of the shared base
 * class, see {@see FunctionalTestCase}.
 *
 * `NullBackend` stands for any other backend. It is transient as well, so this class does
 * not bring back the defect the default exists for.
 */
final class FunctionalTestCaseConfigurationTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'extbase' => [
                        'backend' => NullBackend::class,
                    ],
                ],
            ],
        ],
    ];

    #[Test]
    public function theCacheBackendTheClassConfiguresWins(): void
    {
        $this->assertSame(
            NullBackend::class,
            $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase']['backend'] ?? null,
        );
        $this->assertInstanceOf(
            NullBackend::class,
            $this->get(CacheManager::class)->getCache('extbase')->getBackend(),
        );
    }
}
