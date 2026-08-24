<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\SiteSet;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Proves that the static template and the selectable page TSconfig file of this extension
 * deliver what they claim - the half of the delivery that works on every core version
 * this branch supports, and the only half TYPO3 v12 has at all.
 *
 * This extension is the one that had something to lose. It hid its content element and
 * registered a page TSconfig file before this restructuring, through paths that all
 * moved, so these tests cover behaviour that already existed rather than behaviour that
 * is being introduced.
 *
 * Both mechanisms fail silently when a path is wrong. A static template pointing at a
 * folder without any of the three files the core looks for contributes nothing, and an
 * unresolved page TSconfig include is not an error either.
 *
 * The `sys_template` record the probe is imported from carries `clear = 0` on purpose:
 * the backend button "Create a root TypoScript record" writes `clear = 3`, which discards
 * everything a site set contributed, and so does
 * `FunctionalTestCase::setUpFrontendRootPage()`.
 */
final class StaticTemplateDeliveryTest extends AbstractAcademicStudyPlanTestCase
{
    use DeliveryProbeTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const CONTENT_ELEMENT_TYPE = 'academic_study_plan';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function staticTemplateDataProvider(): \Generator
    {
        yield 'component' => ['EXT:academic_study_plan/Configuration/TypoScript/ContentElement'];
        yield 'aggregate' => ['EXT:academic_study_plan/Configuration/TypoScript/Full'];
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function pageTsConfigFileDataProvider(): \Generator
    {
        yield 'component' => ['EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig'];
        yield 'aggregate' => ['EXT:academic_study_plan/Configuration/TSconfig/Full/page.tsconfig'];
    }

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
     * The aggregate entry also covers
     * `Configuration/TypoScript/Full/include_static_file.txt`, whose entries are comma
     * separated and reach nothing at all when they are written any other way.
     */
    #[Test]
    #[DataProvider('staticTemplateDataProvider')]
    public function staticTemplateDeliversTheComponentTypoScript(string $includeStaticFile): void
    {
        $this->setUpSite(includeStaticFile: $includeStaticFile);

        $body = $this->renderFrontendPage($this->frontendPluginTestBase());

        $this->assertStringContainsString(
            $this->componentConstantMarkup(),
            $body,
            sprintf('The static template "%s" did not deliver "constants.typoscript".', $includeStaticFile),
        );
        $this->assertStringContainsString(
            $this->componentSetupMarkup(),
            $body,
            sprintf('The static template "%s" did not deliver "setup.typoscript".', $includeStaticFile),
        );
        $this->assertStringContainsString(
            $this->componentSubstitutedMarkup(),
            $body,
            sprintf('The static template "%s" delivered a setup the constants were not substituted into.', $includeStaticFile),
        );
        $this->assertStringContainsString(
            $this->componentStylesheetMarkup(),
            $body,
            sprintf('The static template "%s" did not deliver the frontend assets of the content element.', $includeStaticFile),
        );
    }

    /**
     * The hide half, asserted on its own. Without it the re-enable assertion below cannot
     * fail: it checks that the content element is absent from `removeItems`, and an empty
     * list satisfies that just as well as a correct one.
     */
    #[Test]
    public function theContentElementIsHiddenWithoutAPageTsConfigInclude(): void
    {
        $this->setUpSite();

        $this->assertContains(
            self::CONTENT_ELEMENT_TYPE,
            $this->removedContentElementTypes(BackendUtility::getPagesTSconfig(1)),
            'The content element is selectable although no page TSconfig enables it.',
        );
    }

    /**
     * On TYPO3 v12 this is the only mechanism that can re-enable the content element at
     * all - there are no site sets there.
     */
    #[Test]
    #[DataProvider('pageTsConfigFileDataProvider')]
    public function pageTsConfigIncludeReEnablesTheContentElement(string $pageTsConfigInclude): void
    {
        $this->setUpSite(pageTsConfigInclude: $pageTsConfigInclude);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);

        $this->assertNotContains(
            self::CONTENT_ELEMENT_TYPE,
            $this->removedContentElementTypes($pageTsConfig),
            sprintf('The page TSconfig file "%s" did not re-enable the content element.', $pageTsConfigInclude),
        );
        $this->assertArrayHasKey(
            self::CONTENT_ELEMENT_TYPE . '.',
            $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [],
            sprintf('The page TSconfig file "%s" did not deliver the wizard entry.', $pageTsConfigInclude),
        );
    }

    /**
     * The wizard element has to arrive in the "show" list as well. The key is read by
     * TYPO3 v12 only, where `NewContentElementController::getWizards()` never adds an
     * element that is not listed in it - so an element definition without it is invisible
     * in the backend of every v12 installation, and nothing on the v13 leg would notice.
     *
     * It used to sit in a `[typo3.branch == "12.4"]` condition in the always-included
     * `Configuration/page.tsconfig`; it moved into the component file with the element
     * definition, so that both arrive together.
     */
    #[Test]
    #[DataProvider('pageTsConfigFileDataProvider')]
    public function pageTsConfigIncludeAddsTheContentElementToTheWizardShowList(string $pageTsConfigInclude): void
    {
        $this->setUpSite(pageTsConfigInclude: $pageTsConfigInclude);

        $pageTsConfig = BackendUtility::getPagesTSconfig(1);

        $this->assertSame(
            self::CONTENT_ELEMENT_TYPE,
            $pageTsConfig['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['show'] ?? null,
            sprintf('The page TSconfig file "%s" did not add the content element to "show".', $pageTsConfigInclude),
        );
    }
}
