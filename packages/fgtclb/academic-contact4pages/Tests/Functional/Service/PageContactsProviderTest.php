<?php

declare(strict_types=1);

namespace FGTCLB\AcademicContacts4pages\Tests\Functional\Service;

use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
use FGTCLB\AcademicContacts4pages\Service\PageContactsProvider;
use FGTCLB\AcademicContacts4pages\Tests\Functional\AbstractAcademicContacts4PagesTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * The service both the contacts content element and the page contacts data processor ask
 * for the contacts of a page.
 *
 * The plugin and processor tests cover what a visitor ends up seeing; this one pins the
 * three values the service hands out, and in particular the split that neither of them
 * shows directly: `contactsWithoutRole` must hold the shown role-less contacts and
 * nothing else.
 *
 * The fixture gives page 2 five contacts: two that resolve (uid 1 with a role, uid 5
 * without one) and three that do not - a hidden profile, a hidden contract and a profile
 * whose end time has passed.
 */
final class PageContactsProviderTest extends AbstractAcademicContacts4PagesTestCase
{
    private function subject(): PageContactsProvider
    {
        return $this->get(PageContactsProvider::class);
    }

    /**
     * @param list<Contact> $contacts
     * @return list<int>
     */
    private function uids(array $contacts): array
    {
        return array_map(static fn(Contact $contact): int => (int)$contact->getUid(), $contacts);
    }

    private function setUpTestCase(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/PageContactsProvider/contacts.csv');
    }

    #[Test]
    public function onlyContactsWithAVisibleContractAndProfileAreReturned(): void
    {
        $this->setUpTestCase();

        $this->assertSame([1, 5], $this->uids($this->subject()->get(2)->contacts));
    }

    /**
     * "Student Advisors" is carried by the contact with the hidden profile alone, so it
     * must not be among the roles - otherwise the template renders a heading above nothing.
     */
    #[Test]
    public function rolesAreBuiltFromTheReturnedContactsOnly(): void
    {
        $this->setUpTestCase();

        $roles = $this->subject()->get(2)->roles;

        $this->assertSame([1], array_keys($roles));
        $this->assertSame("Dean's Office", $roles[1]->getName());
    }

    #[Test]
    public function contactsWithoutRoleHoldsTheReturnedRoleLessContactsOnly(): void
    {
        $this->setUpTestCase();

        // Contact 4 has no role either, but its profile has expired.
        $this->assertSame([5], $this->uids($this->subject()->get(2)->contactsWithoutRole));
    }

    /**
     * A page without contacts is a normal case, not an error: the plugin renders its
     * wrapper and the page template its own markup.
     */
    #[Test]
    public function aPageWithoutContactsYieldsEmptyValues(): void
    {
        $this->setUpTestCase();

        $pageContacts = $this->subject()->get(1);

        $this->assertSame([], $pageContacts->contacts);
        $this->assertSame([], $pageContacts->roles);
        $this->assertSame([], $pageContacts->contactsWithoutRole);
    }
}
