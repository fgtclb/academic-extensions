<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicprojects_projectlist` and `academicprojects_projectlistsingle` plugins
 * in the frontend.
 *
 * Projects are pages of doktype 30 mapped onto the `pages` table, so the fixtures are page
 * records carrying the `tx_academicprojects_*` columns. Both plugins run the same
 * `ProjectController::listAction()` and differ only in their content element configuration:
 * `academicprojects_projectlistsingle` reads the selected projects from the `pages` field of
 * the content element, while `academicprojects_projectlist` uses it as a page restriction.
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

    #[Test]
    public function projectListPluginRendersTheFilterAndSortingForm(): void
    {
        $this->setUpTestCase('projectListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-projects-filtersorting', $content);
        // The options are written by `CategoryTypes\ViewHelpers\Form\AbstractSelectViewHelper`,
        // which `ViewHelpers\Form\SortingSelectViewHelper` no longer overrides - this is what
        // covers that here, the class has no test of its own.
        $this->assertStringContainsString('<option value="title" selected="selected">Title</option>', $content);
        $this->assertStringContainsString('<option value="asc" selected="selected">Ascending</option>', $content);
    }

    #[Test]
    public function projectListPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('projectListPage');
        // Rendering a header is what requires the `record` view variable on TYPO3 v14.
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => 'Our research projects'], ['uid' => 1]);

        $this->assertStringContainsString('Our research projects', $this->renderHomePage());
    }

    #[Test]
    public function projectListPluginRendersHiddenProjectsWhenConfigured(): void
    {
        $this->setUpTestCase('projectListPage_showHiddenRecords');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Quantum research project', $content);
        $this->assertStringContainsString('Hidden lab', $content);
    }

    #[Test]
    public function projectListPluginRendersNoProjectsFoundLabelWithoutProjects(): void
    {
        $this->setUpTestCase('projectListPage_noProjects');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-projects-list', $content);
        $this->assertStringContainsString('No projects found.', $content);
    }

    #[Test]
    public function projectListSinglePluginRendersOnlySelectedProjects(): void
    {
        $this->setUpTestCase('projectListSinglePage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-projects-list', $content);
        $this->assertStringContainsString('Solar fields', $content);
        $this->assertStringNotContainsString('Quantum research project', $content);
        $this->assertStringNotContainsString('Hidden lab', $content);
    }

    #[Test]
    public function projectListSinglePluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('projectListSinglePage');
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => 'Featured project'], ['uid' => 1]);

        $this->assertStringContainsString('Featured project', $this->renderHomePage());
    }
}
