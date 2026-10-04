<?php

declare(strict_types=1);

namespace FGTCLB\AcademicsDevSite\Tests\Functional;

use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use FGTCLB\TestingHelper\TestCase\FunctionalTestCase;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the icon overview content element of the seed on a page of its own.
 *
 * The element lists the icons of the academic extensions in both registries: the
 * frontend registry of academic_base rendered with `ab:icon` and the inline
 * markup, the icon registry of TYPO3 with `core:icon` in its default markup.
 *
 * Rendered through a `sys_template` record with the static templates of
 * EXT:fluid_styled_content and of this package, the way the `/legacy/` tree is
 * delivered. The rendering definition of the element depends on neither: it is
 * added after the content rendering definitions, which reaches site sets and
 * `sys_template` records alike. That the seed carries the page, in both trees
 * and both languages, is what {@see LegacyDeliveryTest} renders.
 */
abstract class AbstractIconOverviewTestCase extends FunctionalTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * How often the element renders every identifier: at 1em in text, at 2rem,
     * and on a dark ground.
     */
    protected const RENDERINGS_PER_IDENTIFIER = 3;

    /**
     * What the academic extensions depend on, as in {@see AbstractSeedTestCase}.
     */
    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-fluid-styled-content',
        'typo3/cms-felogin',
        'typo3/cms-rte-ckeditor',
    ];

    /**
     * Every package that registers an icon of the academic extensions, so the
     * overview has the whole set to list.
     */
    protected array $testExtensionsToLoad = [
        'fgtclb/environment-state-manager',
        'georgringer/numbered-pagination',
        'fgtclb/category-types',
        'fgtclb/academic-base',
        'fgtclb/academic-persons',
        'fgtclb/academic-persons-edit',
        'fgtclb/academic-persons-sync',
        'fgtclb/academic-contacts4pages',
        'fgtclb/academic-jobs',
        'fgtclb/academic-bite-jobs',
        'fgtclb/academic-partners',
        'fgtclb/academic-programs',
        'fgtclb/academic-projects',
        'fgtclb/academic-study-plan',
        'fgtclb/academics-monorepo-dev-site',
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/IconOverviewPage.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academics_dev_site/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academics_dev_site/Configuration/TypoScript/setup.typoscript',
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

    /**
     * The rendered overview, read per registry section.
     *
     * `icons` holds every icon wrapper, by the identifier it carries, with one entry
     * per rendering: `true` where the wrapper holds exactly one inline `<svg>`. Read
     * from the DOM rather than matched with a pattern, because the icon markup nests
     * `<span>` elements. An identifier a registry does not know comes back as
     * `default-not-found`, not as itself.
     *
     * `inBoth` lists the tiles marked as held by the other registry as well.
     *
     * @return array{icons: array<string, array<string, list<bool>>>, inBoth: array<string, list<string>>}
     *         Both keyed by `frontend` and `backend`.
     */
    protected function renderIconOverview(): array
    {
        $document = new \DOMDocument();
        // The parser knows no HTML5 element and says so once per element. The errors
        // are collected rather than suppressed, so a real parse problem stays
        // inspectable through libxml_get_errors() until they are cleared.
        $useInternalErrors = libxml_use_internal_errors(true);
        try {
            $this->assertTrue(
                $document->loadHTML($this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE)),
                'The page is not parseable HTML.',
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($useInternalErrors);
        }
        $xpath = new \DOMXPath($document);

        $sections = $xpath->query('//div[@class="academics-dev-site-icon-overview"]/section[@data-icon-registry]');
        $this->assertNotFalse($sections);

        $icons = [];
        $inBoth = [];
        foreach ($sections as $section) {
            $this->assertInstanceOf(\DOMElement::class, $section);
            $registry = $section->getAttribute('data-icon-registry');
            $icons[$registry] ??= [];
            $inBoth[$registry] ??= [];
            $marked = $xpath->query('.//li[@data-icon-in-both="yes"]', $section);
            $this->assertNotFalse($marked);
            foreach ($marked as $tile) {
                $this->assertInstanceOf(\DOMElement::class, $tile);
                $inBoth[$registry][] = $tile->getAttribute('data-icon-identifier');
            }
            $wrappers = $xpath->query('.//li[@data-icon-identifier]//span[contains(concat(" ", @class, " "), " t3js-icon ")]', $section);
            $this->assertNotFalse($wrappers);
            foreach ($wrappers as $wrapper) {
                $this->assertInstanceOf(\DOMElement::class, $wrapper);
                $svg = $xpath->query('.//*[local-name()="svg"]', $wrapper);
                $icons[$registry][$wrapper->getAttribute('data-identifier')][] = $svg !== false && $svg->length === 1;
            }
        }

        return ['icons' => $icons, 'inBoth' => $inBoth];
    }
}
