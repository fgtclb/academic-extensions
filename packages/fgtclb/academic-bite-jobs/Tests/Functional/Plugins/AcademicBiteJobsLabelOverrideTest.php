<?php

declare(strict_types=1);

namespace FGTCLB\AcademicBiteJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicBiteJobs\Tests\Functional\AbstractAcademicBiteJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Label overrides through `_LOCAL_LANG` reach every kind of translation the list plugin of
 * this extension renders, on TYPO3 v12 and v13: a label comes from `locallang.xlf`, an
 * override of the extension (`plugin.tx_academicbitejobs`) replaces it, and an override of
 * the plugin (`plugin.tx_academicbitejobs_list`) replaces both.
 *
 * TYPO3 v12 and v13 build the TypoScript path from the extension name a translation passes,
 * only lowercased, so a name with underscores read `plugin.tx_academic_bite_jobs` instead.
 *
 * The column and field labels of the views have no English label in `locallang.xlf`, only
 * a German one, so for them the chain starts at the override.
 *
 * The `test_bitejobs_stub` fixture extension answers the b-ite API with two postings, and
 * with none for the listing key `no-postings`.
 */
final class AcademicBiteJobsLabelOverrideTest extends AbstractAcademicBiteJobsTestCase
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
        $this->addTestExtensionsToLoad('tests/test-bitejobs-stub');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicBiteJobsListPlugin/biteJobsListPage.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function renderListPage(string $view, string $jobListingKey, string $setup): string
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')->update(
            'tt_content',
            [
                'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                    . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                    . '<field index="settings.jobs.jobListingKey"><value index="vDEF">' . $jobListingKey . '</value></field>'
                    . '<field index="settings.jobs.sortBy"><value index="vDEF">title</value></field>'
                    . '<field index="settings.jobs.sortingDirection"><value index="vDEF">asc</value></field>'
                    . '<field index="settings.jobs.view"><value index="vDEF">' . $view . '</value></field>'
                    . '<field index="settings.jobs.limit"><value index="vDEF">10</value></field>'
                    . '</language></sheet></data></T3FlexForms>',
            ],
            ['uid' => 1],
        );
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_bite_jobs/Configuration/TypoScript/List/setup.typoscript',
                    'EXT:academic_bite_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
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
     * @param array<string, string> $overrides TypoScript path => label
     */
    private function localLang(string $key, array $overrides): string
    {
        $setup = '';
        foreach ($overrides as $path => $label) {
            $setup .= $path . '._LOCAL_LANG.default.' . $key . ' = ' . $label . LF;
        }
        return $setup;
    }

    /**
     * The message of an empty list, the column heading of the table view, whose key is built
     * from a variable, and the field label of the list view.
     *
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function pluginTranslationDataProvider(): \Generator
    {
        yield 'message of an empty list' => ['List', 'no-postings', 'no-jobs', '<span> %s </span>'];
        yield 'table view, column heading, key from a variable' => ['Table', 'test-key', 'jobs.bite.title', '<th> %s </th>'];
        yield 'list view, field label' => ['List', 'test-key', 'jobs.bite.endsOn', '<b>%s:</b>'];
    }

    #[Test]
    public function thePluginRendersTheLabelOfTheLanguageFile(): void
    {
        $this->assertStringContainsString(
            '<span> There are currently no job vacancies. </span>',
            $this->renderListPage('List', 'no-postings', ''),
        );
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function thePluginRendersTheLabelOfTheExtension(string $view, string $jobListingKey, string $key, string $markup): void
    {
        $content = $this->renderListPage($view, $jobListingKey, $this->localLang($key, [
            'plugin.tx_academicbitejobs' => 'Extension label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Extension label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function thePluginRendersTheLabelOfThePlugin(string $view, string $jobListingKey, string $key, string $markup): void
    {
        $content = $this->renderListPage($view, $jobListingKey, $this->localLang($key, [
            'plugin.tx_academicbitejobs_list' => 'Plugin label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
    }

    #[DataProvider('pluginTranslationDataProvider')]
    #[Test]
    public function theLabelOfThePluginWinsOverTheOneOfTheExtension(string $view, string $jobListingKey, string $key, string $markup): void
    {
        $content = $this->renderListPage($view, $jobListingKey, $this->localLang($key, [
            'plugin.tx_academicbitejobs' => 'Extension label',
            'plugin.tx_academicbitejobs_list' => 'Plugin label',
        ]));

        $this->assertStringContainsString(sprintf($markup, 'Plugin label'), $content);
        $this->assertStringNotContainsString('Extension label', $content);
    }
}
