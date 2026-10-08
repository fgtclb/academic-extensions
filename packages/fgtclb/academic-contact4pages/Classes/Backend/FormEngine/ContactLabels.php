<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Backend\FormEngine;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class ContactLabels
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function getTitle(array &$parameters): void
    {
        // A record the backend cannot load comes without a uid, a new one with its
        // `NEW…` placeholder, which an integer column comparison rejects on PostgreSQL.
        $uid = $parameters['row']['uid'] ?? null;
        if (!MathUtility::canBeInterpretedAsInteger($uid) || (int)$uid <= 0) {
            return;
        }

        $contactRepository = GeneralUtility::makeInstance(ContactRepository::class);
        $contact = $contactRepository->findByUid((int)$uid);

        if ($contact) {
            $parameters['title'] = $contact->getLabel();
        }
    }
}
