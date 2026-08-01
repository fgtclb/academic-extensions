<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\ContentElement;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academic_study_plan` content element in a second site language.
 *
 * The translation path of this element is hand written rather than inherited from
 * Extbase: `StudyPlanProcessor` resolves the content element through its
 * `transOrigPointerField` and hands the language to `StudyPlanService`, which selects
 * `sys_language_uid IN (0, -1)` only and overlays every semester, module and category
 * one by one through `PageRepository::getLanguageOverlay()`.
 *
 * Two consequences of that design decide what these tests assert, and both were read
 * off core rather than assumed:
 *
 * - `getRecordOverlay()` keeps the **default language** `uid` on an overlaid row and
 *   puts the translation uid into `_LOCALIZED_UID`. The service therefore walks the
 *   record tree by default language uids, which is why the fixtures wire the mm rows
 *   of a module to its default language uid only.
 * - `getLanguageOverlay()` overlays nothing when the language aspect has
 *   `OVERLAYS_OFF`, which is what a site language of `fallbackType: free` produces
 *   (`LanguageAspectFactory::createFromSiteLanguage()`). The element then renders its
 *   default language children on a translated page - documented by
 *   {@see self::freeModeRendersDefaultLanguageChildrenForTranslatedContentElement()}
 *   rather than silently accepted.
 */
final class AcademicStudyPlanContentElementLocalizationTest extends AbstractAcademicStudyPlanTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
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
     * @param 'strict'|'fallback'|'free' $fallbackType
     */
    private function setUpTestCase(string $dataSet, string $fallbackType = 'strict'): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicStudyPlanContentElementLocalization/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_study_plan/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_study_plan/Tests/Functional/ContentElement/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: $fallbackType === 'fallback' ? ['EN'] : [],
                fallbackType: $fallbackType,
            ),
        ]);
    }

    private function renderEnglishPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    private function renderGermanPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/de/home');
    }

    #[Test]
    public function translatedContentElementRendersTranslatedSemesters(): void
    {
        $this->setUpTestCase('localizedStudyPlan');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Erstes Semester', $content);
        $this->assertStringContainsString('[DE] Zweites Semester', $content);
        $this->assertStringContainsString('[DE] Grundlagenkurse', $content);
        $this->assertStringNotContainsString('First Semester', $content);
        $this->assertStringNotContainsString('Second Semester', $content);
    }

    #[Test]
    public function translatedContentElementRendersTranslatedModules(): void
    {
        $this->setUpTestCase('localizedStudyPlan');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Mathematik I', $content);
        $this->assertStringContainsString('[DE] Programmiergrundlagen', $content);
        $this->assertStringContainsString('[DE] Statistik', $content);
        $this->assertStringContainsString('[DE] Anwesenheitspflicht', $content);
        $this->assertStringContainsString('[DE] Lineare Algebra und Analysis.', $content);
        $this->assertStringNotContainsString('Mathematics I', $content);
    }

    #[Test]
    public function translatedContentElementRendersTranslatedCategories(): void
    {
        $this->setUpTestCase('localizedStudyPlan');

        // The categories travel to the markup as a JSON data attribute for the filter
        // script, so the translated label has to show up inside it.
        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Pflicht', $content);
        $this->assertStringContainsString('[DE] Wahlfach', $content);
        $this->assertStringNotContainsString('Mandatory&quot;', $content);
    }

    #[Test]
    public function defaultLanguageStillRendersDefaultLanguageRecords(): void
    {
        $this->setUpTestCase('localizedStudyPlan');

        $content = $this->renderEnglishPage();
        $this->assertStringContainsString('First Semester', $content);
        $this->assertStringContainsString('Mathematics I', $content);
        $this->assertStringNotContainsString('[DE]', $content);
    }

    #[Test]
    public function categoriesAreResolvedThroughTheDefaultLanguageModule(): void
    {
        // The translated module carries mm rows of its own, pointing at the translated
        // categories - what DataHandler leaves behind after localizing a module. The
        // service must not pick them up: it walks the record tree by default language
        // uids, so each category has to appear once, through the overlay, and the
        // relations of the translated module have to stay unused.
        $this->setUpTestCase('localizedStudyPlan_localizedMmRows');

        $content = $this->renderGermanPage();
        $this->assertSame(1, substr_count($content, '[DE] Pflicht'));
        $this->assertSame(1, substr_count($content, '[DE] Wahlfach'));
    }

    #[Test]
    public function freeModeRendersDefaultLanguageChildrenForTranslatedContentElement(): void
    {
        // Free mode is the one configuration in which the `l10n_parent` hop of
        // `StudyPlanProcessor` is load bearing: no overlay happens, so the translated
        // content element itself is rendered, uid and all. Its semesters point at the
        // translated element, but the service only ever selects language 0 and -1, so
        // without the hop to the default language element nothing would resolve at all.
        $this->setUpTestCase('localizedStudyPlan', 'free');

        $content = $this->renderGermanPage();
        // The translated element is what renders ...
        $this->assertStringContainsString('[DE] Studienplan', $content);
        // ... while its children come from the default language, unoverlaid. This is
        // current behaviour, not an endorsement: a free mode site shows English
        // semesters below a German heading.
        $this->assertStringContainsString('First Semester', $content);
        $this->assertStringContainsString('Mathematics I', $content);
        $this->assertStringNotContainsString('[DE] Erstes Semester', $content);
    }

    #[Test]
    public function strictModeKeepsUntranslatedRecordsInTheDefaultLanguage(): void
    {
        // `getTranslatedRecord()` ends in `... ?? $row`, so a record without a
        // translation survives as its default language row even though the site
        // language is strict and core would drop an untranslated `tt_content`.
        // Documented, not asserted as desirable.
        $this->setUpTestCase('localizedStudyPlan_partiallyTranslated');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Erstes Semester', $content);
        $this->assertStringContainsString('[DE] Mathematik I', $content);
        $this->assertStringContainsString('Second Semester', $content);
        $this->assertStringContainsString('Programming Basics', $content);
        $this->assertStringContainsString('Statistics', $content);
    }

    #[Test]
    public function fallbackModeKeepsUntranslatedRecordsInTheDefaultLanguage(): void
    {
        $this->setUpTestCase('localizedStudyPlan_partiallyTranslated', 'fallback');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Erstes Semester', $content);
        $this->assertStringContainsString('[DE] Mathematik I', $content);
        $this->assertStringContainsString('Second Semester', $content);
        $this->assertStringContainsString('Programming Basics', $content);
    }

    #[Test]
    public function categoryRelationsIgnoreHiddenDeletedAndTranslationOnlyRecords(): void
    {
        // The state the two `@todo`s in `fetchCategoriesForModule()` ask about. The
        // relation query joins the mm table only, without the module table, and this
        // is what that costs: nothing. Every category below hangs off the same module.
        $this->setUpTestCase('localizedStudyPlan_categoryEdgeCases');

        $content = $this->renderGermanPage();
        // Translated, so the German label wins.
        $this->assertStringContainsString('[DE] Pflicht', $content);
        // Hidden and deleted categories never enter the result set.
        $this->assertStringNotContainsString('Hidden Category', $content);
        $this->assertStringNotContainsString('Deleted Category', $content);
        // A record that exists only as a translation is not a default language row, so
        // the `IN (0, -1)` constraint keeps it out - it can never be overlaid onto
        // anything.
        $this->assertStringNotContainsString('[DE] Nur uebersetzt', $content);
        // A category whose translation is hidden falls back to the default language
        // label rather than disappearing: the overlay finds nothing and
        // `getTranslatedRecord()` returns the original row.
        $this->assertStringContainsString('Elective', $content);
        $this->assertStringNotContainsString('[DE] Wahlfach hidden', $content);
        // And the relations of a hidden module stay unreachable, because that module is
        // never fetched in the first place - which is precisely why joining the module
        // table into this query would add nothing.
        $this->assertStringNotContainsString('Hidden Module', $content);
        $this->assertStringNotContainsString('Only On Hidden Module', $content);
    }

    #[Test]
    public function recordsForAllLanguagesRenderInBothLanguages(): void
    {
        // `sys_language_uid = -1` is why the query says `IN (0, -1)` rather than `= 0`.
        // Core returns such rows from the overlay untouched, so they have to show up
        // unchanged in either language.
        $this->setUpTestCase('localizedStudyPlan_allLanguagesRecords');

        $english = $this->renderEnglishPage();
        $this->assertStringContainsString('Shared Semester', $english);
        $this->assertStringContainsString('Shared Module', $english);

        $german = $this->renderGermanPage();
        $this->assertStringContainsString('Shared Semester', $german);
        $this->assertStringContainsString('Shared Module', $german);
        $this->assertStringContainsString('[DE] Erstes Semester', $german);
    }
}
