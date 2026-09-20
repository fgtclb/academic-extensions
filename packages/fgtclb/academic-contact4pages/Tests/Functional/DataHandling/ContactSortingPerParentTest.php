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
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * A page contact is an inline child of three records at once: of its page, which
 * owns the order the frontend renders, of the contract it belongs to, and of its
 * contacts role.
 *
 * `RelationHandler::writeForeignField()` numbers the children of the saved parent
 * 1..n into the sort field the relation declares, so all three relations wrote the
 * shared `sorting` column until the contract and role relations gained
 * `contract_sorting` and `role_sorting`. Saving a contract or a contacts role
 * therefore renumbered its contacts across every page that owns one of them.
 *
 * The fixture is built so the assertions fail on every DBMS when the sort column is
 * shared: contract and role both list the contacts of two pages in an order that
 * contradicts the order of both pages.
 */
final class ContactSortingPerParentTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

    /**
     * The localization test needs a second language; the rest of the class does not
     * care which languages exist.
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_ROLE = 'tx_academiccontacts4pages_domain_model_role';
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'contact-sorting-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * Saving the contract in an order that contradicts both pages leaves the order
     * of each page untouched.
     */
    #[Test]
    public function savingAContractKeepsTheContactOrderOfEveryPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');

        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);

        $this->assertSame(
            [1, 2],
            $this->fetchChildUids('page', 10, 'sorting'),
            'Saving the contract rearranged the contacts of page 10.',
        );
        $this->assertSame(
            [3, 4],
            $this->fetchChildUids('page', 11, 'sorting'),
            'Saving the contract rearranged the contacts of page 11.',
        );
    }

    /**
     * Saving the contacts role in an order that contradicts both pages leaves the
     * order of each page untouched.
     */
    #[Test]
    public function savingAContactsRoleKeepsTheContactOrderOfEveryPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');

        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);

        $this->assertSame(
            [1, 2],
            $this->fetchChildUids('page', 10, 'sorting'),
            'Saving the contacts role rearranged the contacts of page 10.',
        );
        $this->assertSame(
            [3, 4],
            $this->fetchChildUids('page', 11, 'sorting'),
            'Saving the contacts role rearranged the contacts of page 11.',
        );
    }

    /**
     * Contract and contacts role keep their own arrangements, in sort columns of
     * their own, and neither overwrites the other.
     */
    #[Test]
    public function contractAndContactsRoleKeepTheirOwnArrangements(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');

        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);

        $this->assertSame(
            [2, 4, 1, 3],
            $this->fetchChildUids('contract', 1, 'contract_sorting'),
            'The contract did not keep the order it was saved with.',
        );
        $this->assertSame(
            [4, 2, 3, 1],
            $this->fetchChildUids('role', 1, 'role_sorting'),
            'The contacts role did not keep the order it was saved with.',
        );
    }

    /**
     * Saving a page still writes the shared `sorting` column - the order the
     * frontend renders - and leaves both other arrangements alone.
     */
    #[Test]
    public function savingAPageKeepsTheArrangementsOfContractAndContactsRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');
        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);

        $this->saveInlineChildren('pages', 10, 'tx_academiccontacts4pages_contacts', [2, 1]);

        $this->assertSame(
            [2, 1],
            $this->fetchChildUids('page', 10, 'sorting'),
            'Saving the page did not rearrange its own contacts.',
        );
        $this->assertSame(
            [2, 4, 1, 3],
            $this->fetchChildUids('contract', 1, 'contract_sorting'),
            'Saving the page rearranged the contacts of the contract.',
        );
        $this->assertSame(
            [4, 2, 3, 1],
            $this->fetchChildUids('role', 1, 'role_sorting'),
            'Saving the page rearranged the contacts of the contacts role.',
        );
    }

    /**
     * The path editors actually use: a contact is created in the inline list of its
     * page and picks its contract and its contacts role in the selects of its own
     * form. Neither of those records is saved, so nothing renumbers their lists - the
     * new contact has to be appended to both, not left at 0 above everything else.
     */
    #[Test]
    public function aContactCreatedOnThePageIsAppendedToItsContractAndRole(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');
        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);

        $newUid = $this->createContactOnPage(10, 1, 1);

        $this->assertSame(
            [2, 4, 1, 3, $newUid],
            $this->fetchChildUids('contract', 1, 'contract_sorting'),
            'The new contact was not appended to the list of its contract.',
        );
        $this->assertSame(
            [4, 2, 3, 1, $newUid],
            $this->fetchChildUids('role', 1, 'role_sorting'),
            'The new contact was not appended to the list of its contacts role.',
        );
    }

    /**
     * Two of them in a row land behind each other rather than tying: the data map is
     * processed record by record, so the second one sees the first one stored. The
     * ranks themselves are asserted, not the resulting order - two records tied at 0
     * come back in uid order on SQLite and would hide the defect.
     */
    #[Test]
    public function twoContactsCreatedInARowDoNotTie(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');
        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);

        $firstUid = $this->createContactOnPage(10, 1, 1);
        $secondUid = $this->createContactOnPage(11, 1, 1);

        $this->assertSame(5, $this->fetchColumn(self::TABLE_CONTACT, $firstUid, 'contract_sorting'));
        $this->assertSame(6, $this->fetchColumn(self::TABLE_CONTACT, $secondUid, 'contract_sorting'));
    }

    /**
     * A contact that changes its contacts role is appended to the new one and leaves
     * the list of its contract untouched - the two columns are independent.
     *
     * The order the two are moved in is what makes the assertion bite: contact 1 sits
     * last in role 1 and contact 4 first, so a rank carried over from the old role
     * would put them the other way round in the new one.
     */
    #[Test]
    public function aContactThatChangesItsRoleIsAppendedToTheNewOne(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');
        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);
        $this->updateRecord(self::TABLE_CONTACT, 1, ['role' => 2]);

        $this->updateRecord(self::TABLE_CONTACT, 4, ['role' => 2]);

        $this->assertSame([2, 3], $this->fetchChildUids('role', 1, 'role_sorting'));
        $this->assertSame([1, 4], $this->fetchChildUids('role', 2, 'role_sorting'));
        $this->assertSame([2, 4, 1, 3], $this->fetchChildUids('contract', 1, 'contract_sorting'));
    }

    /**
     * Clearing the contract resets its column and leaves the contacts role alone.
     */
    #[Test]
    public function clearingTheContractResetsOnlyItsOwnRank(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');
        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);

        $this->updateRecord(self::TABLE_CONTACT, 1, ['contract' => 0]);

        $this->assertSame(0, $this->fetchColumn(self::TABLE_CONTACT, 1, 'contract_sorting'));
        $this->assertSame([2, 4, 3], $this->fetchChildUids('contract', 1, 'contract_sorting'));
        $this->assertSame([4, 2, 3, 1], $this->fetchChildUids('role', 1, 'role_sorting'));
    }

    /**
     * A `copy` command on the contact itself runs `copyRecord()`, which submits a
     * nested data map - so this one is ranked by the data map half. The path the
     * cmdmap half exists for is the cascaded one, below.
     */
    #[Test]
    public function aCopiedContactIsAppendedToBothOfItsSecondaryParents(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');
        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);

        $copyUid = $this->copyRecord(self::TABLE_CONTACT, 1, 10);

        $this->assertSame([2, 4, 1, 3, $copyUid], $this->fetchChildUids('contract', 1, 'contract_sorting'));
        $this->assertSame([4, 2, 3, 1, $copyUid], $this->fetchChildUids('role', 1, 'role_sorting'));
    }

    /**
     * Creates a contact the way the page form does: the record carries the contract
     * and the contacts role in its own selects, and the page relation lists it.
     */
    private function createContactOnPage(int $pageUid, int $contractUid, int $roleUid): int
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $newId = StringUtility::getUniqueId('NEW');
        $existingUids = $this->fetchChildUids('page', $pageUid, 'sorting');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_CONTACT => [
                    $newId => [
                        'pid' => $pageUid,
                        'page' => $pageUid,
                        'contract' => $contractUid,
                        'role' => $roleUid,
                    ],
                ],
                'pages' => [
                    $pageUid => [
                        'tx_academiccontacts4pages_contacts' => implode(',', [...$existingUids, $newId]),
                    ],
                ],
            ],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)$dataHandler->substNEWwithIDs[$newId];
    }

    /**
     * @param array<string, int|string> $values
     */
    private function updateRecord(string $tableName, int $uid, array $values): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([$tableName => [$uid => $values]], [], $backendUser);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    private function copyRecord(string $tableName, int $uid, int $targetPageUid): int
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        return (int)($dataHandler->copyMappingArray_merged[$tableName][$uid] ?? 0);
    }

    private function fetchColumn(string $tableName, int $uid, string $columnName): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select($columnName)
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Localizing the contract cascades into its contacts. A localized child takes
     * the `copyRecord()` branch, whose nested data map the first half of the hook
     * sees, so the translations are appended to the lists they join instead of
     * piling up at 0 - and `DataHandler::$copyMappingArray_merged` would carry them
     * to the second half if they ever took the raw branch instead.
     */
    #[Test]
    public function localizedContactsAreAppendedToTheirSecondaryParents(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/translatedPageWithContacts.csv');

        $this->localizeRecord(self::TABLE_CONTRACT, 1);

        $translations = $this->fetchTranslatedContactUids();
        $this->assertCount(2, $translations, 'Expected both contacts to be localized.');
        foreach ($translations as $uid) {
            $this->assertGreaterThan(
                0,
                $this->fetchColumn(self::TABLE_CONTACT, $uid, 'role_sorting'),
                'The localized contact has no rank in its contacts role.',
            );
            $this->assertGreaterThan(
                0,
                $this->fetchColumn(self::TABLE_CONTACT, $uid, 'contract_sorting'),
                'The localized contact has no rank in its contract.',
            );
        }
        $this->assertSame(
            [1, 2, ...$translations],
            $this->fetchChildUids('role', 1, 'role_sorting'),
            'The localized contacts were not appended to the list of the contacts role.',
        );
    }

    private function localizeRecord(string $tableName, int $uid): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['localize' => 1]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * @return list<int>
     */
    private function fetchTranslatedContactUids(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTACT);
        $queryBuilder->getRestrictions()->removeAll();
        $uids = $queryBuilder
            ->select('uid')
            ->from(self::TABLE_CONTACT)
            ->where(
                $queryBuilder->expr()->gt('sys_language_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            )
            ->orderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }

    /**
     * Copying the **owning** parent is the path the cmdmap half of the hook exists
     * for, and the one a direct copy does not exercise: a cascaded inline child is
     * created by `copyRecord_raw()` -> `insertDB()` with the full database row, no
     * data map involved and nothing that drops a column without a TCA `columns`
     * entry - so the copy arrives carrying the position of the record it was copied
     * from, and ties with it.
     */
    #[Test]
    public function copyingAPageAppendsTheCopiedContactsToBothSecondaryParents(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactSortingPerParent/twoPagesOneContractOneRole.csv');
        $this->saveInlineChildren(self::TABLE_CONTRACT, 1, 'tx_academiccontacts4pages_contacts', [2, 4, 1, 3]);
        $this->saveInlineChildren(self::TABLE_ROLE, 1, 'contacts', [4, 2, 3, 1]);

        $copies = $this->copyRecordWithMapping('pages', 10, 1, self::TABLE_CONTACT);

        $this->assertCount(2, $copies, 'Expected both contacts of the page to be copied.');
        foreach ([['contract', 'contract_sorting'], ['role', 'role_sorting']] as [$parentField, $sortField]) {
            $ranks = $this->fetchRanks(self::TABLE_CONTACT, $parentField, 1, $sortField);
            $this->assertSame(
                array_values(array_unique($ranks)),
                array_values($ranks),
                'Two contacts share a position in ' . $sortField . ': ' . json_encode($ranks),
            );
        }
        // The copies are appended in the order their originals hold in each list, not
        // in the order the copy run happened to create them - and the two lists order
        // them differently, which is the point of the two columns.
        $this->assertSame(
            [2, 4, 1, 3, $copies[2], $copies[1]],
            $this->fetchChildUids('contract', 1, 'contract_sorting'),
        );
        $this->assertSame(
            [4, 2, 3, 1, $copies[2], $copies[1]],
            $this->fetchChildUids('role', 1, 'role_sorting'),
        );
    }

    /**
     * @return array<int, int> The records of $childTable the run created, source uid => new uid.
     */
    private function copyRecordWithMapping(string $tableName, int $uid, int $targetPageUid, string $childTable): array
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        // Copying reaches DataHandler::getLanguageService(), which is typed against
        // $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['copy' => $targetPageUid]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
        $mapping = [];
        foreach ($dataHandler->copyMappingArray_merged[$childTable] ?? [] as $sourceUid => $newUid) {
            $mapping[(int)$sourceUid] = (int)$newUid;
        }
        return $mapping;
    }

    /**
     * @return array<int, int> uid => rank, in the order the sort column puts them in.
     */
    private function fetchRanks(string $tableName, string $parentField, int $parentUid, string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', $sortField)
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy($sortField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $ranks = [];
        foreach ($rows as $row) {
            $ranks[(int)$row['uid']] = (int)$row[$sortField];
        }
        return $ranks;
    }

    /**
     * @param list<int> $childUids
     */
    private function saveInlineChildren(string $tableName, int $uid, string $fieldName, array $childUids): void
    {
        $backendUser = $this->setUpBackendUser(1);
        // The DataHandler writes its log through BackendUtility on TYPO3 v12, whose
        // getLanguageService() returns $GLOBALS['LANG'] and is typed against it.
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [$tableName => [$uid => [$fieldName => implode(',', $childUids)]]],
            [],
            $backendUser,
        );
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * The uids of the contacts of one parent, in the order the given sort column
     * puts them in - `uid` settling ties, the way the inline relation reads them.
     *
     * @return list<int>
     */
    private function fetchChildUids(string $parentField, int $parentUid, string $sortField): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTACT);
        $queryBuilder->getRestrictions()->removeAll();
        $uids = $queryBuilder
            ->select('uid')
            ->from(self::TABLE_CONTACT)
            ->where(
                $queryBuilder->expr()->eq(
                    $parentField,
                    $queryBuilder->createNamedParameter($parentUid, Connection::PARAM_INT),
                ),
            )
            ->orderBy($sortField)
            ->addOrderBy('uid')
            ->executeQuery()
            ->fetchFirstColumn();
        return array_map(intval(...), $uids);
    }
}
