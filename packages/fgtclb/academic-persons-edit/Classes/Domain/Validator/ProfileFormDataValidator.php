<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Validator;

use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileFormData;
use FGTCLB\AcademicPersonsEdit\Exception\UnsuitableValidatorException;

final class ProfileFormDataValidator extends AbstractFormDataValidator
{
    /**
     * @param object $profileFormData
     * @throws UnsuitableValidatorException
     */
    protected function isValid($profileFormData): void
    {
        if (!$profileFormData instanceof ProfileFormData) {
            throw new UnsuitableValidatorException(
                'Not a valid profile object.',
                1297418975
            );
        }

        $this->processValidations($profileFormData, 'profile');
    }
}
