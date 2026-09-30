<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Event;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Service\PageContactsProvider;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Dispatched in {@see PageContactsProvider::get()}, which the contacts content element and
 * the page contacts data processor both ask, after the contacts of a page are read and
 * before they are grouped. A listener replaces the contacts of the page.
 *
 * The contacts are the ones shown to the current visitor: a contact whose contract or
 * profile is not visible is already left out. The roles and the contacts without role are
 * built from the list the listeners hand back, so they cannot disagree with it. A contact a
 * listener adds is rendered as it is.
 *
 * {@see self::getOutput()} tells which of the two outputs asked. The plugin action context
 * is there only when the content element asked, and carries its settings.
 *
 * @api
 */
final class ModifyPageContactsEvent
{
    /**
     * @param list<Contact> $contacts
     */
    public function __construct(
        private array $contacts,
        private readonly int $pageUid,
        private readonly PageContactsOutput $output,
        private readonly ServerRequestInterface $request,
        private readonly ?PluginControllerActionContextInterface $pluginControllerActionContext = null,
    ) {}

    /**
     * @return list<Contact>
     */
    public function getContacts(): array
    {
        return $this->contacts;
    }

    /**
     * @param list<Contact> $contacts
     * @throws \InvalidArgumentException when the value is not a list of contacts
     */
    public function setContacts(array $contacts): void
    {
        if (!array_is_list($contacts)) {
            throw new \InvalidArgumentException(
                'The contacts of a page must be a list, keyed from 0 without gaps.',
                1790767001,
            );
        }
        foreach ($contacts as $contact) {
            if (!$contact instanceof Contact) {
                throw new \InvalidArgumentException(
                    sprintf('The contacts of a page must be instances of %s, %s given.', Contact::class, get_debug_type($contact)),
                    1790767002,
                );
            }
        }
        $this->contacts = $contacts;
    }

    /**
     * The page whose contacts these are. The data processor may read another page than the
     * one that is rendered.
     */
    public function getPageUid(): int
    {
        return $this->pageUid;
    }

    public function getOutput(): PageContactsOutput
    {
        return $this->output;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * The context of the content element that asked, or `null` when the data processor
     * asked.
     */
    public function getPluginControllerActionContext(): ?PluginControllerActionContextInterface
    {
        return $this->pluginControllerActionContext;
    }
}
