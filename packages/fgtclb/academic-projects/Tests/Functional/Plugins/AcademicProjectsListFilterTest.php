<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\CategoryFilterFormAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The category filters of the project list, as the settings `filter.categoryTypes`,
 * `filter.visibleCount` and `filter.hideDisabledOptions` shape them, and the "All" option
 * label of each filter.
 *
 * Categories: Quantum Physics (1) and Energy Research (2) are competence fields, Industry
 * (3) is a cooperation, Physics (4) and Agriculture (5) are departments. Quantum Research
 * is a quantum physics project with industry in the physics department, Solar fields an
 * energy research project. No project carries Agriculture, and no category of the type
 * `funding_partner` exists. The type order of the group is competence field, cooperation,
 * funding partner, department.
 *
 * The list is on `/home`.
 */
final class AcademicProjectsListFilterTest extends AbstractAcademicProjectsTestCase
{
    use CategoryFilterFormAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const LIST_NAMESPACE = 'tx_academicprojects_projectlist';
    private const FORM_CLASS = 'academic-projects-filtersorting';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsListFilter/projectListPage.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The site as a static template configures it. `$constants` and `$setup` are added after
     * the TypoScript of the extension, the way a site package adds its own.
     */
    private function setUpSite(string $constants = '', string $setup = ''): void
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
        $this->appendToSiteTemplate($constants, $setup);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * The same site configured through the aggregate site set and its site settings. The
     * page object comes from a `sys_template` record, which is included after the sets.
     *
     * @param non-empty-string $identifier
     * @param array<string, mixed> $settings
     */
    private function setUpSiteSetSite(string $identifier, array $settings): void
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
            // The site identifier is part of several caches the test instance keeps for the
            // whole class - the site settings among them - so every site of a set needs an
            // identifier of its own.
            identifier: $identifier,
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-projects'],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    private function appendToSiteTemplate(string $constants, string $setup): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'constants', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update(
            'sys_template',
            ['constants' => $template['constants'] . LF . $constants, 'config' => $template['config'] . LF . $setup],
            ['uid' => $template['uid']],
        );
    }

    private function filterSettings(string $categoryTypes = '', int $visibleCount = 0, bool $hideDisabledOptions = false): string
    {
        return 'plugin.tx_academicprojects.filter.categoryTypes = ' . $categoryTypes . LF
            . 'plugin.tx_academicprojects.filter.visibleCount = ' . $visibleCount . LF
            . 'plugin.tx_academicprojects.filter.hideDisabledOptions = ' . (int)$hideDisabledOptions . LF;
    }

    /**
     * The list on `/home` after a visitor filtered it: the POST the form sends, answered with
     * a redirect, and the page that redirect leads to.
     *
     * @param array<string, string> $filterCollection
     */
    private function renderFilteredList(array $filterCollection): string
    {
        $response = $this->requestFrontendPage($this->frontendPostRequest(
            'https://www.acme.com/home',
            [self::LIST_NAMESPACE => ['demand' => ['filterCollection' => $filterCollection]]],
        ));

        return $this->renderFrontendPage($this->assertSeeOtherWithCacheHash($response));
    }

    /**
     * The markup of the filters as it was before the settings existed: every type with a
     * category in the type order of the group, one cell each, the generic "All" label, and
     * the department without a project as a disabled option.
     */
    #[Test]
    public function withoutSettingsTheFiltersRenderAsBefore(): void
    {
        $this->setUpSite();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['competence_field', 'cooperation', 'department'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame(self::DEFAULT_FILTER_CELLS, $this->categoryFilterCellMarkup($content, self::FORM_CLASS));
    }

    #[Test]
    public function theConfiguredFilterTypesAreOfferedInTheirOrder(): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'department,competence_field'));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['department', 'competence_field'], $this->renderedCategoryFilters($content, self::FORM_CLASS)['visible']);
    }

    /**
     * `funding_partner` has no category and is left out before the count applies, so
     * the count is one of the filters that render.
     */
    #[Test]
    public function theFiltersAfterTheVisibleCountAreBehindMoreFilters(): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'funding_partner,department,competence_field,cooperation', visibleCount: 1));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['department'], 'more' => ['competence_field', 'cooperation'], 'disclosure' => 'closed', 'summary' => 'More filters'],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
    }

    #[Test]
    public function aVisibleCountCoveringEveryFilterRendersNoDisclosure(): void
    {
        $this->setUpSite(constants: $this->filterSettings(visibleCount: 3));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['competence_field', 'cooperation', 'department'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
    }

    /**
     * @return \Generator<string, array{0: array<string, string>, 1: string}>
     */
    public static function activeFilterDataProvider(): \Generator
    {
        yield 'a filter behind the disclosure' => [['competence_field' => '1'], 'open'];
        yield 'a filter shown right away' => [['department' => '4'], 'closed'];
        yield 'a filter behind the disclosure, cleared' => [['competence_field' => ''], 'closed'];
    }

    /**
     * @param array<string, string> $filterCollection
     */
    #[DataProvider('activeFilterDataProvider')]
    #[Test]
    public function moreFiltersIsOpenWhileOneOfItsFiltersIsActive(array $filterCollection, string $disclosure): void
    {
        $this->setUpSite(constants: $this->filterSettings(categoryTypes: 'department,competence_field,cooperation', visibleCount: 1));

        $content = $this->renderFilteredList($filterCollection);

        $this->assertSame($disclosure, $this->renderedCategoryFilters($content, self::FORM_CLASS)['disclosure']);
    }

    /**
     * @return \Generator<string, array{0: string, 1: string}>
     */
    public static function labelOverrideDataProvider(): \Generator
    {
        yield 'extension' => ['https://www.acme.com/home', 'plugin.tx_academicprojects'];
        yield 'plugin' => ['https://www.acme.com/home', 'plugin.tx_academicprojects_projectlist'];
    }

    /**
     * A label of its own for one type, set through `_LOCAL_LANG` of the extension or of the
     * plugin, and none for another: the one reads its own, the other the label every filter
     * read before. Up to TYPO3 v13 the extension path is only read because the partial
     * passes the extension name in UpperCamelCase.
     */
    #[DataProvider('labelOverrideDataProvider')]
    #[Test]
    public function theAllOptionReadsALabelOfItsTypeWhenOneExists(string $url, string $typoScriptPath): void
    {
        $this->setUpSite(setup: $typoScriptPath . '._LOCAL_LANG.default.sys_category.projects.allOptions.competence_field = All competence fields');

        $content = $this->renderFrontendPage($url);

        $this->assertSame(['All competence fields', 'Quantum Physics', 'Energy Research'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'competence_field'));
        $this->assertSame(['All options', 'Physics', 'Agriculture (disabled)'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'department'));
    }

    #[Test]
    public function optionsWithoutProjectsAreLeftOutOnDemand(): void
    {
        $this->setUpSite(constants: $this->filterSettings(hideDisabledOptions: true));

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['All options', 'Physics'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'department'));
    }

    #[Test]
    public function theSiteSettingsConfigureTheFilters(): void
    {
        $this->setUpSiteSetSite('acme-site-settings', [
            'plugin.tx_academicprojects.filter.categoryTypes' => 'department,competence_field',
            'plugin.tx_academicprojects.filter.visibleCount' => 1,
            'plugin.tx_academicprojects.filter.hideDisabledOptions' => true,
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['department'], 'more' => ['competence_field'], 'disclosure' => 'closed', 'summary' => 'More filters'],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame(['All options', 'Physics'], $this->categoryFilterOptions($content, self::FORM_CLASS, 'department'));
    }

    /**
     * The site set without any site setting renders what the static template renders
     * without a constant: the declared defaults are the defaults of the constants - no
     * filter left out, none behind "More filters", no option hidden.
     */
    #[Test]
    public function theSiteSetDefaultsRenderTheFiltersAsBefore(): void
    {
        $this->setUpSiteSetSite('acme-site-set-defaults', []);

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(
            ['visible' => ['competence_field', 'cooperation', 'department'], 'more' => [], 'disclosure' => 'none', 'summary' => null],
            $this->renderedCategoryFilters($content, self::FORM_CLASS),
        );
        $this->assertSame(self::DEFAULT_FILTER_CELLS, $this->categoryFilterCellMarkup($content, self::FORM_CLASS));
    }

    /**
     * The cells of the filters before the settings existed.
     */
    private const DEFAULT_FILTER_CELLS = [
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="competence_field" class="form-label"> Competence field </label><select onchange="this.form.submit()" id="competence_field" class="form-select" name="tx_academicprojects_projectlist[demand][filterCollection][competence_field]"><option value="">All options</option><option value="1" class="level-0">Quantum Physics</option><option value="2" class="level-0">Energy Research</option></select></div>',
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="cooperation" class="form-label"> Cooperation </label><select onchange="this.form.submit()" id="cooperation" class="form-select" name="tx_academicprojects_projectlist[demand][filterCollection][cooperation]"><option value="">All options</option><option value="3" class="level-0">Industry</option></select></div>',
        '<div class="col-12 col-md-6 col-lg-4 col-xl-3"><label for="department" class="form-label"> Department </label><select onchange="this.form.submit()" id="department" class="form-select" name="tx_academicprojects_projectlist[demand][filterCollection][department]"><option value="">All options</option><option value="4" class="level-0">Physics</option><option value="5" class="level-0" disabled>Agriculture</option></select></div>',
    ];
}
