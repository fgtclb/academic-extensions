<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Plugins;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
        // Stays inert until a test includes the TypoScript that registers its partial path.
        $this->addTestExtensionsToLoad('tests/test-profile-partial-overrides');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalConstantFiles Constants loaded after the shipped ones.
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicContacts4PagesListPlugin/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                        'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript',
                        'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                    ],
                    $additionalConstantFiles,
                ),
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

    private function setContentElementHeader(string $header): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update('tt_content', ['header' => $header], ['uid' => 1]);
    }

    /**
     * @return \DOMNodeList<\DOMNode>
     */
    private function nodes(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): \DOMNodeList
    {
        $nodes = $xpath->query($query, $context);
        $this->assertInstanceOf(\DOMNodeList::class, $nodes, sprintf('The query "%s" is invalid.', $query));

        return $nodes;
    }

    private function countNodes(\DOMXPath $xpath, string $query, ?\DOMNode $context = null): int
    {
        return $this->nodes($xpath, $query, $context)->length;
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

    /**
     * This plugin renders the profile item of `EXT:academic_persons` through partial root
     * paths of its own, so an integrator who overrides one of the item partials has to
     * register that path here as well. The fixture TypoScript does exactly that, and this
     * is the test that the second registration is all it takes.
     */
    #[Test]
    public function anOverriddenProfilePartialReachesThisPluginToo(): void
    {
        $this->setUpTestCase('contactsListPage', [
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/PartialOverrides.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringContainsString('NAME-OVERRIDE[Müllermann]', $content);
        $this->assertStringContainsString('NAME-OVERRIDE[Huber]', $content);
        // Overriding the name alone leaves the rest of the item alone.
        $this->assertSame(3, $this->countProfileCards($content));
        $this->assertStringContainsString('Dean&#039;s Office', $content);
    }

    /**
     * The other half of the boundary: this plugin arranges its contacts itself, so an
     * override of the item grid of `EXT:academic_persons` - the partial its list, card,
     * selected-profiles and selected-contracts elements share - reaches it in no way.
     * The documentation of both extensions says so, and this is where it is pinned.
     */
    #[Test]
    public function anOverriddenItemGridDoesNotReachThisPlugin(): void
    {
        $this->setUpTestCase('contactsListPage', [
            'EXT:test_profile_partial_overrides/Configuration/TypoScript/GridOverride.typoscript',
        ]);

        $content = $this->renderHomePage();
        $this->assertStringNotContainsString('GRID-OVERRIDE', $content);
        $this->assertSame(3, $this->countProfileCards($content));
        $this->assertRendersProfileName($content, 'Max', 'Müllermann');
    }

    #[Test]
    public function listPluginRendersGroupedContactsOneHeadingLevelDown(): void
    {
        $this->setUpTestCase('contactsListPage');

        // A grouped contact renders through `Profile/SectionHeader`, which is one level
        // below the `Profile/Header` an ungrouped one gets. Asserting the level is what
        // proves `groupedProfiles` arrives in the partial.
        $this->assertMatchesRegularExpression(
            '#<h3 class="academic-persons-item__name card-title">\s*<a href="[^"]*">Max\s+Müllermann</a>\s*</h3>#',
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
            '#<h2 class="academic-persons-item__name card-title">\s*<a href="[^"]*">Max\s+Müllermann</a>\s*</h2>#',
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
            '#<h2 class="academic-persons-item__name card-title">\s*<a href="[^"]*">Erika\s+Beispiel</a>\s*</h2>#',
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

    /**
     * A contact names one contract, and that is the contract its item shows - even one that
     * has ended, and even with the contract options of `EXT:academic_persons` in the
     * settings. They choose among a profile's contracts, which this plugin never renders.
     * The fixture's contact points at "Professor", ended 2020-12-31, the second of the
     * profile's two contracts; the options would pick "Dean".
     */
    #[Test]
    public function listPluginRendersTheContractOfTheContactWhateverTheContractOptions(): void
    {
        $this->setUpTestCase('contactsListPage_expiredContract');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('Professor', $content);
        $this->assertStringNotContainsString('Dean', $content);
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

    /**
     * One card per rendered contact, counted through the class the `Profile/Item` partial
     * of `EXT:academic_persons` wraps every contact in.
     */
    private function countProfileCards(string $content): int
    {
        $document = new \DOMDocument();
        $document->loadHTML($content, LIBXML_NOERROR);

        return $this->countNodes(
            new \DOMXPath($document),
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-item ')]",
        );
    }

    /**
     * The `contactsListPage_addressRecords` fixture gives each of its four contacts an own
     * contract with two email addresses, two phone numbers and two physical addresses, and
     * a different dedicated address record selection. Every profile of the fixture carries
     * its own values, so an assertion on a value proves which contact rendered it.
     */
    #[Test]
    public function listPluginRendersAllAddressRecordsWithoutDedicatedSelection(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecords');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('anna-first@example.org', $content);
        $this->assertStringContainsString('anna-second@example.org', $content);
        $this->assertStringContainsString('+4900011', $content);
        $this->assertStringContainsString('+4900012', $content);
        $this->assertStringContainsString('Annafirst', $content);
        $this->assertStringContainsString('Annasecond', $content);
    }

    #[Test]
    public function listPluginRendersOnlyTheSelectedAddressRecords(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecords');

        $content = $this->renderHomePage();
        $this->assertStringNotContainsString('bruno-first@example.org', $content);
        $this->assertStringContainsString('bruno-second@example.org', $content);
        $this->assertStringNotContainsString('Brunofirst', $content);
        $this->assertStringContainsString('Brunosecond', $content);
    }

    #[Test]
    public function listPluginSuppressesAnAddressRecordKindEntirely(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecords');

        $content = $this->renderHomePage();
        // Bruno has no phone number at all, Clara no email address ...
        $this->assertStringNotContainsString('+4900021', $content);
        $this->assertStringNotContainsString('+4900022', $content);
        $this->assertStringNotContainsString('clara-first@example.org', $content);
        $this->assertStringNotContainsString('clara-second@example.org', $content);
        // ... while the record kinds they did not restrict stay untouched.
        $this->assertStringContainsString('+4900031', $content);
        $this->assertStringContainsString('+4900032', $content);
        $this->assertStringContainsString('Clarafirst', $content);
    }

    /**
     * Dora points at an email address that is not part of her contract, which is what a
     * contact looks like after its contract was switched. Nothing is rendered for a
     * selection that cannot be resolved, exactly like "Do not display".
     */
    #[Test]
    public function listPluginRendersNoAddressRecordForAnUnresolvableSelection(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecords');

        $content = $this->renderHomePage();
        $this->assertStringNotContainsString('dora-first@example.org', $content);
        $this->assertStringNotContainsString('dora-second@example.org', $content);
    }

    /**
     * Emil points at a hidden email address. Hidden address records are selectable in the
     * backend, but reach the frontend only where hidden records are asked for.
     */
    #[Test]
    public function listPluginRendersNoAddressRecordForASelectedHiddenOne(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecords');

        $content = $this->renderHomePage();
        $this->assertStringNotContainsString('emil-hidden@example.org', $content);
        // The selection stays a selection: the visible record of the same contract is not
        // rendered as a replacement.
        $this->assertStringNotContainsString('emil-visible@example.org', $content);
    }

    #[Test]
    public function listPluginRendersASelectedHiddenAddressRecordWhenConfigured(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecordsShowHidden');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('emil-hidden@example.org', $content);
        $this->assertStringNotContainsString('emil-visible@example.org', $content);
    }

    /**
     * Frida displays all of her email addresses, one of which is hidden. The hidden one is
     * part of "all" wherever hidden records are asked for - the contract relation itself
     * never carries it.
     */
    #[Test]
    public function listPluginRendersHiddenAddressRecordsWithoutDedicatedSelectionWhenConfigured(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecordsShowHidden');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('frida-visible@example.org', $content);
        $this->assertStringContainsString('frida-hidden@example.org', $content);
    }

    #[Test]
    public function listPluginKeepsHiddenAddressRecordsOutOfTheCompleteListByDefault(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecords');

        $content = $this->renderHomePage();
        $this->assertStringContainsString('frida-visible@example.org', $content);
        $this->assertStringNotContainsString('frida-hidden@example.org', $content);
    }

    /**
     * "Show hidden records" widens what a selection may resolve to, it does not turn an
     * unresolvable one into the full list.
     */
    #[Test]
    public function listPluginRendersNoAddressRecordForAnUnresolvableSelectionWithHiddenRecords(): void
    {
        $this->setUpTestCase('contactsListPage_addressRecordsShowHidden');

        $content = $this->renderHomePage();
        $this->assertStringNotContainsString('dora-first@example.org', $content);
        $this->assertStringNotContainsString('dora-second@example.org', $content);
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

    /**
     * The `Profile/Item` partial of EXT:academic_persons renders the image through the
     * responsive image partial of EXT:academic_base, so the contacts view has to resolve that
     * partial as well - and hand the placeholder setting of the persons plugins on.
     */
    #[Test]
    public function listPluginRendersTheProfileImageAndThePlaceholderLikeTheProfileList(): void
    {
        $this->setUpTestCase('contactsListPage_profileImage');
        $folder = $this->instancePath . '/fileadmin/images';
        GeneralUtility::mkdir_deep($folder);
        copy(__DIR__ . '/Fixtures/Files/portrait.jpg', $folder . '/portrait.jpg');

        $document = new \DOMDocument();
        $document->loadHTML($this->renderHomePage(), LIBXML_NOERROR);
        $xpath = new \DOMXPath($document);
        $cards = $this->nodes($xpath, "//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-persons-item ')]");
        $this->assertSame(2, $cards->length);

        $withImage = $cards->item(0);
        $this->assertInstanceOf(\DOMElement::class, $withImage);
        $this->assertSame(1, $this->countNodes($xpath, './/picture', $withImage));
        $this->assertSame('image/webp', $this->nodes($xpath, './/picture/source', $withImage)->item(0)?->attributes?->getNamedItem('type')?->nodeValue);

        $withoutImage = $cards->item(1);
        $this->assertInstanceOf(\DOMElement::class, $withoutImage);
        $this->assertStringEndsWith(
            'Images/ProfilePlaceholder.svg',
            (string)$this->nodes($xpath, './/img', $withoutImage)->item(0)?->attributes?->getNamedItem('src')?->nodeValue,
        );
    }

    /**
     * The contract rows come from the `Profile/Contract/Field` partial of
     * EXT:academic_persons, and the phone link target it builds reads a setting of the
     * persons plugin. This plugin maps that setting into its own settings block, exactly as
     * it maps the detail page and the image placeholder, so a contact rendered here gets the
     * same target as a profile rendered there.
     */
    #[Test]
    public function listPluginBuildsThePhoneLinkTargetLikeTheProfilePlugins(): void
    {
        $this->setUpTestCase(
            'contactsListPage_phoneNumber',
            ['EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PhoneLinkPrefix.typoscript'],
        );

        $content = $this->renderHomePage();
        $this->assertStringContainsString('href="tel:+496241509123"', $content);
        $this->assertStringContainsString('>123</a>', $content);
    }
}
