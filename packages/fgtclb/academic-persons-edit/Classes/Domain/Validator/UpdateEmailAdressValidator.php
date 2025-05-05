<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Validator;

use FGTCLB\AcademicPersons\Domain\Model\Email;
use FGTCLB\AcademicPersonsEdit\Exception\UnknownValidatorException;
use FGTCLB\AcademicPersons\Registry\AcademicPersonsSettingsRegistry as SettingsRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

final class UpdateEmailAdressValidator extends AbstractValidator
{
    public function __construct(
        private readonly SettingsRegistry $settingsRegistry,
    ) {
    }

    protected function isValid(mixed $email): void
    {
        if (!$email instanceof Email) {
            $errorString = 'Not a valid email object.';
            $this->addError($errorString, 1297418975);
        }

        $validations = $this->settingsRegistry->getValidationsForValidator('emailAddress');

        foreach ($validations as $property => $validators) {
            foreach ($validators as $validatorClass) {
                $validator = GeneralUtility::makeInstance($validatorClass);
                if (method_exists($validator, 'validate')) {
                    $propertyValue = $email->_getProperty($property);
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