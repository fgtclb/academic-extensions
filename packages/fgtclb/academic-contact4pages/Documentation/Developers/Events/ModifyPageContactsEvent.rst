..  _developers-event-modify-page-contacts-event:
..  _developers-page-contacts-event:

=======================
ModifyPageContactsEvent
=======================

:php:`\FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent` hands a
listener the contacts of a page before they are rendered, by the content element
:ref:`Person’s Contacts on the Page <integrator-contacts-list>` or by the data
processor `academic-page-contacts` in a page template. A listener leaves
contacts out, adds some, or changes their order.

When it is dispatched
=====================

In :php:`\FGTCLB\AcademicContacts4pages\Service\PageContactsProvider::get()`,
which both outputs ask:

*   :php:`ContactsController::listAction()` each time the content element
    renders,
*   :php:`\FGTCLB\AcademicContacts4pages\DataProcessing\ContactsProcessor` each
    time the data processor runs, see :ref:`configuration-page-contacts`.

It is dispatched after the contacts of the page are read and before they are
grouped by role. A page that renders the content element and a page template
with the data processor dispatches it twice, once per output.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getContacts()`, :php:`setContacts()`
        -   The contacts of the page, a list of
            :php:`\FGTCLB\AcademicContacts4pages\Domain\Model\Contact`, in the
            order they are sorted on the page. The setter refuses anything that
            is not a list of contacts, keyed from 0 without gaps.
    *   -   :php:`getPageUid()`
        -   The page whose contacts these are. The data processor may read
            another page than the one that is rendered.
    *   -   :php:`getOutput()`
        -   :php:`PageContactsOutput::Plugin` when the content element asked,
            :php:`PageContactsOutput::DataProcessor` when the data processor
            did.
    *   -   :php:`getRequest()`
        -   The request that is rendered.
    *   -   :php:`getPluginControllerActionContext()`
        -   The context of the content element that asked, with its settings,
            or :php:`null` when the data processor asked.

Example
=======

A site shows the contacts of a page twice: in the content area through the
content element, and in a sidebar of the page template through the data
processor. The sidebar is short and leaves the student advisers out, the
content element keeps them:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/LeaveTheStudentAdvisersOutOfTheSidebar.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicContacts4pages\Domain\Model\Contact;
    use FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent;
    use FGTCLB\AcademicContacts4pages\Event\PageContactsOutput;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    final class LeaveTheStudentAdvisersOutOfTheSidebar
    {
        #[AsEventListener(identifier: 'my-extension/leave-the-student-advisers-out-of-the-sidebar')]
        public function __invoke(ModifyPageContactsEvent $event): void
        {
            // The sidebar of the page template only, the content element keeps them.
            if ($event->getOutput() !== PageContactsOutput::DataProcessor) {
                return;
            }
            $event->setContacts(array_values(array_filter(
                $event->getContacts(),
                // 12 is the uid of the role "Student advisers" in this installation.
                static fn(Contact $contact): bool => $contact->getRole()?->getUid() !== 12,
            )));
        }
    }

:php:`array_values()` matters: :php:`array_filter()` keeps the keys, and the
setter refuses a list with gaps.

..  _developers-page-contacts-event-rules:

Rules and pitfalls
==================

**The roles follow the contacts.** The roles and the contacts without role are
built from the list the listeners hand back. A role whose last contact a
listener removed disappears with it, and a contact a listener added is placed
in its role group, or among the contacts without role.

**A contact is rendered as it is.** The contacts a listener receives are the
ones the current visitor may see: a contact whose contract or profile is not
visible is already left out. A contact a listener adds is not checked again,
so a listener that adds one makes sure its contract and profile resolve.

**The event runs when the page is rendered, not per visitor.** A page is cached
with the contacts the listeners handed back, so a listener cannot show a
different list to different visitors of a cached page. A decision that depends
on the visitor belongs where the page is not cached.

**Change the list, not the objects in it.** Both outputs of a request, and every
content element on the page, get the same contact, contract, profile and role
objects from the Extbase session. A listener that changes a property of one of
them changes it for every output of the request, whatever output it checked
for. Leaving a contact out, or adding one, is safe.

**Hidden address records follow the option, not the listener.** While
:confval:`Show hidden records <contacts-list-show-hidden-records>` is on in
the content element, or :typoscript:`showHiddenRecords` on the data
processor, the contacts show hidden e-mail addresses, phone numbers and
addresses. That applies to every contact of the final list, one a listener
added included. Each output gets copies of the contacts for this, so the
option of one output never reaches another, in whatever order a page template
renders them.

Introduced in
=============

*   :ref:`feature-1790767100` introduced the event, together with the options
    of the data processor.
