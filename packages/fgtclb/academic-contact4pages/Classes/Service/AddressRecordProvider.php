<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Service;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Repository\AddressRepository;
use FGTCLB\AcademicPersons\Domain\Repository\EmailRepository;
use FGTCLB\AcademicPersons\Domain\Repository\PhoneNumberRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Loads the address records of a contract, hidden ones included.
 *
 * The relations of a contract never carry hidden records: Extbase builds the query for a
 * relation from fresh query settings, so the "ignore disabled" of the contact query does
 * not reach them. The contacts list plugin hands this provider to its contacts when its
 * "Show hidden records" option is set, and only then a hidden address record is rendered
 * at all - both as part of the complete list and as a dedicated selection.
 *
 * Records are always looked up through the contract, never by uid alone: a selection can
 * outlive the contract it was made for, and such a record must not surface below a
 * contract it does not belong to.
 */
final class AddressRecordProvider
{
    /**
     * @return ObjectStorage<Email>
     */
    public function findEmailAddresses(int $contractUid): ObjectStorage
    {
        if ($contractUid <= 0) {
            /** @var ObjectStorage<Email> $emailAddresses */
            $emailAddresses = new ObjectStorage();
            return $emailAddresses;
        }

        return $this->toObjectStorage(
            GeneralUtility::makeInstance(EmailRepository::class)->findByContractIncludingHidden($contractUid)
        );
    }

    /**
     * @return ObjectStorage<PhoneNumber>
     */
    public function findPhoneNumbers(int $contractUid): ObjectStorage
    {
        if ($contractUid <= 0) {
            /** @var ObjectStorage<PhoneNumber> $phoneNumbers */
            $phoneNumbers = new ObjectStorage();
            return $phoneNumbers;
        }

        return $this->toObjectStorage(
            GeneralUtility::makeInstance(PhoneNumberRepository::class)->findByContractIncludingHidden($contractUid)
        );
    }

    /**
     * @return ObjectStorage<Address>
     */
    public function findPhysicalAddresses(int $contractUid): ObjectStorage
    {
        if ($contractUid <= 0) {
            /** @var ObjectStorage<Address> $physicalAddresses */
            $physicalAddresses = new ObjectStorage();
            return $physicalAddresses;
        }

        return $this->toObjectStorage(
            GeneralUtility::makeInstance(AddressRepository::class)->findByContractIncludingHidden($contractUid)
        );
    }

    /**
     * @template T of DomainObjectInterface
     * @param QueryResultInterface<int, T> $addressRecords
     * @return ObjectStorage<T>
     */
    private function toObjectStorage(QueryResultInterface $addressRecords): ObjectStorage
    {
        /** @var ObjectStorage<T> $addressRecordStorage */
        $addressRecordStorage = new ObjectStorage();
        foreach ($addressRecords as $addressRecord) {
            $addressRecordStorage->attach($addressRecord);
        }

        return $addressRecordStorage;
    }
}
