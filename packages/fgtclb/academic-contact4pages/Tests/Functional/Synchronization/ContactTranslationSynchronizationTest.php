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
 * Characterisation of what `RecordSynchronizer` does to `academic_contacts4pages`
 * contact records today (ACE-103 / HNEE-1249, resolution planned as ACE-484).
 *
 * A contact is an inline child of a contract (foreign_field `contract`), so the
 * synchronizer's inline recursion is meant to reach it when a profile is synchronised
 * into a target language. Two independent defects meet here:
 *
 * 1. The recursion is dead code. `synchronizeRecord()` reads the column type from
 *    `$columnDefinition['type']` - a key that does not exist in TCA, where the type
 *    lives in `config` - so every column resolves to `unknown` and no inline child is
 *    ever visited. A profile synchronisation translates the profile row and nothing
 *    below it. The lookup broke when the code was extracted out of
 *    `SyncChangesToTranslations` (819ceebe8, first released in 2.1.0); the listener
 *    before that read `config['type']` and did recurse, which is how the duplicated
 *    contacts of ACE-103 came into existence. The first test pins the dead recursion.
 * 2. `createTranslation()` copies every non-inline column verbatim. The contact's
 *    `page` column is a plain `group` field pointing at `pages` without
 *    `l10n_mode=exclude`, so the moment a contact is synchronised - today only by
 *    handing the contact table to the service directly, after a recursion fix through
 *    the profile as well - the translated contact carries the default-language page
 *    uid, whether or not that page is translated. The remaining tests pin that copy
 *    mechanism and the read-side duplication it produces.
 *
 * Every test here pins CURRENT behaviour, defects included; none of it is endorsed.
 * When ACE-484 changes the policy - no translated contact for an untranslated page -
 * these assertions are where the change must become visible.
 */
final class ContactTranslationSynchronizationTest extends AbstractAcademicContacts4PagesTestCase
{
    use SiteBasedTestTrait;

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
     * @return array<int, array<string, mixed>> All rows of the table, keyed and ordered by uid.
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
     * DEFECT PINNED DOWN, NOT ENDORSED (ACE-484). Synchronising the profile translates
     * the profile row and stops there: no contract translation, no contact translation.
     * The inline recursion of `RecordSynchronizer::synchronizeRecord()` tests
     * `$columnDefinition['type']`, a key TCA does not have - the type sits in
     * `config['type']` - so every column falls into the skip branch. Fixing that lookup
     * makes the recursion reach contract and contact and this test fail; whoever gets
     * there inherits the `page` copy defect the sibling tests document.
     */
    #[Test]
    public function profileSynchronizationTranslatesTheProfileButNoInlineChild(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageNotTranslated.csv');

        $this->synchronizeIntoGerman('tx_academicpersons_domain_model_profile', 1);

        $translatedProfile = $this->fetchTranslatedRow('tx_academicpersons_domain_model_profile');
        $this->assertSame(1, (int)$translatedProfile['l10n_parent']);
        $this->assertCount(1, $this->fetchAllRows('tx_academicpersons_domain_model_contract'), 'Expected no contract translation - the inline recursion is dead code.');
        $this->assertCount(1, $this->fetchAllRows('tx_academiccontacts4pages_domain_model_contact'), 'Expected no contact translation - the inline recursion is dead code.');
    }

    /**
     * DEFECT PINNED DOWN, NOT ENDORSED (ACE-484). Page 2 has no German translation, yet
     * synchronising the contact creates a German contact whose `page` column holds the
     * default-language page uid 2, copied verbatim because nothing in
     * `createTranslation()` looks at what a `group` column points at. The `contract`
     * pointer is copied verbatim too, so the German contact hangs below the
     * default-language contract. The intended policy (ACE-484) is that an untranslated
     * page yields no translated contact at all; the day that lands, this test is the one
     * that has to change.
     */
    #[Test]
    public function contactSynchronizationCreatesAGermanContactForAPageThatHasNoGermanTranslation(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageNotTranslated.csv');
        $this->assertPageTwoHasNoTranslation();

        $this->synchronizeIntoGerman('tx_academiccontacts4pages_domain_model_contact', 1);

        $this->assertCount(2, $this->fetchAllRows('tx_academiccontacts4pages_domain_model_contact'), 'Expected the default contact and one created translation.');
        $translatedContact = $this->fetchTranslatedRow('tx_academiccontacts4pages_domain_model_contact');
        $this->assertSame(1, (int)$translatedContact['l10n_parent']);
        $this->assertSame(1, (int)$translatedContact['l10n_source']);
        $this->assertSame(100, (int)$translatedContact['pid']);
        $this->assertSame(1, (int)$translatedContact['contract']);
        // The defect: the German contact points at the untranslated default-language page.
        $this->assertSame(2, (int)$translatedContact['page']);
    }

    /**
     * DEFECT PINNED DOWN, NOT ENDORSED (ACE-484). The read side of the same defect, and
     * the duplication users report: `findByPid()` lifts `respectSysLanguage`, so under a
     * German language aspect the default contact and the German row the synchronizer
     * created both match `page = 2` - one contact, rendered twice. Both result objects
     * map to contact 1 with the German row as their localized uid, on v13 and v14 alike.
     */
    #[Test]
    public function findByPidUnderTheTranslatedLanguageReturnsTheSynchronizedContactTwice(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageNotTranslated.csv');
        $this->synchronizeIntoGerman('tx_academiccontacts4pages_domain_model_contact', 1);
        $translatedContactUid = (int)$this->fetchTranslatedRow('tx_academiccontacts4pages_domain_model_contact')['uid'];
        $this->setUpLanguageAspect(1);

        $contacts = iterator_to_array($this->get(ContactRepository::class)->findByPid(2));

        $this->assertCount(2, $contacts, 'Expected the one contact of page 2 to arrive twice.');
        $this->assertSame(
            [1, 1],
            array_map(static fn(Contact $contact): int => (int)$contact->getUid(), $contacts),
        );
        $this->assertSame(
            [$translatedContactUid, $translatedContactUid],
            array_map(static fn(Contact $contact): int => (int)$contact->_getProperty('_localizedUid'), $contacts),
        );
    }

    /**
     * CURRENT BEHAVIOUR PINNED DOWN (ACE-484 contrast case). When page 2 IS translated
     * into German, the synchronizer behaves exactly as in the untranslated case: the
     * German contact's `page` column holds the default-language uid 2, not the uid 3 of
     * the German page row. Pointing a relation at the default-language uid is how TYPO3
     * models page references, so ACE-484 intends to keep this translation - the value
     * pinned here is what "correct semantics" has to preserve, while the sibling test
     * above documents the case that must stop producing a translation.
     */
    #[Test]
    public function synchronizedContactOfATranslatedPagePointsAtTheDefaultLanguagePageUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactTranslationSynchronization/pageTranslated.csv');

        $this->synchronizeIntoGerman('tx_academiccontacts4pages_domain_model_contact', 1);

        $translatedContact = $this->fetchTranslatedRow('tx_academiccontacts4pages_domain_model_contact');
        $this->assertSame(1, (int)$translatedContact['l10n_parent']);
        $this->assertSame(2, (int)$translatedContact['page']);
        $this->assertNotSame(3, (int)$translatedContact['page']);
    }
}
