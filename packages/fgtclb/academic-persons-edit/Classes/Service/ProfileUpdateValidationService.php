<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Service;

use TYPO3\CMS\Extbase\Error\Result;
use UnexpectedValueException;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersonsEdit\{
    Domain\Factory\ProfileFormDataFactoryInterface,
    Domain\Model\Dto\ProfileFormData,
    Domain\Model\Dto\ProfileUpdatePayload,
    Domain\Validator\ProfileFormDataValidator,
    Service\ProfileGenderOptionsService
};

final readonly class ProfileUpdateValidationService
{
    public function __construct(
        private ProfileFormDataFactoryInterface $profileFormDataFactory,
        private ProfileFormDataValidator $profileFormDataValidator,
        private ProfileGenderOptionsService $profileGenderOptionsService,
    ) {
    }

    public function createFormData(
        PluginControllerActionContext $context,
        Profile $profile,
        ProfileUpdatePayload $payload,
    ): ProfileFormData {
        $profileFormData = $this->profileFormDataFactory->createFromProfile(
            $context,
            $profile,
        );

        foreach ($payload->getData() as $propertyName => $value) {
            if (!$profileFormData->_hasProperty($propertyName)) {
                throw new UnexpectedValueException(
                    sprintf('Unknown profile property "%s".', $propertyName)
                );
            }

            if ($propertyName === 'gender') {
                if (
                    !is_string($value)
                    || !$this->profileGenderOptionsService->isAllowed($value)
                ) {
                    throw new UnexpectedValueException(
                        'Invalid gender value.'
                    );
                }
            }

            $profileFormData->setPropertyOverride(
                $propertyName,
                $value,
            );
        }

        return $profileFormData;
    }

    public function validate(ProfileFormData $profileFormData): Result
    {
        return $this->profileFormDataValidator->validate(
            $profileFormData,
        );
    }
}