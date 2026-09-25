<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ContentElementHeaderAssertionTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Cache\Backend\TransientMemoryBackend;

/**
 * Renders the `academicbitejobs_list` plugin in the frontend.
 *
 * The header of the content element renders once: by default the content element layout
 * renders it and the plugin does not. A site whose layout renders no header switches
 * `renderContentElementHeader` on, and the template then renders it itself.
 *
 * It also guards the list template: the jobs are grouped only when TypoScript names a
 * grouping field, and the view values stored before 2.1 (`ListView`, `CardView`,
 * `TableView`) as well as an empty or unknown one still select a view.
 *
 * This class is deliberately small. It came with ACE-677, the first change on this branch
 * that needed the rendered plugin; `main` carries the full plugin coverage.
 *
 * The `test_bitejobs_stub` fixture extension replaces the core request factory, so no
 * outgoing HTTP request to the b-ite API is performed. It answers two job postings.
 */
final class AcademicBiteJobsListPluginTest extends AbstractAcademicBiteJobsTestCase
{
    use ContentElementHeaderAssertionTrait;
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const HEADER = 'Open positions';
    private const SUBHEADER = 'Apply by the end of the month';
    private const LIST_WRAPPER = '//div[contains(concat(" ", normalize-space(@class), " "), " academic-bite-jobs-list ")]';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        // The Extbase class schema cache stays in memory for this class. TYPO3 core writes it
        // from the destructor of the reflection service, and when the garbage collector runs
        // that destructor inside another serialize(), the entry gets back references of the
        // outer call and cannot be read back. On TYPO3 v12, PHP 8.1 and PostgreSQL this class
        // hit it whenever another test class ran before it in the same process (ACE-729, the
        // defect itself is recorded with ACE-725). An in-memory cache is never serialized.
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'SYS' => [
                'caching' => [
                    'cacheConfigurations' => [
                        'extbase' => [
                            'backend' => TransientMemoryBackend::class,
                        ],
                    ],
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-bitejobs-stub');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param string[] $additionalSetupFiles
     * @param string[] $additionalConstantFiles
     */
    private function setUpTestCase(array $additionalSetupFiles = [], array $additionalConstantFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicBiteJobsListPlugin/biteJobsListPage.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/constants.typoscript',
                    ...$additionalConstantFiles,
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/setup.typoscript',
                    'EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
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

    /**
     * @param array<string, int|string> $values
     */
    private function updateContentElement(array $values): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', $values, ['uid' => 1]);
    }

    /**
     * Stores the given view value in the plugin FlexForm of the content element, or no view
     * field at all for `null`.
     */
    private function storeViewValue(?string $view): void
    {
        $viewField = $view === null
            ? ''
            : '<field index="settings.jobs.view"><value index="vDEF">' . $view . '</value></field>';
        $this->updateContentElement([
            'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                . '<field index="settings.jobs.jobListingKey"><value index="vDEF">test-key</value></field>'
                . '<field index="settings.jobs.sortBy"><value index="vDEF">title</value></field>'
                . '<field index="settings.jobs.sortingDirection"><value index="vDEF">asc</value></field>'
                . $viewField
                . '<field index="settings.jobs.limit"><value index="vDEF">10</value></field>'
                . '</language></sheet></data></T3FlexForms>',
        ]);
    }

    /**
     * @return string[] The trimmed text of every node the expression selects, in document order.
     */
    private function textsOf(string $html, string $expression): array
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $texts = [];
        $nodes = (new \DOMXPath($document))->query($expression);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);
        foreach ($nodes as $node) {
            $texts[] = trim($node->textContent);
        }

        return $texts;
    }

    /**
     * An XPath predicate matching an element carrying the class as a whole token.
     */
    private function hasClass(string $class): string
    {
        return sprintf('contains(concat(" ", normalize-space(@class), " "), " %s ")', $class);
    }

    #[Test]
    public function biteJobsListPluginIsRendered(): void
    {
        $this->setUpTestCase();

        $this->assertStringContainsString('academic-bite-jobs-list', $this->renderHomePage());
    }

    /**
     * The header layouts "Default", 2 and "Hidden", with the number of times the header and
     * the subheader have to render: "Default" is the layout the header partial resolves
     * through a setting, and the one a plugin rendering it without that setting leaves an
     * empty `<header>` for.
     *
     * @return array<string, array{int, int}>
     */
    public static function headerLayouts(): array
    {
        return [
            'header layout "Default"' => [0, 1],
            'header layout 2' => [2, 1],
            'header layout "Hidden"' => [100, 0],
        ];
    }

    #[Test]
    #[DataProvider('headerLayouts')]
    public function biteJobsListPluginLeavesTheContentElementHeaderToTheLayout(int $headerLayout, int $expectedHeadings): void
    {
        $this->setUpTestCase();
        $this->updateContentElement(['header' => self::HEADER, 'subheader' => self::SUBHEADER, 'header_layout' => $headerLayout]);

        $content = $this->renderHomePage();
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame(0, $this->countHeaderElements($content, self::LIST_WRAPPER));
    }

    #[Test]
    #[DataProvider('headerLayouts')]
    public function biteJobsListPluginRendersTheContentElementHeaderWhenSwitchedOn(int $headerLayout, int $expectedHeadings): void
    {
        $this->setUpTestCase(
            ['EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/LayoutWithoutHeader.typoscript'],
            ['EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/RenderContentElementHeader.typoscript'],
        );
        $this->updateContentElement(['header' => self::HEADER, 'subheader' => self::SUBHEADER, 'header_layout' => $headerLayout]);

        $content = $this->renderHomePage();
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::HEADER, self::LIST_WRAPPER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER));
        $this->assertSame($expectedHeadings, $this->countHeadingsReading($content, self::SUBHEADER, self::LIST_WRAPPER));
    }

    /**
     * Without a grouping field the list is not grouped, so a job title takes the heading
     * level of the header layout itself, and no group heading is rendered.
     */
    #[Test]
    public function jobTitlesTakeTheHeadingLevelOfTheHeaderLayoutWithoutGrouping(): void
    {
        $this->setUpTestCase();
        $this->updateContentElement(['header_layout' => 1]);

        $html = $this->renderHomePage();

        $this->assertSame(
            ['Stubbed job posting', 'Second stubbed job posting'],
            $this->textsOf($html, '//div[contains(@class, "academic-bite-jobs-list")]//h2'),
        );
        $this->assertSame(
            [],
            $this->textsOf($html, '//div[contains(@class, "academic-bite-jobs-list")]//h3'),
        );
    }

    #[Test]
    public function jobsAreGroupedByTheFieldTypoScriptNames(): void
    {
        $this->setUpTestCase([
            'EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/GroupByDepartment.typoscript',
        ]);
        $this->updateContentElement(['header_layout' => 1]);

        $html = $this->renderHomePage();

        $this->assertSame(
            ['Research', 'Teaching'],
            $this->textsOf($html, '//div[contains(@class, "academic-bite-jobs-list")]/h2'),
        );
        $this->assertSame(
            ['Stubbed job posting', 'Second stubbed job posting'],
            $this->textsOf($html, '//div[contains(@class, "academic-bite-jobs-itemlist")]//h3'),
        );
    }

    /**
     * @return array<string, array{0: string|null, 1: string}>
     */
    public static function storedViewValues(): array
    {
        return [
            'List' => ['List', 'academic-bite-jobs-itemlist'],
            'Card' => ['Card', 'academic-bite-jobs-itemcards'],
            'Table' => ['Table', 'academic-bite-jobs-itemtable'],
            'ListView before 2.1' => ['ListView', 'academic-bite-jobs-itemlist'],
            'CardView before 2.1' => ['CardView', 'academic-bite-jobs-itemcards'],
            'TableView before 2.1' => ['TableView', 'academic-bite-jobs-itemtable'],
            'empty value' => ['', 'academic-bite-jobs-itemlist'],
            'unknown value' => ['Grid', 'academic-bite-jobs-itemlist'],
            'no view field' => [null, 'academic-bite-jobs-itemlist'],
        ];
    }

    #[DataProvider('storedViewValues')]
    #[Test]
    public function storedViewValueSelectsTheView(?string $view, string $expectedViewClass): void
    {
        $this->setUpTestCase();
        $this->storeViewValue($view);

        $html = $this->renderHomePage();

        $this->assertCount(
            2,
            $this->textsOf($html, sprintf(
                '//*[%s]//*[%s]',
                $this->hasClass($expectedViewClass),
                $this->hasClass('academic-bite-jobs-item'),
            )),
        );
    }
}
