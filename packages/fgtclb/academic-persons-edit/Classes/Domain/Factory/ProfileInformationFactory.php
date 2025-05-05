<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\ProfileInformation as ProfileInformationModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileInformationFormData;

class ProfileInformationFactory
{
    public function get(ProfileInformationFormData $profileInformationFormData): ProfileInformationModel
    {
        $profileInformation = new ProfileInformationModel;

        $profileInformation->setYear($profileInformationFormData->getYear());
        $profileInformation->setYearStart($profileInformationFormData->getYearStart());
        $profileInformation->setYearEnd($profileInformationFormData->getYearEnd());
        $profileInformation->setTitle($profileInformationFormData->getTitle());
        $profileInformation->setBodytext($profileInformationFormData->getBodytext());
        $profileInformation->setLink($profileInformationFormData->getLink());

        return $profileInformation;
    }
}
