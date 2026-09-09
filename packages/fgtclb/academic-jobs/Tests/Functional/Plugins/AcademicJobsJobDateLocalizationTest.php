<?php

declare(strict_types=1);

namespace FGTCLB\AcademicJobs\Tests\Functional\Plugins;

use FGTCLB\AcademicJobs\Tests\Functional\AbstractAcademicJobsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Pins the notation the `academicjobs_list` and `academicjobs_detail` plugins render a
 * job date in.
 *
 * Both templates formatted every date as `d.m.Y`, which is the German notation and only
 * that one - an English visitor of the same installation read `01.10.2026` as the first
 * of October just as often as as the tenth of January. The date is now formatted for the
 * locale of the matched site language, so the same record renders `01.10.2026` under
 * `de-DE` and `Oct 1, 2026` under `en-US`.
 *
 * One site with two languages rather than two sites: the notation is a property of the
 * language a page is requested in, and requesting the same page twice is the shortest
 * way to show that nothing but the language decides it.
 */
final class AcademicJobsJobDateLocalizationTest extends AbstractAcademicJobsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicJobsJobDateLocalization/jobPages.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/ListAndDetailConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_jobs/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        // The German language falls back to the default one: the fixture carries no
        // translated page, content element or job, and what is under test is the
        // notation of the date, not the translation of the record.
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/', fallbackIdentifiers: ['EN']),
        ]);
    }

    /**
     * Takes the detail link the list plugin rendered and requests it, so the cHash stays
     * valid - the same approach `AcademicJobsListAndDetailPluginTest` uses.
     */
    private function renderDetailPageOfJob(string $listContent, int $jobUid): string
    {
        $pattern = sprintf(
            '#href="(?P<uri>[^"]*tx_academicjobs_detail%%5Bjob%%5D=%d[^"]*)"#',
            $jobUid,
        );
        $this->assertMatchesRegularExpression(
            $pattern,
            $listContent,
            sprintf('The list plugin rendered no detail link for job %d.', $jobUid),
        );
        preg_match($pattern, $listContent, $matches);

        return $this->renderFrontendPage(
            'https://www.acme.com' . htmlspecialchars_decode($matches['uri']),
        );
    }

    #[Test]
    public function listPluginRendersTheEmploymentStartDateForTheLocaleOfTheSiteLanguage(): void
    {
        $this->setUpTestCase();

        $englishContent = $this->renderFrontendPage('https://www.acme.com/home');
        $germanContent = $this->renderFrontendPage('https://www.acme.com/de/home');

        $this->assertStringContainsString('Oct 1, 2026', $englishContent);
        $this->assertStringNotContainsString('01.10.2026', $englishContent);
        $this->assertStringContainsString('01.10.2026', $germanContent);
        $this->assertStringNotContainsString('Oct 1, 2026', $germanContent);
    }

    #[Test]
    public function detailPluginRendersTheEmploymentStartDateForTheLocaleOfTheSiteLanguage(): void
    {
        $this->setUpTestCase();

        $englishDetail = $this->renderDetailPageOfJob(
            $this->renderFrontendPage('https://www.acme.com/home'),
            1,
        );
        $germanDetail = $this->renderDetailPageOfJob(
            $this->renderFrontendPage('https://www.acme.com/de/home'),
            1,
        );

        $this->assertStringContainsString('Oct 1, 2026', $englishDetail);
        $this->assertStringNotContainsString('01.10.2026', $englishDetail);
        $this->assertStringContainsString('01.10.2026', $germanDetail);
        $this->assertStringNotContainsString('Oct 1, 2026', $germanDetail);
    }
}
