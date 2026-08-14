<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Controller;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use FGTCLB\AcademicContacts4pages\Service\AddressRecordProvider;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class ContactsController extends ActionController
{
    private ContactRepository $contactRepository;

    public function injectProfileRepository(ContactRepository $contactRepository): void
    {
        $this->contactRepository = $contactRepository;
    }

    public function listAction(): ResponseInterface
    {
        /** @var array<string, mixed> */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $showHiddenRecords = (bool)($this->settings['showHiddenRecords'] ?? false);
        $contacts = $this->contactRepository->findByPid(
            (int)($contentElementData['pid'] ?? 0),
            $showHiddenRecords
        );

        // Hidden address records are missing from the contract relation no matter what the
        // query above ignores, see AddressRecordProvider. Handing the provider over is what
        // lets a contact display them, so it only happens while the option is on.
        $addressRecordProvider = $showHiddenRecords
            ? GeneralUtility::makeInstance(AddressRecordProvider::class)
            : null;

        // Contacts without a role are collected here rather than filtered in the
        // template: the grouped branch can only render a contact that belongs to one of
        // the roles, so without this list they were dropped from the output entirely as
        // soon as any other contact on the page had a role (ACE-322). Keeping the split
        // in the controller also lets the template ask whether the ungrouped block is
        // needed at all, instead of emitting an empty row.
        $roles = [];
        $contactsWithoutRole = [];
        foreach ($contacts as $contact) {
            $contact->setAddressRecordProvider($addressRecordProvider);
            $role = $contact->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
                continue;
            }
            $contactsWithoutRole[] = $contact;
        }

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'contacts' => $contacts,
            'roles' => $roles,
            'contactsWithoutRole' => $contactsWithoutRole,
        ]);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
