<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * What an installed extension can do to both project list plugins, through
 * `ModifyProjectDemandEvent` and `ModifyProjectListEvent`.
 *
 * `EXT:test_project_list_events` ships one listener per event. Both stay inert until a plugin
 * setting asks for something, so each test below includes the TypoScript file of the behaviour
 * it is about. That both plugins render unchanged while *no* extension listens is what
 * `AcademicProjectsProjectListPluginTest` asserts - it does not load this fixture.
 *
 * The fixtures carry an end date in the past on "Solar fields" and none on "Quantum Research",
 * which is what the active state of the demand decides on.
 */
final class AcademicProjectsListEventsTest extends AbstractAcademicProjectsTestCase
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
        $this->addTestExtensionsToLoad('tests/test-project-list-events');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param string[] $listenerTypoScriptFiles The behaviour the fixture listeners are asked for.
     */
    private function setUpTestCase(string $dataSet, array $listenerTypoScriptFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_projects/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_projects/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ],
                    $listenerTypoScriptFiles,
                ),
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

    /**
     * The demand a listener hands back is the one that is queried: the content element asks for
     * every project, and the listener narrows it to the running ones.
     */
    #[Test]
    public function aDemandListenerNarrowsTheList(): void
    {
        $this->setUpTestCase('projectListPage_endDates', [
            'EXT:test_project_list_events/Configuration/TypoScript/ActiveState.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Quantum research project', $content);
        $this->assertStringNotContainsString('Solar fields', $content);
    }

    /**
     * The context tells the two project list plugins apart, so a listener can narrow the
     * single-selection list while the regular list on the same page is unaffected. The
     * single-selection element selects the completed project, so it is left with nothing.
     */
    #[Test]
    public function aDemandListenerCanActOnOnePluginAlone(): void
    {
        $this->setUpTestCase('projectListAndSinglePage_endDates', [
            'EXT:test_project_list_events/Configuration/TypoScript/ActiveStateSingleOnly.typoscript',
        ]);

        $content = $this->renderHomePage();
        // Both content elements rendered, and only one of them has items.
        $this->assertSame(2, substr_count($content, 'academic-projects-list'));
        $this->assertSame(1, substr_count($content, 'academic-projects-itemlist'));
        // The regular list keeps the completed project, the single-selection one does not.
        $this->assertSame(1, substr_count($content, 'Solar fields'));
        $this->assertStringContainsString('No projects found', $content);
    }

    /**
     * `setDemand()` rather than a mutation of the demand the controller built: the demand
     * that is queried has to be the one the listener handed back, not the one the factory
     * produced.
     */
    #[Test]
    public function theDemandAListenerHandsBackIsTheOneThatIsQueried(): void
    {
        $this->setUpTestCase('projectListPage_endDates', [
            'EXT:test_project_list_events/Configuration/TypoScript/ActiveStateReplaced.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Quantum research project', $content);
        $this->assertStringNotContainsString('Solar fields', $content);
    }

    /**
     * The categories are computed before the list event and are not recomputed after it, so
     * a listener that wants the filter of the plugin to differ sets them. The projects are
     * left alone here, which is what tells the two setters apart.
     */
    #[Test]
    public function aListListenerReplacesTheApplicableCategories(): void
    {
        $this->setUpTestCase('projectListPage_categorized', [
            'EXT:test_project_list_events/Configuration/TypoScript/ReplaceCategories.typoscript',
        ]);

        $content = $this->renderHomePage();
        // The filter offers the one category the listener left.
        $this->assertStringContainsString('>Energy Research</option>', $content);
        $this->assertStringNotContainsString('>Quantum Physics</option>', $content);
        // The projects themselves are untouched.
        $this->assertStringContainsString('Quantum research project', $content);
        $this->assertStringContainsString('Solar fields', $content);
    }

    /**
     * The list event carries the view, so a listener assigns variables a project template
     * renders. The fixture extension ships such a template and puts it in front of the shipped
     * one; the value proves the plugin name and the queried projects reach the listener as well.
     */
    #[Test]
    public function aListListenerAssignsAViewVariable(): void
    {
        $this->setUpTestCase('projectListPage', [
            'EXT:test_project_list_events/Configuration/TypoScript/ListMarker.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('<p class="test-project-list-marker">projects-marker|ProjectList|2</p>', $content);
        $this->assertStringContainsString('Quantum research project', $content);
    }

    /**
     * A listener that filters after the query replaces the result, and the replacement is what
     * is rendered.
     */
    #[Test]
    public function aListListenerReplacesTheResult(): void
    {
        $this->setUpTestCase('projectListPage', [
            'EXT:test_project_list_events/Configuration/TypoScript/ReplaceResult.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Solar fields', $content);
        $this->assertStringNotContainsString('Quantum research project', $content);
    }
}
