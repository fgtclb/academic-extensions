<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The three address selects of a page contact, compiled the way the backend compiles the
 * form of an existing contact (ACE-481).
 *
 * The person records sit in a folder the editor of the fixture may not see, the page
 * contacts in one the editor may edit. Contact 2 selects an address of another contract,
 * contact 4 points at a contract that is gone.
 */
final class AddressRecordSelectFormTest extends AbstractAcademicContacts4PagesTestCase
{
    private const TABLE_NAME = 'tx_academiccontacts4pages_domain_model_contact';

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AddressRecordSelectForm/contacts.csv');
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        parent::tearDown();
    }

    /**
     * Core adds the record items of the `foreign_table` before the handler runs, and the
     * handler replaces them with its own, so each address is offered once.
     */
    #[Test]
    public function eachAddressOfTheContractIsOfferedOnce(): void
    {
        $result = $this->compileContactForm(1, backendUserUid: 1);

        $this->assertSame([0, -1, 1, 4], $this->getItemValues($result, 'email_address'));
        $this->assertSame('Business: office@example.org', $this->getItemLabel($result, 'email_address', 1));
        $this->assertSame([0, -1, 1], $this->getItemValues($result, 'phone_number'));
        $this->assertSame([0, -1, 1], $this->getItemValues($result, 'physical_address'));
    }

    /**
     * Core leaves out the records on a page the editor may not see. The handler offers them
     * regardless, as the contract select offers the contract.
     */
    #[Test]
    public function anEditorWithoutAccessToThePersonRecordsIsOfferedTheAddresses(): void
    {
        $result = $this->compileContactForm(1, backendUserUid: 2);

        $this->assertSame([0, -1, 1, 4], $this->getItemValues($result, 'email_address'));
        $this->assertSame([0, -1, 1], $this->getItemValues($result, 'phone_number'));
        $this->assertSame([0, -1, 1], $this->getItemValues($result, 'physical_address'));
    }

    /**
     * Contact 2 selects an e-mail address of another contract, which is what a contact looks
     * like after its contract was switched. The form keeps the value and shows it as a value
     * without a matching item, and submitting the compiled values stores the selection
     * unchanged.
     */
    #[Test]
    public function aSelectionMatchingNoOfferedAddressIsKeptAndShownAsInvalid(): void
    {
        $result = $this->compileContactForm(2, backendUserUid: 1);

        $this->assertSame(['3'], $result['databaseRow']['email_address']);
        $this->assertContains(3, $this->getItemValues($result, 'email_address'));
        // The label core gives a value it finds no item for.
        $this->assertStringContainsStringIgnoringCase('missing label ("3")', $this->getItemLabel($result, 'email_address', 3));

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start(
            [
                self::TABLE_NAME => [
                    2 => [
                        'email_address' => implode(',', $result['databaseRow']['email_address']),
                        'phone_number' => implode(',', $result['databaseRow']['phone_number']),
                        'physical_address' => implode(',', $result['databaseRow']['physical_address']),
                    ],
                ],
            ],
            []
        );
        $dataHandler->process_datamap();

        $this->assertSame([], $dataHandler->errorLog);
        $this->assertSame(
            ['email_address' => 3, 'phone_number' => -1, 'physical_address' => 1],
            $this->getSelectionOfContact(2),
        );
    }

    /**
     * Contact 4 points at a contract that is gone, so the contract select drops the value and
     * `###REC_FIELD_contract###` of the address selects becomes an empty string, which
     * PostgreSQL does not compare with an integer column.
     */
    #[Test]
    public function aContactWhoseContractIsGoneIsCompiledWithoutAnError(): void
    {
        $result = $this->compileContactForm(4, backendUserUid: 1);

        $this->assertSame([], $result['databaseRow']['contract']);
    }

    /**
     * @return array<string, mixed>
     */
    private function compileContactForm(int $contactUid, int $backendUserUid): array
    {
        $this->setUpBackendUser($backendUserUid);
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => self::TABLE_NAME,
                'vanillaUid' => $contactUid,
                'command' => 'edit',
            ],
            GeneralUtility::makeInstance(TcaDatabaseRecord::class),
        );
        // A failing item query of core, from the `foreign_table_where` for instance, does not
        // fail the compile, it only queues a flash message for the editor.
        $this->assertSame(
            [],
            array_map(
                static fn(FlashMessage $message): string => $message->getMessage(),
                GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages(),
            ),
        );

        return $result;
    }

    /**
     * @param array<string, mixed> $result
     * @return list<int>
     */
    private function getItemValues(array $result, string $fieldName): array
    {
        return array_map(
            static fn(array $item): int => (int)$item['value'],
            $result['processedTca']['columns'][$fieldName]['config']['items'],
        );
    }

    /**
     * @param array<string, mixed> $result
     */
    private function getItemLabel(array $result, string $fieldName, int $value): string
    {
        foreach ($result['processedTca']['columns'][$fieldName]['config']['items'] as $item) {
            if ((int)$item['value'] === $value) {
                return (string)$item['label'];
            }
        }
        $this->fail(sprintf('The "%s" select offers no item "%d".', $fieldName, $value));
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
