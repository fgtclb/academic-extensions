..  _important-items-proc-func-helper-passes-the-field-ts-config:

===========================================================
Important: The select item helper passes the field TSconfig
===========================================================

Description
===========

:php:`GetSelectItemsForTcaManagedTableFieldMethodTrait::getSelectItemsForTcaManagedTableField()`
resolves the items of a TCA select outside FormEngine, by calling the field's
:php:`itemsProcFunc` handler with a parameter array it assembles itself. That
array is meant to look like the one FormEngine passes, and in one entry it did
not: :php:`$parameters['TSconfig']` held the complete page TSconfig tree of the
current page.

FormEngine passes something narrower there - the content of the field's
:typoscript:`itemsProcFunc.` page TSconfig, with that key already stripped, and
:php:`null` when the field has none. The helper now passes the same.

Impact
======

A handler that reads a setting of its own from :php:`$parameters['TSconfig']`
found nothing when it was called through this helper, and works now. No handler
of these extensions read that entry before, so nothing this package ships
changes behaviour.

A project handler that compensated for the old shape - by descending through
:php:`$parameters['TSconfig']['TCEFORM.']` itself - has to be adjusted: it now
receives the subtree directly, or :php:`null`.

Affected Installations
======================

Installations with an own :php:`itemsProcFunc` handler that is reached through
this helper and reads page TSconfig. The helper is used by
:composer:`fgtclb/academic-jobs` for the job form of its frontend plugin.

..  index:: Backend, PHP-API, TSConfig, ext:academic_base
