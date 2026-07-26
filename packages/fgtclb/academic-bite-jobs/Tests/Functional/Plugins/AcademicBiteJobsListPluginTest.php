<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;

/**
 * Renders the `academicbitejobs_list` plugin in the frontend.
 *
 * This guards the `record` view variable: TYPO3 v14 renders the header of the
 * `EXT:fluid_styled_content` `Header/All` partial with `{record -> f:render.text(...)}`,
 * which raises an exception when no record object is available. Extbase plugin views
 * assign only `data`, so without the record the plugin fails to render on v14, while it
 * still renders on v13 whose partial reads `data`.
 *
 * The `test_bitejobs_stub` fixture extension replaces the core request factory, so no
 * outgoing HTTP request to the b-ite API is performed.
 */
final class AcademicBiteJobsListPluginTest extends AbstractAcademicBiteJobsTestCase
{
    use SiteBasedTestTrait;

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'subrequestPageErrors' => true,
            ],
        ],
        'FE' => [
            'debug' => false,
        ],
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->coreExtensionsToLoad = array_unique([
            ...array_values($this->coreExtensionsToLoad),
            'typo3/cms-fluid-styled-content',
        ]);
        $this->testExtensionsToLoad = array_unique([
            ...array_values($this->testExtensionsToLoad),
            'tests/test-bitejobs-stub',
        ]);
        parent::setUp();
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicBiteJobsListPlugin/biteJobsListPage.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://www.acme.com/',
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(
                    identifier: 'EN',
                    base: '/',
                ),
            ],
        );
    }

    private function renderHomePage(): string
    {
        $response = $this->executeFrontendSubRequest(
            new InternalRequest('https://www.acme.com/home'),
            new InternalRequestContext(),
        );
        $this->assertSame(200, $response->getStatusCode());

        return (string)$response->getBody();
    }

    #[Test]
    public function biteJobsListPluginIsRendered(): void
    {
        $this->setUpTestCase();

        $this->assertStringContainsString('academic-bite-jobs-list', $this->renderHomePage());
    }

    #[Test]
    public function biteJobsListPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase();
        // Rendering a header is what requires the `record` view variable on TYPO3 v14.
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => 'Open positions'], ['uid' => 1]);

        $this->assertStringContainsString('Open positions', $this->renderHomePage());
    }
}
