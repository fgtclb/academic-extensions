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
     * DEFECT PINNED DOWN, NOT ENDORSED. `findByPid()` lifts `respectSysLanguage`, so the
     * query carries no language constraint at all and every translation is selected next to
     * its default language record. The German translation 3 is therefore mapped a second
     * time onto contact 2 - the same contact rendered twice on a page that is not even
     * translated, once in German - and the German-only contact 4 shows up in the default
     * language as well.
     *
     * Lifting `respectSysLanguage` is what a manual uid selection needs (see ACE-341); this
     * query selects by page and has no such reason. Kept as an assertion so the day the
     * language handling is corrected the change is visible here rather than in a report.
     */
    #[Test]
    public function defaultLanguageAlsoReturnsTranslationsAndSoDuplicatesTheirDefaultRecord(): void
    {
        $this->assertLanguageResult(0, [1, 2, 2, 4, 5], [1, 2, 3, 4, 5]);
    }

    /**
     * DEFECT PINNED DOWN, NOT ENDORSED. The overlay removes nothing on either core version
     * this branch supports: the translated language returns all five rows, contact 2 among
     * them twice - and both copies carry the localized uid 3, so the German record is
     * mapped twice while the untranslated contact 1 survives.
     */
    #[Test]
    public function translatedLanguageReturnsEveryRowIncludingTheUntranslatedOne(): void
    {
        $this->assertLanguageResult(1, [1, 2, 2, 4, 5], [1, 3, 3, 4, 5]);
    }

    /**
     * DEFECT PINNED DOWN, NOT ENDORSED. A language with no translations at all gets the raw
     * five rows, exactly as the default language does - the language of the request makes no
     * difference whatsoever to what this method returns.
     */
    #[Test]
    public function languageWithoutTranslationsReturnsTheSameRowsAsEveryOtherLanguage(): void
    {
        $this->assertLanguageResult(3, [1, 2, 2, 4, 5], [1, 2, 3, 4, 5]);
    }

    /**
     * Counting and iterating agree - both report the five raw rows, because nothing is
     * overlaid away. That is not the method being correct here; it is the same missing
     * language constraint, showing from the other side.
     */
    #[Test]
    public function countAndIterationAgreeBecauseNothingIsOverlaidAway(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryFindByPid/contactsWithTranslations.csv');
        $this->setUpLanguageAspect(3);

        $result = $this->subject()->findByPid(2);

        $this->assertSame(5, $result->count());
        $this->assertCount(5, iterator_to_array($result));
    }
}
