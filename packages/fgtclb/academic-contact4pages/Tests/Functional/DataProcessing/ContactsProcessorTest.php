<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataProcessing;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders a page through the data processor this extension registers, which assigns the
 * contacts of the current page and their roles to the page template.
 *
 * The processor is driven through a real frontend request rather than by calling it
 * directly, because the registration is part of what has to keep working: the extension
 * attaches it at `page.10.dataProcessing.400` by class name, and TYPO3 resolves such an
 * entry from the service container when the class is a public service and instantiates it
 * itself otherwise. A direct instantiation would pass either way and would therefore not
 * notice a broken service definition.
 *
 * The fixture template prints the processed values in a shape assertions can pin: the
 * number of contacts, one line per role and one line per contact. The count matters -
 * a contact whose contract or profile does not resolve carries no name to assert on.
 */
final class ContactsProcessorTest extends AbstractAcademicContacts4PagesTestCase
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

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactsProcessor/contacts.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    // The page object first, the extension after it: the shipped setup adds
                    // the processor to "page.10", so this order is what attaches it here.
                    'EXT:academic_contacts4pages/Tests/Functional/DataProcessing/Fixtures/TypoScript/Setup/PageRendering.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript',
                ],
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
    public function processorAssignsTheContactsOfTheCurrentPage(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('CONTACT:Müllermann', $content);
    }

    /**
     * Of the four contacts of the fixture page only the first one resolves: the second
     * points at a hidden profile, the third at a hidden contract and the fourth at an
     * expired profile.
     */
    #[Test]
    public function processorSkipsContactsWhoseContractOrProfileIsNotVisible(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('COUNT:1', $content);
    }

    /**
     * "Student Advisors" is held by the contact with the hidden profile alone, so the role
     * disappears with it, while "Dean's Office" is held by the contact that renders.
     */
    #[Test]
    public function processorBuildsRolesFromTheAssignedContactsOnly(): void
    {
        $this->setUpTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertStringContainsString('ROLE:Dean&#039;s Office', $content);
        $this->assertStringNotContainsString('ROLE:Student Advisors', $content);
    }
}
