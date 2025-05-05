<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Contract as ContractModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;

class ContractFactory
{
    public function createFromFormData(ContractFormData $contractFormData): ContractModel
    {
        return new ContractModel(
            $contractFormData->getOrganisationalUnit(),
            $contractFormData->getFunctionType(),
            $contractFormData->getValidFrom(),
            $contractFormData->getValidTo(),
            $contractFormData->getPosition(),
            $contractFormData->getLocation(),
            $contractFormData->getRoom(),
            $contractFormData->getOfficeHours(),
            $contractFormData->isPublish()
        );
    }

    public function updateFromFormData(
        ContractModel $contract,
        ContractFormData $contractFormData
    ): ContractModel {
        $contract->setOrganisationalUnit($contractFormData->getOrganisationalUnit());
        $contract->setFunctionType($contractFormData->getFunctionType());
        $contract->setValidFrom($contractFormData->getValidFrom());
        $contract->setValidTo($contractFormData->getValidTo());
        $contract->setPosition($contractFormData->getPosition());
        $contract->setLocation($contractFormData->getLocation());
        $contract->setRoom($contractFormData->getRoom());
        $contract->setOfficeHours($contractFormData->getOfficeHours());
        $contract->setPublish($contractFormData->isPublish());

        return $contract;
    }
}
