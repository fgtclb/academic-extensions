<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPrograms\Tests\Functional\Imaging;

use FGTCLB\AcademicPrograms\Enumeration\PageTypes;
use FGTCLB\AcademicPrograms\Tests\Functional\AbstractAcademicProgramsTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconRegistry;

/**
 * Every identifier below is what a TCA record type resolves to - the page type, the two
 * content elements and the category types - so it reaches the record list, the page tree,
 * the page module and FormEngine through the *default* markup. That markup has to be the
 * inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the ink
 * of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicProgramsTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const DOKTYPE_ICON = 'tx-academicprograms-doktype-program';

    private const PLUGIN_ICON = 'tx-academicprograms-plugin-programs';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            self::DOKTYPE_ICON,
            self::PLUGIN_ICON,
            'category_types.programs.admission_restriction',
            'category_types.programs.application_period',
            'category_types.programs.begin_program',
            'category_types.programs.costs',
            'category_types.programs.paying',
            'category_types.programs.degree',
            'category_types.programs.department',
            'category_types.programs.standard_period',
            'category_types.programs.location',
            'category_types.programs.program_type',
            'category_types.programs.teaching_language',
            'category_types.programs.topic',
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
     * The identifiers above are hand maintained. This walks the TCA instead: every content
     * element and page type named here has to name a registered, colour scheme aware
     * identifier of this extension - not a core or a foreign one, and not none. A content
     * element or page type added later has to be added to the list.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware(
            'academic_programs',
            contentTypes: ['academicprograms_programlist', 'academicprograms_programdetails'],
            pageTypes: [PageTypes::TYPE_ACADEMIC_PROGRAM],
        );
    }

    /**
     * The page type shows its icon in two places that are written separately: the item of
     * the page type select and the page tree, which reads `typeicon_classes`.
     */
    #[Test]
    public function pageTypeIsDrawnWithTheDoktypeIcon(): void
    {
        $itemIcons = array_column($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] ?? [], 'icon', 'value');

        $this->assertSame(self::DOKTYPE_ICON, $itemIcons[PageTypes::TYPE_ACADEMIC_PROGRAM] ?? null);
        $this->assertSame(
            self::DOKTYPE_ICON,
            $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][PageTypes::TYPE_ACADEMIC_PROGRAM] ?? null,
        );
    }

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function contentElementTypes(): \Generator
    {
        yield 'program list' => ['academicprograms_programlist'];
        yield 'program details' => ['academicprograms_programdetails'];
    }

    /**
     * The content element icon is written into the CType select item and, from there, into
     * `typeicon_classes` of `tt_content`, which is what the page module draws.
     */
    #[Test]
    #[DataProvider('contentElementTypes')]
    public function contentElementIsDrawnWithThePluginIcon(string $cType): void
    {
        $itemIcons = array_column($GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'] ?? [], 'icon', 'value');

        $this->assertSame(self::PLUGIN_ICON, $itemIcons[$cType] ?? null);
        $this->assertSame(self::PLUGIN_ICON, $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$cType] ?? null);
    }

    /**
     * The new content element wizard does not read the TCA: its icon is named once more in
     * the page TSconfig of the component. Both have to name the same identifier, or the
     * wizard offers a different glyph than the page module shows afterwards.
     */
    #[Test]
    #[DataProvider('contentElementTypes')]
    public function wizardItemCarriesTheIconOfItsContentElement(string $cType): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RecordIcons/pages.csv');

        $elements = BackendUtility::getPagesTSconfig(1)['mod.']['wizards.']['newContentElement.']['wizardItems.']['academic.']['elements.'] ?? [];

        $this->assertSame(self::PLUGIN_ICON, $elements[$cType . '.']['iconIdentifier'] ?? null);
        $this->assertSame(
            $GLOBALS['TCA']['tt_content']['ctrl']['typeicon_classes'][$cType] ?? null,
            $elements[$cType . '.']['iconIdentifier'] ?? null,
        );
    }

    /**
     * The identifiers were renamed without an alias, and `Extension.svg` is the extension
     * icon only - it is found by convention and must not be registered for anything else.
     */
    #[Test]
    public function extensionIconIsNotRegisteredAsARecordIcon(): void
    {
        $iconRegistry = $this->get(IconRegistry::class);

        $this->assertFalse($iconRegistry->isRegistered('academic-programs'));
        foreach ($iconRegistry->getAllRegisteredIconIdentifiers() as $identifier) {
            // Reading the configuration of a deprecated core icon raises a deprecation,
            // and none of them is ours.
            if ($iconRegistry->isDeprecated($identifier)) {
                continue;
            }
            $source = (string)($iconRegistry->getIconConfigurationByIdentifier($identifier)['options']['source'] ?? '');
            $this->assertNotSame(
                'EXT:academic_programs/Resources/Public/Icons/Extension.svg',
                $source,
                sprintf('The icon "%s" is registered from the extension icon.', $identifier),
            );
        }
    }

    #[Test]
    #[DataProvider('recordIconIdentifiers')]
    public function recordIconIsInTheHouseFormat(string $identifier): void
    {
        $this->assertIconIsInTheHouseFormat($identifier);
    }

    #[Test]
    public function identifiersFollowTheNamingScheme(): void
    {
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_programs', ['doktype', 'plugin']);
    }

    /**
     * The category group file is declared by the `groups:` entry of
     * `Configuration/CategoryTypes.yaml`, which EXT:category_types does not read yet
     * (ACE-364), so nothing registers it. It is exempt until the group icon is used.
     */
    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_programs', ['Extension.svg', 'category-group/programs.svg']);
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_programs');
    }
}
