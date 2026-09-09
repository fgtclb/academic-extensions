<?php

declare(strict_types=1);

/*
 * This file is part of the fgtclb/academic extension collection.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace FGTCLB\AcademicPersons\Tests\Functional\Plugins;

use FGTCLB\AcademicPersons\Tests\Functional\AbstractAcademicPersonsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * A timeline date is written the way the matched site language writes it, not the way this
 * repository or the visitor's browser would.
 *
 * The shipped sections publish the year alone, and a year is spelled identically in every
 * locale, so nothing about the shipped configuration can show this. The fixture extension
 * `test_public_profile_settings` therefore declares two sections: `publications` publishes
 * the whole date, `academicCareer` the year alone. The very same record then renders as
 * `Mar 14, 2024` on the English site and as `14.03.2024` on the German one, while the year
 * only section renders `2015 – 2018` on both.
 *
 * The German language falls back to the default one, so a single set of untranslated records
 * is what both requests render - the difference in the output is the locale and nothing else.
 */
final class AcademicPersonsPublicProfileDateLocalizationTest extends AbstractAcademicPersonsTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration([
            'FE' => [
                'cacheHash' => [
                    'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                    'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                    'enforceValidation' => true,
                ],
            ],
        ]);
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addTestExtensionsToLoad('tests/test-public-profile-settings');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    private function setUpTwoLanguageSite(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicPersonsPublicProfilePlugin/shippedLayout.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_persons/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration('EN', '/'),
            $this->buildLanguageConfiguration(
                identifier: 'DE',
                base: '/de/',
                fallbackIdentifiers: ['EN'],
                fallbackType: 'fallback',
            ),
        ]);
    }

    /**
     * Both languages request the same page and the same profile, so the `cHash` - which is
     * built from the query arguments and the page id - is the same for both.
     */
    private function renderProfile(string $base): string
    {
        return (string)preg_replace('/\s+/', ' ', $this->renderFrontendPage(
            'https://www.acme.com' . $base . 'home?' . http_build_query([
                'tx_academicpersons_detail' => [
                    'controller' => 'Profile',
                    'action' => 'detail',
                    'profile' => 1,
                ],
                'cHash' => '13c8ec3ab2a317651a40bd164df8a366',
            ])
        ));
    }

    /**
     * The English site writes the complete date the way `en-US` writes it, month name first.
     */
    #[Test]
    public function aCompleteDateIsWrittenInTheNotationOfTheEnglishSiteLanguage(): void
    {
        $this->setUpTwoLanguageSite();

        $content = $this->renderProfile('/');

        $this->assertStringContainsString('__timeline-date"> Mar 14, 2024 </p>', $content);
        $this->assertStringNotContainsString('14.03.2024', $content);
    }

    /**
     * The German site writes the same stored date the way `de-DE` writes it, day first and
     * separated by dots. It is the identical record - the difference is the locale of the
     * matched site language, not the data and not the template.
     */
    #[Test]
    public function theSameDateIsWrittenInTheNotationOfTheGermanSiteLanguage(): void
    {
        $this->setUpTwoLanguageSite();

        $content = $this->renderProfile('/de/');

        $this->assertStringContainsString('__timeline-date"> 14.03.2024 </p>', $content);
        $this->assertStringNotContainsString('Mar 14, 2024', $content);
    }

    /**
     * A section that publishes the year alone has nothing left that a locale could write
     * differently, so both sites render the same range - which is what keeps the shipped
     * timeline byte for byte what it was while the three columns were integers.
     */
    #[Test]
    public function aYearOnlySectionRendersTheSameRangeInBothSiteLanguages(): void
    {
        $this->setUpTwoLanguageSite();

        $this->assertStringContainsString('__timeline-date"> 2015 – 2018 </p>', $this->renderProfile('/'));
        $this->assertStringContainsString('__timeline-date"> 2015 – 2018 </p>', $this->renderProfile('/de/'));
    }
}
