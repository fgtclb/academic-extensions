<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber as PhoneNumberModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\PhoneNumberFormData;

class PhoneNumberFactory
{
    public function createFromFormData(Contract $contract, PhoneNumberFormData $phoneNumberFormData): PhoneNumberModel
    {
        return (new PhoneNumberModel())
            ->setContract($contract)
            ->setPhoneNumber($phoneNumberFormData->getPhoneNumber())
            ->setType($phoneNumberFormData->getType());
    }

    public function updateFromFormData(PhoneNumberModel $phoneNumber, PhoneNumberFormData $phoneNumberFormData): PhoneNumberModel
    {
        return $phoneNumber
            ->setPhoneNumber($phoneNumberFormData->getPhoneNumber())
            ->setType($phoneNumberFormData->getType());
    }
}
