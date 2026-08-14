<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataHandling;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The address record selects hold two markers besides the uid of an address record:
 * {@see Contact::DISPLAY_ALL} and the negative {@see Contact::DISPLAY_NONE}. They are
 * plain values of a select without a `foreign_table`, so they have to survive a backend
 * save unchanged - a dropped `-1` would turn "do not display" back into "display all"
 * without the editor noticing.
 */
final class AddressRecordSelectionTest extends AbstractAcademicContacts4PagesTestCase
{
    private const TABLE_NAME = 'tx_academiccontacts4pages_domain_model_contact';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AddressRecordSelection/contacts.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function selectionIsStoredWhenAnExistingContactIsSaved(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_NAME => [
                    1 => [
                        'email_address' => 1,
                        'phone_number' => Contact::DISPLAY_NONE,
                        'physical_address' => Contact::DISPLAY_ALL,
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(
            [
                'email_address' => 1,
                'phone_number' => Contact::DISPLAY_NONE,
                'physical_address' => Contact::DISPLAY_ALL,
            ],
            $this->getSelectionOfContact(1)
        );
    }

    #[Test]
    public function selectionIsStoredWhenAContactIsCreated(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_NAME => [
                    'NEW1' => [
                        'pid' => 100,
                        'page' => 2,
                        'contract' => 1,
                        'email_address' => Contact::DISPLAY_NONE,
                        'phone_number' => 1,
                        'physical_address' => 1,
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $createdUid = (int)($dataHandler->substNEWwithIDs['NEW1'] ?? 0);
        $this->assertGreaterThan(0, $createdUid);
        $this->assertSame(
            [
                'email_address' => Contact::DISPLAY_NONE,
                'phone_number' => 1,
                'physical_address' => 1,
            ],
            $this->getSelectionOfContact($createdUid)
        );
    }

    /**
     * A new contact record keeps the default of the three selects, so contact records
     * created without touching them render as they did before the selects existed.
     */
    #[Test]
    public function contactWithoutSelectionKeepsTheDisplayAllDefault(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_NAME => [
                    'NEW1' => [
                        'pid' => 100,
                        'page' => 2,
                        'contract' => 1,
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(
            [
                'email_address' => Contact::DISPLAY_ALL,
                'phone_number' => Contact::DISPLAY_ALL,
                'physical_address' => Contact::DISPLAY_ALL,
            ],
            $this->getSelectionOfContact((int)($dataHandler->substNEWwithIDs['NEW1'] ?? 0))
        );
    }

    /**
     * @return array<string, int>
     */
    private function getSelectionOfContact(int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_NAME);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('email_address', 'phone_number', 'physical_address')
            ->from(self::TABLE_NAME)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        $this->assertIsArray($row, sprintf('Contact record "%d" does not exist.', $uid));

        return array_map(intval(...), $row);
    }
}
