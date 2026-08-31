<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\DataHandling;

use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The ACE-484 policy guard at the plain backend DataHandler level, without any
 * involvement of the translation synchronization: a backend user localizing a
 * contract (cascading into its contacts) or a contact directly hits
 * {@see \FGTCLB\AcademicContacts4pages\Hook\DataHandlerHooks} the same way.
 *
 * A contact whose page has no translation in the target language yields no
 * translated contact - the freshly localized row is removed again with a regular
 * soft delete. A contact whose page IS translated keeps its translation, pointing
 * at the default-language page uid.
 */
final class ContactLocalizationPolicyTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_CONTRACT = 'tx_academicpersons_domain_model_contract';
    private const TABLE_CONTACT = 'tx_academiccontacts4pages_domain_model_contact';

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            identifier: 'policy-test',
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

    private function localizeRecordAsBackendUser(string $tableName, int $uid): void
    {
        $backendUser = $this->setUpBackendUser(1);
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], [$tableName => [$uid => ['localize' => 1]]], $backendUser);
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
     * Localizing the contract cascades into the contact; page 2 has no German
     * translation, so the guard removes the contact translation again - a soft
     * delete, leaving the default contact as the only undeleted row - while the
     * contract translation itself is kept.
     */
    #[Test]
    public function localizingTheContractRemovesTheContactTranslationOfAnUntranslatedPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactLocalizationPolicy/pageNotTranslated.csv');

        $this->localizeRecordAsBackendUser(self::TABLE_CONTRACT, 1);

        $contractRows = $this->fetchAllRows(self::TABLE_CONTRACT);
        $this->assertCount(2, $contractRows, 'Expected the contract translation to be created.');
        $this->assertSame(1, (int)$contractRows[2]['sys_language_uid']);
        $this->assertSame(0, (int)$contractRows[2]['deleted']);
        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows, 'Expected the default contact and the soft-deleted remains of the removed translation.');
        $this->assertSame(0, (int)$contactRows[1]['deleted']);
        $this->assertSame(0, (int)$contactRows[1]['sys_language_uid']);
        $this->assertSame(1, (int)$contactRows[2]['deleted']);
        $this->assertSame(1, (int)$contactRows[2]['sys_language_uid']);
    }

    /**
     * With page 2 translated the same backend localize keeps the contact
     * translation: wired to the translated contract by the cascade, `page` still
     * holding the default-language uid 2 (not the uid 3 of the German page row).
     */
    #[Test]
    public function localizingTheContractKeepsTheContactTranslationOfATranslatedPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactLocalizationPolicy/pageTranslated.csv');

        $this->localizeRecordAsBackendUser(self::TABLE_CONTRACT, 1);

        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows, 'Expected the default contact and its kept translation.');
        $translatedContact = $contactRows[2];
        $this->assertSame(0, (int)$translatedContact['deleted']);
        $this->assertSame(1, (int)$translatedContact['sys_language_uid']);
        $this->assertSame(1, (int)$translatedContact['l10n_parent']);
        $this->assertSame(2, (int)$translatedContact['page']);
        $contractRows = $this->fetchAllRows(self::TABLE_CONTRACT);
        $this->assertSame((int)$contractRows[2]['uid'], (int)$translatedContact['contract'], 'The cascade must re-point the contact to the translated contract.');
    }

    /**
     * The guard does not depend on the cascade: localizing the contact itself (its
     * own cmdmap entry, as the inline "Localize" control of FormEngine sends it)
     * hits the same policy.
     */
    #[Test]
    public function localizingTheContactDirectlyHitsTheSameGuard(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactLocalizationPolicy/pageNotTranslated.csv');

        $this->localizeRecordAsBackendUser(self::TABLE_CONTACT, 1);

        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows);
        $this->assertSame(0, (int)$contactRows[1]['deleted']);
        $this->assertSame(1, (int)$contactRows[2]['deleted']);
        $this->assertSame(1, (int)$contactRows[2]['sys_language_uid']);
    }
}
