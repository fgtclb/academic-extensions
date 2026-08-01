<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academicprojects_projectlistsingle` plugin in a second site language.
 *
 * A project is a page, and the plugin holds a manual selection of page uids. As in
 * `EXT:academic_persons`, FormEngine stores that selection as default language uids, so
 * `ProjectRepository` drops the language restriction before matching them - the shape
 * that cost the persons plugins their translations under `fallbackType: free` (ACE-341).
 *
 * It was tempting to assume `pages` escapes that, since
 * `PageRepository::getLanguageOverlay()` routes pages through `getPageOverlay()` even in
 * free mode. Rendering it said otherwise: before the fix this plugin listed
 * `[EN] Solar fields project` on the German page, exactly like the persons ones. Hence
 * the same `matchSelectedUidsAcrossLanguages()` here.
 */
final class AcademicProjectsProjectListLocalizationTest extends AbstractAcademicProjectsTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsProjectListLocalization/' . $dataSet . '.csv');
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

    private function renderGermanPage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/de/home');
    }

    #[Test]
    public function selectedProjectRendersTranslatedWithFallbackTypeStrict(): void
    {
        $this->setUpTestCase('projectListSinglePage_fullyLocalized');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Solarfelder Projekt', $content);
        $this->assertStringNotContainsString('[EN] Solar fields project', $content);
    }

    #[Test]
    public function selectedProjectRendersTranslatedWithFallbackTypeFallback(): void
    {
        $this->setUpTestCase('projectListSinglePage_fullyLocalized', 'fallback');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Solarfelder Projekt', $content);
        $this->assertStringNotContainsString('[EN] Solar fields project', $content);
    }

    #[Test]
    public function selectedProjectRendersTranslatedWithFallbackTypeFree(): void
    {
        // The case that broke the persons plugins, and this one with them: without the
        // aspect fix-up the German page listed the English project title.
        $this->setUpTestCase('projectListSinglePage_fullyLocalized', 'free');

        $content = $this->renderGermanPage();
        $this->assertStringContainsString('[DE] Unser Projekt', $content);
        $this->assertStringContainsString('[DE] Solarfelder Projekt', $content);
        $this->assertStringNotContainsString('[EN] Solar fields project', $content);
    }

    #[Test]
    public function selectedProjectDoesNotLeakTheUnselectedOne(): void
    {
        $this->setUpTestCase('projectListSinglePage_fullyLocalized', 'free');

        $content = $this->renderGermanPage();
        $this->assertStringNotContainsString('Quantenforschungsprojekt', $content);
        $this->assertStringNotContainsString('Quantum research project', $content);
    }
}
