<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

final class ContactRepositoryShowHiddenRecordsTest extends AbstractAcademicContacts4PagesTestCase
{
    private function getContactRepository(): ContactRepository
    {
        return $this->get(ContactRepository::class);
    }

    /**
     * @param QueryResultInterface<int, Contact> $result
     * @return int[]
     */
    private function resultUids(QueryResultInterface $result): array
    {
        $uids = [];
        foreach ($result as $contact) {
            $uids[] = (int)$contact->getUid();
        }
        sort($uids);
        return $uids;
    }

    #[Test]
    public function findByPidExcludesHiddenRecordsByDefault(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryShowHidden/contacts.csv');
        $result = $this->getContactRepository()->findByPid(100);
        $this->assertSame([1, 3], $this->resultUids($result));
    }

    #[Test]
    public function findByPidIncludesHiddenRecordsWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryShowHidden/contacts.csv');
        $result = $this->getContactRepository()->findByPid(100, true);
        $this->assertSame([1, 2, 3, 4], $this->resultUids($result));
    }

    /**
     * Contact 2 and its German translation 3 are hidden. In German the contact is
     * represented by its translation, as a visible one is (ACE-857). The language overlay
     * follows the visibility of the context, not the query settings, so on TYPO3 v13 the
     * hidden translation used to be missed and the default record came back instead.
     */
    #[Test]
    public function findByPidReturnsTheHiddenTranslationOfAHiddenContactWhenRequested(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ContactRepositoryShowHidden/contactsWithHiddenTranslation.csv');
        $this->get(Context::class)->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON));

        $localizedUids = [];
        foreach ($this->getContactRepository()->findByPid(2, true) as $contact) {
            $localizedUids[(int)$contact->getUid()] = (int)$contact->_getProperty('_localizedUid');
        }

        $this->assertSame([1 => 1, 2 => 3, 4 => 4, 5 => 5], $localizedUids);
    }
}
