<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Service;

use FGTCLB\AcademicContacts4pages\Service\AddressRecordProvider;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * The provider is what lets the contacts list plugin render a hidden address record: the
 * contract relation of a contact never carries one, because Extbase reconstitutes a
 * relation with fresh query settings that know nothing about the "ignore disabled" of the
 * contact query.
 *
 * The fixture is built so that every rule the provider inherits from
 * `…AcademicPersons\Domain\Repository\*::findByContractIncludingHidden()` is observable at
 * once: a hidden record that must be returned, a deleted one that must not, a record of a
 * second contract, a translation, a record stored in a second folder, and - for the guard
 * on a non-positive contract uid - records that carry no contract at all.
 *
 * `GeneralUtility::makeInstance()` rather than `$this->get()` on purpose: that is how
 * `ContactsController` obtains the provider.
 */
final class AddressRecordProviderTest extends AbstractAcademicContacts4PagesTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AddressRecordProvider/addressRecords.csv');
    }

    private function subject(): AddressRecordProvider
    {
        return GeneralUtility::makeInstance(AddressRecordProvider::class);
    }

    /**
     * @param ObjectStorage<covariant DomainObjectInterface> $addressRecords
     * @return int[]
     */
    private function uidsOf(ObjectStorage $addressRecords): array
    {
        $uids = [];
        foreach ($addressRecords as $addressRecord) {
            $uids[] = (int)$addressRecord->getUid();
        }

        return $uids;
    }

    /**
     * The hidden record 3 sits between the two visible ones, so it proves at once that
     * hidden records are returned and that they take their place by `sorting` rather than
     * being appended - a contract's address records are ordered by the editor.
     */
    #[Test]
    public function emailAddressesOfTheContractAreReturnedInSortingOrderIncludingHiddenOnes(): void
    {
        $emailAddresses = $this->subject()->findEmailAddresses(1);

        $this->assertSame([2, 3, 1], $this->uidsOf($emailAddresses));
        $this->assertSame(
            ['first@example.org', 'hidden@example.org', 'third@example.org'],
            array_map(static fn(Email $email): string => $email->getEmail(), $emailAddresses->toArray())
        );
        $this->assertSame(
            [false, true, false],
            array_map(static fn(Email $email): bool => $email->getHidden(), $emailAddresses->toArray())
        );
    }

    #[Test]
    public function phoneNumbersOfTheContractAreReturnedInSortingOrderIncludingHiddenOnes(): void
    {
        $phoneNumbers = $this->subject()->findPhoneNumbers(1);

        $this->assertSame([2, 1], $this->uidsOf($phoneNumbers));
        $this->assertSame(
            ['+49 30 222', '+49 30 111'],
            array_map(
                static fn(PhoneNumber $phoneNumber): string => $phoneNumber->getPhoneNumber(),
                $phoneNumbers->toArray()
            )
        );
        $this->assertSame(
            [true, false],
            array_map(
                static fn(PhoneNumber $phoneNumber): bool => $phoneNumber->getHidden(),
                $phoneNumbers->toArray()
            )
        );
    }

    #[Test]
    public function physicalAddressesOfTheContractAreReturnedInSortingOrderIncludingHiddenOnes(): void
    {
        $physicalAddresses = $this->subject()->findPhysicalAddresses(1);

        $this->assertSame([2, 1], $this->uidsOf($physicalAddresses));
        $this->assertSame(
            ['Hamburg', 'Berlin'],
            array_map(static fn(Address $address): string => $address->getCity(), $physicalAddresses->toArray())
        );
        $this->assertSame(
            [true, false],
            array_map(static fn(Address $address): bool => $address->getHidden(), $physicalAddresses->toArray())
        );
    }

    /**
     * "Including hidden" lifts the `disabled` enable field only. A deleted record is gone
     * for good - it would otherwise reappear in the frontend the moment an editor switches
     * the plugin option on.
     */
    #[Test]
    public function deletedAddressRecordsAreNeverReturned(): void
    {
        $this->assertNotContains(4, $this->uidsOf($this->subject()->findEmailAddresses(1)));
        $this->assertNotContains(3, $this->uidsOf($this->subject()->findPhoneNumbers(1)));
        $this->assertNotContains(3, $this->uidsOf($this->subject()->findPhysicalAddresses(1)));
    }

    /**
     * The lookup goes through the contract on purpose, never by uid alone: a selection made
     * on a contact can outlive the contract it was made for, and the record it names must
     * not surface below a contract it does not belong to.
     */
    #[Test]
    public function addressRecordsOfAnotherContractAreNotReturned(): void
    {
        $this->assertSame([6], $this->uidsOf($this->subject()->findEmailAddresses(2)));
        $this->assertSame([4], $this->uidsOf($this->subject()->findPhoneNumbers(2)));
        $this->assertSame([4], $this->uidsOf($this->subject()->findPhysicalAddresses(2)));
    }

    /**
     * Email 1 is stored in a second folder while the rest of contract 1 lives in the first
     * one. The repository lifts `respectStoragePage`, so the folder an editor picked has no
     * say in what the frontend renders.
     */
    #[Test]
    public function addressRecordsAreFoundRegardlessOfTheFolderTheyAreStoredIn(): void
    {
        $emailAddresses = $this->subject()->findEmailAddresses(1)->toArray();

        $this->assertSame(
            [100, 101],
            [(int)$emailAddresses[0]->getPid(), (int)$emailAddresses[2]->getPid()]
        );
    }

    /**
     * Email 5 is the German translation of email 2. The default language must see the
     * default language record only - the plugin resolves the overlay through the contract
     * relation, and a translation returned next to its original would be rendered twice.
     */
    #[Test]
    public function translationOfAnAddressRecordIsNotReturnedNextToItsDefaultLanguageRecord(): void
    {
        $emailAddresses = $this->subject()->findEmailAddresses(1);

        $this->assertNotContains(5, $this->uidsOf($emailAddresses));
        $this->assertNotContains(
            'translated@example.org',
            array_map(static fn(Email $email): string => $email->getEmail(), $emailAddresses->toArray())
        );
    }

    /**
     * Contract 3 exists but has no address record of any kind. A contact pointing at it
     * renders no address block rather than raising anything.
     */
    #[Test]
    public function contractWithoutAddressRecordsYieldsEmptyStorages(): void
    {
        $this->assertSame([], $this->uidsOf($this->subject()->findEmailAddresses(3)));
        $this->assertSame([], $this->uidsOf($this->subject()->findPhoneNumbers(3)));
        $this->assertSame([], $this->uidsOf($this->subject()->findPhysicalAddresses(3)));
    }

    /**
     * A contact whose contract was deleted keeps the uid in its column, so the provider is
     * asked for a contract that is no longer resolvable.
     */
    #[Test]
    public function unknownContractUidYieldsEmptyStorages(): void
    {
        $this->assertSame([], $this->uidsOf($this->subject()->findEmailAddresses(999)));
        $this->assertSame([], $this->uidsOf($this->subject()->findPhoneNumbers(999)));
        $this->assertSame([], $this->uidsOf($this->subject()->findPhysicalAddresses(999)));
    }

    /**
     * @return \Generator<string, array{0: int}>
     */
    public static function nonPositiveContractUidDataProvider(): \Generator
    {
        yield 'contract not selected' => [0];
        yield 'negative contract uid' => [-1];
    }

    /**
     * `0` is the default of `tx_academiccontacts4pages_domain_model_contact.contract`, and
     * `Contact::createDisplayContract()` passes `$contract->getUid() ?? 0` - so `0` reaches
     * the provider for every contact that was never given a contract. The fixture holds one
     * address record of each kind with `contract = 0`; without the guard in the provider
     * the query would match exactly those and hand a stranger's address to the contact.
     */
    #[DataProvider('nonPositiveContractUidDataProvider')]
    #[Test]
    public function nonPositiveContractUidYieldsEmptyStorages(int $contractUid): void
    {
        $this->assertSame([], $this->uidsOf($this->subject()->findEmailAddresses($contractUid)));
        $this->assertSame([], $this->uidsOf($this->subject()->findPhoneNumbers($contractUid)));
        $this->assertSame([], $this->uidsOf($this->subject()->findPhysicalAddresses($contractUid)));
    }

    /**
     * `Contract::setEmailAddresses()` and its two siblings take an `ObjectStorage`, so the
     * query result has to be converted rather than passed on. The models have to be the
     * ones of `EXT:academic_persons`, which is what the templates render.
     */
    #[Test]
    public function storagesHoldTheDomainModelsOfTheAddressRecords(): void
    {
        $this->assertContainsOnlyInstancesOf(Email::class, $this->subject()->findEmailAddresses(1));
        $this->assertContainsOnlyInstancesOf(PhoneNumber::class, $this->subject()->findPhoneNumbers(1));
        $this->assertContainsOnlyInstancesOf(Address::class, $this->subject()->findPhysicalAddresses(1));
    }

    /**
     * The provider holds no state, so the same question asked twice gives the same answer -
     * `ContactsController` hands one instance to every contact of a page.
     */
    #[Test]
    public function repeatedLookupsOfTheSameContractReturnEqualStorages(): void
    {
        $provider = $this->subject();

        $this->assertSame(
            $this->uidsOf($provider->findEmailAddresses(1)),
            $this->uidsOf($provider->findEmailAddresses(1))
        );
        $this->assertSame([6], $this->uidsOf($provider->findEmailAddresses(2)));
        $this->assertSame([2, 3, 1], $this->uidsOf($provider->findEmailAddresses(1)));
    }
}
