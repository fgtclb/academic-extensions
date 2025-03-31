<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Controller;

use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use Psr\Http\Message\ResponseInterface;
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
        $contacts = $this->contactRepository->findByPid((int)($contentElementData['pid'] ?? 0));

        $roles = [];
        foreach ($contacts as $contact) {
            $role = $contact->getRole();
            if ($role !== null) {
                $roles[$role->getUid()] = $role;
            }
        }

        $this->view->assignMultiple([
            'data' => $contentElementData,
            'contacts' => $contacts,
            'roles' => $roles,
        ]);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
