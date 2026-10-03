<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\CategoryTypes;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A category type a project adds to the group `projects` has no label in the language file
 * of this extension. The frontend names it by the title it is registered with instead,
 * translated into the language of the page, and a label the site sets still wins.
 *
 * The fixture extension `test_projects_titled_category_type` registers `funding_body` with
 * the title "Funding body", "Förderer" in German, and retitles the shipped
 * `competence_field` "Expertise". Quantum Optics (page 10) is a photonics project funded by
 * a research foundation, and "/home" lists it with its filter. Page 20 and "/de/start" are
 * the German project page and list.
 */
final class CategoryTypeTitleTest extends AbstractAcademicProjectsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const PROJECT_PAGE = 'https://www.acme.com/quantum-optics';
    private const TRANSLATED_PROJECT_PAGE = 'https://www.acme.com/de/quantenoptik';
    private const LIST_PAGE = 'https://www.acme.com/home';
    private const TRANSLATED_LIST_PAGE = 'https://www.acme.com/de/start';

    private const SITE_PACKAGE = 'EXT:academic_projects/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript';
    private const PLUGIN_RENDERING = 'EXT:academic_projects/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript';

    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'tests/projects-titled-category-type';
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/categoryTypeTitle.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * Without a site package, the page object of the plugin tests renders the content of
     * the page and nothing else.
     */
    private function setUpSite(bool $projectPage = false, string $setup = ''): void
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
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
        ]);
    }

    #[Test]
    public function theProjectPageNamesTheTypeByTheRegisteredTitle(): void
    {
        $this->setUpSite(projectPage: true);

        $this->assertSame('Funding body', $this->categoryLabel($this->renderFrontendPage(self::PROJECT_PAGE), 'funding_body'));
    }

    #[Test]
    public function theGermanProjectPageNamesTheTypeByTheGermanTitle(): void
    {
        $this->setUpSite(projectPage: true);

        $this->assertSame('Förderer', $this->categoryLabel($this->renderFrontendPage(self::TRANSLATED_PROJECT_PAGE), 'funding_body'));
    }

    #[Test]
    public function theProjectCardNamesTheTypeByTheRegisteredTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Funding body', $this->categoryLabel($this->renderFrontendPage(self::LIST_PAGE), 'funding_body'));
    }

    #[Test]
    public function theListFilterNamesTheSelectByTheRegisteredTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Funding body', $this->selectLabel($this->renderFrontendPage(self::LIST_PAGE), 'funding_body'));
    }

    #[Test]
    public function theGermanListFilterNamesTheSelectByTheGermanTitle(): void
    {
        $this->setUpSite();

        $this->assertSame('Förderer', $this->selectLabel($this->renderFrontendPage(self::TRANSLATED_LIST_PAGE), 'funding_body'));
    }

    #[Test]
    public function aShippedTypeKeepsTheLabelOfTheExtension(): void
    {
        $this->setUpSite();

        $this->assertSame('Competence field', $this->selectLabel($this->renderFrontendPage(self::LIST_PAGE), 'competence_field'));
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function siteLabelDataProvider(): \Generator
    {
        yield 'label of the extension' => ['plugin.tx_academicprojects'];
        yield 'label of the list plugin' => ['plugin.tx_academicprojects_projectlist'];
    }

    #[DataProvider('siteLabelDataProvider')]
    #[Test]
    public function aLabelOfTheSiteWinsOverTheRegisteredTitle(string $path): void
    {
        $this->setUpSite(setup: $path . '._LOCAL_LANG.default.sys_category\.projects\.funding_body = Sponsor');

        $content = $this->renderFrontendPage(self::LIST_PAGE);

        $this->assertSame('Sponsor', $this->selectLabel($content, 'funding_body'));
        $this->assertSame('Sponsor', $this->categoryLabel($content, 'funding_body'));
    }

    #[Test]
    public function aLabelOfTheSiteWinsOnTheProjectPage(): void
    {
        $this->setUpSite(projectPage: true, setup: 'plugin.tx_academicprojects._LOCAL_LANG.default.sys_category\.projects\.funding_body = Sponsor');

        $this->assertSame('Sponsor', $this->categoryLabel($this->renderFrontendPage(self::PROJECT_PAGE), 'funding_body'));
    }

    /**
     * The label in front of the categories of a type, found by the icon of the type that
     * precedes it on the project page and in the project card.
     */
    private function categoryLabel(string $content, string $type): string
    {
        $pattern = '#data-identifier="category_types\.projects\.' . preg_quote($type, '#') . '".*?<b>(.*?)</b>#s';
        if (preg_match($pattern, $content, $matches) !== 1) {
            $this->fail(sprintf('No categories of the type "%s" are rendered.', $type));
        }
        return rtrim(trim((string)preg_replace('/\s+/', ' ', strip_tags($matches[1]))), ':');
    }

    private function selectLabel(string $content, string $selectId): string
    {
        $pattern = '#<label for="' . preg_quote($selectId, '#') . '"[^>]*>(.*?)</label>#s';
        if (preg_match($pattern, $content, $matches) !== 1) {
            $this->fail(sprintf('No select "%s" is labelled.', $selectId));
        }
        return trim((string)preg_replace('/\s+/', ' ', strip_tags($matches[1])));
    }
}
