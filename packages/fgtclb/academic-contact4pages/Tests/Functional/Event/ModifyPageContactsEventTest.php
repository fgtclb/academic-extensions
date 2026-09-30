<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Event;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders one page with both outputs of the contacts of a page, the page template the data
 * processor feeds and the contacts content element, while a listener of
 * `ModifyPageContactsEvent` removes a contact.
 *
 * `EXT:test_page_contacts_listener` ships the listener. It stays inert until a test includes
 * the TypoScript file of the behaviour it wants, so every test here renders the same page
 * and differs only in what the listener is told.
 *
 * Three tests need no listener: they show hidden records in one output only, with the
 * content element rendered after the page template and inside it. Each output gets its own
 * copies of the contacts, so the option of one never reaches the other.
 *
 * The page template prints `CONTACT:`, `EMAIL:`, `ROLE:` and `WITHOUT-ROLE:` lines inside
 * `div.page-contacts`. Everything outside that element is the content element, which
 * renders the shipped template of the extension.
 */
final class ModifyPageContactsEventTest extends AbstractAcademicContacts4PagesTestCase
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
        $this->addTestExtensionsToLoad('tests/test-page-contacts-listener');
        parent::setUp();
    }

    protected function tearDown(): void
    {
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    /**
     * @param list<string> $additionalSetupFiles Setup loaded after the shipped one.
     */
    private function setUpTestCase(string $listenerSetup = '', array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pageContacts.csv');
        $setup = [
            'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
            'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
            // The page object first, the extension after it: the shipped setup adds the
            // processor to "page.10", so this order is what attaches it here.
            'EXT:academic_contacts4pages/Tests/Functional/Event/Fixtures/TypoScript/Setup/PageWithContentElement.typoscript',
            'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript',
        ];
        if ($listenerSetup !== '') {
            $setup[] = 'EXT:test_page_contacts_listener/Configuration/TypoScript/' . $listenerSetup . '.typoscript';
        }
        $setup = array_merge($setup, $additionalSetupFiles);
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript',
                    'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => $setup,
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(
                identifier: 'EN',
                base: '/',
            ),
        ]);
    }

    private function showHiddenRecordsInContentElement(): void
    {
        $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->update(
                'tt_content',
                [
                    'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data>'
                        . '<sheet index="sDEF"><language index="lDEF">'
                        . '<field index="settings.showHiddenRecords"><value index="vDEF">1</value></field>'
                        . '</language></sheet></data></T3FlexForms>',
                ],
                ['uid' => 1],
            );
    }

    /**
     * @return array{page: string, contentElement: string}
     */
    private function renderOutputs(): array
    {
        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertSame(
            1,
            preg_match('#<div class="page-contacts">.*?</div>#s', $content, $matches),
            'The page template did not render.',
        );
        $contentElement = str_replace($matches[0], '', $content);
        $this->assertStringContainsString('academic-contacts4pages', $contentElement, 'The content element did not render.');

        return ['page' => $matches[0], 'contentElement' => $contentElement];
    }

    /**
     * @return list<string>
     */
    private function printed(string $content, string $prefix): array
    {
        preg_match_all('#<p>' . preg_quote($prefix, '#') . ':([^<]*)</p>#u', $content, $matches);

        return $matches[1];
    }

    #[Test]
    public function withoutListenerBothOutputsShowEveryContact(): void
    {
        $this->setUpTestCase();

        $outputs = $this->renderOutputs();

        $this->assertSame(['Müllermann', 'Huber', 'Olsen'], $this->printed($outputs['page'], 'CONTACT'));
        $this->assertStringContainsString('Olsen', $outputs['contentElement']);
        $this->assertStringContainsString('Huber', $outputs['contentElement']);
    }

    /**
     * "Olsen" is the only contact with the role "Registry", so the role goes with it - in the
     * page output and as a heading of the content element.
     */
    #[Test]
    public function aRemovedContactIsMissingFromBothOutputsAndTakesItsRoleAlong(): void
    {
        $this->setUpTestCase('RemoveContact');

        $outputs = $this->renderOutputs();

        $this->assertSame(['Müllermann', 'Huber'], $this->printed($outputs['page'], 'CONTACT'));
        $this->assertSame(['Deanery'], $this->printed($outputs['page'], 'ROLE'));
        $this->assertStringNotContainsString('Olsen', $outputs['contentElement']);
        $this->assertStringNotContainsString('Registry', $outputs['contentElement']);
        $this->assertStringContainsString('Deanery', $outputs['contentElement']);
    }

    /**
     * "Huber" has no role, so the list of contacts without role follows the list the
     * listener handed back as well.
     */
    #[Test]
    public function aListenerCanChangeThePageOutputOnly(): void
    {
        $this->setUpTestCase('RemoveContactFromPageOutput');

        $outputs = $this->renderOutputs();

        $this->assertSame(['Müllermann', 'Olsen'], $this->printed($outputs['page'], 'CONTACT'));
        $this->assertSame([], $this->printed($outputs['page'], 'WITHOUT-ROLE'));
        $this->assertStringContainsString('Huber', $outputs['contentElement']);
    }

    /**
     * Two content elements on the page, and only the first names a contact in its FlexForm.
     * The listener reads it from the plugin context, which the data processor does not have.
     */
    #[Test]
    public function aListenerCanChangeOneContentElementThroughItsPluginContext(): void
    {
        $this->setUpTestCase();
        $connection = $this->getConnectionPool()->getConnectionForTable('tt_content');
        $connection->update(
            'tt_content',
            [
                'sorting' => 1,
                'pi_flexform' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?><T3FlexForms><data>'
                    . '<sheet index="sDEF"><language index="lDEF">'
                    . '<field index="settings.testRemovePageContact"><value index="vDEF">2</value></field>'
                    . '</language></sheet></data></T3FlexForms>',
            ],
            ['uid' => 1],
        );
        $connection->insert('tt_content', [
            'uid' => 2,
            'pid' => 2,
            'sorting' => 2,
            'CType' => 'academiccontacts4pages_list',
            'header' => '',
            'pi_flexform' => '',
        ]);

        $outputs = $this->renderOutputs();
        $secondElementStart = strpos($outputs['contentElement'], 'id="c2"');
        $this->assertIsInt($secondElementStart, 'The second content element did not render.');
        $firstElement = substr($outputs['contentElement'], 0, $secondElementStart);
        $secondElement = substr($outputs['contentElement'], $secondElementStart);

        $this->assertSame(['Müllermann', 'Huber', 'Olsen'], $this->printed($outputs['page'], 'CONTACT'));
        $this->assertSame(['Huber'], $this->printed($outputs['page'], 'WITHOUT-ROLE'));
        $this->assertStringContainsString('id="c1"', $firstElement);
        $this->assertStringNotContainsString('Huber', $firstElement);
        $this->assertStringContainsString('Olsen', $firstElement);
        $this->assertStringContainsString('Huber', $secondElement);
    }

    /**
     * The page output renders first and shows hidden address records, the content element
     * after it does not. Each output gets its own copies of the contacts, so the option of
     * the page output does not reach the content element.
     */
    #[Test]
    public function hiddenAddressRecordsOfThePageOutputDoNotReachTheContentElement(): void
    {
        $this->setUpTestCase(
            additionalSetupFiles: ['EXT:academic_contacts4pages/Tests/Functional/Event/Fixtures/TypoScript/Setup/PageOutputShowsHiddenRecords.typoscript'],
        );

        $outputs = $this->renderOutputs();

        $this->assertSame(['max-visible@example.org', 'max-hidden@example.org'], $this->printed($outputs['page'], 'EMAIL'));
        $this->assertStringContainsString('max-visible@example.org', $outputs['contentElement']);
        $this->assertStringNotContainsString('max-hidden@example.org', $outputs['contentElement']);
    }

    /**
     * The data processor runs before the page template renders, the content element while
     * it renders, and the address records of a contact are read only when they are printed.
     * With the content rendered first, the content element asks for the contacts in between,
     * so its option must not reach the contacts the page output prints afterwards.
     */
    #[Test]
    public function hiddenAddressRecordsOfAContentElementInsideThePageTemplateDoNotReachThePageOutput(): void
    {
        $this->setUpTestCase(
            additionalSetupFiles: ['EXT:academic_contacts4pages/Tests/Functional/Event/Fixtures/TypoScript/Setup/ContentInsidePageTemplate.typoscript'],
        );
        $this->showHiddenRecordsInContentElement();

        $outputs = $this->renderOutputs();

        $this->assertStringContainsString('max-hidden@example.org', $outputs['contentElement']);
        $this->assertSame(['max-visible@example.org'], $this->printed($outputs['page'], 'EMAIL'));
    }

    #[Test]
    public function hiddenAddressRecordsOfThePageOutputSurviveAContentElementInsideThePageTemplate(): void
    {
        $this->setUpTestCase(
            additionalSetupFiles: [
                'EXT:academic_contacts4pages/Tests/Functional/Event/Fixtures/TypoScript/Setup/ContentInsidePageTemplate.typoscript',
                'EXT:academic_contacts4pages/Tests/Functional/Event/Fixtures/TypoScript/Setup/PageOutputShowsHiddenRecords.typoscript',
            ],
        );

        $outputs = $this->renderOutputs();

        $this->assertStringNotContainsString('max-hidden@example.org', $outputs['contentElement']);
        $this->assertSame(['max-visible@example.org', 'max-hidden@example.org'], $this->printed($outputs['page'], 'EMAIL'));
    }
}
