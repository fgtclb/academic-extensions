<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Controller;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use Psr\Http\Message\ResponseInterface;
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
        $contentObject = $this->request->getAttribute('currentContentObject');
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
