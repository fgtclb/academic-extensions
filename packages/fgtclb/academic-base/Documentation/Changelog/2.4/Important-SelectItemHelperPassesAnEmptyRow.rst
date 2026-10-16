..  _important-select-item-helper-passes-an-empty-row:

=====================================================
Important: The select item helper passes an empty row
=====================================================

Description
===========

:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` declared the
parameter :php:`row` as always present. In the backend form it is, because
TYPO3 hands the record being edited to every :php:`itemsProcFunc`.

:php:`GetSelectItemsForTcaManagedTableFieldMethodTrait::getSelectItemsForTcaManagedTableField()`
calls the :php:`itemsProcFunc` of a field outside the backend form, and the
job form of :composer:`fgtclb/academic-jobs` builds the options of its fields
:guilabel:`Job Type` and :guilabel:`Employment Type` with it. There is no
record there, and the helper passed no :php:`row` at all. A listener of the
event that read :php:`$parameters['row']` without a fallback raised the
warning `Undefined array key "row"` in the job form, and the job form failed
where warnings are turned into exceptions, as with the debug preset.

Two changes resolve that:

*   The helper passes :php:`row` as an empty array.
*   The event, and the item providers :php:`TypeItems` and
    :php:`EmploymentTypeItems` of :composer:`fgtclb/academic-jobs`, declare
    :php:`row` optional in their parameter shape, :php:`row?:`, so static
    analysis reports a listener that reads it without a fallback.

Impact
======

A listener that reads :php:`$parameters['row']` itself gets an empty array in
the job form now instead of a warning. A listener that reads a field of the
row, such as :php:`$parameters['row']['pid']`, still raises a warning there,
`Undefined array key "pid"`, because the empty row has no fields. Read the
row and every field of it with a fallback:

..  code-block:: php

    $row = $parameters['row'] ?? [];
    $pageId = (int)($row['pid'] ?? $parameters['effectivePid']);

The backend form passes the record as before. Nothing changes for a listener
that reads the row with a fallback already, unless it tells the job form apart
by a missing row, with :php:`isset($parameters['row'])`,
:php:`array_key_exists('row', $parameters)` or
:php:`($parameters['row'] ?? null) === null`. Such a check takes the backend
branch in the job form now. Check for an empty row instead:

..  code-block:: php

    $isJobForm = ($parameters['row'] ?? []) === [];

A project that runs static analysis over its listeners may see a new report
about the offset :php:`'row'` where a listener reads the row without a
fallback. That report points at the code that failed in the job form.

Affected Installations
======================

Installations with a listener of
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` that reads
:php:`$parameters['row']`, and that use the job form of
:composer:`fgtclb/academic-jobs` or call the helper in an own controller.

..  index:: Frontend, PHP-API, ext:academic_base
