<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Backend\FormEngine;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ContactLabels
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function getTitle(array &$parameters): void
    {
        if (!isset($parameters['row']) && !isset($parameters['row']['uid'])) {
            return;
        }

        $contactRepository = GeneralUtility::makeInstance(ContactRepository::class);
        $contact = $contactRepository->findByUid($parameters['row']['uid']);

        if ($contact) {
            $parameters['title'] = $contact->getLabel();
        }
    }
}
