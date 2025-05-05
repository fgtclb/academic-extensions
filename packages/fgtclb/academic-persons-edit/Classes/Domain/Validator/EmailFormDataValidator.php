<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Validator;

use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\EmailFormData;
use FGTCLB\AcademicPersonsEdit\Exception\UnsuitableValidatorException;

final class EmailFormDataValidator extends AbstractFormDataValidator
{
    /**
     * @param object $emailFormData
     * @throws UnsuitableValidatorException
     */
    protected function isValid($emailFormData): void
    {
        if (!$emailFormData instanceof EmailFormData) {
            throw new UnsuitableValidatorException(
                'Not a valid email object.',
                1297418975
            );
        }

        $this->processValidations($emailFormData, 'emailAddress');
    }
}
