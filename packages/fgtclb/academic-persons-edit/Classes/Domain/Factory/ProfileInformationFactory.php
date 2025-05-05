<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Profile;
use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation as ProfileInformationModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;

class ProfileInformationFactory
{
    public function createFromFormData(Profile $profile, string $type, ProfileInformationFormData $profileInformationFormData): ProfileInformationModel
    {
        return (new ProfileInformationModel())
            ->setProfile($profile)
            ->setType($type)
            ->setTitle($profileInformationFormData->getTitle())
            ->setBodytext($profileInformationFormData->getBodytext())
            ->setLink($profileInformationFormData->getLink())
            ->setYear($profileInformationFormData->getYear())
            ->setYearStart($profileInformationFormData->getYearStart())
            ->setYearEnd($profileInformationFormData->getYearEnd());
    }

    public function updateFromFormData(ProfileInformationModel $profileInformation, ProfileInformationFormData $profileInformationFormData): ProfileInformationModel
    {
        return $profileInformation
            ->setType($profileInformationFormData->getType())
            ->setTitle($profileInformationFormData->getTitle())
            ->setBodytext($profileInformationFormData->getBodytext())
            ->setLink($profileInformationFormData->getLink())
            ->setYear($profileInformationFormData->getYear())
            ->setYearStart($profileInformationFormData->getYearStart())
            ->setYearEnd($profileInformationFormData->getYearEnd());
    }
}
