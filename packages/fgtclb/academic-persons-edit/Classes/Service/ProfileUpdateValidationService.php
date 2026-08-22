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
    private const EDITABLE_PROPERTIES = [
        'gender',
        'title',
        'firstName',
        'middleName',
        'lastName',
        'website',
        'websiteTitle',
        'publicationsLink',
        'publicationsLinkTitle',
        'coreCompetences',
        'teachingArea',
        'supervisedDoctoralThesis',
        'supervisedThesis',
        'miscellaneous',
        'skipSync',
    ];

    public function __construct(
        private ProfileFormDataFactoryInterface $profileFormDataFactory,
        private ProfileFormDataValidator $profileFormDataValidator,
        private ProfileGenderOptionsService $profileGenderOptionsService,
        private ProfileRichTextSanitizerInterface $profileRichTextSanitizer,
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
            if (
                !in_array($propertyName, self::EDITABLE_PROPERTIES, true)
                || !$profileFormData->_hasProperty($propertyName)
            ) {
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
            } elseif ($propertyName === 'skipSync') {
                if (!is_bool($value)) {
                    throw new UnexpectedValueException('Invalid skipSync value.');
                }
            } elseif (!is_string($value)) {
                throw new UnexpectedValueException(
                    sprintf('Invalid value for profile property "%s".', $propertyName)
                );
            }
            if ($this->profileRichTextSanitizer->supports($propertyName)) {
                $value = $this->profileRichTextSanitizer->sanitize($value);
            }
            $profileFormData->setPropertyOverride(
                $propertyName,
                $value,
            );
        }

        return $profileFormData;
    }

    /**
     * @return array<string, mixed>
     */
    public function getNormalizedData(
        ProfileFormData $profileFormData,
        ProfileUpdatePayload $payload,
    ): array {
        $data = [];
        foreach (array_keys($payload->getData()) as $propertyName) {
            if (!$profileFormData->hasPropertyOverride($propertyName)) {
                throw new UnexpectedValueException(
                    sprintf('Profile property "%s" was not normalized.', $propertyName)
                );
            }
            $data[$propertyName] = $profileFormData->getPropertyOverride($propertyName);
        }
        return $data;
    }

    public function validate(ProfileFormData $profileFormData): Result
    {
        return $this->profileFormDataValidator->validate(
            $profileFormData,
        );
    }
}
