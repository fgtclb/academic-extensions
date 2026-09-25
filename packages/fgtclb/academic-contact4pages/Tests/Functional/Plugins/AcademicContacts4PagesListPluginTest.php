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
 * that needed the rendered plugin, and grew with the role grouping option; `main` carries
 * the full plugin coverage.
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

    /**
     * @param list<string> $additionalConstantFiles Constants loaded after the shipped ones.
     * @param list<string> $additionalSetupFiles Setup loaded after the shipped one.
     */
    private function setUpTestCase(string $dataSet, array $additionalConstantFiles = [], array $additionalSetupFiles = []): void
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
                'setup' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                        'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript',
                        'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                    ],
                    $additionalSetupFiles,
                ),
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
     * Stores the FlexForm of the content element with exactly the given fields, the way the
     * backend saves it - a field that is not passed is not stored at all.
     *
     * @param array<string, string> $fields
     */
    private function setContentElementFlexForm(array $fields): void
    {
        $fieldXml = '';
        foreach ($fields as $name => $value) {
            $fieldXml .= sprintf('<field index="%s"><value index="vDEF">%s</value></field>', $name, $value);
        }
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                [
                    'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data>'
                        . '<sheet index="sDEF"><language index="lDEF">' . $fieldXml . '</language></sheet>'
                        . '</data></T3FlexForms>',
                ],
                ['uid' => 1],
            );
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

    /**
     * The role headings of the grouped list: `Profile/Header` renders them with an empty
     * class attribute, as no position class is passed.
     *
     * @return list<string>
     */
    private function roleHeadings(string $content): array
    {
        $document = new \DOMDocument();
        $document->loadHTML($content, LIBXML_NOERROR);
        $headings = [];
        foreach ($this->nodes(new \DOMXPath($document), "//div[contains(concat(' ', normalize-space(@class), ' '), ' academic-contacts4pages ')]//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6][not(@class) or normalize-space(@class) = '']") as $heading) {
            $headings[] = trim($heading->textContent);
        }

        return $headings;
    }

    /**
     * The profile names in the order the page renders them.
     *
     * @return list<string>
     */
    private function renderedLastNames(string $content): array
    {
        preg_match_all('#(Müllermann|Huber|Beispiel|Nebenan)#u', $content, $matches);

        return $matches[1];
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

    /**
     * A grouped contact renders through `Profile/SectionHeader`, one level below the
     * `Profile/Header` an ungrouped one gets, which proves `groupedProfiles` still arrives
     * in the persons partial now that the contact item partial sits in between.
     */
    #[Test]
    public function listPluginRendersGroupedContactsOneHeadingLevelDown(): void
    {
        $this->setUpTestCase('contactsListPage');

        $this->assertMatchesRegularExpression(
            '#<h3 class="card-title">\s*<a href="[^"]*">Max\s+Müllermann</a>\s*</h3>#',
            $this->renderHomePage(),
        );
    }

    #[Test]
    public function listPluginRendersContactsOfAPageWithoutRolesUngrouped(): void
    {
        $this->setUpTestCase('contactsListPage_withoutRoles');

        $content = $this->renderHomePage();
        $this->assertSame([], $this->roleHeadings($content));
        $this->assertStringNotContainsString('academic-contacts4pages__role', $content);
        $this->assertMatchesRegularExpression(
            '#<h2 class="card-title">\s*<a href="[^"]*">Max\s+Müllermann</a>\s*</h2>#',
            $content,
        );
    }

    /**
     * A project that points the partial root path constant of this plugin at a directory of
     * its own replaces key `10`, where the shipped `Contacts/Item.html` lives. Extbase adds
     * the extension's own partial path whenever the configured ones lack it, so every
     * contact still renders its card.
     */
    #[Test]
    public function theShippedItemPartialSurvivesAReplacedPartialRootPath(): void
    {
        $this->setUpTestCase(
            'contactsListPage',
            ['EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PersonsPartialRootPath.typoscript'],
        );

        $this->assertSame(3, $this->countProfileCards($this->renderHomePage()));
    }

    /**
     * The `contactsListPage_interleavedRoles` fixture sorts its four contacts so that the
     * grouped order differs from the editor's order: Müllermann (Dean's Office), Huber
     * (Student Advisors), Beispiel (no role), Nebenan (Dean's Office). Their uids run the
     * other way round, so neither order can come out of uid order by accident.
     */
    #[Test]
    public function listPluginGroupsByRoleWhenTheOptionIsOn(): void
    {
        $this->setUpTestCase('contactsListPage_interleavedRoles');
        $this->setContentElementFlexForm(['settings.showHiddenRecords' => '0', 'settings.groupByRole' => '1']);

        $content = $this->renderHomePage();
        $this->assertSame(['Dean\'s Office', 'Student Advisors'], $this->roleHeadings($content));
        $this->assertSame(['Müllermann', 'Nebenan', 'Huber', 'Beispiel'], $this->renderedLastNames($content));
        $this->assertStringNotContainsString('academic-contacts4pages__role', $content);
    }

    /**
     * Content elements saved before the option existed carry no value for it, and the
     * TypoScript default is what keeps them grouped.
     */
    #[Test]
    public function listPluginGroupsByRoleWhenTheStoredFlexFormLacksTheOption(): void
    {
        $this->setUpTestCase('contactsListPage_interleavedRoles');
        $this->setContentElementFlexForm(['settings.showHiddenRecords' => '0']);

        $content = $this->renderHomePage();
        $this->assertSame(['Dean\'s Office', 'Student Advisors'], $this->roleHeadings($content));
        $this->assertSame(['Müllermann', 'Nebenan', 'Huber', 'Beispiel'], $this->renderedLastNames($content));
    }

    #[Test]
    public function listPluginRendersAllContactsInSortingOrderWhenTheOptionIsOff(): void
    {
        $this->setUpTestCase('contactsListPage_interleavedRoles');
        $this->setContentElementFlexForm(['settings.showHiddenRecords' => '0', 'settings.groupByRole' => '0']);

        $content = $this->renderHomePage();
        $this->assertSame([], $this->roleHeadings($content));
        $this->assertSame(['Müllermann', 'Huber', 'Beispiel', 'Nebenan'], $this->renderedLastNames($content));
        $this->assertSame(4, $this->countProfileCards($content));
        // One row for all of them, not one per role.
        $this->assertSame(1, substr_count($content, '<div class="row">'));
        // Not grouped, so every name renders one heading level up, as a role-less contact does.
        $this->assertMatchesRegularExpression(
            '#<h2 class="card-title">\s*<a href="[^"]*">Horst\s+Huber</a>\s*</h2>#',
            $content,
        );
    }

    #[Test]
    public function listPluginNamesTheRoleOfEachContactWhenTheOptionIsOff(): void
    {
        $this->setUpTestCase('contactsListPage_interleavedRoles');
        $this->setContentElementFlexForm(['settings.showHiddenRecords' => '0', 'settings.groupByRole' => '0']);

        $document = new \DOMDocument();
        $document->loadHTML($this->renderHomePage(), LIBXML_NOERROR);
        $xpath = new \DOMXPath($document);
        $roleOfContact = [];
        // One grid column per contact, holding the role name and the card.
        foreach ($this->nodes($xpath, "//div[contains(concat(' ', normalize-space(@class), ' '), ' academic-contacts4pages ')]/div[@class='row']/div") as $item) {
            preg_match('#(Müllermann|Huber|Beispiel|Nebenan)#u', $item->textContent, $name);
            $role = $this->nodes($xpath, ".//*[contains(concat(' ', normalize-space(@class), ' '), ' academic-contacts4pages__role ')]", $item)->item(0);
            $roleOfContact[$name[1] ?? '?'] = $role === null ? null : trim($role->textContent);
        }

        $this->assertSame(
            [
                'Müllermann' => 'Dean\'s Office',
                'Huber' => 'Student Advisors',
                'Beispiel' => null,
                'Nebenan' => 'Dean\'s Office',
            ],
            $roleOfContact,
        );
    }

    /**
     * A site package's `Contacts/Item.html` replaces the card of every contact: the
     * grouped ones, the one without a role below them, and all of them in the flat list.
     */
    #[Test]
    public function anOverriddenItemPartialRendersEveryGroupedContact(): void
    {
        $this->setUpTestCase(
            'contactsListPage_interleavedRoles',
            [],
            ['EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/ItemPartialOverride.typoscript'],
        );

        $content = $this->renderHomePage();
        $this->assertSame(0, $this->countProfileCards($content));
        $this->assertSame(['Dean\'s Office', 'Student Advisors'], $this->roleHeadings($content));
        preg_match_all('#ITEM-OVERRIDE\[[^\]]*\]#', $content, $matches);
        $this->assertSame(
            [
                'ITEM-OVERRIDE[4|Dean&#039;s Office|grouped|Müllermann|Professor|1|3]',
                'ITEM-OVERRIDE[1|Dean&#039;s Office|grouped|Nebenan|Coordinator|1|3]',
                'ITEM-OVERRIDE[3|Student Advisors|grouped|Huber|Lecturer|1|3]',
                'ITEM-OVERRIDE[2||flat|Beispiel|Assistant|1|3]',
            ],
            $matches[0],
        );
    }

    #[Test]
    public function anOverriddenItemPartialRendersEveryContactOfTheFlatList(): void
    {
        $this->setUpTestCase(
            'contactsListPage_interleavedRoles',
            [],
            ['EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/ItemPartialOverride.typoscript'],
        );
        $this->setContentElementFlexForm(['settings.showHiddenRecords' => '0', 'settings.groupByRole' => '0']);

        preg_match_all('#ITEM-OVERRIDE\[[^\]]*\]#', $this->renderHomePage(), $matches);
        $this->assertSame(
            [
                'ITEM-OVERRIDE[4|Dean&#039;s Office|flat|Müllermann|Professor|1|3]',
                'ITEM-OVERRIDE[3|Student Advisors|flat|Huber|Lecturer|1|3]',
                'ITEM-OVERRIDE[2||flat|Beispiel|Assistant|1|3]',
                'ITEM-OVERRIDE[1|Dean&#039;s Office|flat|Nebenan|Coordinator|1|3]',
            ],
            $matches[0],
        );
    }

    #[Test]
    public function anOverriddenItemPartialRendersContactsOfAPageWithoutRoles(): void
    {
        $this->setUpTestCase(
            'contactsListPage_withoutRoles',
            [],
            ['EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/ItemPartialOverride.typoscript'],
        );

        $content = $this->renderHomePage();
        $this->assertSame(0, $this->countProfileCards($content));
        $this->assertStringContainsString('|flat|Müllermann|', $content);
        $this->assertStringContainsString('|flat|Huber|', $content);
    }
}
