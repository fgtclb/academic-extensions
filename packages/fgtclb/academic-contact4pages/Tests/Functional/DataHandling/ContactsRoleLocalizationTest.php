<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataHandling;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Localizing a contacts role localizes no page contact (ACE-874).
 *
 * A page contact is translated with its page and keeps the contacts role of its default
 * language record. `DataHandler::localize()` copies every inline child of a localized
 * record all the same: it created a translation of every contact the role lists,
 * attached to the role translation, and one more copy of a contact that is translated
 * or valid in all languages already, so a page showed the contact twice.
 *
 * The fixture lists four contacts on role 1: contact 1 with its translation 3, the
 * untranslated contact 2, and contact 4, valid in all languages.
 */
final class ContactsRoleLocalizationTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = [
        'typo3/cms-install',
        'typo3/cms-rte-ckeditor',
        'typo3/cms-workspaces',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    private const TABLE_ROLE = 'tx_academiccontacts4pages_domain_model_role';
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';

    /**
     * The contacts as the fixture stores them.
     */
    private const CONTACTS = [
        ['uid' => 1, 'sys_language_uid' => 0, 'l10n_parent' => 0, 'page' => 10, 'role' => 1],
        ['uid' => 2, 'sys_language_uid' => 0, 'l10n_parent' => 0, 'page' => 10, 'role' => 1],
        ['uid' => 3, 'sys_language_uid' => 1, 'l10n_parent' => 1, 'page' => 10, 'role' => 1],
        ['uid' => 4, 'sys_language_uid' => -1, 'l10n_parent' => 0, 'page' => 10, 'role' => 1],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'contacts-role-localization-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactsRoleLocalization/roleWithContacts.csv');
        $this->setUpBackendUser(1);
        // The DataHandler reads the language service from $GLOBALS['LANG'].
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['BE_USER'], $GLOBALS['LANG']);
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * `localize` connects the role translation to its default record, `copyToLanguage`
     * copies the role into the language without a connection. Both copy the inline
     * children the same way.
     *
     * @return array<string, array{0: string}>
     */
    public static function languageCommandProvider(): array
    {
        return [
            'localize' => ['localize'],
            'copy to language' => ['copyToLanguage'],
        ];
    }

    #[Test]
    #[DataProvider('languageCommandProvider')]
    public function localizingARoleLeavesItsContactsAlone(string $command): void
    {
        $roleTranslationUid = $this->localizeRole($command);

        $this->assertGreaterThan(1, $roleTranslationUid);
        $this->assertSame(self::CONTACTS, $this->fetchContacts());
        $this->assertSame(0, $this->countRecordsEverCreated(), 'A contact was created and left behind, deleted or not.');
        $this->assertSame(0, $this->contactsColumnOf($roleTranslationUid), 'The role translation still counts removed contacts.');
    }

    /**
     * In a workspace the localization creates new records of that workspace. The
     * contacts among them are discarded, nothing reaches the live workspace or stays
     * behind in the workspace.
     */
    #[Test]
    public function localizingARoleInAWorkspaceLeavesItsContactsAlone(): void
    {
        $GLOBALS['BE_USER']->workspace = 1;

        $roleTranslationUid = $this->localizeRole('localize');

        $this->assertGreaterThan(1, $roleTranslationUid);
        $this->assertSame(self::CONTACTS, $this->fetchContacts());
        $this->assertSame(0, $this->countRecordsEverCreated(), 'A contact was created and left behind, deleted or not.');
    }

    /**
     * The translation the editor saves next creates no contact either.
     */
    #[Test]
    public function savingTheLocalizedRoleLeavesItsContactsAlone(): void
    {
        $roleTranslationUid = $this->localizeRole('localize');

        $this->processDataMap([self::TABLE_ROLE => [$roleTranslationUid => ['name' => 'Büroleitung']]]);
        $this->processDataMap([self::TABLE_ROLE => [$roleTranslationUid => ['name' => 'Leitung des Büros']]]);

        $this->assertSame(self::CONTACTS, $this->fetchContacts());
    }

    /**
     * A plain copy of a role in its own language is not a localization and keeps copying
     * its contacts, as before.
     */
    #[Test]
    public function copyingARoleStillCopiesItsContacts(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_ROLE => [1 => ['copy' => 100]]]);
        $dataHandler->process_cmdmap();
        $this->assertSame([], $dataHandler->errorLog);
        $roleCopyUid = (int)($dataHandler->copyMappingArray_merged[self::TABLE_ROLE][1] ?? 0);

        $this->assertGreaterThan(1, $roleCopyUid);
        $this->assertNotSame([], array_filter(
            $this->fetchContacts(),
            static fn(array $contact): bool => $contact['role'] === $roleCopyUid,
        ));
    }

    /**
     * The core still tries to localize contact 1 with the role and refuses, because the
     * contact is translated already. That message is the only one the run reports.
     */
    private function localizeRole(string $command): int
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [self::TABLE_ROLE => [1 => [$command => 1]]]);
        $dataHandler->process_cmdmap();
        $this->assertCount(1, $dataHandler->errorLog, implode(PHP_EOL, $dataHandler->errorLog));
        // The wording differs between TYPO3 v13 and v14, the record it names does not.
        $this->assertStringContainsString('Localization failed', $dataHandler->errorLog[0]);
        $this->assertStringContainsString('"' . self::TABLE_CONTACT . '"', $dataHandler->errorLog[0]);
        $this->assertMatchesRegularExpression('/record 1\\b/', $dataHandler->errorLog[0]);
        $this->assertMatchesRegularExpression('/\\(3\\)|UID: 3\\)/', $dataHandler->errorLog[0]);

        return (int)($dataHandler->copyMappingArray_merged[self::TABLE_ROLE][1] ?? 0);
    }

    /**
     * @param array<string, array<int, array<string, int|string>>> $dataMap
     */
    private function processDataMap(array $dataMap): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
        $this->assertSame([], $dataHandler->errorLog, 'The DataHandler run reported errors.');
    }

    /**
     * @return list<array<string, int>> The contacts that are not deleted, in uid order.
     */
    private function fetchContacts(): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTACT);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('uid', 'sys_language_uid', 'l10n_parent', 'page', 'role')
            ->from(self::TABLE_CONTACT)
            ->where($queryBuilder->expr()->eq('deleted', 0))
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
        return array_map(
            static fn(array $row): array => array_map(intval(...), $row),
            $rows,
        );
    }

    private function contactsColumnOf(int $roleUid): int
    {
        return (int)$this->getConnectionPool()->getConnectionForTable(self::TABLE_ROLE)
            ->select(['contacts'], self::TABLE_ROLE, ['uid' => $roleUid])
            ->fetchOne();
    }

    private function countRecordsEverCreated(): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::TABLE_CONTACT);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from(self::TABLE_CONTACT)
            ->where($queryBuilder->expr()->gt('uid', 4))
            ->executeQuery()
            ->fetchOne();
    }
}
