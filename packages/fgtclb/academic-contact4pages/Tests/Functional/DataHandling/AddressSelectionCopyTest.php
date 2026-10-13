<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataHandling;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The address selections of a page contact are relations to the address records of its
 * contract (ACE-481): a copy of the contract, or of its profile, selects the copied
 * addresses, a translation keeps the default language addresses, and the reference index
 * knows every selection.
 */
final class AddressSelectionCopyTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';
    private const TABLE_EMAIL = 'tx_academicpersons_domain_model_email';
    private const TABLE_PHONE_NUMBER = 'tx_academicpersons_domain_model_phone_number';
    private const TABLE_ADDRESS = 'tx_academicpersons_domain_model_address';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AddressSelectionCopy/contract.csv');
        $this->writeSiteConfiguration(
            identifier: 'address-selection-copy-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * Contact 3 translates contact 1 and selects the same default language addresses, so
     * its copy has to follow the copy of its default language contact.
     */
    #[Test]
    public function aCopiedContractSelectsItsOwnAddresses(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AddressSelectionCopy/contactTranslation.csv');

        $dataHandler = $this->runDataHandler([], [self::TABLE_CONTRACT => [1 => ['copy' => 100]]]);

        $copiedEmailUid = $this->getCopyUid($dataHandler, self::TABLE_EMAIL, 1);
        $copiedAddressUid = $this->getCopyUid($dataHandler, self::TABLE_ADDRESS, 1);
        $this->assertSame(
            ['email_address' => $copiedEmailUid, 'phone_number' => -1, 'physical_address' => $copiedAddressUid],
            $this->getSelection($this->getCopyUid($dataHandler, self::TABLE_CONTACT, 1)),
        );
        $this->assertSame(
            ['email_address' => 0, 'phone_number' => 0, 'physical_address' => 0],
            $this->getSelection($this->getCopyUid($dataHandler, self::TABLE_CONTACT, 2)),
        );
        $this->assertSame(
            ['email_address' => $copiedEmailUid, 'phone_number' => -1, 'physical_address' => $copiedAddressUid],
            $this->getSelection($this->getCopyUid($dataHandler, self::TABLE_CONTACT, 3)),
        );
        $this->assertSame(
            ['email_address' => 1, 'phone_number' => -1, 'physical_address' => 1],
            $this->getSelection(1),
        );
        $this->assertSame(
            ['email_address' => 1, 'phone_number' => -1, 'physical_address' => 1],
            $this->getSelection(3),
        );
    }

    #[Test]
    public function aCopiedProfileSelectsTheAddressesOfItsCopiedContract(): void
    {
        $dataHandler = $this->runDataHandler([], [self::TABLE_PROFILE => [1 => ['copy' => 100]]]);

        $this->assertSame(
            [
                'email_address' => $this->getCopyUid($dataHandler, self::TABLE_EMAIL, 1),
                'phone_number' => -1,
                'physical_address' => $this->getCopyUid($dataHandler, self::TABLE_ADDRESS, 1),
            ],
            $this->getSelection($this->getCopyUid($dataHandler, self::TABLE_CONTACT, 1)),
        );
        $this->assertSame(
            ['email_address' => 1, 'phone_number' => -1, 'physical_address' => 1],
            $this->getSelection(1),
        );
    }

    /**
     * Translating the contract translates its addresses and its page contacts in one
     * command. The selection is shared by every language of a contact and holds the default
     * language address, the translated address is not a copy to point at.
     */
    #[Test]
    public function aTranslatedContactKeepsTheDefaultLanguageAddresses(): void
    {
        $dataHandler = $this->runDataHandler([], [self::TABLE_CONTRACT => [1 => ['localize' => 1]]]);

        $this->assertGreaterThan(0, $this->getCopyUid($dataHandler, self::TABLE_EMAIL, 1), 'Expected the e-mail address to be translated with its contract.');
        $this->assertSame(
            ['email_address' => 1, 'phone_number' => -1, 'physical_address' => 1],
            $this->getSelection($this->getCopyUid($dataHandler, self::TABLE_CONTACT, 1)),
        );
    }

    /**
     * "Display none" is stored as `-1` and is written to the reference index as a relation to
     * a record `-1` of the address table, as core does for the `-1` and `-2` of the frontend
     * user groups. "Display all", `0`, is no relation.
     */
    #[Test]
    public function theReferenceIndexKnowsTheSelectedAddresses(): void
    {
        $this->runDataHandler(
            [self::TABLE_CONTACT => [2 => ['email_address' => 1, 'phone_number' => -1, 'physical_address' => 0]]],
            [],
        );

        $this->assertSame(
            [
                ['field' => 'email_address', 'ref_table' => self::TABLE_EMAIL, 'ref_uid' => 1],
                ['field' => 'phone_number', 'ref_table' => self::TABLE_PHONE_NUMBER, 'ref_uid' => -1],
            ],
            $this->getSelectionReferences(2),
        );
    }

    #[Test]
    public function theReferenceIndexKnowsTheAddressesACopySelects(): void
    {
        $dataHandler = $this->runDataHandler([], [self::TABLE_CONTRACT => [1 => ['copy' => 100]]]);

        $this->assertSame(
            [
                ['field' => 'email_address', 'ref_table' => self::TABLE_EMAIL, 'ref_uid' => $this->getCopyUid($dataHandler, self::TABLE_EMAIL, 1)],
                ['field' => 'phone_number', 'ref_table' => self::TABLE_PHONE_NUMBER, 'ref_uid' => -1],
                ['field' => 'physical_address', 'ref_table' => self::TABLE_ADDRESS, 'ref_uid' => $this->getCopyUid($dataHandler, self::TABLE_ADDRESS, 1)],
            ],
            $this->getSelectionReferences($this->getCopyUid($dataHandler, self::TABLE_CONTACT, 1)),
        );
    }

    /**
     * @param array<string, array<int|string, array<string, mixed>>> $dataMap
     * @param array<string, array<int|string, array<string, mixed>>> $commandMap
     */
    private function runDataHandler(array $dataMap, array $commandMap): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, $commandMap);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');

        return $dataHandler;
    }

    private function getCopyUid(DataHandler $dataHandler, string $tableName, int $uid): int
    {
        $copyUid = (int)($dataHandler->copyMappingArray_merged[$tableName][$uid] ?? 0);
        $this->assertGreaterThan(0, $copyUid, sprintf('Expected record "%s:%d" to be copied.', $tableName, $uid));

        return $copyUid;
    }

    /**
     * @return array<string, int>
     */
    private function getSelection(int $contactUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTACT);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('email_address', 'phone_number', 'physical_address')
            ->from(self::TABLE_CONTACT)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($contactUid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        $this->assertIsArray($row, sprintf('Contact record "%d" does not exist.', $contactUid));

        return array_map(intval(...), $row);
    }

    /**
     * @return list<array{field: string, ref_table: string, ref_uid: int}>
     */
    private function getSelectionReferences(int $contactUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_refindex');
        $rows = $queryBuilder
            ->select('field', 'ref_table', 'ref_uid')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter(self::TABLE_CONTACT)),
                $queryBuilder->expr()->eq('recuid', $queryBuilder->createNamedParameter($contactUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->in(
                    'field',
                    $queryBuilder->quoteArrayBasedValueListToStringList(['email_address', 'phone_number', 'physical_address'])
                ),
            )
            ->orderBy('field')
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'field' => (string)$row['field'],
                'ref_table' => (string)$row['ref_table'],
                'ref_uid' => (int)$row['ref_uid'],
            ],
            $rows,
        );
    }
}
