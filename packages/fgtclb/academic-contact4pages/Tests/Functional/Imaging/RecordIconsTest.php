<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Imaging;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523). The content
 * element icon is one of them: it is the `tt_content` record type icon of the page module.
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicContacts4PagesTestCase
{
    use ColourSchemeAwareIconsTrait;

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            'tx-academiccontacts4pages-plugin-contacts',
            'tx-academiccontacts4pages-record-contact',
            'tx-academiccontacts4pages-record-role',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsRegisteredWithTheColourSchemeAwareProvider(string $identifier): void
    {
        $this->assertIconIsRegisteredWithCurrentColorProvider($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInlinedInBothMarkups(string $identifier): void
    {
        $this->assertIconIsInlinedInBothMarkups($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconMarkupFollowsTheTextColour(string $identifier): void
    {
        $this->assertIconMarkupFollowsTheTextColour($identifier);
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function renderedRecordIconCarriesItsIdentifier(string $identifier): void
    {
        $this->assertRenderedIconCarriesItsIdentifier($identifier);
    }

    /**
     * The identifiers above are hand maintained, so they cannot catch a record icon that is
     * added later and never converted. This one is derived from the TCA and does.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware('academic_contacts4pages');
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function recordTypeIconDataProvider(): \Generator
    {
        yield 'content element' => ['tt_content', 'academiccontacts4pages_list', 'tx-academiccontacts4pages-plugin-contacts'];
        yield 'contact' => ['tx_academiccontacts4pages_domain_model_contact', 'default', 'tx-academiccontacts4pages-record-contact'];
        yield 'role' => ['tx_academiccontacts4pages_domain_model_role', 'default', 'tx-academiccontacts4pages-record-role'];
    }

    /**
     * The list above pins what is registered; this pins that the TCA actually names it.
     * A registration nothing points at would pass every assertion above while the backend
     * still showed the previous icon.
     */
    #[Test]
    #[DataProvider('recordTypeIconDataProvider')]
    public function recordTypeResolvesToTheRegisteredIcon(string $table, string $type, string $identifier): void
    {
        $this->assertSame($identifier, $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type] ?? null);
    }

    /**
     * The new content element wizard names its icon on its own, in page TSconfig, so it
     * can drift from the icon the page module shows for the same content element - it did,
     * the wizard used to show a core icon of its own. Compared with the TCA rather than
     * with a literal, so the two have to be changed together.
     */
    #[Test]
    public function newContentElementWizardShowsTheIconOfTheContentElement(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordIcons/pagesWithRegisteredPageTsConfig.csv');

        $elements = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        $this->assertArrayHasKey('academiccontacts4pages_list.', $elements, 'The wizard entry is not delivered.');
        $this->assertSame(
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes']['academiccontacts4pages_list'] ?? null,
            $elements['academiccontacts4pages_list.']['iconIdentifier'] ?? null,
        );
        $this->assertSame(
            'tx-academiccontacts4pages-plugin-contacts',
            $elements['academiccontacts4pages_list.']['iconIdentifier'] ?? null,
        );
    }
}
