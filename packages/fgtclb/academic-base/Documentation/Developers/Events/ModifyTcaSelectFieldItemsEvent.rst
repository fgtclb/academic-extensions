..  _developers-event-modify-tca-select-field-items-event:

==============================
ModifyTcaSelectFieldItemsEvent
==============================

:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` hands a
listener the items of a select field after the item provider of an academic
extension built them, so the listener can remove, add or reorder them. It
replaces the two ways a project had before: adding items in TCA, with no say
in their order, or replacing the whole :php:`itemsProcFunc` of a field.

A site that does not offer theses removes that job type, a site that never
shows rooms removes the room from the fields an editor can pick for a person
list, and a project with a field of its own offers it in the same select.

This extension owns the event and dispatches it nowhere itself. The item
providers of three academic extensions dispatch it, and each of them documents
what it passes in.

..  contents::
    :local:
    :depth: 1

..  _developers-event-modify-tca-select-field-items-event-dispatch:

When it is dispatched
=====================

The item provider of the field dispatches the event once each time the items
of the field are built, after it added its own items. In the backend that is
each time the record or the content element is opened in the form. The
parameters a listener hands back are what the field offers.

..  list-table::
    :header-rows: 1
    :widths: 22 30 48

    *   -   Extension
        -   Table and field
        -   Backend label, and where it is shown
    *   -   `academic_jobs
            <https://docs.typo3.org/p/fgtclb/academic-jobs/main/en-us/Developers/Events/ModifyTcaSelectFieldItemsEvent.html>`__
        -   ``tx_academicjobs_domain_model_job``, ``type`` and
            ``employment_type``
        -   :guilabel:`Job Type` and :guilabel:`Employment Type` of a job
            record, and the same two selects of the job form in the frontend
    *   -   `academic_persons
            <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Developers/Events/ModifyTcaSelectFieldItemsEvent.html>`__
        -   ``tt_content``, ``settings.selectedContracts`` and
            ``settings.showFields``, and
            ``tx_academiccontacts4pages_domain_model_contact``, ``contract``
        -   :guilabel:`Selected Contracts` of the selected contracts plugin,
            :guilabel:`Show only selected Fields` of the list plugins, the
            selected profiles and the selected contracts, and
            :guilabel:`Contract` of a page contact of academic_contacts4pages
    *   -   `academic_contacts4pages
            <https://docs.typo3.org/p/fgtclb/academic-contacts4pages/main/en-us/Developers/Events/ModifyTcaSelectFieldItemsEvent.html>`__
        -   ``tx_academiccontacts4pages_domain_model_contact``,
            ``email_address``, ``phone_number`` and ``physical_address``
        -   :guilabel:`E-mail address`, :guilabel:`Phone number` and
            :guilabel:`Physical address` of a page contact

The job form of :guilabel:`academic_jobs` builds the options of its two
selects in the frontend from the same item providers, so the event is
dispatched there as well, and an item a listener removes is neither offered
in the backend nor in the form.

What a listener reads and changes
=================================

..  list-table::
    :header-rows: 1
    :widths: 35 65

    *   -   Method
        -   What it does
    *   -   :php:`getParameters()`
        -   The parameters TYPO3 hands to an :php:`itemsProcFunc`, with the
            items the provider built, see below.
    *   -   :php:`setParameters(array $parameters)`
        -   Replaces them. The items of the array a listener passes are what
            the field offers, in that order.

The parameters are those of an :php:`itemsProcFunc`, see the `TCA reference
<https://docs.typo3.org/m/typo3/reference-tca/main/en-us/ColumnsConfig/CommonProperties/ItemsProcFunc.html#passed-parameters>`__.
The keys a listener needs most:

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Key
        -   Holds
    *   -   ``items``
        -   The items, each with ``label`` and ``value``, and ``icon`` and
            ``group`` where the field uses them. A label is a ``LLL:``
            reference or a text. See below for the form of an item.
    *   -   ``table``
        -   The table of the record. For a FlexForm field, the table of the
            content element, ``tt_content``.
    *   -   ``field``
        -   The field. For a FlexForm field, the name of the FlexForm
            element, ``settings.showFields`` for example.
    *   -   ``row``
        -   The record being edited. For a FlexForm field, the values of the
            FlexForm. Empty in the job form, and declared optional, see the
            pitfalls below.
    *   -   ``flexParentDatabaseRow``
        -   For a FlexForm field, the record of the content element.
    *   -   ``site``, ``effectivePid``
        -   The site and the page the record belongs to. For a record outside
            any site, on page 0 for example, ``site`` is a
            :php:`\TYPO3\CMS\Core\Site\Entity\NullSite`, not a
            :php:`Site`. Call only the methods of
            :php:`\TYPO3\CMS\Core\Site\Entity\SiteInterface` on it, or
            check for :php:`Site` first. ``site`` is :php:`null` when the
            caller has none, a form data group without site resolving on
            TYPO3 v13, or a request without a site in the job form.
    *   -   ``inlineParentUid``, ``inlineTopMostParentUid``
        -   The uid of the direct and of the topmost inline parent, when the
            record is edited as an inline child. A parent that is not saved
            yet has a ``NEW...`` string instead. For a record that is not
            edited as an inline child, the common case, both are the empty
            string ``''``. The site configuration passes :php:`null` as
            ``inlineTopMostParentUid``. Missing in the job form. Cast the
            value, as :php:`(int)($parameters['inlineParentUid'] ?? 0)`, and
            use it only when it is above 0.
    *   -   ``inlineParentTableName``, ``inlineParentFieldName``,
            ``inlineParentConfig`` and the same keys of the topmost parent
        -   The table, the field and the configuration of the inline parent.
            ``''`` and ``[]`` for a record that is not edited as an inline
            child. Missing in the job form.
    *   -   ``TSconfig``
        -   The page TSconfig below
            :typoscript:`TCEFORM.<table>.<field>.itemsProcFunc`, or
            :php:`null` when there is none.

In the backend form, TYPO3 hands the items it built before the item provider
runs, the static items of the field and those of a ``foreign_table``, as
:php:`\TYPO3\CMS\Core\Schema\Struct\SelectItem` objects, on TYPO3 v13
and v14 alike. The items a provider adds are arrays, and the job form of the
frontend hands every item over as an array. A :php:`SelectItem` implements
:php:`\ArrayAccess`, so a closure typed :php:`array|SelectItem` that reads
:php:`$item['value']` works for both.

An item is therefore not always an array. A closure typed :php:`array` alone
raises a :php:`\TypeError` in the backend form for a field with static items.

The event declares these parameters once, as the PHPStan type alias
:php:`ItemsProcFuncParameters`, and the item providers import it. A listener
or an item provider of a project imports it the same way, so static analysis
checks it against the values above:

..  code-block:: php

    /**
     * @phpstan-import-type ItemsProcFuncParameters from \FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent
     */
    final class MyItemsProcFunc
    {
        /**
         * @param ItemsProcFuncParameters $parameters
         */
        public function itemsProcFunc(array &$parameters): void {}
    }

..  _developers-event-modify-tca-select-field-items-event-example:

Example
=======

A site does not offer theses on its job board and never shows the room of a
person. One listener removes both items. It checks the table and the field
first, because it is called for every field of the table above:

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/RemoveUnusedSelectItems.php

    <?php

    declare(strict_types=1);

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Schema\Struct\SelectItem;

    final readonly class RemoveUnusedSelectItems
    {
        /**
         * The values to remove, by table and field.
         */
        private const REMOVED_VALUES = [
            'tx_academicjobs_domain_model_job' => [
                'type' => ['3'],
            ],
            'tt_content' => [
                'settings.showFields' => ['contracts.room'],
            ],
        ];

        #[AsEventListener(identifier: 'my-extension/remove-unused-select-items')]
        public function __invoke(ModifyTcaSelectFieldItemsEvent $event): void
        {
            $parameters = $event->getParameters();
            $removedValues = self::REMOVED_VALUES[$parameters['table']][$parameters['field']] ?? [];
            if ($removedValues === []) {
                return;
            }
            $parameters['items'] = array_values(array_filter(
                $parameters['items'],
                static fn(array|SelectItem $item): bool => !in_array((string)($item['value'] ?? ''), $removedValues, true),
            ));
            $event->setParameters($parameters);
        }
    }

The value is compared as a string, because the item providers declare some
values as integers and the backend hands others over as strings.

..  _developers-event-modify-tca-select-field-items-event-rules:

Rules and pitfalls
==================

*   **Check table and field.** One listener is called for every field of the
    table above. A listener that does not check both changes all of them.
*   **No record in the frontend.** In the job form, the parameters carry
    ``items``, ``config``, ``TSconfig``, ``table``, ``row``, ``field``,
    ``effectivePid`` and ``site``, and none of the keys of an inline parent.
    ``row`` is an empty array there, because the job does not exist yet. The
    event declares ``row`` optional, so static analysis reports a listener
    that reads it without a fallback. Read it as
    :php:`$parameters['row'] ?? []`, and every field of it with a fallback
    too, as :php:`$row['pid'] ?? $parameters['effectivePid']`.
*   **The inline uids are not always integers.** A listener that passes
    :php:`$parameters['inlineParentUid']` to a parameter typed :php:`int`
    raises a :php:`\TypeError` for every record that is not edited as an
    inline child, because the value is ``''`` there. Cast it first.
*   **A FlexForm field has two rows.** ``row`` holds the values of the
    FlexForm, the content element is ``flexParentDatabaseRow``.
*   **Keep the stored values in mind.** Removing an item hides it from the
    form, it does not change the records that store it already. A single
    select shows such a value as an invalid value. A multiple select, such as
    :guilabel:`Show only selected Fields`, no longer shows it, and the next
    save of the record drops it.
*   **Keep the list a list.** Pass the items back with consecutive keys, with
    :php:`array_values()` after a filter, so the order is the one the backend
    shows.
*   **No order is promised** between several listeners. A listener that has
    to run after another one says so with the :php:`after` argument of
    :php:`#[AsEventListener]`.

Introduced in
=============

The event exists since version 2.1. The extensions that dispatch it describe
in their own changelogs when they started to, for example `academic_jobs 2.1
<https://docs.typo3.org/p/fgtclb/academic-jobs/main/en-us/Changelog/2.1/Feature-DispatchModifyTcaSelectFieldItemsEventInTypeItemsAndEmploymentTypeItems.html>`__
and `academic_persons 2.4
<https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Changelog/2.4/Feature-DispatchModifyTcaSelectFieldItemsEventInItemsProcFunc.html>`__.
