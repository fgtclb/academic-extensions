<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Pages;

use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders a page of the page type this extension registers, on a site package that
 * derives the Fluid template name from the backend layout.
 *
 * That derivation is what the shipped page template has to survive, and it does not
 * survive it by itself: "case = uppercamelcase" lowercases the whole string before
 * camel casing it, so the registered layout "pagets__AcademicProject" arrives as "Academicproject"
 * and Fluid finds no such file. The extension therefore sets "page.10.templateName"
 * inside its own page type condition, and this test is what keeps it set.
 *
 * Remove those two lines from "Configuration/TypoScript/Page/AcademicProjects.typoscript" and the page
 * renders the site package's fallback template instead - which is what the second
 * assertion is for.
 *
 * The remaining tests pin what the template renders of the categories assigned to the
 * page. That block read a property the model does not have until ACE-673, so it never
 * appeared; asserting the rendered output rather than the property name is what keeps a
 * rename from hiding it again.
 *
 * The short description and the funders are rich text. They are rendered through the
 * site's "lib.parseFunc_RTE", so a link the editor set to a page reaches the visitor as
 * the page's URL. Printed raw, as before ACE-676, the "t3://" reference itself did.
 */
final class AcademicProjectPageTemplateTest extends AbstractAcademicProjectsTestCase
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

    /**
     * @param bool $withFluidStyledContent false leaves out the TypoScript of fluid_styled_content,
     *                                     so only the core default "lib.parseFunc_RTE" is defined
     */
    private function setUpTestCase(bool $withFluidStyledContent = true): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicProjectPageTemplateTest/page.csv');
        $constants = [
            'EXT:academic_projects/Configuration/TypoScript/constants.typoscript',
        ];
        $setup = [
            // The site package first, the extension after it - see the fixture.
            'EXT:academic_projects/Tests/Functional/Pages/Fixtures/TypoScript/Setup/SitePackage.typoscript',
            'EXT:academic_projects/Configuration/TypoScript/setup.typoscript',
            // The page template renders "styles.content.getContent", which only this component
            // assigns - it is opt-in since the configuration was cut per component.
            'EXT:academic_projects/Configuration/TypoScript/ContentLoad/setup.typoscript',
        ];
        if ($withFluidStyledContent) {
            array_unshift($constants, 'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript');
            array_unshift($setup, 'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript');
        }
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => $constants,
                'setup' => $setup,
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    #[Test]
    public function pageTemplateIsResolvedOnASitePackageDerivingTheNameFromTheBackendLayout(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/quantum-optics');

        $this->assertStringContainsString('academic-projects-detail', $content);
        $this->assertStringNotContainsString('site-package-default-template', $content);
    }

    /**
     * The fixture assigns one category of type "competence_field" and one of type
     * "department" to the page, so both type labels and both category titles have to reach
     * the output.
     */
    #[Test]
    public function projectPageListsItsCategoriesGroupedByType(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/quantum-optics');

        $this->assertStringContainsString('Competence field', $content);
        $this->assertStringContainsString('Photonics', $content);
        $this->assertStringContainsString('Department', $content);
        $this->assertStringContainsString('Institute of Physics', $content);
    }

    /**
     * "funding_partner" is a registered type and the fixture even holds a category of it,
     * but that category is assigned to no page. Neither the type nor its category may show up.
     */
    #[Test]
    public function projectPageOmitsACategoryTypeWithoutAnAssignedCategory(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/quantum-optics');

        $this->assertStringNotContainsString('Funding partner', $content);
        $this->assertStringNotContainsString('Federal Research Ministry', $content);
    }

    #[Test]
    public function projectPageWithoutCategoriesRendersNoCategoryList(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/dark-matter');

        $this->assertStringContainsString('academic-projects-detail', $content);
        $this->assertStringNotContainsString('Competence field', $content);
        $this->assertStringNotContainsString('Photonics', $content);
        $this->assertStringNotContainsString('Institute of Physics', $content);
    }

    #[Test]
    public function projectPageResolvesAPageLinkInTheShortDescription(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/quantum-optics');

        $this->assertStringContainsString('<a href="/dark-matter">the dark matter project</a>', $content);
        $this->assertStringNotContainsString('t3://', $content);
    }

    #[Test]
    public function projectPageResolvesAPageLinkInTheFunders(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/quantum-optics');

        $this->assertStringContainsString('<a href="/dark-matter">the dark matter consortium</a>', $content);
        $this->assertStringNotContainsString('t3://', $content);
    }

    /**
     * TYPO3 defines "lib.parseFunc_RTE" for every site in the default TypoScript of
     * EXT:frontend, so the links resolve without fluid_styled_content and without any
     * TypoScript of the site's own.
     */
    #[Test]
    public function projectPageResolvesRichTextLinksWithoutFluidStyledContent(): void
    {
        $this->setUpTestCase(withFluidStyledContent: false);

        $content = $this->renderFrontendPage('https://www.acme.com/quantum-optics');

        $this->assertStringContainsString('academic-projects-detail', $content);
        $this->assertStringContainsString('<a href="/dark-matter">the dark matter project</a>', $content);
        $this->assertStringContainsString('<a href="/dark-matter">the dark matter consortium</a>', $content);
        $this->assertStringNotContainsString('t3://', $content);
    }
}
