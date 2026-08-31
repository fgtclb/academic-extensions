<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataHandling;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Workspace behaviour of the ACE-484 policy guard.
 *
 * The page-translation check runs with a `WorkspaceRestriction` for the acting
 * workspace: a page translated only in that workspace counts as translated there,
 * while a workspace-only page translation is invisible to a live run. When the
 * guard removes a contact translation created in a workspace, the DataHandler
 * `delete` command dispatches to `discard()` for the versioned row - the new
 * placeholder is removed entirely and no row, deleted or otherwise, leaks into
 * the live state.
 */
final class ContactLocalizationPolicyWorkspaceTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-workspaces',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'policy-workspace-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    private function localizeContractAsBackendUser(int $workspaceId): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->workspace = $workspaceId;
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_CONTRACT => [1 => ['localize' => 1]]], $backendUser);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * @return array<int, array<string, mixed>> All rows of the table, deleted ones included, keyed and ordered by uid.
     */
    private function fetchAllRows(string $tableName): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        $rowsByUid = [];
        foreach ($rows as $row) {
            $rowsByUid[(int)$row['uid']] = $row;
        }
        return $rowsByUid;
    }

    /**
     * Cascade in workspace 1, page not translated at all: the contract translation
     * is created as a versioned row, and the contact translation the cascade
     * created is DISCARDED - the contact table holds exactly the untouched fixture
     * row afterwards. Nothing, deleted or otherwise, leaks into the live state.
     */
    #[Test]
    public function workspaceCascadeWithUntranslatedPageDiscardsTheContactTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactLocalizationPolicy/workspacePageNotTranslated.csv');

        $this->localizeContractAsBackendUser(1);

        $contractRows = $this->fetchAllRows(self::TABLE_CONTRACT);
        $this->assertCount(2, $contractRows, 'Expected the contract translation to be created.');
        $this->assertSame(1, (int)$contractRows[2]['sys_language_uid']);
        $this->assertSame(1, (int)$contractRows[2]['t3ver_wsid']);
        $this->assertSame(1, (int)$contractRows[2]['t3ver_state']);
        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(1, $contactRows, 'Expected the discarded contact translation to leave no row behind.');
        $fixtureContact = $contactRows[1];
        $this->assertSame(0, (int)$fixtureContact['deleted']);
        $this->assertSame(0, (int)$fixtureContact['sys_language_uid']);
        $this->assertSame(0, (int)$fixtureContact['t3ver_wsid']);
    }

    /**
     * Cascade in workspace 1, page translated in the LIVE workspace: the live
     * translation is visible to the workspace, so the contact translation is kept -
     * as a versioned row only, the live state stays untouched.
     */
    #[Test]
    public function workspaceCascadeWithLivePageTranslationKeepsTheContactTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactLocalizationPolicy/workspacePageTranslatedLive.csv');

        $this->localizeContractAsBackendUser(1);

        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows, 'Expected the contact translation to be kept.');
        $translatedContact = $contactRows[2];
        $this->assertSame(0, (int)$translatedContact['deleted']);
        $this->assertSame(1, (int)$translatedContact['sys_language_uid']);
        $this->assertSame(1, (int)$translatedContact['l10n_parent']);
        $this->assertSame(1, (int)$translatedContact['t3ver_wsid']);
        $this->assertSame(1, (int)$translatedContact['t3ver_state']);
        $this->assertSame(2, (int)$translatedContact['page']);
        $this->assertSame(0, (int)$contactRows[1]['t3ver_wsid'], 'The live contact row must stay untouched.');
    }

    /**
     * Cascade in workspace 1, page translated ONLY in that workspace: the
     * translation check runs with the acting workspace, so the page counts as
     * translated and the contact translation is kept as a versioned row.
     */
    #[Test]
    public function workspaceCascadeWithWorkspaceOnlyPageTranslationKeepsTheContactTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactLocalizationPolicy/workspacePageTranslatedWorkspaceOnly.csv');

        $this->localizeContractAsBackendUser(1);

        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows, 'Expected the contact translation to be kept.');
        $translatedContact = $contactRows[2];
        $this->assertSame(0, (int)$translatedContact['deleted']);
        $this->assertSame(1, (int)$translatedContact['sys_language_uid']);
        $this->assertSame(1, (int)$translatedContact['t3ver_wsid']);
        $this->assertSame(1, (int)$translatedContact['t3ver_state']);
    }

    /**
     * The counterpart proving the check honours the ACTING workspace: the same
     * fixture, localized in the live workspace, does not see the workspace-only
     * page translation - the contact translation is removed again (a live soft
     * delete this time, since the row was created live).
     */
    #[Test]
    public function liveCascadeDoesNotCountAWorkspaceOnlyPageTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactLocalizationPolicy/workspacePageTranslatedWorkspaceOnly.csv');

        $this->localizeContractAsBackendUser(0);

        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows, 'Expected the default contact and the soft-deleted remains of the removed translation.');
        $this->assertSame(0, (int)$contactRows[1]['deleted']);
        $removedContact = $contactRows[2];
        $this->assertSame(1, (int)$removedContact['deleted']);
        $this->assertSame(1, (int)$removedContact['sys_language_uid']);
        $this->assertSame(0, (int)$removedContact['t3ver_wsid']);
    }
}
