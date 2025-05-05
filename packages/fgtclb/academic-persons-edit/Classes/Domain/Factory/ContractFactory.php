<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Contract as ContractModel;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;

class ContractFactory
{
    public function createFromFormData(Profile $profile, ContractFormData $contractFormData): ContractModel
    {
        return (new ContractModel())
            ->setProfile($profile)
            ->setOrganisationalUnit($contractFormData->getOrganisationalUnit())
            ->setFunctionType($contractFormData->getFunctionType())
            ->setValidFrom($contractFormData->getValidFrom())
            ->setValidTo($contractFormData->getValidTo())
            ->setPosition($contractFormData->getPosition())
            ->setLocation($contractFormData->getLocation())
            ->setRoom($contractFormData->getRoom())
            ->setOfficeHours($contractFormData->getOfficeHours())
            ->setPublish($contractFormData->isPublish());
    }

    public function updateFromFormData(ContractModel $contract, ContractFormData $contractFormData): ContractModel
    {
        return $contract
            ->setOrganisationalUnit($contractFormData->getOrganisationalUnit())
            ->setFunctionType($contractFormData->getFunctionType())
            ->setValidFrom($contractFormData->getValidFrom())
            ->setValidTo($contractFormData->getValidTo())
            ->setPosition($contractFormData->getPosition())
            ->setLocation($contractFormData->getLocation())
            ->setRoom($contractFormData->getRoom())
            ->setOfficeHours($contractFormData->getOfficeHours())
            ->setPublish($contractFormData->isPublish());
    }
}
