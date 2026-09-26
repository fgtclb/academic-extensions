<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ContentElementHeaderAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
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
 *
 * The header of a content element renders once: by default the content element layout
 * renders it and the plugins do not. A site whose layout renders no header switches
 * `renderContentElementHeader` on, and the templates then render the
 * `EXT:fluid_styled_content` `Header/All` partial themselves. The switched on cases guard
 * the `record` view variable as well: on TYPO3 v14 the partial renders the header through
 * it, and fails without it.
 */
final class AcademicProjectsProjectListPluginTest extends AbstractAcademicProjectsTestCase
{
    use ContentElementHeaderAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const HEADER = 'Our research projects';
    private const SUBHEADER = 'Funded since 2020';
    private const RENDER_HEADER_CONSTANTS = 'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript';
    private const HEADER_PARTIAL_OVERRIDE_SETUP = 'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/HeaderPartialOverride.typoscript';
    private const LAYOUT_WITHOUT_HEADER_SETUP = 'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript';

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

    /**
     * @param list<string> $additionalConstantFiles
     * @param list<string> $additionalSetupFiles
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = [], array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_projects/Configuration/TypoScript/constants.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_projects/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ...$additionalSetupFiles,
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

    private function setContentElementHeader(int $uid, int $headerLayout): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                ['header' => self::HEADER, 'subheader' => self::SUBHEADER, 'header_layout' => $headerLayout],
                ['uid' => $uid],
            );
    }

    /**
     * Every view with the header layouts "Default", 2 and "Hidden", and the number of times
     * the header and the subheader have to render: "Default" is the layout the header
     * partial resolves through a setting, and the one a plugin rendering it without that
     * setting leaves an empty `<header>` for. A view names the data set, the page, the
     * content element and the class of the element the template wraps its output in.
     *
     * @return \Generator<string, array{string, string, int, string, int, int}>
     */
    public static function viewsAndHeaderLayouts(): \Generator
    {
        $views = [
            'project list' => ['projectListPage', 'https://www.acme.com/home', 1, 'academic-projects-list'],
            'selected projects' => ['projectListSinglePage', 'https://www.acme.com/home', 1, 'academic-projects-list'],
        ];
        $headerLayouts = [
            'header layout "Default"' => [0, 1],
            'header layout 2' => [2, 1],
            'header layout "Hidden"' => [100, 0],
        ];
        foreach ($views as $view => [$dataSet, $url, $contentElement, $wrapperClass]) {
            foreach ($headerLayouts as $name => [$headerLayout, $expectedHeadings]) {
                yield $view . ', ' . $name => [$dataSet, $url, $contentElement, $wrapperClass, $headerLayout, $expectedHeadings];
            }
        }
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginLeavesTheContentElementHeaderToTheLayout(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $wrapper = sprintf(
            '//*[@id = "c%d"]//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]',
            $contentElement,
            $wrapperClass,
        );
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER, $wrapper));
        $this->assertSame(0, $this->countHeadingsReading($content, self::SUBHEADER, $wrapper));
    }

    #[Test]
    #[DataProvider('viewsAndHeaderLayouts')]
    public function aPluginRendersTheContentElementHeaderWhenSwitchedOn(
        string $dataSet,
        string $url,
        int $contentElement,
        string $wrapperClass,
        int $headerLayout,
        int $expectedHeadings,
    ): void {
        $this->setUpTestCase($dataSet, [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP]);
        $this->setContentElementHeader($contentElement, $headerLayout);

        $content = $this->renderFrontendPage($url);
        $frame = sprintf('//*[@id = "c%d"]', $contentElement);
        // The fixture layout renders no header, so a heading inside the frame of the element
        // comes from the template. The first two assertions prove the fixture layout and the
        // template of the view rendered.
        $this->assertSame(1, $this->countContentElementHeaderNodes($content, $frame . '[contains(concat(" ", normalize-space(@class), " "), " frame-without-header ")]'));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            sprintf('%s//*[contains(concat(" ", normalize-space(@class), " "), " %s ")]', $frame, $wrapperClass),
        ));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER, $frame));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER, $frame));
    }

    /**
     * A site package that renders the header its own way registers its own `Header/All`
     * above the paths of the extension, and that one renders instead of the partial of
     * EXT:fluid_styled_content, whose path sorts below every other one.
     */
    #[Test]
    public function aHeaderPartialOfTheSitePackageWinsOverTheShippedOne(): void
    {
        $this->setUpTestCase('projectListPage', [self::RENDER_HEADER_CONSTANTS], [self::LAYOUT_WITHOUT_HEADER_SETUP, self::HEADER_PARTIAL_OVERRIDE_SETUP]);
        $this->setContentElementHeader(1, 2);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(0, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame(1, $this->countContentElementHeaderNodes(
            $content,
            '//*[@id = "c1"]//p[@class = "site-package-header"][normalize-space() = "' . self::HEADER . '"]',
        ));
    }
}
