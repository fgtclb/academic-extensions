<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Domain\Model;

use FGTCLB\AcademicContacts4pages\Service\AddressRecordProvider;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Contact extends AbstractEntity
{
    public const DISPLAY_ALL = 0;
    public const DISPLAY_NONE = -1;

    protected int $page = 0;
    protected ?Contract $contract = null;
    protected ?Role $role = null;
    protected int $emailAddress = self::DISPLAY_ALL;
    protected int $phoneNumber = self::DISPLAY_ALL;
    protected int $physicalAddress = self::DISPLAY_ALL;

    /**
     * Contract copy carrying the address records to display, built on first access. Not
     * persisted: the Extbase data map is built from the TCA columns of the table, so a
     * property without a column is never mapped.
     */
    private ?Contract $displayContract = null;

    private ?AddressRecordProvider $addressRecordProvider = null;

    public function __construct()
    {
        $this->initializeObject();
    }

    /**
     * @link https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/ExtensionArchitecture/Extbase/Reference/Domain/Model/Index.html#good-use-initializeobject-for-setup
     */
    public function initializeObject(): void {}

    public function getPage(): int
    {
        return $this->page;
    }

    public function getContract(): ?Contract
    {
        if ($this->contract === null) {
            return $this->contract;
        }

        if (!$this->hasAddressRecordSelection() && $this->addressRecordProvider === null) {
            return $this->contract;
        }
        return $this->displayContract ??= $this->createDisplayContract($this->contract);
    }

    public function setAddressRecordProvider(?AddressRecordProvider $addressRecordProvider): void
    {
        $this->addressRecordProvider = $addressRecordProvider;
        // A contract narrowed before this point was narrowed without the provider.
        $this->displayContract = null;
    }

    public function getUnfilteredContract(): ?Contract
    {
        return $this->contract;
    }

    public function getRole(): ?Role
    {
        return $this->role;
    }

    public function getEmailAddress(): int
    {
        return $this->emailAddress;
    }

    public function getPhoneNumber(): int
    {
        return $this->phoneNumber;
    }

    public function getPhysicalAddress(): int
    {
        return $this->physicalAddress;
    }

    public function hasAddressRecordSelection(): bool
    {
        return $this->emailAddress !== self::DISPLAY_ALL
            || $this->phoneNumber !== self::DISPLAY_ALL
            || $this->physicalAddress !== self::DISPLAY_ALL;
    }

    public function getLabel(): string
    {
        $labelParts = [];
        if ($this->role) {
            $labelParts[] = $this->role->getName();
        }
        if ($this->contract) {
            $labelParts[] = $this->contract->getLabel();
        }
        return implode(' - ', $labelParts);
    }

    private function createDisplayContract(Contract $contract): Contract
    {
        $contractUid = $contract->getUid() ?? 0;
        $provider = $this->addressRecordProvider;

        $displayContract = clone $contract;
        $displayContract->setEmailAddresses(
            $this->selectAddressRecords(
                $contract->getEmailAddresses(),
                $this->emailAddress,
                static fn(): ?ObjectStorage => $provider?->findEmailAddresses($contractUid)
            )
        );
        $displayContract->setPhoneNumbers(
            $this->selectAddressRecords(
                $contract->getPhoneNumbers(),
                $this->phoneNumber,
                static fn(): ?ObjectStorage => $provider?->findPhoneNumbers($contractUid)
            )
        );
        $displayContract->setPhysicalAddresses(
            $this->selectAddressRecords(
                $contract->getPhysicalAddresses(),
                $this->physicalAddress,
                static fn(): ?ObjectStorage => $provider?->findPhysicalAddresses($contractUid)
            )
        );
        return $displayContract;
    }

    /**
     * @template T of DomainObjectInterface
     * @param ObjectStorage<T> $addressRecords the records the frontend loaded, hidden ones missing
     * @param \Closure(): ?ObjectStorage<T> $resolveAllAddressRecords
     * @return ObjectStorage<T>
     */
    private function selectAddressRecords(
        ObjectStorage $addressRecords,
        int $selectedUid,
        \Closure $resolveAllAddressRecords
    ): ObjectStorage {
        /** @var ObjectStorage<T> $selectedAddressRecords */
        $selectedAddressRecords = new ObjectStorage();
        if ($selectedUid === self::DISPLAY_NONE) {
            return $selectedAddressRecords;
        }

        $addressRecords = $resolveAllAddressRecords() ?? $addressRecords;
        if ($selectedUid === self::DISPLAY_ALL) {
            return $addressRecords;
        }

        foreach ($addressRecords as $addressRecord) {
            if ($addressRecord->getUid() === $selectedUid) {
                $selectedAddressRecords->attach($addressRecord);
                return $selectedAddressRecords;
            }
        }

        // The selection cannot be resolved: the contract was switched after it was made,
        // the record was deleted, or it is hidden and hidden ones are not asked for.
        // Deliberately no fallback to all records.
        return $selectedAddressRecords;
    }
}
