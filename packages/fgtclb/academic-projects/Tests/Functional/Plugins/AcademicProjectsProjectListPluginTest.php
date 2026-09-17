<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicprojects_projectlist` plugin in the frontend.
 *
 * Projects are pages of doktype 30 mapped onto the `pages` table, so the fixtures are page
 * records carrying the `tx_academicprojects_*` columns.
 */
final class AcademicProjectsProjectListPluginTest extends AbstractAcademicProjectsTestCase
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
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_projects/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_projects/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
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
    public function projectListPluginRendersAllVisibleProjects(): void
    {
        $this->setUpTestCase('projectListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-projects-list', $content);
        // The project title column takes precedence over the page title, see `Project/Item`.
        $this->assertStringContainsString('Quantum research project', $content);
        $this->assertStringContainsString('Solar fields', $content);
        $this->assertStringContainsString('Photovoltaics on former farmland.', $content);
        $this->assertStringNotContainsString('Hidden lab', $content);
    }

    /**
     * The short description is rich text and goes through the site's "lib.parseFunc_RTE",
     * so the editor's link to a page reaches the visitor as the page's URL, not as "t3://".
     */
    #[Test]
    public function projectListPluginResolvesAPageLinkInTheShortDescription(): void
    {
        $this->setUpTestCase('projectListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<a href="/solar-fields">the farmland project</a>', $content);
        $this->assertStringNotContainsString('t3://', $content);
    }
}
