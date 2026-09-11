<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBase\Tests\Functional\Middleware;

use FGTCLB\AcademicBase\Tests\Functional\AbstractAcademicBaseTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * The endpoint sits after the maintenance mode middleware, so a site in maintenance
 * answers it with `503` like every page. A class of its own because the maintenance
 * mode is instance configuration, which every sub-request reads anew.
 *
 * `devIPmask` is emptied: the sub-request comes from `127.0.0.1`, which the default
 * mask exempts from the maintenance mode.
 */
final class FrontendIconEndpointMaintenanceTest extends AbstractAcademicBaseTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected array $testExtensionsToLoad = [
        'fgtclb/academic-base',
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'devIPmask' => '',
        ],
        'FE' => [
            'debug' => false,
            'pageUnavailable_force' => true,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Database/FrontendIconEndpointPages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: ['setup' => ['EXT:academic_base/Tests/Functional/Fixtures/TypoScript/page.typoscript']],
        );
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(rootPageId: 1, base: 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function answersASiteInMaintenanceWith503(): void
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://www.acme.com/_academic/icons.json?i=tx-academicbase-action-add'),
        );

        $this->assertSame(503, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('ETag'));
        $this->assertStringNotContainsString('tx-academicbase-action-add', (string)$response->getBody());
    }
}
