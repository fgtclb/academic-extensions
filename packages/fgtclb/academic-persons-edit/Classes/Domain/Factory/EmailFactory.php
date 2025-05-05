<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Email as EmailModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\EmailFormData;

class EmailFactory
{
    public function get(EmailFormData $emailFormData): EmailModel
    {
        $email = new EmailModel;

        $email->setEmail($emailFormData->getEmail());
        $email->setType($emailFormData->getType());

        return $email;
    }
}
