<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Routing;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ActiveFiltersAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Site\SiteFinder;

/**
 * Both project lists behind the route enhancers of `Configuration/Routes/List.yaml`.
 *
 * The site configuration imports the shipped file the way a site does. Every combination
 * of filter, active state and sorting is generated from plugin arguments and resolved back
 * by rendering the path, because an enhancer can be broken in one direction only.
 *
 * Categories: Quantum Physics (1, "Quantenphysik" in German) and Energy Research (2) are
 * competence fields, Industry (3) is a cooperation. Quantum Research, a quantum physics
 * project with industry, has no end date and is active. Solar fields (energy research) and
 * Wind lab (quantum physics) ended in 2000 and are completed.
 *
 * - `/home` (`/de/home`): the list, sorted by title ascending, all states.
 * - `/completed`: a list with the active state "completed" preset by the editor.
 * - `/selected`: the list of selected projects, all three of them.
 */
final class ProjectListRouteEnhancerTest extends AbstractAcademicProjectsTestCase
{
    use ActiveFiltersAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const PREFIX = 'academic-projects';
    private const LIST_NAMESPACE = 'tx_academicprojects_projectlist';
    private const SELECTED_NAMESPACE = 'tx_academicprojects_projectlistsingle';
    private const FORM_CLASS = 'academic-projects-filtersorting';
    private const PROJECTS = ['Quantum Research', 'Solar fields', 'Wind lab'];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            // What every new installation gets: a URL with arguments the cache hash covers
            // and no cHash is a 404, not an uncached page.
            'FE' => [
                'cacheHash' => [
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/ProjectListRouteEnhancer/projectListPages.csv');
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
        // The tags show which categories and which state a path resolved to.
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update(
            'sys_template',
            ['constants' => $template['constants'] . "\nplugin.tx_academicprojects.filter.showActiveFilters = 1\n"],
            ['uid' => $template['uid']],
        );
        $this->writeSite();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @return \Generator<string, array{0: int, 1: int, 2: string, 3: array<string, mixed>, 4: string, 5: list<string>, 6: list<string>|null, 7: array{0: string, 1: string}}>
     */
    public static function combinations(): \Generator
    {
        yield 'filter, state and sorting' => [
            0, 2, self::LIST_NAMESPACE,
            ['filterCollection' => ['categories' => '1'], 'activeState' => 'completed', 'sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/home/filter/quantum-physics-1/status/completed/title/desc',
            ['Quantum Physics', 'Completed'],
            ['Wind lab'],
            ['title', 'desc'],
        ];
        yield 'filter and state' => [
            0, 2, self::LIST_NAMESPACE,
            ['filterCollection' => ['categories' => '1'], 'activeState' => 'active'],
            '/home/filter/quantum-physics-1/status/active',
            ['Quantum Physics', 'Active'],
            ['Quantum Research'],
            ['title', 'asc'],
        ];
        yield 'filter and sorting' => [
            0, 2, self::LIST_NAMESPACE,
            ['filterCollection' => ['categories' => '1'], 'sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/home/filter/quantum-physics-1/title/desc',
            ['Quantum Physics'],
            ['Wind lab', 'Quantum Research'],
            ['title', 'desc'],
        ];
        yield 'state and sorting' => [
            0, 2, self::LIST_NAMESPACE,
            ['activeState' => 'completed', 'sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/home/status/completed/title/desc',
            ['Completed'],
            ['Wind lab', 'Solar fields'],
            ['title', 'desc'],
        ];
        yield 'only a filter' => [
            0, 2, self::LIST_NAMESPACE,
            ['filterCollection' => ['categories' => '2']],
            '/home/filter/energy-research-2',
            ['Energy Research'],
            ['Solar fields'],
            ['title', 'asc'],
        ];
        yield 'only a state' => [
            0, 2, self::LIST_NAMESPACE,
            ['activeState' => 'completed'],
            '/home/status/completed',
            ['Completed'],
            ['Solar fields', 'Wind lab'],
            ['title', 'asc'],
        ];
        yield 'only a sorting' => [
            0, 2, self::LIST_NAMESPACE,
            ['sortingField' => 'tx_academicprojects_budget', 'sortingDirection' => 'desc'],
            '/home/budget/desc',
            [],
            null,
            ['tx_academicprojects_budget', 'desc'],
        ];
        yield 'selected projects: filter, state and sorting' => [
            0, 6, self::SELECTED_NAMESPACE,
            ['filterCollection' => ['categories' => '1'], 'activeState' => 'all', 'sortingField' => 'title', 'sortingDirection' => 'desc'],
            '/selected/filter/quantum-physics-1/status/all/title/desc',
            ['Quantum Physics'],
            ['Wind lab', 'Quantum Research'],
            ['title', 'desc'],
        ];
        yield 'selected projects: only a state' => [
            0, 6, self::SELECTED_NAMESPACE,
            ['activeState' => 'active'],
            '/selected/status/active',
            ['Active'],
            ['Quantum Research'],
            ['title', 'asc'],
        ];
        yield 'German: filter, state and sorting' => [
            1, 2, self::LIST_NAMESPACE,
            ['filterCollection' => ['categories' => '1'], 'activeState' => 'completed', 'sortingField' => 'lastUpdated', 'sortingDirection' => 'desc'],
            '/de/home/filter/quantenphysik-1/status/abgeschlossen/zuletzt-aktualisiert/absteigend',
            ['Quantenphysik', 'Abgeschlossen'],
            ['Wind lab'],
            ['lastUpdated', 'desc'],
        ];
        yield 'German: only a state' => [
            1, 2, self::LIST_NAMESPACE,
            ['activeState' => 'active'],
            '/de/home/status/aktiv',
            ['Aktiv'],
            ['Quantum Research'],
            ['title', 'asc'],
        ];
        yield 'German: only a sorting' => [
            1, 2, self::LIST_NAMESPACE,
            ['sortingField' => 'tx_academicprojects_start_date', 'sortingDirection' => 'asc'],
            '/de/home/startdatum/aufsteigend',
            [],
            null,
            ['tx_academicprojects_start_date', 'asc'],
        ];
    }

    /**
     * @param array<string, mixed> $demand
     * @param list<string> $tags
     * @param list<string>|null $projects null where the fixture has no values to order by
     * @param array{0: string, 1: string} $sorting
     */
    #[DataProvider('combinations')]
    #[Test]
    public function everyCombinationGeneratesAPathThatResolves(int $languageId, int $pageId, string $namespace, array $demand, string $path, array $tags, ?array $projects, array $sorting): void
    {
        $uri = $this->generateUri($languageId, $pageId, $namespace, $demand);

        $this->assertSame('https://www.acme.com' . $path, $uri);

        $content = $this->renderFrontendPage($uri);
        $this->assertSame($tags, array_keys($this->activeFilterTags($content, self::PREFIX)));
        if ($projects !== null) {
            $this->assertProjects($projects, $content);
        }
        $this->assertMatchesRegularExpression(sprintf('#<option value="%s" selected="selected">#', $sorting[0]), $content);
        $this->assertMatchesRegularExpression(sprintf('#<option value="%s" selected="selected">#', $sorting[1]), $content);
    }

    /**
     * The links the list renders itself take the routes: the active filter tags, and the
     * redirect that answers the filter form, which always carries the state and the sorting.
     */
    #[Test]
    public function theLinksOfTheListArePaths(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home/filter/quantum-physics-1/status/completed/title/desc');

        $tags = $this->activeFilterTags($content, self::PREFIX);
        $this->assertSame('/home/status/completed/title/desc', $tags['Quantum Physics']['href']);
        $this->assertSame('/home/filter/quantum-physics-1/status/all/title/desc', $tags['Completed']['href']);

        $response = $this->submitFrontendForm('https://www.acme.com/home', self::FORM_CLASS, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['competence_field' => '2'], 'activeState' => 'completed']],
        ]);
        $this->assertSame(303, $response->getStatusCode());
        $this->assertSame('https://www.acme.com/home/filter/energy-research-2/status/completed/title/asc', $response->getHeaderLine('Location'));
    }

    /**
     * The values follow the language of the site, and a value of another language is not a
     * second address of the same list.
     */
    #[Test]
    public function aValueOfAnotherLanguageIsNotFound(): void
    {
        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/de/home/status/completed')->getStatusCode());
        $this->assertSame(404, $this->requestFrontendPage('https://www.acme.com/home/status/abgeschlossen')->getStatusCode());
    }

    /**
     * The enhancer declares no defaults, so the bare page stays the list as the editor
     * configured it, with the preset state.
     */
    #[Test]
    public function theBarePageKeepsThePresetState(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/completed');

        $this->assertSame(['Completed'], array_keys($this->activeFilterTags($content, self::PREFIX)));
        $this->assertProjects(['Solar fields', 'Wind lab'], $content);
    }

    #[Test]
    public function withoutTheImportAListLinkKeepsItsQueryArguments(): void
    {
        $this->writeSite(false);

        $uri = $this->generateUri(0, 2, self::LIST_NAMESPACE, ['activeState' => 'completed', 'sortingField' => 'title', 'sortingDirection' => 'asc']);

        $this->assertStringStartsWith('https://www.acme.com/home?', $uri);
        $this->assertStringContainsString(rawurlencode(self::LIST_NAMESPACE . '[demand][activeState]') . '=completed', $uri);
    }

    private function writeSite(bool $withImport = true): void
    {
        $routing = [];
        if ($withImport) {
            $routing = [
                'imports' => [
                    ['resource' => 'EXT:academic_projects/Configuration/Routes/List.yaml'],
                ],
                'routeEnhancers' => [
                    'AcademicProjectsList' => ['limitToPages' => [2, 4]],
                    'AcademicProjectsListSingle' => ['limitToPages' => [6]],
                ],
            ];
        }
        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: $routing,
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                // Falls back to English, so the content element and the projects, which are
                // not translated, render on the German page as well.
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/', fallbackIdentifiers: ['EN']),
            ],
        );
    }

    /**
     * @param array<string, mixed> $demand
     */
    private function generateUri(int $languageId, int $pageId, string $namespace, array $demand): string
    {
        $site = $this->get(SiteFinder::class)->getSiteByIdentifier('acme');

        return (string)$site->getRouter()->generateUri($pageId, [
            '_language' => $site->getLanguageById($languageId),
            $namespace => [
                'action' => 'list',
                'controller' => 'Project',
                'demand' => $demand,
            ],
        ]);
    }

    /**
     * The projects a list shows, in the order it shows them, and none of the others.
     *
     * @param list<string> $expected
     */
    private function assertProjects(array $expected, string $content): void
    {
        $positions = [];
        foreach (self::PROJECTS as $project) {
            $position = strpos($content, $project);
            if ($position !== false) {
                $positions[$project] = $position;
            }
        }
        asort($positions);

        $this->assertSame($expected, array_keys($positions));
    }
}
