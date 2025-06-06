<?php

declare(strict_types=1);

namespace FGTCLB\AcademicPersonsEdit\Domain\Factory;

use FGTCLB\AcademicPersons\Domain\Model\Profile as ProfileModel;
use FGTCLB\AcademicPersonsEdit\Domain\Model\Dto\ProfileFormData;

class ProfileFactory
{
    public function createFromFormData(ProfileFormData $profileFormData): ProfileModel
    {
        return (new ProfileModel())
            ->setGender($profileFormData->getGender())
            ->setTitle($profileFormData->getTitle())
            ->setFirstName($profileFormData->getFirstName())
            ->setMiddleName($profileFormData->getMiddleName())
            ->setLastName($profileFormData->getLastName())
            ->setWebsite($profileFormData->getWebsite())
            ->setWebsiteTitle($profileFormData->getWebsiteTitle())
            ->setPublicationsLink($profileFormData->getPublicationsLink())
            ->setPublicationsLinkTitle($profileFormData->getPublicationsLinkTitle())
            ->setTeachingArea($profileFormData->getTeachingArea())
            ->setCoreCompetences($profileFormData->getCoreCompetences())
            ->setSupervisedThesis($profileFormData->getSupervisedThesis())
            ->setSupervisedDoctoralThesis($profileFormData->getSupervisedDoctoralThesis())
            ->setMiscellaneous($profileFormData->getMiscellaneous());
    }

    public function updateFromFormData(ProfileModel $profile, ProfileFormData $profileFormData): ProfileModel
    {
        return $profile
            ->setGender($profileFormData->getGender())
            ->setTitle($profileFormData->getTitle())
            ->setFirstName($profileFormData->getFirstName())
            ->setMiddleName($profileFormData->getMiddleName())
            ->setLastName($profileFormData->getLastName())
            ->setWebsite($profileFormData->getWebsite())
            ->setWebsiteTitle($profileFormData->getWebsiteTitle())
            ->setPublicationsLink($profileFormData->getPublicationsLink())
            ->setPublicationsLinkTitle($profileFormData->getPublicationsLinkTitle())
            ->setTeachingArea($profileFormData->getTeachingArea())
            ->setCoreCompetences($profileFormData->getCoreCompetences())
            ->setSupervisedThesis($profileFormData->getSupervisedThesis())
            ->setSupervisedDoctoralThesis($profileFormData->getSupervisedDoctoralThesis())
            ->setMiscellaneous($profileFormData->getMiscellaneous());
    }
}
