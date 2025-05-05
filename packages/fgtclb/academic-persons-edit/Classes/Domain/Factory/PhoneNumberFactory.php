<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\PhoneNumber as PhoneNumberModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\PhoneNumberFormData;

class PhoneNumberFactory
{
    public function get(PhoneNumberFormData $phoneNumberFormData): PhoneNumberModel
    {
        $phoneNumber = new PhoneNumberModel;

        $phoneNumber->setPhoneNumber($phoneNumberFormData->getPhoneNumber());
        $phoneNumber->setType($phoneNumberFormData->getType());

        return $phoneNumber;
    }
}
