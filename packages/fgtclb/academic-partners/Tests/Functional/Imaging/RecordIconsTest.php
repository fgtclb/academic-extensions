<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPartners\Tests\Functional\Imaging;

use FGTCLB\AcademicPartners\Backend\FormEngine\PartnerItems;
use FGTCLB\AcademicPartners\Enumeration\PageTypes;
use FGTCLB\AcademicPartners\Tests\Functional\AbstractAcademicPartnersTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\ColourSchemeAwareIconsTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

/**
 * Every identifier below is what a TCA record type resolves to, so it reaches the record
 * list, the page tree and FormEngine through the *default* markup. That markup has to be
 * the inlined file rather than an <img>, because an <img> is opaque to CSS and keeps the
 * ink of its file on the dark cards of a dark backend colour scheme (ACE-523).
 *
 * The identifiers are spelled out here rather than read back out of the registration, so a
 * rename has to be made twice instead of silently agreeing with itself.
 */
final class RecordIconsTest extends AbstractAcademicPartnersTestCase
{
    use ColourSchemeAwareIconsTrait;

    private const DOKTYPE_ICON = 'tx-academicpartners-doktype-partner';
    private const PLUGIN_ICON = 'tx-academicpartners-plugin-partners';
    private const PARTNERSHIP_ICON = 'tx-academicpartners-record-partnership';
    private const ROLE_ICON = 'tx-academicpartners-record-role';

    /**
     * @return \Generator<string, array{0: string}>
     */
    public static function recordIconIdentifiers(): \Generator
    {
        $identifiers = [
            self::DOKTYPE_ICON,
            self::PLUGIN_ICON,
            self::PARTNERSHIP_ICON,
            self::ROLE_ICON,
            'category_types.partners.region',
            'category_types.partners.partner_type',
            'category_types.partners.collaboration_type',
            'category_types.partners.sdg',
        ];
        foreach ($identifiers as $identifier) {
            yield $identifier => [$identifier];
        }
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string}>
     */
    public static function recordTypeIcons(): \Generator
    {
        yield 'academic partner page type' => ['pages', '40', self::DOKTYPE_ICON];
        yield 'partner list' => ['tt_content', 'academicpartners_list', self::PLUGIN_ICON];
        yield 'partner map' => ['tt_content', 'academicpartners_map', self::PLUGIN_ICON];
        yield 'partnerships list' => ['tt_content', 'academicpartners_partnershipslist', self::PLUGIN_ICON];
        yield 'partnerships teaser' => ['tt_content', 'academicpartners_partnershipsteaser', self::PLUGIN_ICON];
        yield 'partnership' => ['tx_academicpartners_domain_model_partnership', 'default', self::PARTNERSHIP_ICON];
        yield 'role' => ['tx_academicpartners_domain_model_role', 'default', self::ROLE_ICON];
    }

    /**
     * @return \Generator<string, array{0: string, 1: string, 2: string, 3: string}>
     */
    public static function typeSelectItemIcons(): \Generator
    {
        yield 'academic partner page type' => ['pages', 'doktype', '40', self::DOKTYPE_ICON];
        yield 'partner list' => ['tt_content', 'CType', 'academicpartners_list', self::PLUGIN_ICON];
        yield 'partner map' => ['tt_content', 'CType', 'academicpartners_map', self::PLUGIN_ICON];
        yield 'partnerships list' => ['tt_content', 'CType', 'academicpartners_partnershipslist', self::PLUGIN_ICON];
        yield 'partnerships teaser' => ['tt_content', 'CType', 'academicpartners_partnershipsteaser', self::PLUGIN_ICON];
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
     * A registered identifier nothing points at is as invisible as a missing one: the page
     * tree, the page module and the record list read `typeicon_classes`, and nothing else.
     */
    #[Test]
    #[DataProvider('recordTypeIcons')]
    public function recordTypeResolvesToItsIcon(string $table, string $type, string $identifier): void
    {
        $this->assertSame(
            $identifier,
            $GLOBALS['TCA'][$table]['ctrl']['typeicon_classes'][$type] ?? null,
            sprintf('%s.ctrl.typeicon_classes.%s does not name the icon of this extension.', $table, $type),
        );
    }

    /**
     * The type selects are a channel of their own: the doktype select of the page
     * properties and the CType select of a content element show the icon of their item,
     * which is written independently of `typeicon_classes`.
     */
    #[Test]
    #[DataProvider('typeSelectItemIcons')]
    public function typeSelectItemCarriesItsIcon(string $table, string $field, string $value, string $identifier): void
    {
        $icons = [];
        foreach ($GLOBALS['TCA'][$table]['columns'][$field]['config']['items'] ?? [] as $item) {
            if ((string)($item['value'] ?? '') === $value) {
                $icons[] = $item['icon'] ?? null;
            }
        }

        $this->assertSame([$identifier], $icons, sprintf('The %s.%s item "%s" does not carry its icon.', $table, $field, $value));
    }

    /**
     * The partner select of a partnership record is filled by an itemsProcFunc, which sets
     * the icon of every item itself. The items are partner pages, so they carry the icon
     * of the page type, not the one of the partnership record they are selected on.
     */
    #[Test]
    public function partnerSelectItemsOfAPartnershipCarryThePageTypeIcon(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/partners.csv');

        $parameters = ['items' => []];
        (new PartnerItems())->itemsProcFunc($parameters);

        $this->assertSame(
            [
                ['label' => 'Alpha University', 'value' => 10, 'icon' => self::DOKTYPE_ICON],
            ],
            $parameters['items'],
        );
    }

    /**
     * The identifiers above are hand maintained. This walks the TCA instead: every type of
     * a table of this extension, and every content element and page type named here, has
     * to name a registered, colour scheme aware identifier of this extension - not a core
     * or a foreign one, and not none. A table added later is covered as it is; a content
     * element or page type added later has to be added to the list.
     */
    #[Test]
    public function everyRecordTypeIconOfThisExtensionIsColourSchemeAware(): void
    {
        $this->assertEveryRecordTypeIconIsColourSchemeAware(
            'academic_partners',
            contentTypes: [
                'academicpartners_list',
                'academicpartners_map',
                'academicpartners_partnershipslist',
                'academicpartners_partnershipsteaser',
            ],
            pageTypes: [PageTypes::ACADEMIC_PARTNERS],
        );
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
        $this->assertIconIdentifiersFollowTheNamingScheme('academic_partners', ['doktype', 'plugin', 'record']);
    }

    #[Test]
    public function everyIconFileIsTheSourceOfARegisteredIcon(): void
    {
        $this->assertEveryIconFileIsRegistered('academic_partners');
    }

    #[Test]
    public function everyIconFileIsAttributedInTheLicenceNotice(): void
    {
        $this->assertEveryIconFileIsAttributedInTheNotice('academic_partners');
    }
}
