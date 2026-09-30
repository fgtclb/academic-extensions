..  _feature-1790767100:

===========================================================
Feature: Page contacts processor options and contacts event
===========================================================

Description
===========

The data processor that hands the contacts of a page to a page template now
provides what the contacts content element renders, and takes options:

*   A page template also receives `contactsWithoutRole`, the contacts without a
    role, which the content element lists below its role groups. A page
    template that renders the contacts grouped by role no longer loses them.
*   :typoscript:`as` puts `contacts`, `roles` and `contactsWithoutRole` below
    one variable of that name.
*   :typoscript:`showHiddenRecords = 1` shows hidden contacts and the hidden
    e-mail addresses, phone numbers and addresses of a contact, as the
    :guilabel:`Show hidden records` option of the content element does.
*   :typoscript:`pageUid` reads the contacts of another page than the one that
    is rendered.

The processor has the identifier `academic-page-contacts`, which the shipped
TypoScript uses at :typoscript:`page.10.dataProcessing.400`. The class name
keeps working, and so does a subclass of the processor that a project registers
as described in :ref:`important-contacts-without-visible-profile-are-skipped`.

The new PSR-14 event
:php:`\FGTCLB\AcademicContacts4pages\Event\ModifyPageContactsEvent` lets a
listener change the contacts of a page before the content element or the page
template renders them. It tells the listener which of the two asked, and hands
it the plugin context of the content element when the content element asked.
The roles and the contacts without role are built from the list the listeners
hand back.

See :ref:`configuration-page-contacts` and
:ref:`developers-page-contacts-event`.

Impact
======

A page that keeps the shipped configuration renders as before, with the new
variable `contactsWithoutRole` available to its template. A project that copied
the controller of the content element, or subclassed the processor, to change
which contacts it shows can replace the copy with a listener. The behaviour is
the same on TYPO3 v13 and v14.

..  index:: Frontend, PHP-API, TypoScript, NotScanned, ext:academic_contacts4pages
