<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Validator;

use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;
use FGTCLB\AcademicPersonsEdit\Exception\UnsuitableValidatorException;

final class ProfileInformationFormDataValidator extends AbstractFormDataValidator
{
    protected function isValid(mixed $profileInformationFormData): void
    {
        if (!$profileInformationFormData instanceof ProfileInformationFormData) {
            throw new UnsuitableValidatorException(
                'Not a valid profile information object.',
                1297418975
            );
        }

        $this->processValidations($profileInformationFormData, 'profileInformation');
    }
}
