<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\ContentElement;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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

    /**
     * @return string[] The class attribute of every element below the study plan, in
     *         document order, without the ones the icon ViewHelper of academic_base
     *         writes - those are the markup of the TYPO3 icon API and differ between
     *         TYPO3 versions.
     */
    private function classInventoryOf(string $html): array
    {
        $nodes = $this->parseHtml($html)->query('//div[contains(@class, "academic-study-plan")]//*[@class]');
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);

        $classes = [];
        foreach ($nodes as $node) {
            $this->assertInstanceOf(\DOMElement::class, $node);
            $class = $node->getAttribute('class');
            if (!str_contains($class, 'icon')) {
                $classes[] = $class;
            }
        }

        return $classes;
    }

    private function visibleTextOf(string $html): string
    {
        $nodes = $this->parseHtml($html)->query('//div[contains(@class, "academic-study-plan")]');
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);
        $node = $nodes->item(0);
        $this->assertInstanceOf(\DOMElement::class, $node);

        return preg_replace('#\\s+#', ' ', trim($node->textContent)) ?? '';
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

    /**
     * Credit points are stored with two decimals, and read back as "2.50" and "30.00"
     * on every DBMS. What the visitor reads carries no trailing zeros - in the column
     * header, in the module, in its dialog and in the label the dialog trigger
     * announces - because the template receives numbers rather than database strings.
     */
    #[Test]
    public function contentElementRendersDecimalCreditPointsWithoutTrailingZeros(): void
    {
        $this->setUpTestCase('studyPlanPage_decimalCreditPoints');

        $this->assertSame(
            'category-label-placeholder First Semester 30 CP Mathematics I 2.5 CP '
            . 'First Semester, 30 CP. Show module details: Mathematics I Mathematics I 2.5 CP '
            . 'Linear algebra and analysis. Colloquium Second Semester Thesis 12.75 CP '
            . 'Second Semester. Show module details: Thesis Thesis 12.75 CP Final project.',
            $this->visibleTextOf($this->renderHomePage()),
        );
    }

    /**
     * A semester or module without credit points shows none. The partials guard the
     * value with a condition, which is false for 0 and for "0.00" alike - Fluid reads
     * a numeric string as a number - so this is a guard for the markup rather than
     * for the number the template receives.
     */
    #[Test]
    public function contentElementRendersNoCreditPointsWhereThereAreNone(): void
    {
        $this->setUpTestCase('studyPlanPage_decimalCreditPoints');

        $this->assertSame(
            ['30 CP', '2.5 CP', '2.5 CP', '12.75 CP', '12.75 CP'],
            // A credit points span holds the number and the unit and nothing else.
            $this->textsOf($this->renderHomePage(), '//span[not(*)][substring(normalize-space(), string-length(normalize-space()) - 2) = " CP"]'),
        );
    }

    /**
     * The label the dialog trigger announces names the credit points of the semester
     * only when it has them. A semester without them is not announced as "0 CP", the
     * same as the column header shows none for it (ACE-863).
     */
    #[Test]
    public function contentElementAnnouncesNoCreditPointsForASemesterWithoutThem(): void
    {
        $this->setUpTestCase('studyPlanPage_decimalCreditPoints');

        $this->assertSame(
            [
                'First Semester, 30 CP. Show module details: Mathematics I',
                'Second Semester. Show module details: Thesis',
            ],
            $this->dialogTriggerLabelsOf($this->renderHomePage()),
        );
    }

    /**
     * The label the dialog trigger announces names the note of the semester only when
     * there is one. A semester without a note must not leave an empty item behind,
     * which a screen reader reads out as "Second Semester, comma, 30 CP" (ACE-863).
     */
    #[Test]
    public function contentElementAnnouncesTheNoteOfASemesterOnlyWhenItHasOne(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $this->assertSame(
            [
                'First Semester, Foundation courses, 30 CP. Show module details: Mathematics I',
                'Second Semester, 30 CP. Show module details: Statistics',
            ],
            $this->dialogTriggerLabelsOf($this->renderHomePage()),
        );
    }

    /**
     * @return string[] The visually hidden label of every dialog trigger, whitespace collapsed
     *                  as a screen reader reads it.
     */
    private function dialogTriggerLabelsOf(string $html): array
    {
        return array_map(
            static fn(string $text): string => preg_replace('#\\s+#', ' ', $text) ?? '',
            $this->textsOf($html, '//button[@data-study-plan-dialog-trigger]/span'),
        );
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

        $this->assertSame(1, $this->nodeCountOf($html, '//ul[@data-study-plan-filter]/li'));
        $this->assertSame(1, $this->nodeCountOf($html, '//ul[@data-study-plan-filter]/li[@hidden]'));
        // The placeholders themselves stay: they are what the module substitutes.
        $this->assertStringContainsString('category-label-placeholder', $html);
    }

    /**
     * The contract between the templates and the script: every part the script drives
     * is found by its own `data-study-plan-*` attribute, on the element the manual
     * names. An override that keeps them keeps the interaction, whatever it does to the
     * classes - so a template that drops one of them is a defect this test is here to
     * report.
     */
    #[Test]
    public function contentElementCarriesTheDataAttributeContract(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $html = $this->renderHomePage();

        // One container, and it is the element the classes name as well.
        $this->assertSame(
            1,
            $this->nodeCountOf($html, '//div[@data-study-plan="1"][contains(@class, "academic-study-plan")]'),
        );
        // One filter list, holding the one item the script clones.
        $this->assertSame(1, $this->nodeCountOf($html, '//div[@data-study-plan]//ul[@data-study-plan-filter]'));
        $this->assertSame(
            1,
            $this->nodeCountOf($html, '//ul[@data-study-plan-filter]/li[@data-study-plan-filter-template]'),
        );
        // Two semesters, each with its header.
        $this->assertSame(2, $this->nodeCountOf($html, '//div[@data-study-plan]/div[@role="list"]/div[@role="listitem"][@data-study-plan-semester]'));
        $this->assertSame(
            2,
            $this->nodeCountOf($html, '//div[@data-study-plan-semester]/div[@data-study-plan-semester-header]'),
        );
        // Three modules, of which the two with content carry a trigger and a dialog.
        $this->assertSame(3, $this->nodeCountOf($html, '//li[@data-study-plan-module]'));
        $this->assertSame(
            2,
            $this->nodeCountOf($html, '//li[@data-study-plan-module]/button[@data-study-plan-dialog-trigger]'),
        );
        $this->assertSame(
            2,
            $this->nodeCountOf($html, '//li[@data-study-plan-module]/dialog[@data-study-plan-dialog]'),
        );
    }

    /**
     * What a visitor reads is pinned against the output of the unsplit template, recorded
     * from this very fixture, and what a stylesheet selects against the classes the
     * templates render, so a partial that loses an element or a class fails here rather
     * than in an installation.
     */
    #[Test]
    public function contentElementRendersTheTextOfTheUnsplitTemplateAndItsClasses(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $html = $this->renderHomePage();

        $this->assertSame(
            'category-label-placeholder First Semester 30 CP Foundation courses Mathematics I 10 CP '
            . 'Mandatory attendance First Semester, Foundation courses, 30 CP. Show module details: '
            . 'Mathematics I Mathematics I 10 CP Mandatory attendance Linear algebra and analysis. '
            . 'Programming Basics 5 CP Second Semester 30 CP Statistics 8 CP Second Semester, 30 CP. '
            . 'Show module details: Statistics Statistics 8 CP Descriptive and inductive. '
            . 'All modules are subject to change.',
            $this->visibleTextOf($html),
        );
        $this->assertSame(
            [
                'row',
                'col',
                'change-me',
                'visually-hidden',
                // The module without content: the condition that adds the class leaves the
                // attribute empty.
                '',
                'col',
                'change-me',
                'visually-hidden',
            ],
            $this->classInventoryOf($html),
        );
    }

    #[Test]
    public function contentElementRendersDialogForModuleWithDescription(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // A module with a description is clickable and gets its own dialog.
        $this->assertStringContainsString('<dialog id="popup-1" data-study-plan-dialog>', $content);
        $this->assertStringContainsString('Linear algebra and analysis.', $content);
        $this->assertStringContainsString('class="change-me"', $content);
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
        // The glyphs are frontend icons, rendered by `ab:icon` from the frontend icon
        // registry. It never fails on an unknown identifier: it renders the
        // `default-not-found` placeholder, and the identifier that was asked for is gone
        // from the markup. A glyph missing from the `Configuration/FrontendIcons.php` of
        // academic_base, or a tag left on `core:icon`, which asks the registry of the
        // backend, ends there.
        $this->assertStringNotContainsString('default-not-found', $content);
        // The three shared identifiers the element actually asks for, so a rename in
        // academic_base without one in the partials is caught here too.
        $this->assertStringContainsString('data-identifier="tx-academicbase-action-expand"', $content);
        $this->assertStringContainsString('data-identifier="tx-academicbase-action-collapse"', $content);
        $this->assertStringContainsString('data-identifier="tx-academicbase-action-close"', $content);
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function controlIconIdentifiers(): \Generator
    {
        yield 'accordion expand' => ['tx-academicbase-action-expand'];
        yield 'accordion collapse' => ['tx-academicbase-action-collapse'];
        yield 'dialog close' => ['tx-academicbase-action-close'];
    }

    /**
     * An inline SVG with a `viewBox` and no size collapses to 0 px inside the flex header
     * of a semester and inside the shrink-to-fit close button of the dialog, which is how
     * the three controls were invisible before 3.0. Asserted on the rendered page: the
     * icon is inlined, drawn in the text colour, and carries a size of its own. The
     * stylesheet sizes it as well, see `stylesheetSelectsTheRenderedControlIcons()`.
     */
    #[Test]
    #[DataProvider('controlIconIdentifiers')]
    public function contentElementRendersControlIconsInlineWithASize(string $identifier): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();

        $pattern = '#<span class="[^"]*\bicon-' . preg_quote($identifier, '#') . '\b[^"]*" data-identifier="'
            . preg_quote($identifier, '#') . '"[^>]*>\s*<span class="icon-markup">\s*(<svg\b[^>]*>)#';
        $this->assertMatchesRegularExpression($pattern, $content, sprintf('Icon "%s" is not rendered as inline SVG.', $identifier));
        preg_match($pattern, $content, $matches);
        $svg = $matches[1] ?? '';
        $this->assertStringContainsString('width="1em"', $svg);
        $this->assertStringContainsString('height="1em"', $svg);
        $this->assertStringContainsString('fill="currentColor"', $svg);
    }

    /**
     * The stylesheet selects the accordion glyphs by the class the icon markup derives from
     * the identifier, so a renamed identifier leaves the stylesheet selecting nothing: both
     * glyphs show, or neither. It also sizes every inlined icon of the element, so a site
     * that registers a drawing without a size does not bring the 0 px icons back.
     */
    #[Test]
    public function stylesheetSelectsTheRenderedControlIcons(): void
    {
        $css = (string)file_get_contents(
            GeneralUtility::getFileAbsFileName('EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css')
        );

        $this->assertStringContainsString('.col .icon-tx-academicbase-action-collapse {', $css);
        $this->assertStringContainsString('.col.open .icon-tx-academicbase-action-expand {', $css);
        $this->assertStringContainsString('.col.open .icon-tx-academicbase-action-collapse {', $css);
        $this->assertMatchesRegularExpression(
            '#\.academic-study-plan \.icon \{[^}]*\bwidth: 1\.25rem;[^}]*\bheight: 1\.25rem;#',
            $css,
        );
        $this->assertMatchesRegularExpression(
            '#\.academic-study-plan \.icon svg \{[^}]*\bwidth: 100%;[^}]*\bheight: 100%;#',
            $css,
        );
    }

    /**
     * The shipped stylesheet switches the glyphs of a semester header through the classes
     * of their wrappers: `.icon-tx-academicbase-action-collapse` is hidden in a closed
     * semester, `.icon-tx-academicbase-action-expand` in an open one, and every `.icon` of
     * the header on a wide viewport. The script never looks at them, so nothing else
     * notices when the classes are gone.
     */
    #[Test]
    public function contentElementGivesTheGlyphsTheClassesTheStylesheetSelects(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $xpath = $this->parseHtml($this->renderHomePage());
        $headers = $xpath->query('//*[@data-study-plan-semester-header]');
        $this->assertInstanceOf(\DOMNodeList::class, $headers);
        $this->assertGreaterThan(0, $headers->count());
        foreach ($headers as $header) {
            foreach (['icon-tx-academicbase-action-expand', 'icon-tx-academicbase-action-collapse'] as $glyphClass) {
                $glyphs = $xpath->query(
                    sprintf(
                        './/*[contains(concat(" ", normalize-space(@class), " "), " icon ")'
                        . ' and contains(concat(" ", normalize-space(@class), " "), " %s ")]',
                        $glyphClass,
                    ),
                    $header,
                );
                $this->assertInstanceOf(\DOMNodeList::class, $glyphs);
                $this->assertSame(1, $glyphs->count(), sprintf('A semester header renders not exactly one "%s" glyph.', $glyphClass));
            }
        }
    }

    #[Test]
    public function contentElementRendersNoDialogForModuleWithoutContent(): void
    {
        $this->setUpTestCase('studyPlanPage');

        $content = $this->renderHomePage();
        // Module 2 has neither description nor audio file, so it stays inert.
        $this->assertStringNotContainsString('id="popup-2"', $content);
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
        $this->assertSame(0, $this->nodeCountOf($content, '//div[@data-study-plan]/div[@role="list"]'));
    }
}
