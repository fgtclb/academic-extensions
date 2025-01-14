<?php

namespace FGTCLB\AcademicContacts4pages\DataProcessing;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

class ContactsProcessor implements DataProcessorInterface
{
    /**
     * Make project data accessable in Fluid
     *
     * @param ContentObjectRenderer $cObj The data of the content element or page
     * @param array<string, mixed> $contentObjectConfiguration The configuration of Content Object
     * @param array<string, mixed> $processorConfiguration The configuration of this processor
     * @param array<string, mixed> $processedData Key/value store of processed data (e.g. to be passed to a Fluid View)
     * @return array<string, mixed> the processed data as key/value store
     */
    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ) {
        $contactRepository = GeneralUtility::makeInstance(ContactRepository::class);

        // With the introduction of the new PageViewContentObject in version TYPO3 v13.1 the structure of the processedData array has changed.
        // @see https://docs.typo3.org/c/typo3/cms-core/main/en-us//Changelog/13.1/Feature-103504-NewContentObjectPageView.html
        // @see https://github.com/TYPO3/typo3/blob/12.4/typo3/sysext/frontend/Classes/ContentObject/FluidTemplateContentObject.php#L283
        // @see https://github.com/TYPO3/typo3/blob/13.1/typo3/sysext/frontend/Classes/ContentObject/PageViewContentObject.php#L158
        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);
        if ($versionInformation->getMajorVersion() >= 13) {
            $pageUid = $processedData['page']->getId();
        } else {
            $pageUid = $processedData['data']['uid'];
        }

        $contacts = $contactRepository->findByPid($pageUid);

        $roles = [];
        foreach ($contacts as $contact) {
            $role = $contact->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }
        $processedData['roles'] = $roles;

        return $processedData;
    }
}
