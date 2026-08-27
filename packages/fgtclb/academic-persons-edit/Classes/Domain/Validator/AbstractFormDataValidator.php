<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Validator;

use FGTCLB\AcademicPersons\Settings\AcademicPersonsSettings;
use FGTCLB\AcademicPersons\Settings\ValidationSet;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\AbstractFormData;
use FGTCLB\AcademicPersonsEdit\Settings\AcademicPersonsEditSettingsFactory;
use FGTCLB\AcademicPersonsEdit\Exception\UnknownValidatorException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Reflection\ObjectAccess;
use TYPO3\CMS\Extbase\Validation\Validator\AbstractValidator;
use TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface;

/**
 * @internal to be used only in `EXT:academic_person_edit` and not part of public API. May change at any time.
 */
abstract class AbstractFormDataValidator extends AbstractValidator
{
    private ?AcademicPersonsSettings $academicPersonsSettings = null;

    public function injectAcademicPersonsSettings(AcademicPersonsSettings $academicPersonsSettings): void
    {
        $this->academicPersonsSettings = $academicPersonsSettings;
    }

    /**
     * @throws UnknownValidatorException
     */
    protected function processValidationSet(object $subject, ValidationSet $validationSet): void
    {
        foreach ($validationSet->validations as $property => $validation) {
            $value = $subject instanceof AbstractFormData && $subject->hasPropertyOverride($property)
                ? $subject->getPropertyOverride($property)
                : ObjectAccess::getPropertyPath($subject, $property);
            foreach ($validation->validatorClassNames as $validatorClassName) {
                $validator = GeneralUtility::makeInstance($validatorClassName);
                if ($validator instanceof ValidatorInterface) {
                    $validationResult = $validator->validate($value);
                    if ($validationResult->hasErrors()) {
                        foreach ($validationResult->getErrors() as $error) {
                            $this->result->forProperty($property)->addError($error);
                        }
                    }
                    continue;
                }
                throw new UnknownValidatorException(
                    'Unknown validator',
                    1702379249
                );
            }
        }
    }

    /**
     * Keep manually instantiated validators usable while normal runtime instances
     * receive the settings through method injection.
     */
    protected function getAcademicPersonsSettings(): AcademicPersonsSettings
    {
        return $this->academicPersonsSettings ??= GeneralUtility::makeInstance(
            AcademicPersonsEditSettingsFactory::class,
        )->get();
    }
}
