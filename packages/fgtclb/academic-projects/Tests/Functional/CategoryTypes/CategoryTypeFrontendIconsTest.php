<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * The category type icons of the project page and the project card come from the
 * frontend icon registry.
 *
 * The fixture `tests/projects-frontend-icons` is a site package. In its
 * `Configuration/FrontendIcons.php` it replaces the icon of the shipped competence
 * field, and it adds the type `consortium` with one drawing for the backend (`icon`)
 * and another one for the frontend (`frontendIcon`). Each drawing is a rectangle of its
 * own. The cooperation is a shipped type nobody replaces.
 *
 * Quantum Optics (page 10) carries one category of each of the three types, and "/home"
 * lists it.
 */
final class CategoryTypeFrontendIconsTest extends AbstractAcademicProjectsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const SITE_PACKAGE = 'EXT:academic_projects/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript';
    private const PLUGIN_RENDERING = 'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    /**
     * The rectangles of the fixture, as the serialisers of both core versions write them.
     */
    private const SITE_REPLACED = 'x="1" y="1" width="2" height="14"';
    private const TYPE_BACKEND = 'x="13" y="1" width="2" height="14"';
    private const TYPE_FRONTEND = 'x="4" y="4" width="8" height="8"';

    /**
     * Parts of the shipped `CompetenceField.svg` and `Cooperation.svg`.
     */
    private const SHIPPED_COMPETENCE_FIELD = 'd="M13.25 6c0-.474';
    private const SHIPPED_COOPERATION = 'd="M12.5 11a1.5';

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/projects-frontend-icons');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/categoryTypeFrontendIcons.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * The project page is rendered by the site package, the list plugin by the page object of
     * the plugin tests, which renders the content of the page and nothing else.
     */
    private function setUpSite(bool $projectPage): void
    {
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_projects/Configuration/TypoScript/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    ...($projectPage ? [self::SITE_PACKAGE] : []),
                    'EXT:academic_projects/Configuration/TypoScript/setup.typoscript',
                    ...($projectPage ? [] : [self::PLUGIN_RENDERING]),
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
    }

    /**
     * @return \Generator<string, array{0: string, 1: bool}>
     */
    public static function placeDataProvider(): \Generator
    {
        yield 'project page' => ['https://www.acme.com/quantum-optics', true];
        yield 'project card of the list' => ['https://www.acme.com/home', false];
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aShippedTypeShowsTheFrontendReplacementOfTheSitePackage(string $url, bool $projectPage): void
    {
        $this->setUpSite($projectPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.projects.competence_field');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::SITE_REPLACED, $markup);
            $this->assertStringNotContainsString(self::SHIPPED_COMPETENCE_FIELD, $markup);
        }
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aTypeWithAFrontendIconShowsIt(string $url, bool $projectPage): void
    {
        $this->setUpSite($projectPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.projects.consortium');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::TYPE_FRONTEND, $markup);
            $this->assertStringNotContainsString(self::TYPE_BACKEND, $markup);
        }
    }

    #[DataProvider('placeDataProvider')]
    #[Test]
    public function aShippedTypeNobodyReplacesShowsTheShippedIcon(string $url, bool $projectPage): void
    {
        $this->setUpSite($projectPage);

        $icons = $this->renderedIconMarkups($this->renderFrontendPage($url), 'category_types.projects.cooperation');

        $this->assertNotSame([], $icons);
        foreach ($icons as $markup) {
            $this->assertStringContainsString(self::SHIPPED_COOPERATION, $markup);
        }
    }

    /**
     * The backend keeps the drawings the frontend does not show: the declared `icon` of
     * the new type and the shipped competence field.
     */
    #[Test]
    public function theBackendRegistryKeepsTheDeclaredIcons(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        $this->assertSame(
            'EXT:test_projects_frontend_icons/Resources/Public/Icons/TypeBackend.svg',
            $iconRegistry->getIconConfigurationByIdentifier('category_types.projects.consortium')['options']['source'] ?? null,
        );
        $this->assertSame(
            'EXT:academic_projects/Resources/Public/Icons/CategoryTypes/CompetenceField.svg',
            $iconRegistry->getIconConfigurationByIdentifier('category_types.projects.competence_field')['options']['source'] ?? null,
        );
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
