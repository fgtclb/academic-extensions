<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The three address record selects of a contact record are filled from the contract the
 * contact points at. The fixture gives contract 1 a hidden record, which is offered with a
 * marker, and records that must not be offered at all - a deleted one and a translation -
 * plus records of a second contract, so the assertions on the full item list cover the
 * exclusions as well.
 */
final class AddressRecordItemsTest extends AbstractAcademicContacts4PagesTestCase
{
    private const TABLE_NAME = 'tx_academiccontacts4pages_domain_model_contact';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AddressRecordItems/addressRecords.csv');
        // The item labels of hidden records are composed and therefore translated by the
        // itemsProcFunc itself, which FormEngine only ever calls with a language service.
        $GLOBALS['LANG'] = GeneralUtility::makeInstance(LanguageServiceFactory::class)->create('default');
    }

    /**
     * The hidden record stays selectable and is marked as such - whether it reaches the
     * frontend is decided by the "Show hidden records" option of the plugin.
     */
    #[Test]
    public function emailAddressItemsAreBuiltFromTheAddressRecordsOfTheContract(): void
    {
        $this->assertSame(
            [
                ...$this->getStaticItems('email_address'),
                ['label' => 'plain@example.org', 'value' => 2],
                ['label' => 'Business: office@example.org', 'value' => 1],
                ['label' => '[Hidden] Private: hidden@example.org', 'value' => 3],
            ],
            $this->callItemsProcFunc('email_address', ['uid' => 1, 'contract' => 1]),
        );
    }

    #[Test]
    public function phoneNumberItemsAreBuiltFromTheAddressRecordsOfTheContract(): void
    {
        $this->assertSame(
            [
                ...$this->getStaticItems('phone_number'),
                ['label' => 'Mobile: +49 170 1234567', 'value' => 1],
                ['label' => '+49 30 1234567', 'value' => 2],
            ],
            $this->callItemsProcFunc('phone_number', ['uid' => 1, 'contract' => 1]),
        );
    }

    /**
     * An address record without a single filled field falls back to its uid, so it stays
     * selectable instead of showing up as an empty option.
     */
    #[Test]
    public function physicalAddressItemsAreBuiltFromTheAddressRecordsOfTheContract(): void
    {
        $this->assertSame(
            [
                ...$this->getStaticItems('physical_address'),
                ['label' => 'Business: Musterstraße 1, Building A, 10115 Berlin, Germany', 'value' => 1],
                ['label' => '#2', 'value' => 2],
            ],
            $this->callItemsProcFunc('physical_address', ['uid' => 1, 'contract' => 1]),
        );
    }

    #[Test]
    public function itemsOfTheSecondContractAreBuiltFromItsOwnAddressRecords(): void
    {
        $this->assertSame(
            [
                ...$this->getStaticItems('email_address'),
                ['label' => 'Business: other-contract@example.org', 'value' => 6],
            ],
            $this->callItemsProcFunc('email_address', ['uid' => 1, 'contract' => 2]),
        );
    }

    /**
     * @return \Generator<string, array{0: array<string, mixed>}>
     */
    public static function contractIsNotResolvableDataProvider(): \Generator
    {
        yield 'contract not selected' => [['uid' => 1, 'contract' => 0]];
        yield 'contract missing in row' => [['uid' => 1]];
        yield 'contract of a deleted record' => [['uid' => 1, 'contract' => 99]];
    }

    /**
     * @param array<string, mixed> $row
     */
    #[DataProvider('contractIsNotResolvableDataProvider')]
    #[Test]
    public function onlyTheStaticItemsRemainWithoutAContract(array $row): void
    {
        $this->assertSame(
            $this->getStaticItems('email_address'),
            $this->callItemsProcFunc('email_address', $row),
        );
    }

    /**
     * FormEngine hands over select values as an array once they went through the data
     * providers of the form.
     */
    #[Test]
    public function contractIsResolvedFromAnArrayValue(): void
    {
        $this->assertSame(
            [
                ...$this->getStaticItems('email_address'),
                ['label' => 'plain@example.org', 'value' => 2],
                ['label' => 'Business: office@example.org', 'value' => 1],
                ['label' => '[Hidden] Private: hidden@example.org', 'value' => 3],
            ],
            $this->callItemsProcFunc('email_address', ['uid' => 1, 'contract' => ['1']]),
        );
    }

    /**
     * A contact created inline below a contract does not carry the relation in its row
     * before it is saved for the first time.
     */
    #[Test]
    public function contractIsResolvedFromTheInlineParent(): void
    {
        $this->assertSame(
            [
                ...$this->getStaticItems('email_address'),
                ['label' => 'plain@example.org', 'value' => 2],
                ['label' => 'Business: office@example.org', 'value' => 1],
                ['label' => '[Hidden] Private: hidden@example.org', 'value' => 3],
            ],
            $this->callItemsProcFunc(
                'email_address',
                ['uid' => 0, 'contract' => 0],
                [
                    'inlineParentTableName' => 'tx_academicpersons_domain_model_contract',
                    'inlineParentUid' => 1,
                ],
            ),
        );
    }

    #[Test]
    public function inlineParentOfAnotherTableIsNotUsedAsContract(): void
    {
        $this->assertSame(
            $this->getStaticItems('email_address'),
            $this->callItemsProcFunc(
                'email_address',
                ['uid' => 0, 'contract' => 0],
                [
                    'inlineParentTableName' => 'pages',
                    'inlineParentUid' => 1,
                ],
            ),
        );
    }

    #[Test]
    public function itemsCanBeModifiedWithAnEventListener(): void
    {
        $itemsToSet = [
            0 => [
                'label' => 'some label',
                'value' => 123,
            ],
        ];

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'event-dispatch-modification-checker',
            static function (ModifyTcaSelectFieldItemsEvent $event) use ($itemsToSet): void {
                $parameters = $event->getParameters();
                if ($parameters['table'] === self::TABLE_NAME && $parameters['field'] === 'email_address') {
                    $parameters['items'] = $itemsToSet;
                    $event->setParameters($parameters);
                }
            }
        );
        $listenerProvider = $container->get(ListenerProvider::class);
        $listenerProvider->addListener(ModifyTcaSelectFieldItemsEvent::class, 'event-dispatch-modification-checker');

        $this->assertSame($itemsToSet, $this->callItemsProcFunc('email_address', ['uid' => 1, 'contract' => 1]));
    }

    #[Test]
    public function staticItemsCarryTheDisplayMarkersOfTheModel(): void
    {
        foreach (['email_address', 'phone_number', 'physical_address'] as $fieldName) {
            $this->assertSame(
                [Contact::DISPLAY_ALL, Contact::DISPLAY_NONE],
                array_column($this->getStaticItems($fieldName), 'value'),
                sprintf('Static items of "%s" do not match the display markers.', $fieldName),
            );
        }
    }

    /**
     * The items the TCA itself defines, which FormEngine passes into the `itemsProcFunc`.
     * Their labels are still the untranslated `LLL:` references at that point, the
     * translation of the whole item list happens afterwards.
     *
     * @return array<int, array{label?: string|null, value?: mixed, icon?: string|null, group?: string|null}>
     */
    private function getStaticItems(string $fieldName): array
    {
        return $GLOBALS['TCA'][self::TABLE_NAME]['columns'][$fieldName]['config']['items'] ?? [];
    }

    /**
     * @param array<string, mixed> $row
     * @param array<string, mixed> $additionalParameters
     * @return array<int, array{label?: string|null, value?: mixed, icon?: string|null, group?: string|null}>
     */
    private function callItemsProcFunc(string $fieldName, array $row, array $additionalParameters = []): array
    {
        $itemsProcFunc = (string)($GLOBALS['TCA'][self::TABLE_NAME]['columns'][$fieldName]['config']['itemsProcFunc'] ?? '');
        if ($itemsProcFunc === '') {
            throw new \RuntimeException(
                sprintf('No itemsProcFunc configured for "%s.%s".', self::TABLE_NAME, $fieldName),
                1786579202,
            );
        }

        $items = $this->getStaticItems($fieldName);
        $parameters = [
            'items' => &$items,
            'config' => $GLOBALS['TCA'][self::TABLE_NAME]['columns'][$fieldName]['config'],
            'TSconfig' => [],
            'table' => self::TABLE_NAME,
            'row' => $row,
            'field' => $fieldName,
            'effectivePid' => 100,
            'site' => null,
            ...$additionalParameters,
        ];
        GeneralUtility::callUserFunction($itemsProcFunc, $parameters, $this);

        return $parameters['items'];
    }
}
