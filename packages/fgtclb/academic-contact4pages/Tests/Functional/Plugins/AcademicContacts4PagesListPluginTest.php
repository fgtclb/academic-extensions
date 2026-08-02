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
 * The content element header is not part of this template. It comes from
 * `lib.contentElement`, which `PLUGIN_TYPE_CONTENT_ELEMENT` wires up, and on TYPO3 v14 it
 * renders through the `record` view variable — which is what the header assertion covers.
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
                    'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/setup.typoscript',
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

    private function setContentElementHeader(string $header): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => $header], ['uid' => 1]);
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

    #[Test]
    public function listPluginRendersContactsGroupedByRole(): void
    {
        $this->setUpTestCase('contactsListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-contacts4pages', $content);
        // Each role becomes the heading of its own group.
        $this->assertStringContainsString('Dean&#039;s Office', $content);
        $this->assertStringContainsString('Student Advisors', $content);
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Horst', 'Huber');
        $this->assertRendersProfileName($content, 'Erika', 'Beispiel');
    }

    #[Test]
    public function listPluginRendersGroupedContactsOneHeadingLevelDown(): void
    {
        $this->setUpTestCase('contactsListPage');

        // A grouped contact renders through `Profile/SectionHeader`, which is one level
        // below the `Profile/Header` an ungrouped one gets. Asserting the level is what
        // proves `groupedProfiles` arrives in the partial.
        $this->assertMatchesRegularExpression(
            '#<h3 class="card-title">\s*<a href="[^"]*">Max\s+Müllermann</a>\s*</h3>#',
            $this->renderHomePage(),
        );
    }

    #[Test]
    public function listPluginRendersContactsWithoutRole(): void
    {
        $this->setUpTestCase('contactsListPage_withoutRoles');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('academic-contacts4pages', $content);
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Horst', 'Huber');
        $this->assertStringNotContainsString('Dean&#039;s Office', $content);
        // Without a role the flat branch renders, which uses `Profile/Header` and
        // therefore one heading level higher.
        $this->assertMatchesRegularExpression(
            '#<h2 class="card-title">\s*<a href="[^"]*">Max\s+Müllermann</a>\s*</h2>#',
            $content,
        );
    }

    #[Test]
    public function listPluginRendersContactsWithoutRoleBesideGroupedOnes(): void
    {
        // The mixed case: some contacts of the page carry a role, one does not. Before
        // ACE-322 the grouped branch was taken for all of them and the role-less contact
        // was dropped from the markup entirely - no notice, no placeholder.
        $this->setUpTestCase('contactsListPage_mixedRoles');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Dean&#039;s Office', $content);
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Horst', 'Huber');
        $this->assertRendersProfileName($content, 'Erika', 'Beispiel');
        // The role she used to hold is no longer held by anyone, so its group is gone.
        $this->assertStringNotContainsString('Student Advisors', $content);
    }

    #[Test]
    public function listPluginRendersUngroupedContactsAfterTheRoleGroups(): void
    {
        $this->setUpTestCase('contactsListPage_mixedRoles');

        $content = $this->renderHomePage();
        $roleHeading = strpos($content, 'Dean&#039;s Office');
        $grouped = strpos($content, 'Müllermann');
        $ungrouped = strpos($content, 'Beispiel');
        $this->assertIsInt($roleHeading);
        $this->assertIsInt($grouped);
        $this->assertIsInt($ungrouped);
        $this->assertLessThan($ungrouped, $roleHeading, 'The role groups come first.');
        $this->assertLessThan($ungrouped, $grouped, 'A role-less contact renders after the grouped ones.');
    }

    #[Test]
    public function listPluginRendersUngroupedContactsOneHeadingLevelUp(): void
    {
        $this->setUpTestCase('contactsListPage_mixedRoles');

        // The ungrouped block renders through `Profile/Header` rather than
        // `Profile/SectionHeader`, so a role-less contact keeps the higher heading level
        // it has on a page with no roles at all - the two branches stay consistent.
        $this->assertMatchesRegularExpression(
            '#<h2 class="card-title">\s*<a href="[^"]*">Erika\s+Beispiel</a>\s*</h2>#',
            $this->renderHomePage(),
        );
    }

    #[Test]
    public function listPluginEmitsNoEmptyRowWhenEveryContactHasARole(): void
    {
        // The ungrouped block is conditional, so the fully grouped page must render
        // exactly the rows of its two role groups and nothing extra.
        $this->setUpTestCase('contactsListPage');

        $this->assertSame(2, substr_count($this->renderHomePage(), '<div class="row">'));
    }

    #[Test]
    public function listPluginRendersContentElementHeader(): void
    {
        $this->setUpTestCase('contactsListPage');
        $this->setContentElementHeader('Your contacts');

        $this->assertStringContainsString('Your contacts', $this->renderHomePage());
    }

    #[Test]
    public function listPluginRendersTheContractDataOfEachContact(): void
    {
        $this->setUpTestCase('contactsListPage');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Professor', $content);
        // A contract location is a relation, so the partial has to render its title
        // rather than the object.
        $this->assertStringContainsString('Main Campus', $content);
        $this->assertStringNotContainsString('Domain\\Model\\Location', $content);
        $this->assertStringContainsString('A 101', $content);
        $this->assertStringContainsString('Lecturer', $content);
    }

    #[Test]
    public function listPluginLinksEachContactToTheConfiguredDetailPage(): void
    {
        $this->setUpTestCase('contactsListPage');

        $this->assertStringContainsString('href="/profiles?tx_academicpersons_detail', $this->renderHomePage());
    }

    #[Test]
    public function listPluginOnlyRendersContactsOfItsOwnPage(): void
    {
        $this->setUpTestCase('contactsListPage_onlyForeignContacts');

        $content = $this->renderHomePage();
        // The single contact of the fixture belongs to another page, so the plugin renders
        // its wrapper and nothing else.
        $this->assertStringContainsString('academic-contacts4pages', $content);
        $this->assertStringNotContainsString('Nina', $content);
    }

    #[Test]
    public function listPluginHidesHiddenContactsByDefault(): void
    {
        $this->setUpTestCase('contactsListPage_hiddenRecord');

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertStringNotContainsString('Horst', $content);
    }

    #[Test]
    public function listPluginRendersHiddenContactsWhenConfigured(): void
    {
        $this->setUpTestCase('contactsListPage_showHiddenRecords');

        $content = $this->renderHomePage();
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
        $this->assertRendersProfileName($content, 'Horst', 'Huber');
    }

    #[Test]
    public function listPluginRendersWrapperWithoutContacts(): void
    {
        $this->setUpTestCase('contactsListPage_noContacts');

        $content = $this->renderHomePage();
        // This extension has no "nothing found" label; the plugin still has to render
        // rather than fail.
        $this->assertStringContainsString('academic-contacts4pages', $content);
        $this->assertStringNotContainsString('academic-persons-item', $content);
    }
}
