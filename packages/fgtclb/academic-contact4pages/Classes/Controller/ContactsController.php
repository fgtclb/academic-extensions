<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Controller;

use FGTCLB\AcademicContacts4pages\Service\AddressRecordProvider;
use FGTCLB\AcademicContacts4pages\Service\PageContactsProvider;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class ContactsController extends ActionController
{
    private PageContactsProvider $pageContactsProvider;

    public function injectPageContactsProvider(PageContactsProvider $pageContactsProvider): void
    {
        $this->pageContactsProvider = $pageContactsProvider;
    }

    public function listAction(): ResponseInterface
    {
        /** @var array<string, mixed> */
        $contentElementData = $this->getCurrentContentObjectRenderer()?->data ?? [];
        $showHiddenRecords = (bool)($this->settings['showHiddenRecords'] ?? false);

        // Which contacts are shown, and the split into roles and role-less contacts, is
        // decided by the provider - the data processor of this extension asks the same one.
        // Contacts without a role are a list of their own rather than a filter in the
        // template: the grouped branch can only render a contact that belongs to one of the
        // roles, so without this list they were dropped from the output entirely as soon as
        // any other contact on the page had a role (ACE-322). It also lets the template ask
        // whether the ungrouped block is needed at all, instead of emitting an empty row.
        $pageContacts = $this->pageContactsProvider->get(
            (int)($contentElementData['pid'] ?? 0),
            $showHiddenRecords
        );

        // Hidden address records are missing from the contract relation no matter what the
        // query above ignores, see AddressRecordProvider. Handing the provider over is what
        // lets a contact display them, so it only happens while the option is on.
        $addressRecordProvider = $showHiddenRecords
            ? GeneralUtility::makeInstance(AddressRecordProvider::class)
            : null;
        foreach ($pageContacts->contacts as $contact) {
            $contact->setAddressRecordProvider($addressRecordProvider);
        }

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'contacts' => $pageContacts->contacts,
            'roles' => $pageContacts->roles,
            'contactsWithoutRole' => $pageContacts->contactsWithoutRole,
        ]);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
