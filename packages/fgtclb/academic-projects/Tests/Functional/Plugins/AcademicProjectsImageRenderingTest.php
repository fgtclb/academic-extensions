<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Plugins;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\FunctionalTestCase\ResponsiveImageAssertionTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the image of the project list through the shared image partial of academic_base.
 *
 * The list item asks for the `card` preset: four sources and a fallback processed to 690
 * pixels, which is what separates it from the `logo` preset the partner lists use. The
 * fixture gives Quantum Research an 800 x 600 image and Solar fields none, so one
 * request renders both branches.
 */
final class AcademicProjectsImageRenderingTest extends AbstractAcademicProjectsTestCase
{
    use FrontendPluginRenderingTrait;
    use ResponsiveImageAssertionTrait;
    use SiteBasedTestTrait;

    private const FIXTURES = __DIR__ . '/Fixtures/AcademicProjectsImage/';

    private const CARD_SOURCES = 4;
    private const CARD_FALLBACK_WIDTH = 690;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(self::FIXTURES . 'Files/landscape.jpg', $folder . '/landscape.jpg');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'projectImagePages.csv');
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
        ]);
    }

    /**
     * @return array{0: \DOMElement, 1: \DOMElement} the item with an image and the one without,
     *         in the order the plugin renders them
     */
    private function items(\DOMXPath $xpath): array
    {
        $items = [];
        foreach ($this->nodesMatching($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-projects-item ')]") as $item) {
            $this->assertInstanceOf(\DOMElement::class, $item);
            $items[] = $item;
        }
        $this->assertCount(2, $items);

        return [$items[0], $items[1]];
    }

    #[Test]
    public function projectListItemShowsTheImageAsAResponsivePicture(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        [$withImage] = $this->items($xpath);
        $this->assertRendersResponsivePicture(
            $xpath,
            $withImage,
            self::CARD_SOURCES,
            self::CARD_FALLBACK_WIDTH,
            'card-img-top img-fluid',
            'The laboratory of the quantum research project',
        );
    }

    #[Test]
    public function projectListItemWithoutAnImageShowsNone(): void
    {
        $this->setUpTestCase();

        $xpath = $this->parseRenderedPage($this->renderFrontendPage('https://www.acme.com/home'));
        [, $withoutImage] = $this->items($xpath);
        $this->assertRendersNoImage($xpath, $withoutImage, 'card-img-top img-fluid');
    }

    /**
     * The academic_base partials are registered below the partial root path constant, so a
     * project overrides `Academic/Image.html` the way it overrides any partial of this
     * extension.
     *
     * This plugin has a single partial root path and it *is* that constant, so a project
     * that sets it names a directory that holds only the override. The other partials of
     * the extension stay resolvable because Extbase prepends its own partial directory
     * when the configured paths do not list it - at the lowest precedence, so the project
     * still wins for every file it does carry.
     */
    #[Test]
    public function projectOverrideOfTheSharedImagePartialWins(): void
    {
        $this->importCSVDataSet(self::FIXTURES . 'projectImagePages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_projects/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ImagePartialOverride.typoscript',
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
        ]);

        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(1, substr_count($content, '<span class="project-image-override">card</span>'));
        $this->assertStringNotContainsString('<picture', $content);
        // The item partial of the extension is still found, although the only configured
        // partial root path is the one of the project.
        $this->assertStringContainsString('academic-projects-item', $content);
    }
}
