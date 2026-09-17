<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Service;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Domain\Model\Role;

/**
 * The contacts of one page that are shown to the current visitor, already split into the
 * three values a template needs.
 *
 * `$roles` holds only the roles at least one of the shown contacts carries, keyed by the
 * uid of the role, and `$contactsWithoutRole` only shown contacts - so a role whose
 * contacts are all invisible does not reach the output as an empty group.
 */
final readonly class PageContacts
{
    /**
     * @param list<Contact> $contacts
     * @param array<int, Role> $roles
     * @param list<Contact> $contactsWithoutRole
     */
    public function __construct(
        public array $contacts,
        public array $roles,
        public array $contactsWithoutRole,
    ) {}
}
