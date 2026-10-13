<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Plugins;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\TestingHelper\FunctionalTestCase\FrontendPluginRenderingTrait;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An address selection made in a workspace, published, and rendered by the contacts list
 * plugin (ACE-481).
 *
 * Publishing keeps the uid of every record: a changed record is written into its live
 * record and the version row is deleted, a record created in the workspace keeps its own
 * uid. A selection therefore has to hold the uid of the live record, and that is what the
 * compiled form offers. The selection is taken from the form, as an editor makes it.
 *
 * The first workspace edit of a contract versions its addresses and its page contacts in
 * the same command, and core records each of them in the copy mapping, live uid to version
 * uid. The selects declare their address table in `dontRemapTablesOnCopy`, so core does
 * not remap the contact version through that mapping to the version row of its address.
 */
final class AddressSelectionPublishTest extends AbstractAcademicContacts4PagesTestCase
{
    use FrontendPluginRenderingTrait;
    use SiteBasedTestTrait;

    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';
    private const TABLE_EMAIL = 'tx_academicpersons_domain_model_email';
    private const TABLE_PHONE_NUMBER = 'tx_academicpersons_domain_model_phone_number';
    private const TABLE_ADDRESS = 'tx_academicpersons_domain_model_address';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        $this->configurationToUseInTestInstance = $this->frontendPluginTestConfiguration();
        $this->addCoreExtensionsToLoad('typo3/cms-fluid-styled-content');
        $this->addCoreExtensionsToLoad('typo3/cms-workspaces');
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AddressSelectionPublish/contactsListPage.csv');
        $this->setUpFrontendRootPage(
            pageId: 1,
            typoScriptFiles: [
                'constants' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/constants.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/constants.typoscript',
                    'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Constants/PluginConfiguration.typoscript',
                ],
                'setup' => [
                    'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript',
                    'EXT:academic_persons/Configuration/TypoScript/Default/setup.typoscript',
                    'EXT:academic_contacts4pages/Configuration/TypoScript/List/setup.typoscript',
                    'EXT:academic_contacts4pages/Tests/Functional/Plugins/Fixtures/TypoScript/Setup/Rendering.typoscript',
                ],
            ],
        );
        $this->writeFrontendPluginTestSite([
            $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
        ]);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG'], $GLOBALS['TYPO3_REQUEST']);
        $this->removeWrittenSiteConfiguration();
        parent::tearDown();
    }

    #[Test]
    public function aSelectedAddressChangedInTheWorkspaceIsRenderedAfterPublishing(): void
    {
        $this->runDataHandler(1, [self::TABLE_EMAIL => [11 => ['email' => 'anna-changed@example.org']]]);
        $offeredUid = $this->getOfferedUid('email_address', 'anna-changed@example.org');
        $this->runDataHandler(1, [self::TABLE_CONTACT => [1 => ['email_address' => $offeredUid]]]);

        $this->publishWorkspace();

        $this->assertSame(11, $this->getSelection(1)['email_address']);
        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('anna-changed@example.org', $content);
        $this->assertStringNotContainsString('anna-first@example.org', $content);
        $this->assertStringNotContainsString('anna-second@example.org', $content);
    }

    #[Test]
    public function aSelectedAddressCreatedInTheWorkspaceIsRenderedAfterPublishing(): void
    {
        $dataHandler = $this->runDataHandler(1, [
            self::TABLE_PHONE_NUMBER => [
                'NEW1' => ['pid' => 100, 'contract' => 1, 'phone_number' => '+4900099'],
            ],
        ]);
        $createdUid = (int)$dataHandler->substNEWwithIDs['NEW1'];
        $offeredUid = $this->getOfferedUid('phone_number', '+4900099');
        $this->assertSame($createdUid, $offeredUid);
        $this->runDataHandler(1, [self::TABLE_CONTACT => [1 => ['phone_number' => $offeredUid]]]);

        $this->publishWorkspace();

        $this->assertSame($createdUid, $this->getSelection(1)['phone_number']);
        $content = $this->renderFrontendPage('https://www.acme.com/home');
        $this->assertStringContainsString('+4900099', $content);
        $this->assertStringNotContainsString('+4900011', $content);
    }

    /**
     * Asserts the stored selections only. Publishing the contract also moves its addresses
     * to the contract's deleted version row, because their own pointer to the contract is
     * remapped the same way (ACE-907), so the page shows none of them yet.
     */
    #[Test]
    public function aSelectionSurvivesTheWorkspaceEditOfItsContract(): void
    {
        $this->runDataHandler(1, [self::TABLE_CONTRACT => [1 => ['position' => 'Head of office']]]);

        $this->assertGreaterThan(0, $this->getVersionUid(self::TABLE_EMAIL, 11), 'Expected the e-mail address to be versioned with its contract.');
        $this->assertGreaterThan(0, $this->getVersionUid(self::TABLE_ADDRESS, 12), 'Expected the postal address to be versioned with its contract.');
        $contactVersionUid = $this->getVersionUid(self::TABLE_CONTACT, 1);
        $this->assertGreaterThan(0, $contactVersionUid, 'Expected the page contact to be versioned with its contract.');
        $this->assertSame(
            ['email_address' => 11, 'phone_number' => 0, 'physical_address' => 12],
            $this->getSelection($contactVersionUid),
        );

        $this->publishWorkspace();

        $this->assertSame(
            ['email_address' => 11, 'phone_number' => 0, 'physical_address' => 12],
            $this->getSelection(1),
        );
        $this->assertSame(0, (int)$this->getRow(self::TABLE_EMAIL, 11)['deleted']);
        $this->assertSame(0, (int)$this->getRow(self::TABLE_ADDRESS, 12)['deleted']);
    }

    /**
     * The value the e-mail or phone number select of contact 1 offers for an address, in the
     * form compiled for workspace 1.
     */
    private function getOfferedUid(string $fieldName, string $address): int
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = 1;
        $request = (new ServerRequest('https://localhost/typo3/record/edit'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $request = $request->withAttribute('normalizedParams', NormalizedParams::createFromRequest($request));
        $GLOBALS['TYPO3_REQUEST'] = $request;
        $result = GeneralUtility::makeInstance(FormDataCompiler::class)->compile(
            [
                'request' => $request,
                'tableName' => self::TABLE_CONTACT,
                'vanillaUid' => 1,
                'command' => 'edit',
            ],
            $this->get(TcaDatabaseRecord::class),
        );
        unset($GLOBALS['TYPO3_REQUEST']);

        $offeredUids = [];
        foreach ($result['processedTca']['columns'][$fieldName]['config']['items'] as $item) {
            if (str_contains((string)$item['label'], $address)) {
                $offeredUids[] = (int)$item['value'];
            }
        }
        $this->assertCount(1, $offeredUids, sprintf('Expected the "%s" select to offer "%s" once.', $fieldName, $address));

        return $offeredUids[0];
    }

    /**
     * @param array<string, array<int|string, array<string, mixed>>> $dataMap
     * @param array<string, array<int|string, array<string, mixed>>> $commandMap
     */
    private function runDataHandler(int $workspaceId, array $dataMap, array $commandMap = []): DataHandler
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $workspaceId;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, $commandMap, $backendUser);
        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');

        return $dataHandler;
    }

    /**
     * Publishes every record of workspace 1 in the tables this test touches, the way the
     * workspace module does when the whole workspace is published, and leaves the
     * frontend request without a backend user.
     */
    private function publishWorkspace(): void
    {
        $commandMap = [];
        foreach ([self::TABLE_CONTRACT, self::TABLE_EMAIL, self::TABLE_PHONE_NUMBER, self::TABLE_ADDRESS, self::TABLE_CONTACT] as $tableName) {
            $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $workspaceRows = $queryBuilder
                ->select('uid', 't3ver_oid')
                ->from($tableName)
                ->where(
                    $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
                )
                ->orderBy('uid')
                ->executeQuery()
                ->fetchAllAssociative();
            foreach ($workspaceRows as $workspaceRow) {
                $liveUid = (int)$workspaceRow['t3ver_oid'] ?: (int)$workspaceRow['uid'];
                $commandMap[$tableName][$liveUid]['version'] = [
                    'action' => 'publish',
                    'swapWith' => (int)$workspaceRow['uid'],
                ];
            }
        }
        $this->runDataHandler(0, [], $commandMap);
        unset($GLOBALS['BE_USER']);
    }

    private function getVersionUid(string $tableName, int $liveUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();

        return (int)$queryBuilder
            ->select('uid')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('t3ver_oid', $queryBuilder->createNamedParameter($liveUid, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return array<string, mixed>
     */
    private function getRow(string $tableName, int $uid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $row = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();
        $this->assertIsArray($row, sprintf('Record "%s:%d" does not exist.', $tableName, $uid));

        return $row;
    }

    /**
     * @return array<string, int>
     */
    private function getSelection(int $contactUid): array
    {
        $row = $this->getRow(self::TABLE_CONTACT, $contactUid);

        return [
            'email_address' => (int)$row['email_address'],
            'phone_number' => (int)$row['phone_number'],
            'physical_address' => (int)$row['physical_address'],
        ];
    }
}
