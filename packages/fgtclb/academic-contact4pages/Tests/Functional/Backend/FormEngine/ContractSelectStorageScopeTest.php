<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Backend\FormEngine;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The contract select of a contact record, restricted by the page TSconfig of the page
 * the record is stored on. The handler comes from `academic_persons`; what this test
 * adds is the second field it serves - a plain TCA column with `selectSingle`, where the
 * page TSconfig arrives under `TCEFORM.<table>.<field>.itemsProcFunc.` without the
 * FlexForm detour, and where only one contract can be referenced.
 *
 * @see \FGTCLB\AcademicPersons\Tests\Functional\Backend\FormEngine\ContractSelectStorageScopeTest
 */
final class ContractSelectStorageScopeTest extends AbstractAcademicContacts4PagesTestCase
{
    private const TABLE = 'tx_academiccontacts4pages_domain_model_contact';
    private const STORAGE_ONE = 20;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContractSelectStorageScope/contacts.csv');
        $connection = $this->getConnectionPool()->getConnectionForTable('pages');
        $connection->update(
            'pages',
            ['TSconfig' => $this->tsConfig(['storagePids = ' . self::STORAGE_ONE])],
            ['uid' => 41],
        );
        $connection->update(
            'pages',
            ['TSconfig' => $this->tsConfig(['storagePids = ' . self::STORAGE_ONE, 'recursive = 1'])],
            ['uid' => 42],
        );
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    #[Test]
    public function everyContractIsOfferedWithoutTheSetting(): void
    {
        $this->assertSame([1, 2, 3], $this->offeredContractUids(1));
    }

    /**
     * Contract 3 is stored in the hidden subfolder of the listed page, which is only
     * reached with a depth. Contract 2 is not on the listed page either, and is offered
     * only because this record references it - see
     * `theReferencedContractStaysSelected()`.
     */
    #[Test]
    public function contractsOfAnotherPageAreNotOffered(): void
    {
        $this->assertSame([1, 2], $this->offeredContractUids(2));
    }

    #[Test]
    public function contractsOfASubFolderAreOfferedWithADepth(): void
    {
        $this->assertSame([1, 3], $this->offeredContractUids(3));
    }

    /**
     * The select is the only source of its items - the column has no `foreign_table` -
     * so `AbstractItemProvider::processSelectFieldValue()` drops a value that is not
     * among them from the row. The editor would then see an empty select and the next
     * save would clear the contract.
     */
    #[Test]
    public function theReferencedContractStaysSelected(): void
    {
        $this->assertSame(['2'], $this->compile(2)['databaseRow']['contract']);
    }

    /**
     * The field declares an empty item of value 0, which is also its default. The
     * handler used to merge its contracts over the declared items by index, which
     * replaced that item with the first contract, so a new contact, and contact 4,
     * which has no contract, showed `[ MISSING LABEL ("0") ]` in its contract select
     * (ACE-868). The form of a new contact is not compiled here: the record label of
     * a contact still looks the `NEW…` placeholder up as a uid, which PostgreSQL
     * rejects (ACE-872).
     */
    #[Test]
    public function theEmptyItemIsKept(): void
    {
        $result = $this->compile(4);
        $items = array_map(
            static fn($item): array => is_array($item) ? $item : $item->toArray(),
            array_values($result['processedTca']['columns']['contract']['config']['items'] ?? []),
        );

        $this->assertSame(['0'], $result['databaseRow']['contract']);
        $this->assertSame([0, 1, 2, 3], array_map(static fn(array $item): int => (int)$item['value'], $items));
        // FormEngine puts a value without an item back as `[ MISSING LABEL ("0") ]`.
        $this->assertSame('', $items[0]['label']);
    }

    /**
     * @return int[]
     */
    private function offeredContractUids(int $contactUid): array
    {
        $items = $this->compile($contactUid)['processedTca']['columns']['contract']['config']['items'] ?? [];
        $uids = array_map(
            static fn(array $item): int => (int)($item['value'] ?? 0),
            array_map(
                static fn($item): array => is_array($item) ? $item : $item->toArray(),
                array_values($items),
            ),
        );
        // The empty item the field declares, see `theEmptyItemIsKept()`.
        $uids = array_values(array_filter($uids, static fn(int $uid): bool => $uid > 0));
        sort($uids);

        return $uids;
    }

    /**
     * @return array<string, mixed>
     */
    private function compile(int $contactUid): array
    {
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        return GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => self::TABLE,
                'vanillaUid' => $contactUid,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
    }

    /**
     * @param string[] $lines
     */
    private function tsConfig(array $lines): string
    {
        return implode("\n", [
            'TCEFORM.' . self::TABLE . '.contract.itemsProcFunc {',
            ...array_map(static fn(string $line): string => '  ' . $line, $lines),
            '}',
        ]);
    }
}
