<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersonsEdit\Domain\Factory;

use Fgtclb\AcademicPersons\Domain\Model\Contract as ContractModel;
use Fgtclb\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;

class ContractFactory
{
    public function get(ContractFormData $contractFormData): ContractModel
    {
        $contract = new ContractModel;
        $contract->setPosition($contractFormData->getPosition());
        $contract->setLocation($contractFormData->getLocation());
        $contract->setRoom($contractFormData->getRoom());
        $contract->setOrganisationalUnit($contractFormData->getOrganisationalUnit());
        $contract->setFunctionType($contractFormData->getFunctionType());
        $contract->setOfficeHours($contractFormData->getOfficeHours());
        $contract->setValidFrom($contractFormData->getValidFrom());
        $contract->setValidTo($contractFormData->getValidTo());

        return $contract;
    }
}
