<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Address as AddressModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\AddressFormData;

class AddressFactory
{
    public function createFromFormData(AddressFormData $addressFormData): AddressModel
    {
        return new AddressModel(
            $addressFormData->getStreet(),
            $addressFormData->getStreetNumber(),
            $addressFormData->getAdditional(),
            $addressFormData->getZip(),
            $addressFormData->getCity(),
            $addressFormData->getState(),
            $addressFormData->getCountry(),
            $addressFormData->getType()
        );
    }

    public function updateFromFormData(
        AddressModel $address,
        AddressFormData $addressFormData
    ): AddressModel {
        $address->setStreet($addressFormData->getStreet());
        $address->setStreetNumber($addressFormData->getStreetNumber());
        $address->setAdditional($addressFormData->getAdditional());
        $address->setZip($addressFormData->getZip());
        $address->setCity($addressFormData->getCity());
        $address->setState($addressFormData->getState());
        $address->setCountry($addressFormData->getCountry());
        $address->setType($addressFormData->getType());

        return $address;
    }
}
