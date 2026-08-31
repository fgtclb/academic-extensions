<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Synchronization;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\AcademicPersons\Domain\Model\Dto\Syncronizer\SynchronizerContext;
use FGTCLB\AcademicPersons\Service\RecordSynchronizerInterface;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Target behaviour of the translation synchronization for `academic_contacts4pages`
 * contact records (ACE-484, resolving ACE-103 / HNEE-1249).
 *
 * A contact is an inline child of a contract (foreign_field `contract`), so the
 * DataHandler cascade of a profile synchronization localizes the contract and the
 * contact below it. The contact's `page` column is a plain `group` relation holding
 * the default-language page uid, copied verbatim by `localize`. The policy guard
 * (`DataHandlerHooks::processCmdmap_afterFinish`) therefore removes a freshly
 * localized contact again when its page has no translation in the target language;
 * a contact whose page IS translated keeps its translation, still pointing at the
 * default-language page uid - that is how TYPO3 models page references.
 *
 * The read side (`ContactRepository::findByPid()`) returns each contact exactly
 * once per language context, including for legacy duplicated translations that an
 * earlier synchronizer version already wrote into a database.
 */
final class ContactTranslationSynchronizationTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

    private const TABLE_PROFILE = 'tx_academicpersons_domain_model_profile';
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
            identifier: 'synchronizer-test',
            site: $this->buildSiteConfiguration(1, 'https://www.acme.com/'),
            languages: [
                $this->buildDefaultLanguageConfiguration(identifier: 'EN', base: '/'),
                $this->buildLanguageConfiguration(identifier: 'DE', base: '/de/'),
            ],
        );
    }

    protected function tearDown(): void
    {
        GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites', true);
        parent::tearDown();
    }

    /**
     * Builds the context the way `SyncChangesToTranslations` does - site resolved
     * through the `SiteFinder`, allowed language ids as plain integers - but calls the
     * service directly instead of routing through the event listener, which would add
     * the persons-edit extension and its extension configuration to the arrangement.
     */
    private function synchronizeIntoGerman(string $tableName, int $uid): void
    {
        $recordSynchronizer = $this->get(RecordSynchronizerInterface::class);
        $context = SynchronizerContext::create(
            recordSyncronizer: $recordSynchronizer,
            site: $this->get(SiteFinder::class)->getSiteByPageId(1),
            allowedLanguageIds: [1],
            tableName: $tableName,
            uid: $uid,
        );
        $recordSynchronizer->synchronize($context);
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
     * @return array<string, mixed> The single language-1 row of the table, failing the test if there is none.
     */
    private function fetchTranslatedRow(string $tableName): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from($tableName)
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', 1),
            )
            ->executeQuery()
            ->fetchAllAssociative();
        $this->assertCount(1, $rows, sprintf('Expected exactly one language-1 row in "%s".', $tableName));
        return $rows[0];
    }

    private function assertPageTwoHasNoTranslation(): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $this->assertSame(0, (int)$queryBuilder
            ->count('uid')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('l10n_parent', 2))
            ->executeQuery()
            ->fetchOne(), 'Fixture guard failed: page 2 must not have a translation here.');
    }

    /**
     * The guard removes a freshly localized contact with a regular DataHandler soft
     * delete, so "no translated contact" means: no undeleted row carrying a language.
     * The soft-deleted remains are asserted explicitly where a localization happened.
     */
    private function assertNoLiveTranslatedContact(): void
    {
        foreach ($this->fetchAllRows(self::TABLE_CONTACT) as $row) {
            if ((int)$row['deleted'] === 0) {
                $this->assertSame(0, (int)$row['sys_language_uid'], sprintf(
                    'Contact %d is an undeleted translated contact - the ACE-484 guard did not remove it.',
                    (int)$row['uid'],
                ));
            }
        }
    }

    /**
     * Extbase reads the language aspect of the global context when it builds the query
     * settings, so this is what a frontend request in that language leaves behind.
     */
    private function setUpLanguageAspect(int $languageId): void
    {
        GeneralUtility::makeInstance(Context::class)->setAspect(
            'language',
            new LanguageAspect($languageId, $languageId, LanguageAspect::OVERLAYS_ON)
        );
    }

    /**
     * ACE-484: the DataHandler cascade still reaches contract AND contact when the
     * profile is synchronised, but the contact translation pointing at the
     * untranslated page 2 is removed again by the policy guard - as a soft delete,
     * observable in the remains: exactly one undeleted contact (the default), and
     * the removed translation row still present with `deleted=1`.
     */
    #[Test]
    public function profileSynchronizationWithUntranslatedPageYieldsNoTranslatedContact(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageNotTranslated.csv');
        $this->assertPageTwoHasNoTranslation();

        $this->synchronizeIntoGerman(self::TABLE_PROFILE, 1);

        $translatedProfile = $this->fetchTranslatedRow(self::TABLE_PROFILE);
        $this->assertSame(1, (int)$translatedProfile['l10n_parent']);
        $this->assertCount(2, $this->fetchAllRows(self::TABLE_CONTRACT), 'Expected the contract to be translated by the cascade.');
        $translatedContract = $this->fetchTranslatedRow(self::TABLE_CONTRACT);
        $this->assertSame((int)$translatedProfile['uid'], (int)$translatedContract['profile']);
        $this->assertNoLiveTranslatedContact();
        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows, 'Expected the default contact and the soft-deleted remains of the removed translation.');
        $this->assertSame(0, (int)$contactRows[1]['deleted']);
        $this->assertSame(1, (int)$contactRows[2]['deleted']);
        $this->assertSame(1, (int)$contactRows[2]['sys_language_uid']);
    }

    /**
     * The same policy holds when the contact table is synchronised directly, without
     * the profile cascade: page 2 has no German translation, so no German contact
     * survives the run.
     */
    #[Test]
    public function contactSynchronizationWithUntranslatedPageYieldsNoTranslatedContact(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageNotTranslated.csv');
        $this->assertPageTwoHasNoTranslation();

        $this->synchronizeIntoGerman(self::TABLE_CONTACT, 1);

        $this->assertNoLiveTranslatedContact();
        $contactRows = $this->fetchAllRows(self::TABLE_CONTACT);
        $this->assertCount(2, $contactRows, 'Expected the default contact and the soft-deleted remains of the removed translation.');
        $this->assertSame(1, (int)$contactRows[2]['deleted']);
    }

    /**
     * ACE-484 contrast case: when page 2 IS translated into German, the cascade's
     * contact translation is kept - and its `page` column holds the default-language
     * uid 2, not the uid 3 of the German page row, because pointing a relation at
     * the default-language uid is how TYPO3 models page references.
     */
    #[Test]
    public function profileSynchronizationWithTranslatedPageKeepsTheTranslatedContact(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageTranslated.csv');

        $this->synchronizeIntoGerman(self::TABLE_PROFILE, 1);

        $translatedContact = $this->fetchTranslatedRow(self::TABLE_CONTACT);
        $this->assertSame(0, (int)$translatedContact['deleted']);
        $this->assertSame(1, (int)$translatedContact['l10n_parent']);
        $this->assertSame(2, (int)$translatedContact['page']);
        $this->assertNotSame(3, (int)$translatedContact['page']);
        $translatedContract = $this->fetchTranslatedRow(self::TABLE_CONTRACT);
        $this->assertSame((int)$translatedContract['uid'], (int)$translatedContact['contract']);
    }

    /**
     * The direct contact synchronisation keeps the translation for a translated page
     * as well, wired below the default-language contract (the `contract` pointer is
     * copied verbatim when the cascade does not run).
     */
    #[Test]
    public function contactSynchronizationWithTranslatedPageKeepsTheTranslationPointingAtTheDefaultPageUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageTranslated.csv');

        $this->synchronizeIntoGerman(self::TABLE_CONTACT, 1);

        $this->assertCount(2, $this->fetchAllRows(self::TABLE_CONTACT), 'Expected the default contact and one kept translation.');
        $translatedContact = $this->fetchTranslatedRow(self::TABLE_CONTACT);
        $this->assertSame(0, (int)$translatedContact['deleted']);
        $this->assertSame(1, (int)$translatedContact['l10n_parent']);
        $this->assertSame(1, (int)$translatedContact['l10n_source']);
        $this->assertSame(100, (int)$translatedContact['pid']);
        $this->assertSame(1, (int)$translatedContact['contract']);
        $this->assertSame(2, (int)$translatedContact['page']);
    }

    /**
     * Read side, untranslated page: after the profile synchronisation (which leaves
     * no contact translation behind) the one contact of page 2 arrives exactly once
     * under the German language aspect, as the overlaid default record.
     */
    #[Test]
    public function findByPidUnderTheTranslatedLanguageReturnsTheContactOnceForAnUntranslatedPage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageNotTranslated.csv');
        $this->synchronizeIntoGerman(self::TABLE_PROFILE, 1);
        $this->setUpLanguageAspect(1);

        $contacts = iterator_to_array($this->get(ContactRepository::class)->findByPid(2));

        $this->assertCount(1, $contacts, 'Expected the one contact of page 2 to arrive exactly once.');
        $this->assertSame(1, (int)$contacts[0]->getUid());
        $this->assertSame(1, (int)$contacts[0]->_getProperty('_localizedUid'));
    }

    /**
     * Read side, translated page: the kept contact translation represents the contact
     * under the German language aspect - once, with the translation row as its
     * localized uid.
     */
    #[Test]
    public function findByPidUnderTheTranslatedLanguageReturnsTheTranslatedContactOnce(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageTranslated.csv');
        $this->synchronizeIntoGerman(self::TABLE_PROFILE, 1);
        $translatedContactUid = (int)$this->fetchTranslatedRow(self::TABLE_CONTACT)['uid'];
        $this->setUpLanguageAspect(1);

        $contacts = iterator_to_array($this->get(ContactRepository::class)->findByPid(2));

        $this->assertCount(1, $contacts, 'Expected the one contact of page 2 to arrive exactly once.');
        $this->assertSame(1, (int)$contacts[0]->getUid());
        $this->assertSame($translatedContactUid, (int)$contacts[0]->_getProperty('_localizedUid'));
    }

    /**
     * The real-world value of the read-side fix (HNEE-class installations): a legacy
     * duplicated contact translation that an earlier synchronizer version already
     * wrote into the database - page untranslated, `page` column copied verbatim -
     * collapses to one contact per language context without any database cleanup.
     * No synchronisation runs here; the fixture IS the legacy state.
     */
    #[Test]
    public function findByPidCollapsesALegacyDuplicatedContactTranslationToOneContact(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageNotTranslatedLegacyDuplicate.csv');
        $this->assertPageTwoHasNoTranslation();
        $contactRepository = $this->get(ContactRepository::class);

        $this->setUpLanguageAspect(1);
        $germanContacts = iterator_to_array($contactRepository->findByPid(2));
        $this->assertCount(1, $germanContacts, 'Expected the legacy duplicate to collapse to one contact under German.');
        $this->assertSame(1, (int)$germanContacts[0]->getUid());
        $this->assertSame(2, (int)$germanContacts[0]->_getProperty('_localizedUid'));

        $this->setUpLanguageAspect(0);
        $defaultContacts = iterator_to_array($contactRepository->findByPid(2));
        $this->assertCount(1, $defaultContacts, 'Expected the legacy duplicate to stay invisible in the default language.');
        $this->assertSame(1, (int)$defaultContacts[0]->getUid());
        $this->assertSame(1, (int)$defaultContacts[0]->_getProperty('_localizedUid'));
        $this->assertSame(
            count($defaultContacts),
            $contactRepository->findByPid(2)->count(),
            'count() must agree with the number of iterated objects.',
        );
    }
}
