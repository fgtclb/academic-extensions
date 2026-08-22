<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

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
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-bitejobs-stub');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
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
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/setup.typoscript',
                    'EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
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
