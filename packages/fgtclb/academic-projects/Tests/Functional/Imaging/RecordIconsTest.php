<?php

declare(strict_types=1);

namespace FGTCLB\AcademicProjects\Tests\Functional\Imaging;

use FGTCLB\AcademicProjects\Enumeration\PageTypes;
use FGTCLB\AcademicProjects\Tests\Functional\AbstractAcademicProjectsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicProjectsTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const PAGE_TYPE_ICON = 'tx-academicprojects-doktype-project';
    private const CONTENT_ELEMENT_ICON = 'tx-academicprojects-plugin-projects';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            self::PAGE_TYPE_ICON,
            self::CONTENT_ELEMENT_ICON,
            'category_types.projects.competence_field',
            'category_types.projects.cooperation',
            'category_types.projects.funding_partner',
            'category_types.projects.department',
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
        $this->assertEveryRecordTypeIconIsColourSchemeAware('academic_projects');
    }

    /**
     * The page tree reads the icon of a page from `typeicon_classes`, the doktype selector
     * from its select item. Both are written separately, so both are asserted.
     */
    #[Test]
    public function pageTypeUsesItsIcon(): void
    {
        $this->assertSame(
            self::PAGE_TYPE_ICON,
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][PageTypes::TYPE_ACEDEMIC_PROJECT] ?? null,
        );
        $this->assertSame(
            self::PAGE_TYPE_ICON,
            $this->selectItemIcon('pages', 'doktype', (string)PageTypes::TYPE_ACEDEMIC_PROJECT),
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function contentElementTypes(): \Generator
    {
        yield 'project list' => ['academicprojects_projectlist'];
        yield 'selected project list' => ['academicprojects_projectlistsingle'];
    }

    #[Test]
    #[DataProvider('contentElementTypes')]
    public function contentElementUsesThePluginIcon(string $cType): void
    {
        $this->assertSame(
            self::CONTENT_ELEMENT_ICON,
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$cType] ?? null,
        );
        $this->assertSame(self::CONTENT_ELEMENT_ICON, $this->selectItemIcon('tt_content', 'CType', $cType));
    }

    /**
     * The new content element wizard does not read the TCA: its icon is a second copy in
     * the page TSconfig of the component, and it has to name the same identifier.
     */
    #[Test]
    #[DataProvider('contentElementTypes')]
    public function newContentElementWizardUsesThePluginIcon(string $cType): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordIcons/pages.csv');

        $elements = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        $this->assertArrayHasKey($cType . '.', $elements);
        $this->assertSame(self::CONTENT_ELEMENT_ICON, $elements[$cType . '.']['iconIdentifier'] ?? null);
    }

    private function selectItemIcon(string $table, string $field, string $value): ?string
    {
        foreach ($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'] ?? [] as $item) {
            if ((string)($item['value'] ?? '') === $value) {
                return $item['icon'] ?? null;
            }
        }
        return null;
    }
}
