..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 cuts the configuration of the study plan into components, moves its
page TSconfig and its frontend assets, turns the credit points into a decimal
column and renders the content element through the :file:`Default` layout. The
page TSconfig entry decides whether editors can still select the content
element after the update. Work through the sections in order. Skip a step that
does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_study_plan` 2.4 declares no new dependency of its own. It
needs these packages. Upgrade them first or in the same deployment, in this
order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 2.4.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.

Plan the configuration step of the page TSconfig into the same deployment as
the package update. On every site that selects the page TSconfig of version
2.3, the content element is not offered to editors until it is done.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/academic-study-plan':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796669`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_study_plan` there, in this
order. Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3`
instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-database:

Update the database
===================

The column :sql:`credit_points` of the tables
:sql:`tx_academicstudyplan_domain_model_semester` and
:sql:`tx_academicstudyplan_domain_model_module` changes from :sql:`int(11)` to
:sql:`decimal(10,2) unsigned`. Run the database compare right after the package
update, before editors enter a decimal value, which the integer column cannot
hold on MariaDB, MySQL and PostgreSQL:

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or use :guilabel:`Admin Tools > Maintenance > Analyze Database Structure` and
apply the change of the column in both tables. The change rewrites both tables
and keeps every stored value. Nothing is added or dropped.

An installation that redefined the column in an :file:`ext_tables.sql` of its
own removes that definition before the compare, or the column stays as it is. A
TCA override that only adds :php:`'format' => 'decimal'` to the field can go.
See :ref:`important-study-plan-credit-points-are-decimal`.

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

All of this step is described in
:ref:`breaking-site-sets-and-static-templates-restructured`. The deprecated
static template path is described in
:ref:`deprecation-legacy-static-template-path`.

..  _upgrade-from-2-3-to-2-4-page-tsconfig:

Page TSconfig of the content element
------------------------------------

The content element is hidden for the whole installation, as it was in 2.3, and
the page TSconfig of its component brings it back. The file version 2.3
registered for the page field :guilabel:`Include static Page TSconfig (from
extensions)` is gone, and the value
a page record stored for it is not rewritten. It simply stops resolving, without
a message, and the content element is no longer offered on that page tree.

On TYPO3 12, and on TYPO3 13 for a site that does not depend on the site set
of this extension, edit the page record of every site root that selected
:guilabel:`Academic StudyPlan (Default) (academic_study_plan)`, tab
:guilabel:`Resources`, field :guilabel:`Include static Page TSconfig (from
extensions)`. Remove the old entry
and select :guilabel:`Academic Study Plan: All components
(academic_study_plan)` instead. This query lists the page records that still
store the old value:

..  code-block:: sql

    SELECT uid, pid, title FROM pages
    WHERE tsconfig_includes LIKE '%academic_study_plan/Configuration/TsConfig/Default.tsconfig%';

See :ref:`important-1787141400` as well.

An existing study plan opened on a page where the content element is not
enabled keeps its type. The field :guilabel:`Type` shows it followed by
:guilabel:`(not enabled on this page)`, and saving the record keeps it, as long
as :guilabel:`academic_base` 2.4 is installed. The warning of the Breaking entry
linked above, that saving such a record writes another type, no longer applies.
A study plan whose type was rewritten that way before the update is not
repaired. Its record history shows the change, and the type is corrected by
hand there.

..  _upgrade-from-2-3-to-2-4-static-template:

Static template
---------------

A :sql:`sys_template` record that selected :guilabel:`Academic StudyPlan
(Default)` in its field :guilabel:`Include TypoScript sets` on the tab
:guilabel:`Advanced Options` keeps delivering the content element. The entry
is listed as :guilabel:`Academic Study Plan: Path up to 2.3 (deprecated, use
All components) (academic_study_plan)` now and is removed in version 4.0.
Replace it with :guilabel:`Academic Study Plan: All components
(academic_study_plan)`. On TYPO3 12 the static template is the only way to
deliver the TypoScript of the content element.

..  _upgrade-from-2-3-to-2-4-site-sets:

Site sets
---------

On TYPO3 13, a site that depends on :yaml:`fgtclb/academic-study-plan-default`
needs no change. The set is an alias of the new aggregate
:yaml:`fgtclb/academic-study-plan` now, which can be named instead.

A site uses the site set or the static template, not both. With both, the
static template sets the three new template path constants back to their
defaults after the site settings, see :ref:`one-mechanism-per-site`.

..  _upgrade-from-2-3-to-2-4-imports:

Imports in a site package
-------------------------

An :typoscript:`@import` of a file that moved loads nothing and reports no
error. Replace the old paths:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_study_plan/Configuration/TypoScript/Includes/ContentElement.typoscript`
        -   `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`
    *   -   `EXT:academic_study_plan/Configuration/TypoScript/Includes/Page.typoscript`
        -   `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`
    *   -   `EXT:academic_study_plan/Configuration/TsConfig/Default.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`
    *   -   `EXT:academic_study_plan/Configuration/TsConfig/Includes/academic-study-plan.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`
    *   -   `EXT:academic_study_plan/Configuration/Sets/AcademicStudyPlan/setup.typoscript`
        -   `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`
    *   -   `EXT:academic_study_plan/Configuration/Sets/AcademicStudyPlan/page.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`

The file :file:`Configuration/TypoScript/Default/setup.typoscript` still exists.
It is deprecated, keeps delivering the content element and is removed in
version 4.0. Replace an import of it with
`EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`,
see :ref:`deprecation-legacy-static-template-path`.

The setup reads constants now. Import
`EXT:academic_study_plan/Configuration/TypoScript/ContentElement/constants.typoscript`
into the constants of the same template, or the content element cannot find its
template. That also applies to a site package that keeps importing the
deprecated :file:`Configuration/TypoScript/Default/setup.typoscript`: it imports
the :file:`constants.typoscript` of that folder as well.

..  _upgrade-from-2-3-to-2-4-template-paths:

Template paths
--------------

The Fluid root paths of the content element are constants now:
:typoscript:`plugin.tx_academicstudyplan.view.templateRootPath`,
:typoscript:`partialRootPath` and :typoscript:`layoutRootPath`. An override of
:typoscript:`tt_content.academic_study_plan.templateRootPaths` keeps working.
Setting the constants is the shorter way.

..  _upgrade-from-2-3-to-2-4-label-overrides:

Label overrides
---------------

Label overrides are read from :typoscript:`plugin.tx_academicstudyplan` now, no
longer from :typoscript:`plugin.tx_academic_study_plan`. Move them:

..  code-block:: typoscript

    # Before
    plugin.tx_academic_study_plan._LOCAL_LANG.default.credits = ECTS

    # After
    plugin.tx_academicstudyplan._LOCAL_LANG.default.credits = ECTS

See :ref:`important-label-overrides-use-the-documented-path` and the list of
labels in :ref:`configuration-labels`.

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-2-4-asset-paths:

Asset paths
-----------

The stylesheet and the script of the content element moved into a
:file:`frontend/` folder. The extension loads them from there itself. Only own
TypoScript, a site package or a template override that names one of the files
has to add the folder:

..  list-table::
    :header-rows: 1

    *   -   Old path
        -   New path
    *   -   `EXT:academic_study_plan/Resources/Public/Css/academic-study-plan.css`
        -   `EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css`
    *   -   `EXT:academic_study_plan/Resources/Public/JavaScript/academic-study-plan.js`
        -   `EXT:academic_study_plan/Resources/Public/JavaScript/frontend/academic-study-plan.js`

See :ref:`breaking-frontend-assets-are-built`.

..  _upgrade-from-2-3-to-2-4-template:

The template
------------

One template file changed in 2.4,
:file:`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html`. No
partial or layout of the extension changed, and none was removed. A copy of the
template in a site package keeps rendering, but misses these changes. Compare it
with the 2.4 file and take them over. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
        -   Renders through :html:`<f:layout name="Default" />` and wraps its
            markup in :html:`<f:section name="Main">`. It no longer renders the
            partial :file:`Header/All`, because the layout renders the header.
            A copy made only to add the layout can be dropped.
        -   :ref:`important-study-plan-renders-through-the-default-layout`
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
        -   Renders the icons with the identifiers `academic-study-plan-plus`,
            `academic-study-plan-minus` and `academic-study-plan-close`. The
            identifiers `plus`, `minus` and `close-button` of version 2.3 are
            registered by neither TYPO3 nor this extension.
        -   :ref:`important-study-plan-controls-are-visible`
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
        -   Translates the labels with
            :html:`extensionName: 'AcademicStudyPlan'`, which is what makes the
            label overrides of the previous step apply.
        -   :ref:`important-label-overrides-use-the-documented-path`
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
        -   The hidden text of the module button names the note and the credit
            points of a semester only when the semester has them.
        -   :ref:`important-ace-836-academic-study-plan`

..  _upgrade-from-2-3-to-2-4-site-stylesheets:

Site stylesheets
----------------

*   The header of the content element sits above the
    :html:`.academic-study-plan` wrapper now, inside the frame of the layout. A
    selector such as :css:`.academic-study-plan header` becomes
    :css:`.frame-type-academic_study_plan header`.
*   The icon markup carries the classes :html:`.icon-academic-study-plan-plus`
    and :html:`.icon-academic-study-plan-minus` instead of :html:`.icon-plus`
    and :html:`.icon-minus`. A site package that registered icons named
    `plus`, `minus` or `close-button` for this element can drop them.
*   A category title is rendered as text. A site that put markup into a
    category title to style its filter button styles :html:`.filter button`
    instead, see :ref:`important-study-plan-category-title-is-text`.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The new content element wizard offers :guilabel:`Academic Study Plan` on
    every site that should offer it.
*   Existing study plans render, with their credit points unchanged. A semester
    or module accepts a credit point value such as 2.5 in the backend.
*   The study plan renders inside the frame of the :file:`Default` layout. The
    settings of the tab :guilabel:`Appearance` take effect now, and on an
    installation whose editors used that tab long ago, spacing, frames and the
    :guilabel:`Link to top` link may appear where they were chosen.
*   The plus and minus markers of the semesters on a narrow viewport and the
    close button of a module dialog are visible.
*   The browser loads the stylesheet and the script of the study plan from the
    :file:`frontend/` folders without an error.
