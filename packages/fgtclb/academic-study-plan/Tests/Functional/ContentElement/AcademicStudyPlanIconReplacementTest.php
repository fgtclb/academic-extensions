<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\ContentElement;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * A site package replaces a glyph of the study plan in its own
 * `Configuration/FrontendIcons.php`, and the study plan shows its drawing without a
 * template override. A replacement left in `Configuration/Icons.php` does not reach the
 * study plan, which shows the shipped glyph.
 *
 * The fixture `tests/study-plan-icons` is that site package: it replaces
 * `academic-study-plan-close` in the file of the frontend and `academic-study-plan-plus`
 * in the file of the backend, both with a rectangle. A TYPO3 v14 test instance orders the
 * packages by their keys, so the first test asserts that the fixture loads after
 * academic_study_plan.
 */
final class AcademicStudyPlanIconReplacementTest extends AbstractAcademicStudyPlanTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * The rectangle of the fixture, as the serialisers of both core versions write it.
     */
    private const REPLACED_DRAWING = 'x="2" y="5" width="12" height="6"';

    /**
     * The path of the shipped `plus.svg`.
     */
    private const SHIPPED_PLUS = 'd="M8 3v10M3 8h10"';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/study-plan-icons');
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicStudyPlanContentElement/studyPlanPage.csv');
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
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theSitePackageLoadsAfterTheExtension(): void
    {
        $packageKeys = array_keys($this->get(PackageManager::class)->getActivePackages());

        $this->assertGreaterThan(
            array_search('academic_study_plan', $packageKeys, true),
            array_search('test_study_plan_icons', $packageKeys, true),
        );
    }

    #[Test]
    public function aFrontendIconOfTheSitePackageReplacesTheCloseGlyph(): void
    {
        $glyphs = $this->renderedIconMarkups($this->renderFrontendPage('https://www.acme.com/home'), 'academic-study-plan-close');

        $this->assertNotSame([], $glyphs);
        foreach ($glyphs as $markup) {
            $this->assertStringContainsString(self::REPLACED_DRAWING, $markup);
        }
    }

    #[Test]
    public function aReplacementInTheBackendFileDoesNotReachThePlusGlyph(): void
    {
        // The extension no longer registers the identifier there, so this is the
        // replacement of the fixture, read by the backend registry.
        $this->assertTrue($this->get(IconRegistry::class)->isRegistered('academic-study-plan-plus'));
        $glyphs = $this->renderedIconMarkups($this->renderFrontendPage('https://www.acme.com/home'), 'academic-study-plan-plus');

        $this->assertNotSame([], $glyphs);
        foreach ($glyphs as $markup) {
            $this->assertStringNotContainsString(self::REPLACED_DRAWING, $markup);
            $this->assertStringContainsString(self::SHIPPED_PLUS, $markup);
        }
    }

    /**
     * The inner markup of every rendered icon with the identifier, in page order.
     *
     * @return list<string>
     */
    private function renderedIconMarkups(string $content, string $identifier): array
    {
        preg_match_all(
            '@data-identifier="' . preg_quote($identifier, '@') . '" aria-hidden="true">\s*<span class="icon-markup">(.*?)</span>@s',
            $content,
            $matches,
        );

        return $matches[1];
    }
}
