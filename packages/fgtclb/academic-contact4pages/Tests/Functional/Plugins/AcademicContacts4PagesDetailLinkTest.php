<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Plugins;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * The setting `plugin.tx_academicpersons.detailLink` of `EXT:academic_persons` on the
 * contact list, delivered the two ways a site gets the TypoScript of this extension.
 *
 * The contact list renders its contacts through `Profile/Item` of that extension with its
 * own plugin settings, so the setting reaches it only through the mapping in the setup of
 * this extension. The mapping reads a constant this extension does not declare: on the
 * static path it resolves only because the component's `include_static_file.txt` brings
 * the persons TypoScript along, which is why the static site selects the static template
 * of this extension alone.
 *
 * The page `/home` holds one contact with a role and one without, which render through
 * the two branches of `Contacts/Item`. No detail page is configured, so a detail link
 * points to the page of the element.
 */
final class AcademicContacts4PagesDetailLinkTest extends AbstractAcademicContacts4PagesTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    private const NAMES = ['Max Müllermann', 'Anna Achterberg'];
    private const RENDERING_SETUP = "@import 'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript'";

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AcademicContacts4PagesDetailLink/records.csv');
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function theStaticTemplateLinksTheNamesByDefault(): void
    {
        $this->setUpStaticTemplateSite('');

        $this->assertNamesLinkToTheDetailView($this->renderHomePage());
    }

    #[Test]
    public function theStaticTemplateFollowsThePersonsConstant(): void
    {
        $this->setUpStaticTemplateSite('plugin.tx_academicpersons.detailLink = none');

        $this->assertNamesWithoutLink($this->renderHomePage());
    }

    /**
     * The contact list has no option of its own. A site that wants its names linked while
     * the persons elements show them as text says so in its TypoScript setup, which the
     * manual shows.
     */
    #[Test]
    public function theTypoScriptSetupOfTheSiteKeepsTheLinksOfTheContactList(): void
    {
        $this->setUpStaticTemplateSite(
            'plugin.tx_academicpersons.detailLink = none',
            'plugin.tx_academiccontacts4pages.settings.detailLink = link',
        );

        $this->assertNamesLinkToTheDetailView($this->renderHomePage());
    }

    #[Test]
    public function theSiteSetLinksTheNamesByDefault(): void
    {
        $this->setUpSiteSetSite([]);

        $this->assertNamesLinkToTheDetailView($this->renderHomePage());
    }

    #[Test]
    public function theSiteSetFollowsThePersonsSiteSetting(): void
    {
        $this->setUpSiteSetSite(['plugin.tx_academicpersons.detailLink' => 'none']);

        $this->assertNamesWithoutLink($this->renderHomePage());
    }

    /**
     * A TypoScript record that selects the static template of the contact list and
     * nothing of `EXT:academic_persons`.
     */
    private function setUpStaticTemplateSite(string $constants, string $setup = ''): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 3,
                'title' => 'Site package',
                'constants' => $constants,
                'config' => self::RENDERING_SETUP . "\n" . $setup,
                'include_static_file' => 'EXT:fluid_styled_content/Configuration/TypoScript/,EXT:academic_contacts4pages/Configuration/TypoScript/List',
            ],
        );
        $this->writeSite('static-' . md5($constants . $setup), []);
    }

    /**
     * A site that gets the TypoScript of both extensions from their sets. The setting is
     * declared by the aggregate set of `EXT:academic_persons`, which a site needs to set it.
     *
     * @param array<string, string> $settings
     */
    private function setUpSiteSetSite(array $settings): void
    {
        // The page object only, "clear = 0" keeps what the sets contribute.
        $this->getConnectionPool()->getConnectionForTable('sys_template')->insert(
            'sys_template',
            [
                'pid' => 1,
                'root' => 1,
                'clear' => 0,
                'title' => 'Site package',
                'constants' => '',
                'config' => self::RENDERING_SETUP,
            ],
        );
        $this->writeSite(
            'set-' . md5(json_encode($settings, JSON_THROW_ON_ERROR)),
            [
                'dependencies' => ['typo3/fluid-styled-content', 'fgtclb/academic-persons', 'fgtclb/academic-contacts4pages'],
                'settings' => $settings,
            ],
        );
    }

    /**
     * The site identifier is part of several caches the test instance keeps for the whole
     * class, so differently configured sites get different ones.
     *
     * @param array<non-empty-string, mixed> $additionalRootConfiguration
     */
    private function writeSite(string $identifier, array $additionalRootConfiguration): void
    {
        $this->writeSiteConfiguration(
            identifier: 'acme-' . substr($identifier, 0, 16),
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: self::FRONTEND_PLUGIN_TEST_BASE,
                additionalRootConfiguration: $additionalRootConfiguration,
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
            ],
        );
    }

    private function renderHomePage(): \DOMXPath
    {
        $document = new \DOMDocument();
        $document->loadHTML('<?xml encoding="utf-8" ?>' . $this->renderFrontendPage('https://www.acme.com/home'), LIBXML_NOERROR);

        return new \DOMXPath($document);
    }

    /**
     * The names the contact list renders, each with the address it links to, or null for a
     * name without a link.
     *
     * @return array<string, string|null>
     */
    private function names(\DOMXPath $xpath): array
    {
        $headings = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " academic-contacts4pages-list ")]'
            . '//*[contains(concat(" ", normalize-space(@class), " "), " ace-name ")]',
        );
        $this->assertInstanceOf(\DOMNodeList::class, $headings);
        $names = [];
        foreach ($headings as $heading) {
            $this->assertInstanceOf(\DOMElement::class, $heading);
            $anchors = $heading->getElementsByTagName('a');
            $this->assertLessThanOrEqual(1, $anchors->length);
            $names[trim(preg_replace('/\s+/u', ' ', $heading->textContent) ?? '')] = $anchors->item(0)?->getAttribute('href');
        }
        $this->assertEqualsCanonicalizing(self::NAMES, array_keys($names), 'The contact list did not render both contacts.');

        return $names;
    }

    private function assertNamesWithoutLink(\DOMXPath $xpath): void
    {
        $this->assertSame([null, null], array_values($this->names($xpath)), 'The contact list links a name.');
    }

    private function assertNamesLinkToTheDetailView(\DOMXPath $xpath): void
    {
        $names = $this->names($xpath);
        foreach (['Max Müllermann' => 1, 'Anna Achterberg' => 2] as $name => $uid) {
            $href = $names[$name];
            $this->assertIsString($href, sprintf('The contact list renders "%s" without a link.', $name));
            $this->assertStringStartsWith('/home?', $href);
            $this->assertStringContainsString(sprintf('tx_academicpersons_detail%%5Bprofile%%5D=%d', $uid), $href);
        }
    }
}
