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
 * The template renders the `EXT:fluid_styled_content` `Header/All` partial, which on
 * TYPO3 v14 resolves the header through the `record` view variable. Here that variable
 * comes from the record transformation of `lib.contentElement` rather than from a view,
 * which is what `contentElementRendersHeader()` verifies rather than assumes.
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

    private function setContentElementHeader(string $header): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => $header], ['uid' => 1]);
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
        $this->setContentElementHeader('Study plan B.Sc.');

        // The template renders the fluid_styled_content header partial, which resolves
        // the header through `record` on TYPO3 v14. Here that comes from the record
        // transformation of `lib.contentElement` rather than from an Extbase view.
        $this->assertStringContainsString('Study plan B.Sc.', $this->renderHomePage());
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
     * The three controls used to be invisible: their files had a viewBox but no width or
     * height, and an inline SVG without a size collapses to 0 px inside the flex header of
     * a semester and inside the shrink-to-fit close button of the dialog. Asserted on the
     * rendered markup: the icon is inlined, drawn in the text colour, and carries a size of
     * its own. The stylesheet sizes it as well, see the test below.
     *
     * The class `icon-<identifier>` is asserted too, because the stylesheet toggles the
     * accordion icons through it.
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
     * The stylesheet addresses the accordion icons by the class the icon markup derives from
     * the identifier, so a renamed identifier leaves the stylesheet selecting nothing: both
     * icons show, or neither. It also has to size the inlined icons itself, so a project
     * that registers a drawing without a size does not bring the 0 px icons back.
     */
    #[Test]
    public function stylesheetAddressesTheRenderedControlIcons(): void
    {
        $css = (string)file_get_contents(
            GeneralUtility::getFileAbsFileName('EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css')
        );

        $this->assertStringContainsString('.col .icon-tx-academicbase-action-collapse {', $css);
        $this->assertStringContainsString('.col.open .icon-tx-academicbase-action-expand {', $css);
        $this->assertStringContainsString('.col.open .icon-tx-academicbase-action-collapse {', $css);
        $this->assertMatchesRegularExpression(
            '#\.academic-study-plan \.icon svg \{[^}]*\bwidth: 1em;[^}]*\bheight: 1em;#',
            $css,
        );
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
