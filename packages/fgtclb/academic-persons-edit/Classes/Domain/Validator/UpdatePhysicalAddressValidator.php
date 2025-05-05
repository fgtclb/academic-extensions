<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Validator;

use FGTCLB\AcademicPersons\Domain\Model\Address;
use FGTCLB\AcademicPersonsEdit\Exception\UnknownValidatorException;
use FGTCLB\AcademicPersons\Registry\AcademicPersonsSettingsRegistry as SettingsRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;

final class UpdatePhysicalAddressValidator extends AbstractValidator
{
    public function __construct(
        private readonly SettingsRegistry $settingsRegistry,
    ) {
    }

    protected function isValid(mixed $address): void
    {
        if (!$address instanceof Address) {
            $errorString = 'Not a valid email object.';
            $this->addError($errorString, 1297418975);
        }

        $validations = $this->settingsRegistry->getValidationsForValidator('physicalAddress');

        foreach ($validations as $property => $validators) {
            foreach ($validators as $validatorClass) {
                $validator = GeneralUtility::makeInstance($validatorClass);
                if (method_exists($validator, 'validate')) {
                    $propertyValue = $address->_getProperty($property);
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