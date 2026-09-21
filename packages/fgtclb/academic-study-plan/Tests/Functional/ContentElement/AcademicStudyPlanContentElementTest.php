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
 * configuration, site and request helpers — and no FlexForm is involved. The
 * configuration lives on the content element record itself.
 *
 * The template renders through the `Default` content element layout, which renders the
 * `EXT:fluid_styled_content` `Header/All` partial for it. On TYPO3 v14 that partial
 * resolves the header through the `record` view variable. Here that variable comes from
 * the record transformation of `lib.contentElement` rather than from a view, which is
 * what `contentElementRendersHeader()` verifies rather than assumes.
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

        return preg_split('#\\s+#', trim($node->getAttribute('class')), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    #[Test]
    public function contentElementRendersItsSemesters(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-study-plan', $content);
        $this->assertStringContainsString('data-study-plan="1"', $content);
        $this->assertStringContainsString('First Semester', $content);
        $this->assertStringContainsString('Second Semester', $content);
        // The note of a semester is optional and only the first one has it.
        $this->assertStringContainsString('Foundation courses', $content);
    }

    #[Test]
    public function contentElementRendersTheModulesOfEachSemester(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Mathematics I', $content);
        $this->assertStringContainsString('Programming Basics', $content);
        $this->assertStringContainsString('Statistics', $content);
        $this->assertStringContainsString('Mandatory attendance', $content);
    }

    #[Test]
    public function contentElementRendersCreditPointsOfSemestersAndModules(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // Both the semester and the module render their credit points followed by the
        // `credits` label, which this extension abbreviates to "CP".
        $this->assertMatchesRegularExpression('#30\s+CP#', $content);
        $this->assertMatchesRegularExpression('#10\s+CP#', $content);
    }

    #[Test]
    public function contentElementRendersHeader(): void
    {
        $this->setUpTestCase('studyPlanPage');
        $this->updateContentElement(['header' => 'Study plan B.Sc.']);

        // The layout renders the fluid_styled_content header partial, which resolves the
        // header through `record` on TYPO3 v14. Here that comes from the record
        // transformation of `lib.contentElement` rather than from an Extbase view.
        $this->assertStringContainsString('Study plan B.Sc.', $this->renderHomePage());
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
     * The filter list holds one `<li>`, and it is a template rather than a control: the
     * module clones it per category and empties the list first. Its placeholder text is
     * therefore never meant to be on screen, so it is rendered `hidden` - unconditionally,
     * because a script can fail to load on any installation.
     */
    #[Test]
    public function contentElementHidesTheFilterTemplateItem(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $html = $this->renderHomePage();

        $this->assertSame(1, $this->nodeCountOf($html, '//ul[@class="filter"]/li'));
        $this->assertSame(1, $this->nodeCountOf($html, '//ul[@class="filter"]/li[@hidden]'));
        // The placeholders themselves stay: they are what the module substitutes.
        $this->assertStringContainsString('category-label-placeholder', $html);
    }

    #[Test]
    public function contentElementRendersDialogForModuleWithDescription(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // A module with a description is clickable and gets its own dialog.
        $this->assertStringContainsString('<dialog id="popup-1">', $content);
        $this->assertStringContainsString('Linear algebra and analysis.', $content);
        $this->assertStringContainsString('class="module clickable"', $content);
    }

    #[Test]
    public function contentElementLabelsTheModalTriggerForScreenReaders(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // The trigger carries no visible text, so its visually hidden span is the only
        // thing announcing what the control does. A missing `modal.open` label does not
        // fail loudly: `f:translate` resolves it to an empty string and the span
        // degrades to a bare ": Mathematics I".
        $this->assertMatchesRegularExpression(
            '#First Semester,\s+Foundation courses,\s+30\s+CP\.\s+Show module details: Mathematics I#',
            $content,
        );
        $this->assertDoesNotMatchRegularExpression('#CP\.\s+: Mathematics I#', $content);
    }

    #[Test]
    public function contentElementRendersOnlyResolvableIcons(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // `core:icon` never fails on an unknown identifier: it renders the
        // `default-not-found` placeholder, the small red "broken" icon, and the
        // identifier that was asked for is gone from the markup.
        $this->assertStringNotContainsString('default-not-found', $content);
        // The three identifiers the element actually asks for, so a rename in
        // `Configuration/Icons.php` without one in the template is caught here too.
        $this->assertStringContainsString('data-identifier="academic-study-plan-plus"', $content);
        $this->assertStringContainsString('data-identifier="academic-study-plan-minus"', $content);
        $this->assertStringContainsString('data-identifier="academic-study-plan-close"', $content);
    }

    #[Test]
    public function contentElementRendersNoDialogForModuleWithoutContent(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // Module 2 has neither description nor audio file, so it stays inert.
        $this->assertStringNotContainsString('<dialog id="popup-2">', $content);
    }

    #[Test]
    public function contentElementRendersModuleCategoriesAsDataAttribute(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // The categories of a module travel to the markup as JSON for the filter script.
        $this->assertStringContainsString('Mandatory', $content);
        $this->assertStringContainsString('Elective', $content);
        $this->assertStringContainsString('#cc0000', $content);
    }

    #[Test]
    public function contentElementRendersFooterNote(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $this->assertStringContainsString(
            'All modules are subject to change.',
            $this->renderHomePage(),
        );
    }

    #[Test]
    public function contentElementOmitsFooterNoteWhenEmpty(): void
    {
        $this->setUpTestCase('studyPlanPage_withoutFooterNote');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('First Semester', $content);
        $this->assertStringNotContainsString('All modules are subject to change.', $content);
    }

    #[Test]
    public function contentElementHidesHiddenSemestersAndModules(): void
    {
        $this->setUpTestCase('studyPlanPage_hiddenRecords');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('First Semester', $content);
        $this->assertStringContainsString('Mathematics I', $content);
        $this->assertStringNotContainsString('Hidden Semester', $content);
        $this->assertStringNotContainsString('Hidden Module', $content);
    }

    #[Test]
    public function contentElementRendersWithoutSemesters(): void
    {
        $this->setUpTestCase('studyPlanPage_withoutSemesters');

        $content = $this->renderHomePage();
        // The element still renders, only the semester list is skipped.
        $this->assertStringContainsString('academic-study-plan', $content);
        $this->assertStringNotContainsString('<ul class="semesters row">', $content);
    }
}
