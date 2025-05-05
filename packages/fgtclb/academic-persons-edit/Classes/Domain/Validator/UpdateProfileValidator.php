<?php

declare(strict_types=1);

namespace Fgtclb\AcademicPersonsEdit\Domain\Validator;

use Fgtclb\AcademicPersons\Domain\Model\Profile;
use Fgtclb\AcademicPersonsEdit\Exception\UnknownValidatorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\NotEmptyValidator;

final class UpdateProfileValidator extends AbstractValidator
{
    protected function isValid(mixed $profile): void
    {
        if (!$profile instanceof Profile) {
            $errorString = 'Not a valid profile object.';
            $this->addError($errorString, 1297418975);
        }

        $validations = [
            'firstName' => [
                NotEmptyValidator::class,
            ],
            'lastName' => [
                NotEmptyValidator::class,
            ],
        ];

        foreach ($validations as $property => $validators) {
            foreach ($validators as $validatorClass) {
                $validator = GeneralUtility::makeInstance($validatorClass);
                $propertyValue = $profile->_getProperty($property);
                if (method_exists($validator, 'validate')) {
                    $validationResult = $validator->validate($propertyValue);
                    if ($validationResult->hasErrors()) {
                        foreach ($validationResult->getErrors() as $error) {
                            $this->addErrorForProperty(
                                $property,
                                $error->getMessage(),
                                $error->getCode()
                            );
                        }
                    }
                } else {
                    throw new UnknownValidatorException(
                        'Unknown validator',
                        1702379249
                    );
                }

            }
        }
    }
}