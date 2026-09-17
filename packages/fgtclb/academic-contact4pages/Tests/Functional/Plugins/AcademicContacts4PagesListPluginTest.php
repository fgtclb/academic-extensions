<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Plugins;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders the `academiccontacts4pages_list` plugin in the frontend.
 *
 * The plugin has no record selection of its own: `ContactRepository::findByPid()` matches
 * the `page` field of a contact against the page the content element sits on, with the
 * storage page deliberately ignored. Each contact points at an `EXT:academic_persons`
 * contract, which points at a profile, and the template renders that profile through the
 * `Profile/Item` partial of that extension — so the fixtures carry all three tables.
 *
 * This class is deliberately small. It came with ACE-101, the first change on this branch
 * that needed the rendered plugin; `main` carries the full plugin coverage.
 */
final class AcademicContacts4PagesListPluginTest extends AbstractAcademicContacts4PagesTestCase
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

    private function setUpTestCase(string $dataSet): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicContacts4PagesListPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript',
                    'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript',
                    'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
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

    private function renderHomePage(): string
    {
        return $this->renderFrontendPage('https://www.acme.com/home');
    }

    /**
     * The item partial composes the heading from first, middle and last name, so an empty
     * middle name leaves two spaces in the markup. Matching on `\s+` asserts the rendered
     * name without depending on that spacing.
     */
    private function assertRendersProfileName(string $content, string $first, string $last): void
    {
        $this->assertMatchesRegularExpression(
            sprintf('#%s\s+%s#u', preg_quote($first, '#'), preg_quote($last, '#')),
            $content,
        );
    }

    /**
     * One card per rendered contact, counted through the class the `Profile/Item` partial
     * of `EXT:academic_persons` wraps every contact in.
     */
    private function countProfileCards(string $content): int
    {
        $document = new \DOMDocument();
        $document->loadHTML($content, LIBXML_NOERROR);
        $nodes = (new \DOMXPath($document))->query(
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-item ')]",
        );
        $this->assertInstanceOf(\DOMNodeList::class, $nodes);

        return $nodes->length;
    }

    /**
     * The baseline the two tests below narrow down: every contact of the page with a
     * visible contract and profile renders one card below the heading of its role.
     */
    #[Test]
    public function listPluginRendersContactsGroupedByRole(): void
    {
        $this->setUpTestCase('contactsListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-contacts4pages', $content);
        $this->assertStringContainsString('Dean&#039;s Office', $content);
        $this->assertStringContainsString('Student Advisors', $content);
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Horst', 'Huber');
        $this->assertRendersProfileName($content, 'Erika', 'Beispiel');
        $this->assertSame(3, $this->countProfileCards($content));
    }

    /**
     * The `contactsListPage_unresolvedProfiles` fixture holds four contacts of one page:
     * one with a visible profile, one whose profile is hidden, one whose contract is
     * hidden and one whose profile has expired. The card count is what is asserted rather
     * than the absence of the three names: a contact whose relation does not resolve
     * renders an *empty* card, so its name is missing either way and an assertion on the
     * name alone would pass without the fix.
     *
     * Contacts 1 and 3 share the role "Dean's Office", so that heading survives while
     * "Student Advisors" - held only by the contact with the hidden profile - must not.
     */
    #[Test]
    public function listPluginSkipsContactsWhoseContractOrProfileIsNotVisible(): void
    {
        $this->setUpTestCase('contactsListPage_unresolvedProfiles');

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertSame(1, $this->countProfileCards($content), 'Only the resolvable contact renders a card.');
        $this->assertStringContainsString('Dean&#039;s Office', $content);
        $this->assertStringNotContainsString('Student Advisors', $content);
    }

    /**
     * "Show hidden records" is about contact rows, not about the people behind them: the
     * hidden contact row of the fixture points at a visible profile and renders, while the
     * visible contact row pointing at a hidden profile still does not.
     */
    #[Test]
    public function listPluginKeepsSkippingUnresolvedContactsWithHiddenRecordsShown(): void
    {
        $this->setUpTestCase('contactsListPage_unresolvedProfilesShowHidden');

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Paula', 'Prüfer');
        $this->assertSame(2, $this->countProfileCards($content), 'The hidden contact row renders, the unresolved ones do not.');
        $this->assertStringNotContainsString('Student Advisors', $content);
    }
}
