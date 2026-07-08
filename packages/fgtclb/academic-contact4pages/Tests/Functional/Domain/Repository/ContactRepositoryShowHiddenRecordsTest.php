<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Domain\Repository;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;
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
}
