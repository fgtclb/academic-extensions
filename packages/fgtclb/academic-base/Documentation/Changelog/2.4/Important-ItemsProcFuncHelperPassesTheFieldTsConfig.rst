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

FormEngine passes something narrower there: the content of the field's
:typoscript:`itemsProcFunc.` page TSconfig, with that key already stripped, and
:php:`null` when the field has none. The helper now passes the same.

..  code-block:: typoscript

    # The part a handler receives in $parameters['TSconfig']
    TCEFORM.tx_academicjobs_domain_model_job.type.itemsProcFunc {
        mySetting = 1
    }

Impact
======

A handler that reads a setting of its own from :php:`$parameters['TSconfig']`
found nothing when it was called through this helper, and works now. No handler
of these extensions read that entry before, so nothing this package ships
changes behaviour.

A project handler that compensated for the old shape, by descending through
:php:`$parameters['TSconfig']['TCEFORM.']` itself, has to be adjusted: it now
receives the subtree directly, or :php:`null`. A handler that also serves the
backend form already expects that shape, because FormEngine always passed it.

..  code-block:: php

    // Before, only through this helper
    $mySetting = $parameters['TSconfig']['TCEFORM.'][$table . '.'][$field . '.']['itemsProcFunc.']['mySetting'] ?? null;

    // After, the same as in FormEngine
    $mySetting = $parameters['TSconfig']['mySetting'] ?? null;

Affected Installations
======================

Installations with an own :php:`itemsProcFunc` handler that is reached through
this helper and reads page TSconfig. The helper is used by
:composer:`fgtclb/academic-jobs` for the job form of its frontend plugin.

..  index:: Backend, PHP-API, TSConfig, ext:academic_base
