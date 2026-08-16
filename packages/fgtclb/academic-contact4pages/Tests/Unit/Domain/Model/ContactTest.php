<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Unit\Domain\Model;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Model\Role;
use FGTCLB\AcademicContacts4pages\Service\AddressRecordProvider;
use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Covers the dedicated address record selection: {@see Contact::getContract()} hands out
 * a contract carrying only the selected email address, phone number and physical address.
 */
final class ContactTest extends UnitTestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        new Contact();
    }

    #[Test]
    public function getContractReturnsNullForNewModel(): void
    {
        $this->assertNull((new Contact())->getContract());
    }

    #[Test]
    public function hasAddressRecordSelectionIsFalseForNewModel(): void
    {
        $this->assertFalse((new Contact())->hasAddressRecordSelection());
    }

    #[Test]
    public function getContractReturnsTheContractItselfWithoutDedicatedSelection(): void
    {
        $contract = $this->createContract();
        $contact = $this->createContact($contract);

        $this->assertSame($contract, $contact->getContract());
    }

    /**
     * @return \Generator<string, array{0: non-empty-string, 1: int, 2: array<int, int>}>
     */
    public static function addressRecordSelectionDataProvider(): \Generator
    {
        yield 'no selection displays all email addresses' => ['emailAddress', Contact::DISPLAY_ALL, [11, 12]];
        yield 'selected email address is displayed alone' => ['emailAddress', 12, [12]];
        yield 'no email address is displayed' => ['emailAddress', Contact::DISPLAY_NONE, []];
        yield 'unresolvable email address displays none' => ['emailAddress', 99, []];
        yield 'no selection displays all phone numbers' => ['phoneNumber', Contact::DISPLAY_ALL, [21, 22]];
        yield 'selected phone number is displayed alone' => ['phoneNumber', 21, [21]];
        yield 'no phone number is displayed' => ['phoneNumber', Contact::DISPLAY_NONE, []];
        yield 'unresolvable phone number displays none' => ['phoneNumber', 99, []];
        yield 'no selection displays all physical addresses' => ['physicalAddress', Contact::DISPLAY_ALL, [31, 32]];
        yield 'selected physical address is displayed alone' => ['physicalAddress', 32, [32]];
        yield 'no physical address is displayed' => ['physicalAddress', Contact::DISPLAY_NONE, []];
        yield 'unresolvable physical address displays none' => ['physicalAddress', 99, []];
    }

    /**
     * @param non-empty-string $propertyName
     * @param array<int, int> $expectedUids
     */
    #[DataProvider('addressRecordSelectionDataProvider')]
    #[Test]
    public function getContractNarrowsTheSelectedAddressRecordKind(
        string $propertyName,
        int $selectedUid,
        array $expectedUids
    ): void {
        $contact = $this->createContact($this->createContract());
        $contact->_setProperty($propertyName, $selectedUid);

        $displayContract = $contact->getContract();
        $this->assertInstanceOf(Contract::class, $displayContract);
        $addressRecords = match ($propertyName) {
            'emailAddress' => $displayContract->getEmailAddresses(),
            'phoneNumber' => $displayContract->getPhoneNumbers(),
            'physicalAddress' => $displayContract->getPhysicalAddresses(),
            default => throw new \LogicException(sprintf('Unknown address record kind "%s".', $propertyName), 1786579201),
        };

        $this->assertSame($expectedUids, $this->getUids($addressRecords));
    }

    #[Test]
    public function getContractKeepsTheOtherAddressRecordKindsUntouched(): void
    {
        $contact = $this->createContact($this->createContract());
        $contact->_setProperty('emailAddress', 11);

        $displayContract = $contact->getContract();
        $this->assertInstanceOf(Contract::class, $displayContract);
        $this->assertSame([11], $this->getUids($displayContract->getEmailAddresses()));
        $this->assertSame([21, 22], $this->getUids($displayContract->getPhoneNumbers()));
        $this->assertSame([31, 32], $this->getUids($displayContract->getPhysicalAddresses()));
    }

    #[Test]
    public function getContractNarrowsAllThreeAddressRecordKindsAtOnce(): void
    {
        $contact = $this->createContact($this->createContract());
        $contact->_setProperty('emailAddress', 12);
        $contact->_setProperty('phoneNumber', Contact::DISPLAY_NONE);
        $contact->_setProperty('physicalAddress', 31);

        $displayContract = $contact->getContract();
        $this->assertInstanceOf(Contract::class, $displayContract);
        $this->assertSame([12], $this->getUids($displayContract->getEmailAddresses()));
        $this->assertSame([], $this->getUids($displayContract->getPhoneNumbers()));
        $this->assertSame([31], $this->getUids($displayContract->getPhysicalAddresses()));
    }

    /**
     * The contract is a persisted entity shared with everything else that resolves it, so
     * the narrowing must not reach it.
     */
    #[Test]
    public function getContractLeavesTheContractRecordItselfUntouched(): void
    {
        $contract = $this->createContract();
        $contact = $this->createContact($contract);
        $contact->_setProperty('emailAddress', 11);
        $contact->_setProperty('phoneNumber', Contact::DISPLAY_NONE);

        $contact->getContract();

        $this->assertSame([11, 12], $this->getUids($contract->getEmailAddresses()));
        $this->assertSame([21, 22], $this->getUids($contract->getPhoneNumbers()));
        $this->assertSame($contract, $contact->getUnfilteredContract());
    }

    /**
     * Two contacts on different pages can point at the same contract, and Extbase hands
     * out one instance for it, so one narrowing must not reach the other contact.
     */
    #[Test]
    public function contactsSharingAContractNarrowItIndependently(): void
    {
        $contract = $this->createContract();
        $firstContact = $this->createContact($contract);
        $firstContact->_setProperty('emailAddress', 11);
        $secondContact = $this->createContact($contract);
        $secondContact->_setProperty('emailAddress', Contact::DISPLAY_NONE);

        $firstDisplayContract = $firstContact->getContract();
        $secondDisplayContract = $secondContact->getContract();
        $this->assertInstanceOf(Contract::class, $firstDisplayContract);
        $this->assertInstanceOf(Contract::class, $secondDisplayContract);
        $this->assertSame([11], $this->getUids($firstDisplayContract->getEmailAddresses()));
        $this->assertSame([], $this->getUids($secondDisplayContract->getEmailAddresses()));
        $this->assertSame([11, 12], $this->getUids($contract->getEmailAddresses()));
    }

    #[Test]
    public function getContractReturnsTheSameNarrowedContractOnEveryCall(): void
    {
        $contact = $this->createContact($this->createContract());
        $contact->_setProperty('emailAddress', 11);

        $this->assertSame($contact->getContract(), $contact->getContract());
    }

    /**
     * The plugin sets the provider after the contact was mapped, so a contract narrowed
     * before that must not be handed out afterwards.
     */
    #[Test]
    public function settingTheAddressRecordProviderDiscardsTheNarrowedContract(): void
    {
        $contact = $this->createContact($this->createContract());
        $contact->_setProperty('emailAddress', 11);
        $narrowedContract = $contact->getContract();

        $contact->setAddressRecordProvider(null);

        $this->assertNotSame($narrowedContract, $contact->getContract());
    }

    /**
     * @return \Generator<string, array{0: non-empty-string, 1: int}>
     */
    public static function addressRecordSelectionFlagDataProvider(): \Generator
    {
        yield 'a selected email address' => ['emailAddress', 12];
        yield 'no email address' => ['emailAddress', Contact::DISPLAY_NONE];
        yield 'a selected phone number' => ['phoneNumber', 21];
        yield 'no phone number' => ['phoneNumber', Contact::DISPLAY_NONE];
        yield 'a selected physical address' => ['physicalAddress', 31];
        yield 'no physical address' => ['physicalAddress', Contact::DISPLAY_NONE];
    }

    /**
     * The flag decides whether the contract is handed out as it is or copied, so each of the
     * three fields has to raise it on its own - including the "display none" end of the range,
     * which is a selection too although nothing is picked.
     *
     * @param non-empty-string $propertyName
     */
    #[DataProvider('addressRecordSelectionFlagDataProvider')]
    #[Test]
    public function eachAddressRecordKindRaisesTheSelectionFlagOnItsOwn(string $propertyName, int $selectedUid): void
    {
        $contact = $this->createContact($this->createContract());
        $contact->_setProperty($propertyName, $selectedUid);

        $this->assertTrue($contact->hasAddressRecordSelection());
    }

    /**
     * The label is what the backend shows for a contact record, and both parts are optional:
     * the role is a free relation and the contract is only set once a person was picked.
     */
    #[Test]
    public function theLabelIsEmptyWithoutARoleAndWithoutAContract(): void
    {
        $this->assertSame('', (new Contact())->getLabel());
    }

    #[Test]
    public function theLabelIsTheRoleNameAloneWithoutAContract(): void
    {
        $contact = new Contact();
        $contact->_setProperty('role', $this->createRole('Head of department'));

        $this->assertSame('Head of department', $contact->getLabel());
    }

    #[Test]
    public function theLabelIsTheContractLabelAloneWithoutARole(): void
    {
        $contract = $this->createContract();
        $contact = $this->createContact($contract);

        $this->assertSame($contract->getLabel(), $contact->getLabel());
    }

    #[Test]
    public function theLabelJoinsTheRoleNameAndTheContractLabel(): void
    {
        $contract = $this->createContract();
        $contact = $this->createContact($contract);
        $contact->_setProperty('role', $this->createRole('Head of department'));

        $this->assertSame('Head of department - ' . $contract->getLabel(), $contact->getLabel());
    }

    /**
     * Once the plugin hands over a provider, the provider is what the records come from - the
     * relation is only the fallback for a contact that has none. A contract that was never
     * persisted therefore ends up empty, because the provider cannot look anything up for it.
     */
    #[Test]
    public function aProviderReplacesTheRelationRecordsEvenWithoutASelection(): void
    {
        $contract = $this->createContract();
        $contract->_setProperty('uid', null);
        $contact = $this->createContact($contract);
        $contact->setAddressRecordProvider(new AddressRecordProvider());

        $displayContract = $contact->getContract();
        $this->assertInstanceOf(Contract::class, $displayContract);
        $this->assertNotSame($contract, $displayContract);
        $this->assertSame([], $this->getUids($displayContract->getEmailAddresses()));
        $this->assertSame([], $this->getUids($displayContract->getPhoneNumbers()));
        $this->assertSame([], $this->getUids($displayContract->getPhysicalAddresses()));
        $this->assertSame([11, 12], $this->getUids($contract->getEmailAddresses()));
    }

    private function createRole(string $name): Role
    {
        $role = new Role();
        $role->_setProperty('uid', 1);
        $role->_setProperty('name', $name);
        return $role;
    }

    private function createContact(Contract $contract): Contact
    {
        $contact = new Contact();
        $contact->_setProperty('uid', 1);
        $contact->_setProperty('contract', $contract);
        return $contact;
    }

    private function createContract(): Contract
    {
        $contract = new Contract();
        $contract->_setProperty('uid', 1);
        foreach ([11, 12] as $uid) {
            $emailAddress = new Email();
            $emailAddress->_setProperty('uid', $uid);
            $emailAddress->setEmail(sprintf('contact-%d@example.org', $uid));
            $contract->addEmailAddress($emailAddress);
        }
        foreach ([21, 22] as $uid) {
            $phoneNumber = new PhoneNumber();
            $phoneNumber->_setProperty('uid', $uid);
            $phoneNumber->setPhoneNumber(sprintf('+49 30 %d', $uid));
            $contract->addPhoneNumber($phoneNumber);
        }
        foreach ([31, 32] as $uid) {
            $physicalAddress = new Address();
            $physicalAddress->_setProperty('uid', $uid);
            $physicalAddress->setCity(sprintf('City %d', $uid));
            $contract->addPhysicalAddress($physicalAddress);
        }
        return $contract;
    }

    /**
     * @param iterable<DomainObjectInterface> $addressRecords
     * @return array<int, int|null>
     */
    private function getUids(iterable $addressRecords): array
    {
        $uids = [];
        foreach ($addressRecords as $addressRecord) {
            $uids[] = $addressRecord->getUid();
        }
        return $uids;
    }
}
