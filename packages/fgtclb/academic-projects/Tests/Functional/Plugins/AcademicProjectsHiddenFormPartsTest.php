<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * A part of the form the content element hides keeps the value of the content element:
 * a visitor who submits the visible part changes that part only (ACE-916).
 *
 * The form posts to the page, and the list renders in the answer to the post. A post carries
 * the fields of the parts the form shows, a hidden part has no field.
 *
 * Categories: Quantum Physics (1) and Energy Research (2) are competence fields, Industry (3)
 * a cooperation. Quantum Research (Quantum Physics) and Wind Parks (Energy Research) are
 * industry projects and run without an end date, Solar Fields (Energy Research) ended in
 * 2000.
 *
 * - `/hidden-filter`: the category filter hidden, Energy Research preset, the sorting and
 *   the state select shown.
 * - `/hidden-sorting`: the sorting hidden and preset to title, descending.
 * - `/hidden-state`: the state select hidden and preset to active projects.
 * - `/selected-hidden-filter`: the selected projects element with the category filter
 *   hidden and Energy Research preset.
 * - `/hidden-filter-no-defaults`: the category filter hidden, no default categories.
 * - `/visible-filter`: the category filter shown, Energy Research preset.
 */
final class AcademicProjectsHiddenFormPartsTest extends AbstractAcademicProjectsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprojects_projectlist';
    private const SINGLE_NAMESPACE = 'tx_academicprojects_projectlistsingle';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
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
        $content = $this->postDemand('https://www.acme.com/hidden-filter', self::LIST_NAMESPACE, [
            'sortingField' => 'title',
            'sortingDirection' => 'desc',
            'activeState' => 'all',
        ]);

        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertRenderedInOrder($content, 'Wind Parks', 'Solar Fields');
    }

    #[Test]
    public function filteringAListWhoseSortingIsHiddenKeepsTheDefaultSorting(): void
    {
        $content = $this->postDemand('https://www.acme.com/hidden-sorting', self::LIST_NAMESPACE, [
            'filterCollection' => ['competence_field' => '2', 'cooperation' => ''],
            'activeState' => 'all',
        ]);

        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertRenderedInOrder($content, 'Wind Parks', 'Solar Fields');
    }

    #[Test]
    public function filteringAListWhoseStateSelectIsHiddenKeepsTheDefaultState(): void
    {
        $content = $this->postDemand('https://www.acme.com/hidden-state', self::LIST_NAMESPACE, [
            'sortingField' => 'title',
            'sortingDirection' => 'asc',
            'filterCollection' => ['competence_field' => '2', 'cooperation' => ''],
        ]);

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
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-state', 5, [
            self::LIST_NAMESPACE => ['demand' => ['activeState' => 'completed']],
        ]));

        $this->assertStringContainsString('Quantum Research', $content);
        $this->assertStringContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    /**
     * Categories a link or a hand-made URL carries for a list whose filter is hidden are
     * added to the default categories: they narrow the list to the industry projects.
     */
    #[Test]
    public function aUrlNarrowsTheCategoriesOfAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter', 2, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '3']]],
        ]));

        $this->assertStringContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
        $this->assertStringNotContainsString('Quantum Research', $content);
    }

    /**
     * A URL never replaces a default category of a hidden filter: no project is in both
     * competence fields.
     */
    #[Test]
    public function aUrlDoesNotReplaceTheCategoriesOfAHiddenFilter(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter', 2, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '1']]],
        ]));

        $this->assertStringContainsString('academic-projects-list', $content);
        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertStringNotContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    /**
     * A list that hides its filter without default categories is filtered by the
     * categories of a link, as before.
     */
    #[Test]
    public function aUrlFiltersAHiddenFilterWithoutDefaultCategories(): void
    {
        $content = $this->renderFrontendPage($this->pageUrl('/hidden-filter-no-defaults', 7, [
            self::LIST_NAMESPACE => ['demand' => ['filterCollection' => ['categories' => '1']]],
        ]));

        $this->assertStringContainsString('Quantum Research', $content);
        $this->assertStringNotContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    /**
     * A list that shows its filter works as before: the selection of the visitor replaces
     * the default categories.
     */
    #[Test]
    public function aSelectionInAVisibleFilterReplacesTheDefaultCategories(): void
    {
        $content = $this->postDemand('https://www.acme.com/visible-filter', self::LIST_NAMESPACE, [
            'sortingField' => 'title',
            'sortingDirection' => 'asc',
            'filterCollection' => ['competence_field' => '1', 'cooperation' => ''],
            'activeState' => 'all',
        ]);

        $this->assertStringContainsString('Quantum Research', $content);
        $this->assertStringNotContainsString('Wind Parks', $content);
        $this->assertStringNotContainsString('Solar Fields', $content);
    }

    #[Test]
    public function sortingSelectedProjectsWhoseFilterIsHiddenKeepsTheDefaultCategories(): void
    {
        $content = $this->postDemand('https://www.acme.com/selected-hidden-filter', self::SINGLE_NAMESPACE, [
            'sortingField' => 'title',
            'sortingDirection' => 'desc',
            'activeState' => 'all',
        ]);

        $this->assertStringNotContainsString('Quantum Research', $content);
        $this->assertRenderedInOrder($content, 'Wind Parks', 'Solar Fields');
    }

    /**
     * The page as the answer to a post of the filter form renders it.
     *
     * @param array<string, mixed> $demand
     */
    private function postDemand(string $url, string $pluginNamespace, array $demand): string
    {
        $parsedBody = [$pluginNamespace => ['demand' => $demand]];
        $body = new Stream('php://temp', 'rw');
        $body->write(http_build_query($parsedBody));
        $body->rewind();

        return $this->renderFrontendPage(
            (new InternalRequest($url))
                ->withMethod('POST')
                ->withAddedHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->withBody($body)
                ->withParsedBody($parsedBody),
        );
    }

    /**
     * The URL of a page with plugin arguments and the cache hash a link carries: without
     * one, the page answers `404`.
     *
     * @param array<string, mixed> $arguments
     */
    private function pageUrl(string $path, int $pageId, array $arguments): string
    {
        $query = HttpUtility::buildQueryString($arguments);
        $cacheHash = GeneralUtility::makeInstance(CacheHashCalculator::class)
            ->generateForParameters('id=' . $pageId . '&' . $query);

        return 'https://www.acme.com' . $path . '?' . $query . '&cHash=' . $cacheHash;
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
