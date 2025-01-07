<?php

namespace FGTCLB\AcademicContacts4pages\DataProcessing;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
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
        $contacts = $contactRepository->findByPid($processedData['data']['uid']);

        $processedData['contacts'] = $contacts;

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
