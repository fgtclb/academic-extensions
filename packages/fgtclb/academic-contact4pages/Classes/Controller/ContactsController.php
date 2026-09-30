<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Controller;

use FGTCLB\AcademicBase\Controller\DispatchModifyPluginViewEventMethodTrait;
use FGTCLB\AcademicBase\Controller\GetCurrentContentRecordMethodTrait;
use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContext;
use FGTCLB\AcademicContacts4pages\Event\PageContactsOutput;
use FGTCLB\AcademicContacts4pages\Service\PageContactsProvider;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

final class ContactsController extends ActionController
{
    use DispatchModifyPluginViewEventMethodTrait;
    use GetCurrentContentRecordMethodTrait;

    private PageContactsProvider $pageContactsProvider;

    public function injectPageContactsProvider(PageContactsProvider $pageContactsProvider): void
    {
        $this->pageContactsProvider = $pageContactsProvider;
    }

    public function listAction(): ResponseInterface
    {
        $context = new PluginControllerActionContext($this->request, $this->settings);
        $contentObjectRenderer = $this->getCurrentContentObjectRenderer();
        /** @var array<string, mixed> */
        $contentElementData = $contentObjectRenderer?->data ?? [];

        // Which contacts are shown, and the split into roles and role-less contacts, is
        // decided by the provider - the data processor of this extension asks the same one,
        // and the provider dispatches the event that lets a listener change them for both.
        // Contacts without a role are a list of their own rather than a filter in the
        // template: the grouped branch can only render a contact that belongs to one of the
        // roles, so without this list they were dropped from the output entirely as soon as
        // any other contact on the page had a role (ACE-322). It also lets the template ask
        // whether the ungrouped block is needed at all, instead of emitting an empty row.
        $pageContacts = $this->pageContactsProvider->get(
            (int)($contentElementData['pid'] ?? 0),
            (bool)($this->settings['showHiddenRecords'] ?? false),
            PageContactsOutput::Plugin,
            $this->request,
            $context,
        );

        // The shipped template does not need `record`. A project template that renders the
        // header partial of EXT:fluid_styled_content does on TYPO3 v14, see the trait.
        $this->view->assignMultiple([
            'data' => $contentElementData,
            'record' => $this->getCurrentContentRecord($contentObjectRenderer),
            'contacts' => $pageContacts->contacts,
            'roles' => $pageContacts->roles,
            'contactsWithoutRole' => $pageContacts->contactsWithoutRole,
        ]);
        $this->dispatchModifyPluginViewEvent($context, $this->view, $this->eventDispatcher);

        return $this->htmlResponse();
    }

    private function getCurrentContentObjectRenderer(): ?ContentObjectRenderer
    {
        return $this->request->getAttribute('currentContentObject');
    }
}
