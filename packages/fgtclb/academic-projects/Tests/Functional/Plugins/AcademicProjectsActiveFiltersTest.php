<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ActiveFiltersAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The active filter tags, the reset link and the result count of both project lists,
 * switched on by `filter.showActiveFilters`, `filter.showReset` and `filter.showResultCount`.
 *
 * Categories: Quantum Physics (1) and Energy Research (2) are competence fields, Industry (3)
 * is a cooperation. Quantum Research, a quantum physics project with industry, has no end
 * date and is active. Solar fields (energy research) and Wind lab (quantum physics) ended in
 * 2000 and are completed.
 *
 * - `/home`: the list.
 * - `/completed`: a list with the active state "completed" preset by the editor.
 * - `/state-hidden`: the same, with the state select hidden.
 * - `/selected`: the list of selected projects, all three of them.
 * - `/category-hidden`: a list whose category filter is hidden and whose state select is not.
 */
final class AcademicProjectsActiveFiltersTest extends AbstractAcademicProjectsTestCase
{
    use ActiveFiltersAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const PREFIX = 'academic-projects';
    private const LIST_NAMESPACE = 'tx_academicprojects_projectlist';
    private const SELECTED_NAMESPACE = 'tx_academicprojects_projectlistsingle';
    private const ALL_ON = "plugin.tx_academicprojects.filter.showActiveFilters = 1\n"
        . "plugin.tx_academicprojects.filter.showReset = 1\n"
        . "plugin.tx_academicprojects.filter.showResultCount = 1\n";

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsActiveFilters/projectListPages.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function everyCategoryIsATagThatRemovesOnlyThatCategory(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3', 'all'));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Quantum Physics', 'Industry'], array_keys($tags));
        $this->assertSame(
            ['activeState' => 'all', 'filterCollection' => ['categories' => '3'], 'sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['Quantum Physics']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame('Remove filter: Industry', $tags['Industry']['label']);
        $this->assertSame('/home', $this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertSame('1 project found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * An active state other than "all" is a tag of its own. Removing it keeps the
     * categories, removing the category keeps the state.
     */
    #[Test]
    public function theActiveStateIsATagThatLeadsBackToAllProjects(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '1', 'completed'));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Quantum Physics', 'Completed'], array_keys($tags));
        $this->assertSame(
            ['activeState' => 'all', 'filterCollection' => ['categories' => '1'], 'sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['Completed']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame(
            ['activeState' => 'completed', 'sortingDirection' => 'desc', 'sortingField' => 'title'],
            $this->activeFiltersDemand($tags['Quantum Physics']['href'], self::LIST_NAMESPACE),
        );
        $this->assertSame('1 project found', $this->activeFiltersResultCount($content, self::PREFIX));

        $allQuantumPhysics = $this->renderFrontendPage('https://www.acme.com' . $tags['Completed']['href']);
        $this->assertSame(['Quantum Physics'], array_keys($this->activeFilterTags($allQuantumPhysics, self::PREFIX)));
        $this->assertSame('2 projects found', $this->activeFiltersResultCount($allQuantumPhysics, self::PREFIX));
    }

    /**
     * With no category, the state "all" and nothing preset there is nothing to reset.
     */
    #[Test]
    public function aSortingChangeAloneOffersNoResetLink(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '', 'all'));

        $this->assertStringNotContainsString('academic-projects-active-filters', $content);
        $this->assertSame('3 projects found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    #[Test]
    public function noMatchingProjectIsCountedAsZero(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/home', '2', 'active'));

        $this->assertSame('0 projects found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * The preset state is a tag like a selected one. The reset link would lead to the page
     * shown, so it is offered only once the visitor selected something, removing the preset
     * state included, and then leads back to the preset.
     */
    #[Test]
    public function thePresetStateIsATagAndTheResetLinkReturnsToIt(): void
    {
        $this->setUpSite(self::ALL_ON);

        $preset = $this->renderFrontendPage('https://www.acme.com/completed');
        $this->assertSame(['Completed'], array_keys($this->activeFilterTags($preset, self::PREFIX)));
        $this->assertNull($this->activeFiltersResetLink($preset, self::PREFIX));
        $this->assertSame('2 projects found', $this->activeFiltersResultCount($preset, self::PREFIX));

        $allStates = $this->renderFrontendPage('https://www.acme.com' . $this->activeFilterTags($preset, self::PREFIX)['Completed']['href']);
        $this->assertSame([], $this->activeFilterTags($allStates, self::PREFIX));
        $this->assertSame('/completed', $this->activeFiltersResetLink($allStates, self::PREFIX));
        $this->assertSame('3 projects found', $this->activeFiltersResultCount($allStates, self::PREFIX));

        $selected = $this->renderFrontendPage($this->listUrl('/completed', '1', 'all'));
        $this->assertSame(['Quantum Physics'], array_keys($this->activeFilterTags($selected, self::PREFIX)));
        $this->assertSame('/completed', $this->activeFiltersResetLink($selected, self::PREFIX));
    }

    /**
     * A state the visitor cannot change is no tag. The category tag keeps it.
     */
    #[Test]
    public function aHiddenStateSelectShowsNoStateTag(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/state-hidden', '1', 'completed'));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Quantum Physics'], array_keys($tags));
        $this->assertSame('completed', $this->activeFiltersDemand($tags['Quantum Physics']['href'], self::LIST_NAMESPACE)['activeState']);
    }

    /**
     * The category tags follow the category filter, the state tag the state select.
     */
    #[Test]
    public function aHiddenCategoryFilterShowsOnlyTheStateTag(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/category-hidden', '1', 'completed'));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Completed'], array_keys($tags));
        $this->assertSame(['categories' => '1'], $this->activeFiltersDemand($tags['Completed']['href'], self::LIST_NAMESPACE)['filterCollection'] ?? null);
        $this->assertSame('/category-hidden', $this->activeFiltersResetLink($content, self::PREFIX));
    }

    #[Test]
    public function theListOfSelectedProjectsOffersTheSameTags(): void
    {
        $this->setUpSite(self::ALL_ON);

        $content = $this->renderFrontendPage($this->listUrl('/selected', '1', 'completed', self::SELECTED_NAMESPACE));

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame(['Quantum Physics', 'Completed'], array_keys($tags));
        $this->assertSame('/selected', parse_url($tags['Completed']['href'], PHP_URL_PATH));
        $this->assertSame(['categories' => '1'], $this->activeFiltersDemand($tags['Completed']['href'], self::SELECTED_NAMESPACE)['filterCollection'] ?? null);
        $this->assertSame('/selected', $this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertSame('1 project found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * The settings are off by default: a site that sets none of them renders the lists as
     * before, whatever the visitor filtered.
     */
    #[Test]
    public function nothingIsRenderedWithoutTheSettings(): void
    {
        $this->setUpSite();

        foreach (['/home' => self::LIST_NAMESPACE, '/selected' => self::SELECTED_NAMESPACE] as $path => $namespace) {
            $content = $this->renderFrontendPage($this->listUrl($path, '1,3', 'completed', $namespace));

            $this->assertStringNotContainsString('academic-projects-active-filters', $content, $path);
            $this->assertStringNotContainsString('academic-projects-result-count', $content, $path);
        }
    }

    #[Test]
    public function theSettingsAreSiteSettings(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => "@import 'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript'",
            ],
        );
        $this->writeSiteConfiguration(
            identifier: 'active-filters',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-projects'],
                    'settings' => [
                        'plugin.tx_academicprojects.filter.showActiveFilters' => true,
                        'plugin.tx_academicprojects.filter.showReset' => true,
                        'plugin.tx_academicprojects.filter.showResultCount' => true,
                    ],
                ],
            ),
            languages: [$this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/')],
        );

        $content = $this->renderFrontendPage($this->listUrl('/home', '1,3', 'all'));

        $this->assertSame(['Quantum Physics', 'Industry'], array_keys($this->activeFilterTags($content, self::PREFIX)));
        $this->assertSame('/home', $this->activeFiltersResetLink($content, self::PREFIX));
        $this->assertSame('1 project found', $this->activeFiltersResultCount($content, self::PREFIX));
    }

    /**
     * The site as a static template configures it, with `$constants` added after the
     * constants of the extension.
     */
    private function setUpSite(string $constants = ''): void
    {
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
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['constants' => $template['constants'] . "\n" . $constants], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * A list URL as a filter submission redirects to, sorted by title descending. The demand
     * is excluded from the cHash, so it needs none.
     */
    private function listUrl(string $path, string $categories, string $activeState, string $namespace = self::LIST_NAMESPACE): string
    {
        $demand = ['sortingField' => 'title', 'sortingDirection' => 'desc', 'activeState' => $activeState];
        if ($categories !== '') {
            $demand['filterCollection'] = ['categories' => $categories];
        }

        return 'https://www.acme.com' . $path . '?' . http_build_query([$namespace => ['demand' => $demand]]);
    }
}
