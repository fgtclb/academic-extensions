<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A part of the form the content element hides keeps the value of the content element:
 * a visitor who submits the visible part changes that part only (ACE-916).
 *
 * The form is submitted from the page the plugin rendered, with the fields it holds, and
 * the redirect is followed.
 *
 * Categories: Quantum Physics (1) and Energy Research (2) are competence fields, Industry
 * (3) is a cooperation. Quantum Research (quantum physics, industry) and Wind Parks (energy
 * research, industry) run without an end date, Solar Fields (energy research) ended in 2000.
 *
 * - `/hidden-filter`: the category filter hidden, Energy Research preset, the sorting and
 *   the state select shown.
 * - `/hidden-sorting`: the sorting hidden and preset to title, descending.
 * - `/hidden-state`: the state select hidden and preset to active projects.
 * - `/selected-hidden-filter`: the selected projects element with the category filter
 *   hidden and Energy Research preset.
 * - `/hidden-filter-no-preset`: the category filter hidden, no category preset.
 * - `/energy`: the whole form shown, Energy Research preset.
 */
final class AcademicProjectsHiddenFormPartsTest extends AbstractAcademicProjectsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprojects_projectlist';
    private const SINGLE_NAMESPACE = 'tx_academicprojects_projectlistsingle';
    private const FORM = '//form[@name="demand"]';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'FE' => [
                'cacheHash' => [
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsHiddenFormParts/projectListPages.csv');
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
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function sortingAListWhoseFilterIsHiddenKeepsTheDefaultCategories(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-filter', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'activeState' => 'all',
                'filterCollection' => ['categories' => '2'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertRenderedInOrder($content, 'Wind Parks', 'Solar Fields');
    }

    #[Test]
    public function filteringAListWhoseSortingIsHiddenKeepsTheDefaultSorting(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-sorting', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['competence_field' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'activeState' => 'all',
                'filterCollection' => ['categories' => '2'],
                'sortingDirection' => 'desc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertRenderedInOrder($content, 'Wind Parks', 'Solar Fields');
    }

    #[Test]
    public function filteringAListWhoseStateSelectIsHiddenKeepsTheDefaultState(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-state', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['competence_field' => '2']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            [
                'activeState' => 'active',
                'filterCollection' => ['categories' => '2'],
                'sortingDirection' => 'asc',
                'sortingField' => 'title',
            ],
            $this->demandArguments($location, self::LIST_NAMESPACE),
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
        $this->assertStringNotContainsString('Quantum Research', $content);
    }

    /**
     * Neither a link nor a hand-made URL changes a hidden part: the list shows the active
     * state of the content element, not the one of the URL.
     */
    #[Test]
    public function aUrlDoesNotChangeTheStateOfAHiddenStateSelect(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-state?tx_academicprojects_projectlist%5Bdemand%5D%5BactiveState%5D=completed',
        );

        $this->assertStringContainsString('Quantum Research', $content);
        $this->assertStringContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    /**
     * Row three of the issue: the sorting alone, submitted on a list whose state select is
     * hidden, keeps the preset state.
     */
    #[Test]
    public function sortingAListWhoseStateSelectIsHiddenKeepsTheDefaultState(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/hidden-state', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('active', $this->demandArguments($location, self::LIST_NAMESPACE)['activeState'] ?? null);
        $content = $this->renderFrontendPage($location);
        $this->assertStringNotContainsString('Solar Fields', $content);
        $this->assertRenderedInOrder($content, 'Wind Parks', 'Quantum Research');
    }

    /**
     * A sorting a request carries for a list whose sorting is hidden, from an override of
     * the form or a hand-made request, does not change it.
     */
    #[Test]
    public function aSubmittedSortingDoesNotChangeAHiddenSorting(): void
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest('https://www.acme.com/hidden-sorting', [
            self::LIST_NAMESPACE => ['demand' => [
                'filterCollection' => ['competence_field' => '2'],
                'sortingField' => 'title',
                'sortingDirection' => 'asc',
            ]],
        ]));

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame('desc', $this->demandArguments($location, self::LIST_NAMESPACE)['sortingDirection'] ?? null);
        $this->assertRenderedInOrder($this->renderFrontendPage($location), 'Wind Parks', 'Solar Fields');
    }

    #[Test]
    public function aLinkedSortingDoesNotChangeAHiddenSorting(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-sorting?tx_academicprojects_projectlist%5Bdemand%5D%5BsortingField%5D=title&tx_academicprojects_projectlist%5Bdemand%5D%5BsortingDirection%5D=asc',
        );

        $this->assertRenderedInOrder($content, 'Wind Parks', 'Solar Fields');
        $this->assertRenderedInOrder($content, 'Solar Fields', 'Quantum Research');
    }

    /**
     * Categories a link carries for a list whose filter is hidden narrow the list and never
     * replace a default category: Energy Research stays, Industry is added.
     */
    #[Test]
    public function aUrlNarrowsAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-filter?tx_academicprojects_projectlist%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=3',
        );

        $this->assertStringContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    /**
     * Without default categories, the categories of a link filter a list whose filter is
     * hidden, as they did before.
     */
    #[Test]
    public function aUrlFiltersAHiddenFilterWithoutDefaultCategories(): void
    {
        $content = $this->renderFrontendPage(
            'https://www.acme.com/hidden-filter-no-preset?tx_academicprojects_projectlist%5Bdemand%5D%5BfilterCollection%5D%5Bcategories%5D=1',
        );

        $this->assertStringContainsString('Quantum Research', $content);
        $this->assertStringNotContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    /**
     * The site offers the cooperation only. The default competence field of the element is
     * no part of the form, so a submitted cooperation keeps it.
     */
    #[Test]
    public function aFilterKeepsTheDefaultsOfACategoryTypeItDoesNotOffer(): void
    {
        $this->addConstants('plugin.tx_academicprojects.filter.categoryTypes = cooperation');

        $response = $this->submitFrontendForm('https://www.acme.com/energy', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['cooperation' => '3']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '2,3'],
            $this->demandArguments($location, self::LIST_NAMESPACE)['filterCollection'] ?? null,
        );
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    /**
     * The competence field is part of the form, so "All" clears its default, as before.
     */
    #[Test]
    public function aFilterStillClearsTheDefaultsOfACategoryTypeItOffers(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/energy', self::FORM, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['competence_field' => '', 'cooperation' => '3']]],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '3'],
            $this->demandArguments($location, self::LIST_NAMESPACE)['filterCollection'] ?? null,
        );
        $content = $this->renderFrontendPage($location);
        $this->assertStringContainsString('Wind Parks', $content);
        $this->assertStringContainsString('Quantum Research', $content);
    }

    #[Test]
    public function sortingSelectedProjectsWhoseFilterIsHiddenKeepsTheDefaultCategories(): void
    {
        $response = $this->submitFrontendForm('https://www.acme.com/selected-hidden-filter', self::FORM, [
            self::SINGLE_NAMESPACE => ['demand' => ['sortingDirection' => 'desc']],
        ]);

        $location = $this->assertSeeOtherWithCacheHash($response);
        $this->assertSame(
            ['categories' => '2'],
            $this->demandArguments($location, self::SINGLE_NAMESPACE)['filterCollection'] ?? null,
        );

        $content = $this->renderFrontendPage($location);
        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertRenderedInOrder($content, 'Wind Parks', 'Solar Fields');
    }

    /**
     * Adds TypoScript constants to the template of the site, the way a site package sets
     * them.
     */
    private function addConstants(string $constants): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['constants' => $template['constants'] . LF . $constants], ['uid' => $template['uid']]);
    }

    /**
     * @return array<string, mixed>
     */
    private function demandArguments(string $location, string $pluginNamespace): array
    {
        parse_str((string)parse_url($location, PHP_URL_QUERY), $query);
        $demand = $query[$pluginNamespace]['demand'] ?? null;
        $this->assertIsArray($demand, 'The URL carries no demand: ' . $location);

        return $demand;
    }

    private function assertRenderedInOrder(string $content, string $first, string $second): void
    {
        $firstPosition = strpos($content, $first);
        $secondPosition = strpos($content, $second);
        $this->assertNotFalse($firstPosition, $first . ' is not rendered.');
        $this->assertNotFalse($secondPosition, $second . ' is not rendered.');
        $this->assertLessThan($secondPosition, $firstPosition, $first . ' is not rendered before ' . $second . '.');
    }
}
