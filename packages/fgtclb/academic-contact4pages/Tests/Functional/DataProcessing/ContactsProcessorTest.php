<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataProcessing;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;

/**
 * Renders a page through the data processor this extension registers, which assigns the
 * contacts of a page, their roles and the contacts without role to the page template.
 *
 * The processor is driven through a real frontend request rather than by calling it
 * directly, because the registration is part of what has to keep working: the extension
 * attaches it at `page.10.dataProcessing.400` by its identifier, installations attach it
 * by class name, and TYPO3 resolves the class name from the service container only while
 * the class is a public service and instantiates it itself otherwise. A direct
 * instantiation would pass either way and would therefore not notice a broken service
 * definition.
 *
 * The fixture template prints the processed values in a shape assertions can pin: the
 * number of contacts, one line per role, per contact, per e-mail address of a contact and
 * per contact without role. The count matters - a contact whose contract or profile does
 * not resolve carries no name to assert on. The options of the processor are set by
 * TypoScript files loaded after the shipped setup, as an integrator sets them.
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

    /**
     * @param list<string> $additionalSetupFiles Setup loaded after the shipped one, which is
     *                                           how an integrator configures the processor.
     */
    private function setUpTestCase(string $dataSet = 'contacts', array $additionalSetupFiles = []): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactsProcessor/' . $dataSet . '.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript',
                ],
                'setup' => array_merge(
                    [
                        'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                        'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                        // The page object first, the extension after it: the shipped setup adds
                        // the processor to "page.10", so this order is what attaches it here.
                        'EXT:academic_contacts4pages/Tests/Functional/DataProcessing/Fixtures/TypoScript/Setup/PageRendering.typoscript',
                        'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript',
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

    private function setUpProcessorOptionsTestCase(string ...$setupFileNames): void
    {
        $this->setUpTestCase(
            'processorOptions',
            array_map(
                static fn(string $fileName): string => 'EXT:academic_contacts4pages/Tests/Functional/DataProcessing/Fixtures/TypoScript/Setup/' . $fileName . '.typoscript',
                array_values($setupFileNames),
            ),
        );
    }

    /**
     * The lines the fixture template prints for one kind of value, in the order it prints
     * them.
     *
     * @return list<string>
     */
    private function printed(string $content, string $prefix): array
    {
        preg_match_all('#<p>' . preg_quote($prefix, '#') . ':([^<]*)</p>#u', $content, $matches);

        return $matches[1];
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

    /**
     * Of the three visible contacts of page 2, "Huber" has no role. The content element lists
     * such a contact apart from the role groups (ACE-322), and the page template gets the
     * same list.
     */
    #[Test]
    public function processorAssignsTheContactsWithoutRole(): void
    {
        $this->setUpProcessorOptionsTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Müllermann', 'Huber', 'Olsen'], $this->printed($content, 'CONTACT'));
        $this->assertSame(['Deanery', 'Registry'], $this->printed($content, 'ROLE'));
        $this->assertSame(['Huber'], $this->printed($content, 'WITHOUT-ROLE'));
    }

    #[Test]
    public function processorWritesAllListsBelowTheConfiguredVariable(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorAs');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['0'], $this->printed($content, 'TOP-LEVEL-COUNT'));
        $this->assertSame(['0'], $this->printed($content, 'TOP-LEVEL-ROLES'));
        $this->assertSame(['Müllermann', 'Huber', 'Olsen'], $this->printed($content, 'CONTACT'));
        $this->assertSame(['Deanery', 'Registry'], $this->printed($content, 'ROLE'));
        $this->assertSame(['Huber'], $this->printed($content, 'WITHOUT-ROLE'));
    }

    #[Test]
    public function processorLeavesHiddenContactsAndAddressRecordsOutByDefault(): void
    {
        $this->setUpProcessorOptionsTestCase();

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertNotContains('Beispiel', $this->printed($content, 'CONTACT'));
        $this->assertSame(['max-visible@example.org'], $this->printed($content, 'EMAIL'));
    }

    /**
     * "Beispiel" is a hidden contact without a role, and the contract of "Müllermann" has a
     * hidden e-mail address. The option of the content element shows both, and so does the
     * option of the processor.
     */
    #[Test]
    public function processorShowsHiddenContactsAndAddressRecordsWhenConfigured(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorShowHiddenRecords');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Müllermann', 'Huber', 'Beispiel', 'Olsen'], $this->printed($content, 'CONTACT'));
        $this->assertSame(['Huber', 'Beispiel'], $this->printed($content, 'WITHOUT-ROLE'));
        $this->assertSame(['max-visible@example.org', 'max-hidden@example.org'], $this->printed($content, 'EMAIL'));
    }

    #[Test]
    public function processorReadsTheContactsOfTheConfiguredPage(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorPageUid');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Nebenan'], $this->printed($content, 'CONTACT'));
        $this->assertSame(['Nebenan'], $this->printed($content, 'WITHOUT-ROLE'));
        $this->assertSame([], $this->printed($content, 'ROLE'));
    }

    #[Test]
    public function processorIsFoundByItsIdentifier(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorByIdentifier');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Müllermann', 'Huber', 'Olsen'], $this->printed($content, 'CONTACT'));
    }

    /**
     * Installations attach the processor by class name. TYPO3 takes such an entry from the
     * container only while the service is public, and instantiates the class without its
     * constructor arguments otherwise.
     */
    #[Test]
    public function processorIsStillFoundByItsClassName(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorByClassName');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Müllermann', 'Huber', 'Olsen'], $this->printed($content, 'CONTACT'));
        $this->assertSame(['Huber'], $this->printed($content, 'WITHOUT-ROLE'));
    }

    #[Test]
    public function processorReadsItsOptionsThroughStdWrap(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorPageUidStdWrap');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['Nebenan'], $this->printed($content, 'CONTACT'));
    }

    /**
     * Attached to the rendering of a content element, the current record is not a page, so
     * the processor has no page to read and leaves the processed data alone.
     */
    #[Test]
    public function processorAssignsNothingForAnotherRecordWithoutPageUid(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorOnContentRecord');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['no'], $this->printed($content, 'RECORD-ASSIGNED'));
        $this->assertSame([], $this->printed($content, 'RECORD-CONTACT'));
    }

    #[Test]
    public function processorReadsTheConfiguredPageForAnotherRecord(): void
    {
        $this->setUpProcessorOptionsTestCase('ProcessorOnContentRecord', 'ProcessorOnContentRecordWithPageUid');

        $content = $this->renderFrontendPage('https://www.acme.com/home');

        $this->assertSame(['yes'], $this->printed($content, 'RECORD-ASSIGNED'));
        $this->assertSame(['Nebenan'], $this->printed($content, 'RECORD-CONTACT'));
    }
}
