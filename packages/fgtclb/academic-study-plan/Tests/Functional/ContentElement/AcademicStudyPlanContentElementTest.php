<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\ContentElement;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academic_study_plan` content element in the frontend.
 *
 * Unlike the other rendering tests of this repository this is not a plugin: there is no
 * Extbase controller at all. The content element is built straight from
 * `lib.contentElement` with a `templateName` and a single data processor, which is why
 * `FrontendPluginRenderingTrait` is used only for its scaffolding — instance
 * configuration, site and request helpers — and no FlexForm is involved.
 *
 * This class is deliberately small. It came with ACE-702, the first change on this branch
 * that needed the rendered content element, and covers what that change is about: the
 * settings of the `Appearance` tab, which the `Default` content element layout renders and
 * a template without that layout silently dropped. `main` carries the full coverage of the
 * element, which was never backported.
 */
final class AcademicStudyPlanContentElementTest extends AbstractAcademicStudyPlanTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicStudyPlanContentElement/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_study_plan/Configuration/TypoScript/ContentElement/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript',
                    'EXT:academic_study_plan/Tests/Functional/ContentElement/Fixtures/TypoScript/Setup/Rendering.typoscript',
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
     * @param array<string, int|string> $fields
     */
    private function updateContentElement(array $fields): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', $fields, ['uid' => 1]);
    }

    private function parseHtml(string $html): \DOMXPath
    {
        $document = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new \DOMXPath($document);
    }

    /**
     * @return string[] The trimmed text of every node the expression selects, in document order.
     */
    private function textsOf(string $html, string $expression): array
    {
        $nodes = $this->parseHtml($html)->query($expression);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);

        $texts = [];
        foreach ($nodes as $node) {
            $texts[] = trim($node->textContent);
        }

        return $texts;
    }

    private function nodeCountOf(string $html, string $expression): int
    {
        $nodes = $this->parseHtml($html)->query($expression);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);

        return $nodes->count();
    }

    /**
     * @return string[] The class tokens of the single node the expression selects.
     */
    private function classesOf(string $html, string $expression): array
    {
        $nodes = $this->parseHtml($html)->query($expression);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);
        $this->assertSame(1, $nodes->count(), 'Expected exactly one node for ' . $expression);
        $node = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node);

        return preg_split('#\s+#', trim($node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    #[Test]
    public function contentElementRendersItsSemestersAndModules(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // The class of the wrapper, not the bare string: the stylesheet URL this branch
        // registers through `page.includeCSS` ends in `academic-study-plan.css` and
        // would satisfy a substring assertion on its own.
        $this->assertStringContainsString('class="academic-study-plan container"', $content);
        $this->assertStringContainsString('data-study-plan="1"', $content);
        $this->assertStringContainsString('First Semester', $content);
        $this->assertStringContainsString('Mathematics I', $content);
    }

    /**
     * The frame wrapper is rendered by the `Default` content element layout, which is
     * the only place in fluid_styled_content that reads the Appearance tab. The class
     * list is asserted whole: it is the contract with that layout, so a core change to
     * it is meant to be seen here.
     */
    #[Test]
    public function contentElementAppliesTheFrameAndSpacingOfTheAppearanceTab(): void
    {
        $this->setUpTestCase('studyPlanPage');
        // All four fields of the `frames` palette, so the pinned list below proves the
        // whole palette rather than three quarters of it.
        $this->updateContentElement([
            'layout' => 1,
            'frame_class' => 'ruler-before',
            'space_before_class' => 'large',
            'space_after_class' => 'small',
        ]);

        $this->assertSame(
            [
                'frame',
                'frame-ruler-before',
                'frame-type-academic_study_plan',
                'frame-layout-1',
                'frame-space-before-large',
                'frame-space-after-small',
            ],
            $this->classesOf($this->renderHomePage(), '//div[@id="c1"]'),
        );
    }

    #[Test]
    public function contentElementCarriesTheAnchorOfItsRecord(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $html = $this->renderHomePage();

        // A link to the element from another page jumps to this anchor.
        $this->assertSame(1, $this->nodeCountOf($html, '//div[@id="c1"]'));
        // The study plan itself is inside the frame, not next to it.
        $this->assertSame(
            1,
            $this->nodeCountOf($html, '//div[@id="c1"]//div[contains(@class, "academic-study-plan")]'),
        );
    }

    /**
     * "No frame" drops the wrapper on every content element of the site. The anchor
     * survives it, as a bare `<a>` the layout renders instead.
     */
    #[Test]
    public function contentElementRendersNoFrameWrapperWithoutAFrame(): void
    {
        $this->setUpTestCase('studyPlanPage');
        $this->updateContentElement(['frame_class' => 'none']);

        $html = $this->renderHomePage();

        $this->assertSame(0, $this->nodeCountOf($html, '//div[@id="c1"]'));
        $this->assertSame(1, $this->nodeCountOf($html, '//a[@id="c1"]'));
        $this->assertSame(1, $this->nodeCountOf($html, '//div[contains(@class, "academic-study-plan")]'));
        $this->assertStringContainsString('First Semester', $html);
    }

    /**
     * The Appearance tab carries a second palette, `appearanceLinks`, and the layout
     * renders its `linkToTop` through the `Footer` section. It is the one setting of
     * that tab whose effect needs no stylesheet to be visible.
     */
    #[Test]
    public function contentElementRendersTheLinkToTopTheEditorAskedFor(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $this->assertSame(0, $this->nodeCountOf($this->renderHomePage(), '//div[@id="c1"]//a[@href="#top"]'));

        $this->updateContentElement(['linkToTop' => 1]);

        $this->assertSame(1, $this->nodeCountOf($this->renderHomePage(), '//div[@id="c1"]//a[@href="#top"]'));
    }

    /**
     * The layout renders the header, once, and before the study plan wrapper rather than
     * inside it. Both halves matter: a template that renders `Header/All` itself *and*
     * uses the layout would render it twice.
     */
    #[Test]
    public function contentElementRendersTheHeaderOnceInTheLayoutHeadingLevel(): void
    {
        $this->setUpTestCase('studyPlanPage');
        $this->updateContentElement(['header' => 'Study plan B.Sc.', 'header_layout' => 2]);

        $html = $this->renderHomePage();

        $this->assertSame(['Study plan B.Sc.'], $this->textsOf($html, '//div[@id="c1"]/header/h2'));
        $this->assertSame(
            [],
            $this->textsOf($html, '//div[contains(@class, "academic-study-plan")]//header'),
        );
    }

    #[Test]
    public function contentElementRendersNoHeaderWhenTheEditorHidIt(): void
    {
        $this->setUpTestCase('studyPlanPage');
        $this->updateContentElement(['header' => 'Study plan B.Sc.', 'header_layout' => 100]);

        $html = $this->renderHomePage();

        $this->assertStringNotContainsString('Study plan B.Sc.', $html);
        // The element itself still renders.
        $this->assertStringContainsString('First Semester', $html);
    }
}
