<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Service;

use FGTCLB\AcademicBase\Domain\Model\Dto\PluginControllerActionContextInterface;
use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;
use FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent;
use FGTCLB\AcademicContacts4pages\Event\PageContactsOutput;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Decides which contacts of a page are shown to the current visitor.
 *
 * The contacts content element and the page contacts data processor both ask this service,
 * so the rule exists once rather than in each of them - and, before that, in every project
 * template that had to guard against a contact without a person. The same goes for the one
 * extension point: `ModifyPageContactsEvent` is dispatched here, so a listener reaches both
 * outputs and cannot forget one of them.
 *
 * `ContactRepository::findByPid()` restricts the contact table alone. The contract and the
 * profile behind a contact are relations, and Extbase builds the query for a relation from
 * fresh query settings: what the contact query ignores never reaches them, so a hidden
 * contract, or a profile that is hidden, outside its start and end time or restricted to a
 * frontend user group, resolves to `null` instead of raising anything. Such a contact used
 * to render as an empty card below its role heading, which is what this service drops.
 *
 * That is also why "show hidden records" does not widen the rule: it lifts the `disabled`
 * field of the *contact row*, and a relation would stay unresolved even if it did not.
 * What it does widen are the address records of a contact, see `AddressRecordProvider`.
 */
final readonly class PageContactsProvider
{
    public function __construct(
        private ContactRepository $contactRepository,
        private EventDispatcherInterface $eventDispatcher,
        private AddressRecordProvider $addressRecordProvider,
    ) {}

    public function get(
        int $pageUid,
        bool $showHiddenRecords,
        PageContactsOutput $output,
        ServerRequestInterface $request,
        ?PluginControllerActionContextInterface $pluginControllerActionContext = null,
    ): PageContacts {
        $contacts = [];
        foreach ($this->contactRepository->findByPid($pageUid, $showHiddenRecords) as $contact) {
            if ($this->isResolvable($contact)) {
                $contacts[] = $contact;
            }
        }

        $event = new ModifyPageContactsEvent($contacts, $pageUid, $output, $request, $pluginControllerActionContext);
        $this->eventDispatcher->dispatch($event);

        // Grouped only now, from the list the listeners handed back: a role whose contacts a
        // listener removed must not reach the output as an empty group.
        $contacts = [];
        $roles = [];
        $contactsWithoutRole = [];
        foreach ($event->getContacts() as $contact) {
            // Hidden address records are missing from the contract relation no matter what
            // the contact query ignores, see AddressRecordProvider. Handing the provider over
            // is what lets a contact display them, so it only happens while the option is on.
            // Each call hands out copies: the Extbase session gives every output of a request
            // the same contact objects, and Fluid reads the address records only when it
            // renders them - by then a content element rendered inside the page template has
            // asked again, with its own option. The contract and the role stay shared.
            $contact = clone $contact;
            $contact->setAddressRecordProvider($showHiddenRecords ? $this->addressRecordProvider : null);
            $contacts[] = $contact;

            $role = $contact->getRole();
            if ($role !== null) {
                $roles[(int)$role->getUid()] = $role;
                continue;
            }
            $contactsWithoutRole[] = $contact;
        }

        return new PageContacts($contacts, $roles, $contactsWithoutRole);
    }

    /**
     * Asks for the unfiltered contract on purpose: `getContract()` builds the display copy
     * carrying the selected address records, and a contact that is dropped here never
     * needs it.
     */
    private function isResolvable(Contact $contact): bool
    {
        $contract = $contact->getUnfilteredContract();

        return $contract !== null && $contract->getProfile() !== null;
    }
}
