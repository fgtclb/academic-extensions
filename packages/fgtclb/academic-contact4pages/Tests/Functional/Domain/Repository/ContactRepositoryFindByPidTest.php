<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Everything `ContactRepository::findByPid()` decides except the hidden flag, which
 * `ContactRepositoryShowHiddenRecordsTest` covers on its own.
 *
 * The argument is the uid of the page a contact points at through its `page` column, not
 * the page the contact record is stored on: the query lifts `respectStoragePage`, so the
 * storage folder of a contact is irrelevant and the `page` column alone selects. That
 * distinction is the reason the fixture spreads the contacts of one page over two storage
 * folders and gives them a `sorting` that contradicts their uid order.
 *
 * @see \FGTCLB\AcademicContacts4pages\Tests\Functional\Domain\Repository\ContactRepositoryShowHiddenRecordsTest
 */
final class ContactRepositoryFindByPidTest extends AbstractAcademicContacts4PagesTestCase
{
    /**
     * The order is what is asserted here, so the result is taken as it comes - unlike in
     * `ContactRepositoryShowHiddenRecordsTest`, which sorts before comparing.
     *
     * @param QueryResultInterface<int, Contact> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $contact) {
            $uids[] = (int)$contact->getUid();
        }

        return $uids;
    }

    private function subject(): ContactRepository
    {
        return $this->get(ContactRepository::class);
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
     * Contacts 1, 2 and 3 point at page 2, contact 4 points at page 3. The result is
     * ordered by `sorting`, which here is the reverse of the uid order, so an accidental
     * fallback to the primary key would be visible.
     */
    #[Test]
    public function contactsOfTheRequestedPageAreReturnedInSortingOrder(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $this->assertSame([2, 3, 1], $this->resultUids($this->subject()->findByPid(2)));
    }

    /**
     * Contact 3 lives in a second storage folder. It has to come back nonetheless, because
     * the query lifts `respectStoragePage` - the plugin never configures a storage pid and
     * an editor is free to file contact records wherever the page tree suits them.
     */
    #[Test]
    public function contactsAreFoundRegardlessOfTheStorageFolderTheyLiveIn(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $contacts = $this->subject()->findByPid(2);

        $this->assertContains(3, $this->resultUids($contacts));
        $this->assertSame([100, 101, 100], array_map(
            static fn(Contact $contact): int => (int)$contact->getPid(),
            iterator_to_array($contacts)
        ));
    }

    /**
     * Contacts of a different page must not leak into the result - the plugin renders one
     * page's contacts, and every page of an installation stores them in the same folder.
     */
    #[Test]
    public function contactsOfAnotherPageAreNotReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $this->assertSame([4], $this->resultUids($this->subject()->findByPid(3)));
    }

    /**
     * Contact 6 is deleted, and no argument of the method brings it back: `findByPid()`
     * only ever lifts the `disabled` enable field.
     */
    #[Test]
    public function deletedContactsAreNeverReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $this->assertNotContains(6, $this->resultUids($this->subject()->findByPid(2)));
        $this->assertNotContains(6, $this->resultUids($this->subject()->findByPid(2, true)));
    }

    /**
     * Lifting the hidden flag must not reorder the result: the hidden contact 5 carries the
     * lowest `sorting` of the page and therefore appears first, not appended at the end.
     */
    #[Test]
    public function hiddenContactsAreSortedIntoTheResultRatherThanAppended(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $this->assertSame([5, 2, 3, 1], $this->resultUids($this->subject()->findByPid(2, true)));
    }

    #[Test]
    public function pageWithoutContactsYieldsAnEmptyResult(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $result = $this->subject()->findByPid(4);

        $this->assertSame([], $this->resultUids($result));
        $this->assertSame(0, $result->count());
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function noContactPointsAtThePageDataProvider(): \Generator
    {
        yield 'page uid that does not exist' => [999];
        yield 'negative page uid' => [-1];
    }

    /**
     * A page uid nothing points at is not an error: the plugin passes whatever page it is
     * rendered on, and the data processor whatever record it is called for.
     */
    #[DataProvider('noContactPointsAtThePageDataProvider')]
    #[Test]
    public function pageUidNoContactPointsAtYieldsAnEmptyResult(int $pid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $this->assertSame([], $this->resultUids($this->subject()->findByPid($pid)));
    }

    /**
     * `page` is a group field with `minitems` 1, but a record written around FormEngine -
     * an import, or `ContactsController` reading a content element without a pid - keeps
     * the `0` default. Contact 7 is such a record, and it is reachable by `findByPid(0)`
     * rather than being treated as unassigned. Worth pinning down: the controller passes
     * `(int)($contentElementData['pid'] ?? 0)`, so `0` is a value that reaches the method.
     */
    #[Test]
    public function contactWithoutAPageIsReturnedForPageUidZero(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $this->assertSame([7], $this->resultUids($this->subject()->findByPid(0)));
    }

    /**
     * The `page` column is not joined against `pages`, so a contact pointing at a page that
     * was deleted in the meantime is still returned. Contact 8 points at page 500, which
     * the fixture never creates.
     */
    #[Test]
    public function contactPointingAtAMissingPageIsStillReturned(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contacts.csv');

        $this->assertSame([8], $this->resultUids($this->subject()->findByPid(500)));
    }

    #[Test]
    public function resultIsEmptyWithoutAnyContactRecord(): void
    {
        $result = $this->subject()->findByPid(2);

        $this->assertSame([], $this->resultUids($result));
        $this->assertSame(0, $result->count());
    }

    /**
     * The language fixture holds five contacts of page 2: the default language records 1
     * and 2, the German translation 3 of contact 2, the German record 4 that has no
     * default language record at all, and the "all languages" record 5.
     *
     * @param int[] $expectedUids
     * @param int[] $expectedLocalizedUids
     */
    private function assertLanguageResult(int $languageId, array $expectedUids, array $expectedLocalizedUids): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contactsWithTranslations.csv');
        $this->setUpLanguageAspect($languageId);

        $contacts = iterator_to_array($this->subject()->findByPid(2));

        $this->assertSame(
            $expectedUids,
            array_map(static fn(Contact $contact): int => (int)$contact->getUid(), $contacts),
            'Unexpected uids.'
        );
        $this->assertSame(
            $expectedLocalizedUids,
            array_map(static fn(Contact $contact): int => (int)$contact->_getProperty('_localizedUid'), $contacts),
            'Unexpected localized uids.'
        );
    }

    /**
     * ACE-484: in the default language only default-language records (and "all
     * languages" records) are returned. The German translation 3 no longer duplicates
     * its default record 2, and the German-only contact 4 no longer leaks into the
     * default language. Identical on TYPO3 v13 and v14.
     */
    #[Test]
    public function defaultLanguageReturnsOnlyDefaultAndAllLanguageRecords(): void
    {
        $this->assertLanguageResult(0, [1, 2, 5], [1, 2, 5]);
    }

    /**
     * ACE-484: under the translated language each contact arrives exactly once - the
     * untranslated contact 1 as its default record, contact 2 represented by its
     * German translation 3, the German-only contact 4 as itself, and the "all
     * languages" contact 5. The order follows the `sorting` of the rows actually
     * fetched (10, 25, 30, 40). Identical on TYPO3 v13 and v14.
     */
    #[Test]
    public function translatedLanguageReturnsEachContactExactlyOnce(): void
    {
        $this->assertLanguageResult(1, [1, 2, 4, 5], [1, 3, 4, 5]);
    }

    /**
     * ACE-484: a language with no translated contact at all falls back to the
     * default-language records plus the "all languages" record - no foreign-language
     * rows leak in, no untranslated contact is dropped. Identical on TYPO3 v13 and v14.
     */
    #[Test]
    public function languageWithoutTranslationsReturnsTheDefaultLanguageRecords(): void
    {
        $this->assertLanguageResult(3, [1, 2, 5], [1, 2, 5]);
    }

    /**
     * ACE-484: `count()` and iteration agree in every language context, because the
     * query selects exactly one row per contact and the overlay maps rows one to one
     * instead of removing or duplicating any. `{contacts -> f:count()}` and an
     * iterating template can no longer disagree.
     */
    #[Test]
    public function countAgreesWithTheIterationInEveryLanguage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contactsWithTranslations.csv');

        foreach ([0 => 3, 1 => 4, 3 => 3] as $languageId => $expectedCount) {
            $this->setUpLanguageAspect($languageId);
            $result = $this->subject()->findByPid(2);
            $this->assertSame($expectedCount, $result->count(), sprintf('Unexpected count() in language %d.', $languageId));
            $this->assertCount($expectedCount, iterator_to_array($result), sprintf('Unexpected number of objects in language %d.', $languageId));
        }
    }
}
