<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Controller;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class ContactsController extends ActionController
{
    private ContactRepository $contactRepository;

    public function injectProfileRepository(ContactRepository $contactRepository): void
    {
        $this->contactRepository = $contactRepository;
    }

    public function listAction(): ResponseInterface
    {
        $versionInformation = GeneralUtility::makeInstance(Typo3Version::class);

        // With version TYPO3 v12 the access to the content object renderer has changed
        // @see https://docs.typo3.org/m/typo3/reference-coreapi/12.4/en-us/ApiOverview/RequestLifeCycle/RequestAttributes/CurrentContentObject.html
        if ($versionInformation->getMajorVersion() >= 12) {
            $contentObject = $this->request->getAttribute('currentContentObject');
        } else {
            $contentObject = $this->configurationManager->getContentObject();
        }

        $contacts = $this->contactRepository->findByPid($contentObject->data['pid']);

        $roles = [];
        foreach ($contacts as $contact) {
            $role = $contact->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }

        $this->view->assignMultiple([
            'data' => $contentObject->data,
            'contacts' => $contacts,
            'roles' => $roles,
        ]);

        return $this->htmlResponse();
    }
}
