<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\ContentElement;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The two switches that decide which assets the study plan content element brings to a
 * page.
 *
 * The element registers its stylesheet and its module from inside its own template, so
 * an installation that styles the element itself has to override that template - and
 * then either loses the module or ships a copy of it. Both assets are switchable
 * instead, once per site.
 *
 * The switches exist twice, under one name: as site settings declared by the content
 * element set, and as TypoScript constants of the same path for an installation that
 * configures its frontend through `sys_template` records. Both delivery mechanisms are
 * covered here, because the two files that declare them are different files and nothing
 * but a test keeps their defaults in step.
 */
final class AcademicStudyPlanAssetSwitchTest extends AbstractAcademicStudyPlanTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    /**
     * The file name of the shipped stylesheet, as `f:asset.css` renders it into the
     * `<link>` of the page. It carries a cache buster, so it is asserted by name.
     */
    private const STYLESHEET = 'academic-study-plan.css';

    /**
     * The bare specifier `f:asset.module` registers. It reaches the page twice - in the
     * import map and in the `import` statement that loads it - and neither appears when
     * no module was registered at all.
     */
    private const MODULE = '@fgtclb/academic-study-plan/frontend/academic-study-plan.js';

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

    #[Test]
    public function elementBringsBothAssetsWhenTheSiteConfiguresNothing(): void
    {
        $this->setUpSiteSetSite();

        $html = $this->renderStudyPlanPage();

        $this->assertStringContainsString(self::STYLESHEET, $html);
        $this->assertStringContainsString(self::MODULE, $html);
    }

    /**
     * The assets belong to the element, not to the site: a page of the same site that
     * carries no study plan must not pay for it.
     */
    #[Test]
    public function pageWithoutTheElementBringsNeitherAsset(): void
    {
        $this->setUpSiteSetSite();

        $html = $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE);

        $this->assertStringNotContainsString(self::STYLESHEET, $html);
        $this->assertStringNotContainsString(self::MODULE, $html);
        // Two absences alone would also be satisfied by a site that delivered nothing
        // at all, which is the failure the site set delivery test exists for. The page
        // that does carry the element proves the delivery, on the very same site.
        $withElement = $this->renderStudyPlanPage();
        $this->assertStringContainsString(self::STYLESHEET, $withElement);
        $this->assertStringContainsString(self::MODULE, $withElement);
    }

    #[Test]
    public function stylesheetIsSkippedWhenTheSiteSettingIsOff(): void
    {
        $this->setUpSiteSetSite(['plugin.tx_academicstudyplan.assets.css' => false]);

        $html = $this->renderStudyPlanPage();

        $this->assertStringNotContainsString(self::STYLESHEET, $html);
        // Only this one switch was thrown.
        $this->assertStringContainsString(self::MODULE, $html);
        $this->assertStringContainsString('First Semester', $html);
    }

    #[Test]
    public function moduleIsSkippedWhenTheSiteSettingIsOff(): void
    {
        $this->setUpSiteSetSite(['plugin.tx_academicstudyplan.assets.js' => false]);

        $html = $this->renderStudyPlanPage();

        $this->assertStringNotContainsString(self::MODULE, $html);
        $this->assertStringContainsString(self::STYLESHEET, $html);
        // The markup is unchanged - an integrator who switches the module off brings
        // their own and needs everything it reads.
        $this->assertStringContainsString('First Semester', $html);
        $this->assertStringContainsString('category-id-placeholder', $html);
    }

    /**
     * The static template counterpart. An installation without site sets never reads
     * `settings.definitions.yaml`, so the same switch has to exist as a constant - and
     * the two files have to agree on the default, which the test above and this one pin
     * together.
     */
    #[Test]
    public function stylesheetIsSkippedWhenTheTypoScriptConstantIsOff(): void
    {
        $this->setUpStaticTemplateSite(['EXT:academic_study_plan/Tests/Functional/ContentElement/Fixtures/TypoScript/Constants/NoStylesheet.typoscript']);

        $html = $this->renderStudyPlanPage();

        $this->assertStringNotContainsString(self::STYLESHEET, $html);
        $this->assertStringContainsString(self::MODULE, $html);
    }

    #[Test]
    public function moduleIsSkippedWhenTheTypoScriptConstantIsOff(): void
    {
        $this->setUpStaticTemplateSite(['EXT:academic_study_plan/Tests/Functional/ContentElement/Fixtures/TypoScript/Constants/NoModule.typoscript']);

        $html = $this->renderStudyPlanPage();

        $this->assertStringNotContainsString(self::MODULE, $html);
        $this->assertStringContainsString(self::STYLESHEET, $html);
    }

    #[Test]
    public function staticTemplateBringsBothAssetsWithoutAnOverride(): void
    {
        $this->setUpStaticTemplateSite();

        $html = $this->renderStudyPlanPage();

        $this->assertStringContainsString(self::STYLESHEET, $html);
        $this->assertStringContainsString(self::MODULE, $html);
    }

    private function renderStudyPlanPage(): string
    {
        return $this->renderFrontendPage(self::FRONTEND_PLUGIN_TEST_BASE . 'home');
    }

    /**
     * A site driven by site sets. The `sys_template` record it carries is deliberately
     * `clear = 0`: a clear flag discards everything the sets contributed, and the record
     * exists only to bring the page object that renders the content of a page.
     *
     * @param array<string, bool> $settings Site settings, in the flat notation the
     *        declared keys are written in. A nested tree is accepted just as well.
     */
    private function setUpSiteSetSite(array $settings = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicStudyPlanContentElement/studyPlanPage.csv');
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Rendering',
                'constants' => '',
                'config' => '@import \'EXT:academic_study_plan/Tests/Functional/ContentElement/Fixtures/TypoScript/Setup/Rendering.typoscript\'',
            ],
        );
        $this->writeSiteConfiguration(
            // The site identifier is part of several caches the test instance keeps for
            // the whole class, so differently configured sites need different ones.
            identifier: 'acme-' . substr(md5(json_encode($settings, JSON_THROW_ON_ERROR)), 0, 10),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: [
                    'dependencies' => [
                        'typo3/fluid-styled-content',
                        'fgtclb/academic-study-plan-content-element',
                    ],
                    'settings' => $settings,
                ],
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    /**
     * A site that names no set and configures itself through a `sys_template` record, the
     * way an installation did before site sets existed.
     *
     * @param list<string> $additionalConstants Read after the shipped constants, which is
     *        where an integrator overrides one.
     */
    private function setUpStaticTemplateSite(array $additionalConstants = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicStudyPlanContentElement/studyPlanPage.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_study_plan/Configuration/TypoScript/ContentElement/constants.typoscript',
                    ...$additionalConstants,
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
}
