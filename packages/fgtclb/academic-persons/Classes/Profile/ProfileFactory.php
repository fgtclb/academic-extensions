<?php

declare(strict_types=1);

/*
 * This file is part of the "academic_persons_edit" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace FGTCLB\AcademicPersons\Profile;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

#[Autoconfigure(public: true, shared: true)]
final class ProfileFactory extends AbstractProfileFactory
{
    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    protected function createProfileFromFrontendUser(array $frontendUserData): Profile
    {
        $pid = (int)$frontendUserData['pid'];

        if ($pid < 0) {
            throw new \InvalidArgumentException(
                'The PID must be a positive integer.',
                1627471234
            );
        }

        $profile = new Profile();
        $profile->setPid($pid);
        $this->applyProfileData($frontendUserData, $profile);

        $contract = new Contract();
        $contract->setPid($pid);
        $profile->getContracts()->attach($contract);

        $this->applyContractData($frontendUserData, $contract, $pid);

        return $profile;
    }

    /**
     * Updates the profile based on frontend user data.
     *
     * @param array<string, int|string|null> $frontendUserData
     */
    protected function updateProfileFromFrontendUser(array $frontendUserData, Profile $profile): void
    {
        $pid = (int)$frontendUserData['pid'];
        $this->applyProfileData($frontendUserData, $profile);

        // Update the first contract if it exists
        $contracts = $profile->getContracts();
        if ($contracts->count() === 0) {
            return;
        }

        $contracts->rewind();
        $contract = $contracts->current();

        $this->applyContractData($frontendUserData, $contract, $pid);
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function applyProfileData(array $frontendUserData, Profile $profile): void
    {
        $profile->setTitle((string)($frontendUserData['title'] ?? ''));
        $profile->setFirstName((string)($frontendUserData['first_name'] ?? ''));
        $profile->setMiddleName((string)($frontendUserData['middle_name'] ?? ''));
        $profile->setLastName((string)($frontendUserData['last_name'] ?? ''));
        $profile->setWebsite((string)($frontendUserData['www'] ?? ''));
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function applyContractData(array $frontendUserData, Contract $contract, int $pid): void
    {
        $this->applyPhysicalAddress($frontendUserData, $contract, $pid);
        $this->applyEmailAddress($frontendUserData, $contract, $pid);
        $this->applyPhoneNumber($frontendUserData, $contract, $pid, 'phone', 'telephone');
        $this->applyPhoneNumber($frontendUserData, $contract, $pid, 'fax', 'fax');
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function applyPhysicalAddress(array $frontendUserData, Contract $contract, int $pid): void
    {
        $addresses = $contract->getPhysicalAddresses();
        if ($addresses->count() > 0) {
            $addresses->rewind();
            $address = $addresses->current();
        } else {
            $address = new Address();
            $address->setPid($pid);
            $contract->getPhysicalAddresses()->attach($address);
        }

        $address->setStreet((string)($frontendUserData['address'] ?? ''));
        $address->setZip((string)($frontendUserData['zip'] ?? ''));
        $address->setCity((string)($frontendUserData['city'] ?? ''));
        $address->setCountry((string)($frontendUserData['country'] ?? ''));
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function applyEmailAddress(array $frontendUserData, Contract $contract, int $pid): void
    {
        if (empty($frontendUserData['email'])) {
            return;
        }

        $emails = $contract->getEmailAddresses();
        if ($emails->count() > 0) {
            $emails->rewind();
            $email = $emails->current();
        } else {
            $email = new Email();
            $email->setPid($pid);
            $contract->getEmailAddresses()->attach($email);
        }

        $email->setEmail((string)($frontendUserData['email']));
    }

    /**
     * @param array<string, int|string|null> $frontendUserData
     */
    private function applyPhoneNumber(
        array $frontendUserData,
        Contract $contract,
        int $pid,
        string $type,
        string $dataKey
    ): void {
        if (empty($frontendUserData[$dataKey])) {
            return;
        }

        $phoneNumbers = $contract->getPhoneNumbers();
        $existingPhoneNumber = null;

        foreach ($phoneNumbers as $phoneNumber) {
            if ($phoneNumber->getType() === $type) {
                $existingPhoneNumber = $phoneNumber;
                break;
            }
        }

        if ($existingPhoneNumber === null) {
            $existingPhoneNumber = new PhoneNumber();
            $existingPhoneNumber->setPid($pid);
            $existingPhoneNumber->setType($type);
            $contract->getPhoneNumbers()->attach($existingPhoneNumber);
        }

        $existingPhoneNumber->setPhoneNumber((string)($frontendUserData[$dataKey]));
    }
}
