..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 moves the environment and state manager of this extension into an
extension of its own, restructures the page TSconfig of the content element
group :guilabel:`Academic` and keeps the stored type of an academic content
element selectable on a page that does not offer it. Work through the sections
in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_base` 2.4 needs one new package:

*   :composer:`fgtclb/environment-state-manager` 1.x, the extension
    :guilabel:`environment_state_manager`. It is declared in
    :file:`composer.json` as `^1.0` and in :file:`ext_emconf.php` as
    `1.0.0-1.99.99`, see :ref:`important-ace-254-academic-base`. Composer
    installs it on its own. A classic installation installs it before it
    activates :guilabel:`academic_base` 2.4.

Every other academic extension depends on this one. Upgrade it in the same
deployment as all academic extensions of the installation, and on a classic
installation before them.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0'

Composer installs :composer:`fgtclb/environment-state-manager` with it, and
version 1.2 or later of the Composer plugin
:composer:`typo3/class-alias-loader`, which keeps one deprecated class name
resolving, see
:ref:`upgrade-from-2-3-to-2-4-environment-state-manager`. The TYPO3 core
requires that plugin itself, so it is already allowed in every Composer based
TYPO3 installation. A project that requires branch ``2`` as ``2.x-dev`` keeps
an inline alias such as ``2.x-dev as 2.4.x-dev`` working, see
:ref:`important-1790796659`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, because
:guilabel:`academic_base` 2.4 cannot be activated without it. Then update
:guilabel:`academic_base` there, and after it every other academic extension of
the installation. Run the commands of this page with
:bash:`typo3/sysext/core/bin/typo3` instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-3-to-2-4-page-tsconfig:

Page TSconfig imports
---------------------

The page TSconfig that labels the content element group :guilabel:`Academic`
moved, and the directory :file:`Configuration/TsConfig/` is spelled
:file:`Configuration/TSconfig/` now. An :typoscript:`@import` of an old path
loads nothing and reports no error, so search the site package for the old
paths and replace them:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_base/Configuration/TsConfig/Wizards/NewContentElement.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig`
    *   -   `EXT:academic_base/Configuration/TsConfig/Wizards/*.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/Full/page.tsconfig`
    *   -   `EXT:academic_base/Configuration/Sets/AcademicBaseCTypeGroup/page.tsconfig`
        -   `EXT:academic_base/Configuration/TSconfig/CTypeGroup/page.tsconfig`

An import that only served to label the group can be removed instead. The
:file:`Configuration/page.tsconfig` of this extension delivers the label to
every installation, on TYPO3 12 and TYPO3 13, whatever the site configuration
says.

Site configurations need no change. The set
:yaml:`fgtclb/academic-base-ctype-group` keeps its name, and the new aggregate
set :yaml:`fgtclb/academic-base` depends on it. Two entries for the page field
:guilabel:`Include static Page TSconfig (from extensions)` on the tab
:guilabel:`Resources` are new as well and need not be selected. See
:ref:`breaking-site-sets-and-page-tsconfig-restructured`.

..  _upgrade-from-2-3-to-2-4-select-item-labels:

Label overrides of select items
-------------------------------

The items of a TCA select field that a frontend form of an academic extension
offers, the job type of the job form of :guilabel:`academic_jobs` for example,
read their label overrides from the path without underscores now. Move an
override from the extension key to the extension name, or to the path of the
one plugin it is meant for:

..  code-block:: typoscript

    # Before
    plugin.tx_academic_jobs._LOCAL_LANG.default.tx_academicjobs_domain_model_job.jobtype.job = Position

    # After
    plugin.tx_academicjobs._LOCAL_LANG.default.tx_academicjobs_domain_model_job.jobtype.job = Position

See :ref:`important-select-item-labels-read-the-documented-path`.

..  _upgrade-from-2-3-to-2-4-code:

Adapt your code
===============

..  _upgrade-from-2-3-to-2-4-environment-state-manager:

The environment and state manager
---------------------------------

The classes of the namespaces :php:`\FGTCLB\AcademicBase\Environment`,
:php:`\FGTCLB\AcademicBase\Core12\Environment` and
:php:`\FGTCLB\AcademicBase\Core13\Environment` are deprecated and are removed
in version 3.0. They were marked :php:`@internal` and raise no deprecation
notice. Code of a project that uses them:

#.  Requires :composer:`fgtclb/environment-state-manager` in its own
    :file:`composer.json`, or :guilabel:`environment_state_manager` in its own
    :file:`ext_emconf.php`.
#.  Replaces the namespace prefix with :php:`\FGTCLB\EnvironmentStateManager`,
    and :php:`Core12\Environment` and :php:`Core13\Environment` with
    :php:`Core12` and :php:`Core13`. The class names stay the same.
#.  Types against the :php:`ExtendedStateInterface` of the running core
    version where it calls the TypoScriptFrontendController accessors of a
    state, which :php:`StateInterface` no longer declares.

See :ref:`deprecation-1783574951`.

..  _upgrade-from-2-3-to-2-4-items-proc-func:

Own handlers of a TCA select field
----------------------------------

An :php:`itemsProcFunc` handler that is reached through
:php:`GetSelectItemsForTcaManagedTableFieldMethodTrait` receives the same
:php:`$parameters['TSconfig']` as in the backend form now: the page TSconfig
below :typoscript:`itemsProcFunc.` of the field, or :php:`null`. A handler that
descended through the complete page TSconfig tree to compensate reads the
setting directly instead:

..  code-block:: php

    // Before, only through the trait
    $mySetting = $parameters['TSconfig']['TCEFORM.'][$table . '.'][$field . '.']['itemsProcFunc.']['mySetting'] ?? null;

    // After, the same as in the backend form
    $mySetting = $parameters['TSconfig']['mySetting'] ?? null;

The parameters of
:php:`\FGTCLB\AcademicBase\Event\ModifyTcaSelectFieldItemsEvent` declare that
entry as nullable for the same reason. See
:ref:`important-items-proc-func-helper-passes-the-field-ts-config`.

The trait passes an empty :php:`$parameters['row']` now, because there is no
record outside the backend form, and the event declares :php:`row` optional.
A listener of the event reads the row, and every field of it, with a
fallback:

..  code-block:: php

    $row = $parameters['row'] ?? [];
    $pageId = (int)($row['pid'] ?? $parameters['effectivePid']);

See :ref:`important-select-item-helper-passes-an-empty-row`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The new content element wizard shows the group :guilabel:`Academic` with
    its label on every site.
*   On a classic installation, :guilabel:`environment_state_manager` is listed
    as active in :guilabel:`Admin Tools > Extensions`.
*   Open an existing academic content element on a page where its component is
    not enabled. The field :guilabel:`Type` shows the stored type followed by
    :guilabel:`(not enabled on this page)`, and saving the record keeps it.
    This holds for the content elements of every academic extension, as long
    as :guilabel:`academic_base` 2.4 is installed.
*   A content element whose type was rewritten before the update is not
    repaired, because its former type is not stored in the record. Its record
    history shows the change for as long as the history is kept. Correct the
    type by hand there. See :ref:`important-ace-666-academic-base`.
*   A search of the site package and of own extensions for
    `TsConfig/Wizards`, `Sets/AcademicBaseCTypeGroup` and
    `AcademicBase\Environment` finds nothing any more.
