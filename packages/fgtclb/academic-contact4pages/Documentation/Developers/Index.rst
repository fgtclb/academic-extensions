..  _developers:

==============
For developers
==============

The contacts content element and the data processor that hands the contacts of
a page to a page template read the contacts through one service, and that
service dispatches one PSR-14 event. It is the supported way to change which
contacts a page shows, in either output, without copying the controller.

Which classes of this extension are public API, and what that promises, is
stated for all academic extensions on the `extension points page of
academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Developers/ExtensionPoints/Index.html>`__.
The content element also dispatches :php:`ModifyPluginViewEvent` of
:guilabel:`academic_base` when it renders, after this event, and that page
describes it.

..  _developers-page-contacts-event:

The page contacts event
=======================

:php:`\FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent` is
dispatched each time the content element renders and each time the data
processor runs, after the contacts of the page are read and before they are
grouped by role.

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns
    *   -   :php:`getContacts()`, :php:`setContacts()`
        -   The contacts of the page, a list of
            :php:`\FGTCLB\AcademicContacts4pages\Domain\Model\Contact`. The
            setter refuses anything else.
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

..  _developers-page-contacts-event-rules:

Rules worth knowing
===================

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
:guilabel:`Show hidden records` is on in the content element, or
:typoscript:`showHiddenRecords` on the data processor, the contacts show hidden
e-mail addresses, phone numbers and addresses. That applies to every contact
of the final list, one a listener added included. Each output gets copies of
the contacts for this, so the option of one output never reaches another, in
whatever order a page template renders them.
