<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Service;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Repository\ContactRepository;

/**
 * Decides which contacts of a page are shown to the current visitor.
 *
 * The contacts content element and the page contacts data processor both ask this service,
 * so the rule exists once rather than in each of them - and, before that, in every project
 * template that had to guard against a contact without a person.
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
 */
final class PageContactsProvider
{
    public function __construct(
        private readonly ContactRepository $contactRepository,
    ) {}

    public function get(int $pageUid, bool $showHiddenRecords = false): PageContacts
    {
        $contacts = [];
        $roles = [];
        $contactsWithoutRole = [];

        foreach ($this->contactRepository->findByPid($pageUid, $showHiddenRecords) as $contact) {
            if (!$this->isResolvable($contact)) {
                continue;
            }

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
