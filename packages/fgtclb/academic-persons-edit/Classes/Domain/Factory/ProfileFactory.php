<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Profile as ProfileModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileFormData;

class ProfileFactory
{
    public function createFromFormData(ProfileFormData $profileFormData): ProfileModel
    {
        return new ProfileModel(
            $profileFormData->getTitle(),
            $profileFormData->getFirstName(),
            $profileFormData->getMiddleName(),
            $profileFormData->getLastName(),
            $profileFormData->getGender(),
            $profileFormData->getPublicationsLink(),
            $profileFormData->getPublicationsLinkTitle(),
            $profileFormData->getWebsite(),
            $profileFormData->getWebsiteTitle()
        );
    }

    public function updateFromFormData(
        ProfileModel $profile,
        ProfileFormData $profileFormData
    ): ProfileModel {
        $profile->setTitle($profileFormData->getTitle());
        $profile->setFirstName($profileFormData->getFirstName());
        $profile->setMiddleName($profileFormData->getMiddleName());
        $profile->setLastName($profileFormData->getLastName());
        $profile->setGender($profileFormData->getGender());
        $profile->setPublicationsLink($profileFormData->getPublicationsLink());
        $profile->setPublicationsLinkTitle($profileFormData->getPublicationsLinkTitle());
        $profile->setWebsite($profileFormData->getWebsite());
        $profile->setWebsiteTitle($profileFormData->getWebsiteTitle());

        return $profile;
    }
}
