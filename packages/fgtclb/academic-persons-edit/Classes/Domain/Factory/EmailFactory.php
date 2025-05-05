<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Contract;
use FGTCLB\AcademicPersons\Domain\Model\Email as EmailModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\EmailFormData;

class EmailFactory
{
    public function createFromFormData(Contract $contract, EmailFormData $emailFormData): EmailModel
    {
        return (new EmailModel())
            ->setContract($contract)
            ->setEmail($emailFormData->getEmail())
            ->setType($emailFormData->getType());
    }

    public function updateFromFormData(EmailModel $email, EmailFormData $emailFormData): EmailModel
    {
        return $email
            ->setEmail($emailFormData->getEmail())
            ->setType($emailFormData->getType());

    }
}
