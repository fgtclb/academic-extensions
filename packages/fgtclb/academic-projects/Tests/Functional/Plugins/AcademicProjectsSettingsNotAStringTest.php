<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * TypoScript makes an array of a setting as soon as it has a child. Extbase merges the
 * FlexForm of an element over the TypoScript settings, but a stored string does not replace
 * an array there (`ArrayUtility::mergeRecursiveWithOverrule()`), so every element gets the
 * array, whether it stores a value or not. Such an array is ignored like an unknown value,
 * so the list keeps the default of the extension, title, A to Z, and all projects,
 * instead of failing with a `TypeError` (ACE-926). `AcademicProjectsFilterUrlTest` covers
 * the values of a request.
 *
 * Quantum Research and Wind Parks run without an end date, Solar Fields ended in 2000.
 *
 * - `/stored`: a list that stores the sorting title, Z to A, and the active projects.
 * - `/no-settings`: a list that stores no setting at all.
 */
final class AcademicProjectsSettingsNotAStringTest extends AbstractAcademicProjectsTestCase
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

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectsSettingsNotAString/records.csv');
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
                    'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/AcademicProjectsSettingsNotAString/SettingsWithAChild.typoscript',
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
    public function settingsThatTypoScriptTurnedIntoArraysAreIgnored(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/no-settings');

        $this->assertRenderedInOrder($content, 'Quantum Research', 'Solar Fields');
        $this->assertRenderedInOrder($content, 'Solar Fields', 'Wind Parks');
    }

    /**
     * The sorting title, Z to A, and the active state "active" the element stores do not
     * replace the arrays of TypoScript, so this element keeps the defaults of the
     * extension as well: title, A to Z, and the ended Solar Fields listed beside the
     * running projects.
     */
    #[Test]
    public function storedSettingsDoNotReplaceTheArraysOfTypoScript(): void
    {
        $content = $this->renderFrontendPage('https://www.acme.com/stored');

        $this->assertRenderedInOrder($content, 'Quantum Research', 'Solar Fields');
        $this->assertRenderedInOrder($content, 'Solar Fields', 'Wind Parks');
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
