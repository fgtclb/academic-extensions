<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Validator;

use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ContractFormData;
use FGTCLB\AcademicPersonsEdit\Exception\UnsuitableValidatorException;

final class ContractFormDataValidator extends AbstractFormDataValidator
{
    /**
     * @param object $contractFormData
     * @throws UnsuitableValidatorException
     */
    protected function isValid($contractFormData): void
    {
        if (!$contractFormData instanceof ContractFormData) {
            throw new UnsuitableValidatorException(
                'Not a valid contract object.',
                1297418975
            );
        }

        $this->processValidations($contractFormData, 'contract');
    }
}
