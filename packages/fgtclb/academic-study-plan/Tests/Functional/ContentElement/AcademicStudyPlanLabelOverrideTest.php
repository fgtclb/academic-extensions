<?php

declare(strict_types=1);

namespace FGTCLB\AcademicStudyPlan\Tests\Functional\ContentElement;

use FGTCLB\AcademicStudyPlan\Tests\Functional\AbstractAcademicStudyPlanTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Label overrides through `_LOCAL_LANG` of the extension (`plugin.tx_academicstudyplan`)
 * reach every kind of translation the study plan content element renders, on TYPO3 v12 and
 * v13.
 *
 * TYPO3 v12 and v13 build the TypoScript path from the extension name a translation passes,
 * only lowercased, so a name with underscores read `plugin.tx_academic_study_plan` instead.
 *
 * The study plan is a content element rendered by `FLUIDTEMPLATE`, not an Extbase plugin:
 * there is no plugin whose own `_LOCAL_LANG` could apply, so the chain here is the label of
 * `locallang.xlf` and the override of the extension.
 */
final class AcademicStudyPlanLabelOverrideTest extends AbstractAcademicStudyPlanTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicStudyPlanContentElement/studyPlanPage.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function renderStudyPlanPage(string $setup): string
    {
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
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_template');
        $template = $connection->select(['uid', 'config'], 'sys_template', ['pid' => 1])->fetchAssociative();
        $this->assertIsArray($template);
        $connection->update('sys_template', ['config' => $template['config'] . LF . $setup], ['uid' => $template['uid']]);
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);

        return (string)preg_replace('/\s+/', ' ', $this->renderFrontendPage('https://www.acme.com/home'));
    }

    /**
     * A label in an attribute of the filter navigation, one in the text of a semester, and one
     * in an attribute of the module dialog - all in the one template of the element.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function translationDataProvider(): \Generator
    {
        yield 'attribute of the filter navigation' => ['filter.label', 'Filter by category', '<nav role="navigation" aria-label="%s">'];
        yield 'credit points of a semester' => ['credits', 'CP', '<span class="credits small"> 30 %s </span>'];
        yield 'attribute of the module dialog' => ['modal.close', 'Close', '<button aria-label="%s">'];
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function theContentElementRendersTheLabelOfTheLanguageFile(string $key, string $label, string $markup): void
    {
        $this->assertStringContainsString(sprintf($markup, $label), $this->renderStudyPlanPage(''));
    }

    #[DataProvider('translationDataProvider')]
    #[Test]
    public function theContentElementRendersTheLabelOfTheExtension(string $key, string $label, string $markup): void
    {
        $content = $this->renderStudyPlanPage('plugin.tx_academicstudyplan._LOCAL_LANG.default.' . $key . ' = Extension label');

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }
}
