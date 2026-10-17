..  _developers-event-modify-tca-select-field-items-event:

==============================
ModifyTcaSelectFieldItemsEvent
==============================

:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` belongs to
:guilabel:`academic_base`. The academic extensions dispatch it from the item
providers of their backend select fields, so a listener changes the items an
editor is offered without replacing the item provider. The event is described
on :ref:`its page in the manual of academic_base
<fgtclb/academic-base:developers-event-modify-tca-select-field-items-event>`.
This page describes the selects of :guilabel:`academic_contacts4pages` that
dispatch it.

When it is dispatched
=====================

:php:`\FGTCLB\AcademicContacts4pages\Backend\FormEngine\AddressRecordItems`
dispatches it each time the backend form of a contact builds the items of one of
its three address selects:

..  list-table::
    :header-rows: 1

    *   -   Field
        -   Label
        -   Method of the item provider
    *   -   `email_address`
        -   :guilabel:`E-mail address`
        -   :php:`emailAddressItems()`
    *   -   `phone_number`
        -   :guilabel:`Phone number`
        -   :php:`phoneNumberItems()`
    *   -   `physical_address`
        -   :guilabel:`Physical address`
        -   :php:`physicalAddressItems()`

The table is `tx_academiccontacts4pages_domain_model_contact`, in the form of a
contact on its own and inline in the tab :guilabel:`Contacts` of a page or
:guilabel:`Linked pages` of a contract. The event is dispatched after the item
provider replaced the record items TYPO3 added with its own list, so a listener
receives the final items.

The :guilabel:`Contract` select of a contact is filled by the item provider of
:guilabel:`academic_persons`, which dispatches the same event for the field
`contract` of this table. `Its page in the manual of academic_persons
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Developers/Events/ModifyTcaSelectFieldItemsEvent.html>`__
describes the selects it fills.

What this extension passes in
=============================

..  list-table::
    :header-rows: 1

    *   -   Method
        -   Returns or does
    *   -   :php:`getParameters()`
        -   The parameters of the item provider: :php:`items`, :php:`table`,
            :php:`field`, :php:`row`, :php:`config`, :php:`TSconfig`,
            :php:`effectivePid`, :php:`site` and the inline parent keys. The
            uids of the inline parents are the empty string ``''`` unless the
            contact is edited inline, and a ``NEW...`` string for a parent
            that is not saved yet.
    *   -   :php:`setParameters()`
        -   Replaces the parameters. What counts is :php:`items`, the list the
            select offers.

The items of an address select:

*   :guilabel:`Display all` with the value `0`, the default of the field, and
    :guilabel:`Do not display` with the value `-1`.
*   One item per address of the contract the contact names, with the uid of
    the address as the value and a label built from its type and its text.
    Hidden addresses are offered too, marked as hidden. Only addresses of the
    default language and of the workspace of the editor are offered.

In the backend form, TYPO3 v13 and v14 hand the static items of a field to the
item provider as :php:`\TYPO3\CMS\Core\Schema\Struct\SelectItem` objects,
and the items a provider adds are arrays. A :php:`SelectItem` implements
:php:`\ArrayAccess`, so a closure typed :php:`array|SelectItem` that reads
:php:`$item['value']` works for both.

Example
=======

A site wants every contact on its pages to show a phone number, and takes
:guilabel:`Do not display` out of the phone number select:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/AlwaysShowAPhoneNumberOfPageContacts.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Schema\Struct\SelectItem;

    final class AlwaysShowAPhoneNumberOfPageContacts
    {
        #[AsEventListener(identifier: 'my-extension/always-show-a-phone-number-of-page-contacts')]
        public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
        {
            $parameters = $event->getParameters();
            if ($parameters['table'] !== 'tx_academiccontacts4pages_domain_model_contact'
                || $parameters['field'] !== 'phone_number'
            ) {
                return;
            }
            $parameters['items'] = array_values(array_filter(
                $parameters['items'],
                // -1 is "Do not display".
                static fn(array|SelectItem $item): bool => (int)($item['value'] ?? 0) !== -1,
            ));
            $event->setParameters($parameters);
        }
    }

Rules and pitfalls
==================

**Check the table and the field.** One listener receives the event of every
select of every academic extension that dispatches it.

**A removed item does not change stored values.** A contact that stores the
value of an item a listener removed keeps it. The form shows it as an invalid
value, and the frontend renders it as before, until an editor picks another
item. Do not remove :guilabel:`Display all`, the value a new contact gets.

**The items are what the editor is offered, not what the frontend shows.** The
frontend renders the addresses the contact selects, and hidden ones only while
:confval:`Show hidden records <contacts-list-show-hidden-records>` is on. An
item a listener adds whose value is no address of the contract shows no
address of that kind in the frontend.

Introduced in
=============

*   :ref:`feature-dedicated-address-record-selection` added the three selects
    in version 2.4, and with them the dispatch.
*   :ref:`important-ace-481-academic-contacts4pages` made the selects
    relations to the address tables in version 2.4, and states that a
    listener receives the final item list.
