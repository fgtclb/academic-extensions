<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation as ProfileInformationModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;

class ProfileInformationFactory
{
    public function createFromFormData(ProfileInformationFormData $profileInformationFormData): ProfileInformationModel
    {
        return new ProfileInformationModel(
            $profileInformationFormData->getType(),
            $profileInformationFormData->getTitle(),
            $profileInformationFormData->getBodytext(),
            $profileInformationFormData->getLink(),
            $profileInformationFormData->getYear(),
            $profileInformationFormData->getYearStart(),
            $profileInformationFormData->getYearEnd(),
        );
    }

    public function updateFromFormData(
        ProfileInformationModel $profileInformation,
        ProfileInformationFormData $profileInformationFormData
    ): ProfileInformationModel {
        $profileInformation->setType($profileInformationFormData->getType());
        $profileInformation->setTitle($profileInformationFormData->getTitle());
        $profileInformation->setBodytext($profileInformationFormData->getBodytext());
        $profileInformation->setLink($profileInformationFormData->getLink());
        $profileInformation->setYear($profileInformationFormData->getYear());
        $profileInformation->setYearStart($profileInformationFormData->getYearStart());
        $profileInformation->setYearEnd($profileInformationFormData->getYearEnd());

        return $profileInformation;
    }
}
