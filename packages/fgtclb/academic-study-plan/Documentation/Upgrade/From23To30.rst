..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_study_plan` 2.3 straight
to 3.0, without the step to 2.4. Version 2.4 delivers the page TSconfig per
component, moves the configuration files, stores decimal credit points and
reads the label overrides from the documented path. Version 3.0 drops TYPO3 12,
makes the record tables of the study plan workspace aware, ships no stylesheet,
loads the script from the template as an ES module, renders speaking classes
and the shared icons of :guilabel:`academic_base`, and splits the template into
partials with a markup contract of data attributes. The page leaves out two
states of 2.4 that 3.0 replaces: the asset paths below :file:`frontend/`, see
:ref:`breaking-frontend-assets-are-built`, and the control icon identifiers of
2.4, see :ref:`important-study-plan-controls-are-visible`. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_study_plan` 3.0 declares no new dependency of its own. It
needs these packages. Upgrade them first or in the same deployment, in this
order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update:

*   The database update, without which the backend list of the study plan
    categories and every study plan fail, see
    :ref:`upgrade-from-2-3-to-3-0-database`.
*   The page TSconfig of the content element, on every site that selects the
    page TSconfig of version 2.3. The content element is not offered to
    editors until it is done, see :ref:`upgrade-from-2-3-to-3-0-page-tsconfig`.
*   The rules of the site stylesheet for the study plan. Without them a
    semester header shows both of its glyphs and the filter does not
    collapse, see :ref:`upgrade-from-2-3-to-3-0-stylesheet`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-3-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.3, which supports
TYPO3 13.4. Version 3.0 no longer installs on TYPO3 12, and Composer and the
Extension Manager refuse it next to an older patch release of TYPO3 13.

Stay on TYPO3 13 until the upgrade is complete when the installation also has
:guilabel:`academic_jobs`, :guilabel:`academic_persons`,
:guilabel:`academic_persons_edit` or :guilabel:`academic_projects`. Their
content element wizards read the column :sql:`tt_content.list_type`, which
TYPO3 14 removes, so they only run on TYPO3 13. Move to TYPO3 14.3.7 or later
once they have run.

See :ref:`breaking-removed-typo3-v12-support` and
:ref:`important-minimum-typo3-versions`.

..  _upgrade-from-2-3-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-study-plan':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_study_plan` there, in this
order. Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3`
instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

An installation that redefined :sql:`credit_points` in an
:file:`ext_tables.sql` of its own removes that definition first, because a
declared column wins over the derived one. A TCA override that only adds
:php:`'format' => 'decimal'` to the field can go. Then update the database:

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

Two changes reach the tables of the study plan, and no stored record is
changed:

*   The column :sql:`credit_points` of the tables
    :sql:`tx_academicstudyplan_domain_model_semester` and
    :sql:`tx_academicstudyplan_domain_model_module` changes from
    :sql:`int(11)` to a decimal column with two decimals. TYPO3 derives it
    from the TCA now. The change keeps every stored value, and editors can
    enter a value such as 2.5 afterwards, which the integer column cannot hold
    on MariaDB, MySQL and PostgreSQL. See
    :ref:`important-study-plan-credit-points-are-decimal` and
    :ref:`important-ace-722-academic-study-plan-derived-column`.
*   The tables :sql:`tx_academicstudyplan_domain_model_category`,
    :sql:`tx_academicstudyplan_domain_model_semester` and
    :sql:`tx_academicstudyplan_domain_model_module` are workspace aware and get
    the columns :sql:`t3ver_oid`, :sql:`t3ver_wsid`, :sql:`t3ver_state` and
    :sql:`t3ver_stage` with an index. Until they are there, the backend list of
    the study plan categories and the frontend of every study plan fail with a
    database error. See :ref:`important-1787907823`.

The comparison also applies the changes of the index definitions TYPO3
13.4.35 and 14.3.7 bring.

On SQLite two things differ:

*   :bash:`vendor/bin/typo3 extension:setup` can create the index over
    :sql:`t3ver_oid` and :sql:`t3ver_wsid` without adding the four columns,
    and reports success. Check afterwards that all three tables carry the four
    columns, and take the schema from a database built with the new state
    where they are missing.
*   The database analyzer can report "table … already exists" and "no such
    table: __temp__…" for the semester and module tables, because both changes
    rebuild those tables in one run. The tables end up complete with every
    value kept, and a second comparison offers nothing more.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

The structure of the configuration is described in
:ref:`breaking-site-sets-and-static-templates-restructured`. The deprecated
static template path is described in
:ref:`deprecation-legacy-static-template-path`.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. Run it on the staging copy right after the
package update. For this extension it reports, among others, a page that still
selects the page TSconfig file of 2.3, an import of a file that moved, a site
that depends on the alias set :yaml:`fgtclb/academic-study-plan-default`, and
a site that delivers this extension through its site set and a static
template at once:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also lists the template overrides of the
site that no longer match a file of this extension, or that are identical to
the shipped file:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_study_plan --site=<site identifier>

Each finding, and what to do about it, is described in the `upgrade check
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/UpgradeCheck/Index.html>`__
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-page-tsconfig:

Page TSconfig of the content element
------------------------------------

The content element is hidden for the whole installation, as it was in 2.3,
and the page TSconfig of its component brings it back. The file version 2.3
registered for the page field :guilabel:`Include static Page TSconfig (from
extensions)` is gone, and the value a page record stored for it is not
rewritten. It simply stops resolving, without a message, and the content
element is no longer offered on that page tree.

For a site that does not depend on the site set of this extension, edit the
page record of every site root that selected
:guilabel:`Academic StudyPlan (Default) (academic_study_plan)`, tab
:guilabel:`Resources`, field :guilabel:`Include static Page TSconfig (from
extensions)`. Remove the old entry and select :guilabel:`Academic Study Plan:
All components (academic_study_plan)` instead. This query lists the page
records that still store the old value:

..  code-block:: sql

    SELECT uid, pid, title FROM pages
    WHERE tsconfig_includes LIKE '%academic_study_plan/Configuration/TsConfig/Default.tsconfig%';

See :ref:`important-1787141400` as well.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
`its changelog entry <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Changelog/2.4/Important-HiddenContentTypeStaysSelectable.html>`__.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-3-0-static-template:

Static template
---------------

A :sql:`sys_template` record that selected :guilabel:`Academic StudyPlan
(Default)` in its field :guilabel:`Include TypoScript sets` on the tab
:guilabel:`Advanced Options` keeps delivering the content element. The entry
is listed as :guilabel:`Academic Study Plan: Path up to 2.3 (deprecated, use
All components) (academic_study_plan)` now and is removed in version 4.0.
Replace it with :guilabel:`Academic Study Plan: All components
(academic_study_plan)`.

..  _upgrade-from-2-3-to-3-0-site-sets:

Site sets
---------

A site that depends on :yaml:`fgtclb/academic-study-plan-default` keeps
working. The set is an alias of the aggregate :yaml:`fgtclb/academic-study-plan`
now, which the upgrade check reports as a notice and which goes away in version
4.0. Depend on :yaml:`fgtclb/academic-study-plan` instead, see
:ref:`configuration-components`.

A site uses the site set or the static template, not both. With both, the
static template sets the constants of the extension back to their defaults
after the site settings, see :ref:`one-mechanism-per-site`.

..  _upgrade-from-2-3-to-3-0-imports:

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
    *   -   `EXT:academic_study_plan/Configuration/TsConfig/Default.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`
    *   -   `EXT:academic_study_plan/Configuration/TsConfig/Includes/academic-study-plan.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`
    *   -   `EXT:academic_study_plan/Configuration/Sets/AcademicStudyPlan/setup.typoscript`
        -   `EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`
    *   -   `EXT:academic_study_plan/Configuration/Sets/AcademicStudyPlan/page.tsconfig`
        -   `EXT:academic_study_plan/Configuration/TSconfig/ContentElement/page.tsconfig`

Remove an import of
`EXT:academic_study_plan/Configuration/TypoScript/Includes/Page.typoscript`
without a replacement. It added the stylesheet and the script to every page.
In 3.0 there is no stylesheet, and the template loads the script itself.

The file :file:`Configuration/TypoScript/Default/setup.typoscript` still exists.
It is deprecated, keeps delivering the content element and is removed in
version 4.0. Replace an import of it with
`EXT:academic_study_plan/Configuration/TypoScript/ContentElement/setup.typoscript`.

The setup reads constants now. Import
`EXT:academic_study_plan/Configuration/TypoScript/ContentElement/constants.typoscript`
into the constants of the same template, or the content element cannot find its
template. That also applies to a site package that keeps importing the
deprecated :file:`Configuration/TypoScript/Default/setup.typoscript`: it imports
the :file:`constants.typoscript` of that folder as well.

..  _upgrade-from-2-3-to-3-0-template-paths:

Template paths
--------------

The Fluid root paths of the content element are constants now:
:typoscript:`plugin.tx_academicstudyplan.view.templateRootPath`,
:typoscript:`partialRootPath` and :typoscript:`layoutRootPath`. An override of
:typoscript:`tt_content.academic_study_plan.templateRootPaths` keeps working.
Setting the constants is the shorter way.

..  _upgrade-from-2-3-to-3-0-label-overrides:

Label overrides
---------------

Label overrides are read from :typoscript:`plugin.tx_academicstudyplan`, no
longer from :typoscript:`plugin.tx_academic_study_plan`. Move them:

..  code-block:: typoscript

    # Before
    plugin.tx_academic_study_plan._LOCAL_LANG.default.credits = ECTS

    # After
    plugin.tx_academicstudyplan._LOCAL_LANG.default.credits = ECTS

See :ref:`important-label-overrides-use-the-documented-path` and the list of
labels in :ref:`configuration-labels`.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-3-0-stylesheet:

Site stylesheet
---------------

The study plan ships no stylesheet any more. The file
:file:`EXT:academic_study_plan/Resources/Public/Css/academic-study-plan.css`
of 2.3 is removed, and the page loads no stylesheet for the element. Style it
in the site package. Three parts of the element do not work without rules of
the site:

*   A semester header shows the expand and the collapse glyph at the same
    time. The stylesheet shows one of them.
*   The collapsible filter stays on screen while it is collapsed whenever a
    rule of the site gives the filter list a :css:`display`. The stylesheet
    needs :css:`.ace-filter .ace-list[hidden] { display: none }`.
*   The accordion of a narrow viewport and the highlighting of a category have
    no visible effect. The script only writes the classes :html:`open` and
    :html:`highlighted`.

The stylesheet of the development instances of the academic extensions carries
the rules the extension shipped, adapted to the 3.0 markup. Copy what the site
needs and adapt it to the theme. See
:ref:`breaking-study-plan-ships-no-stylesheet`, :ref:`styling` and the example
in :ref:`styling-example`.

..  _upgrade-from-2-3-to-3-0-classes:

Speaking classes and the layout
-------------------------------

*   The templates carry speaking classes with the prefix `ace-` now, instead
    of the classes of the theme they were developed with. The content element
    keeps `academic-study-plan`, but loses `container`. The filter, the
    semesters, the modules and the dialog carry the classes the table of
    :ref:`breaking-study-plan-speaking-frontend-classes` lists, the grid
    classes `row` and `col` stay. Move the selectors of the site stylesheet to
    the new classes. How the markup of the study plan is built up is described
    in :ref:`styling-markup`, the class system of all academic extensions in
    the `Styling chapter of academic_base <https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
*   The content element renders through the :file:`Default` layout of
    :guilabel:`fluid_styled_content`. Its header sits above the
    :html:`.academic-study-plan` wrapper, inside the frame of the layout. A
    selector such as :css:`.academic-study-plan header` becomes
    :css:`.frame-type-academic_study_plan header`, see
    :ref:`important-study-plan-renders-through-the-default-layout`.
*   A category title is rendered as text. A site that put markup into a
    category title to style its filter button styles the button of the filter
    instead, see :ref:`important-study-plan-category-title-is-text`.

..  _upgrade-from-2-3-to-3-0-icons:

Icons
-----

The record icons of the study plan are renamed, and the three controls of the
frontend are the shared action icons of :guilabel:`academic_base`:

..  list-table::
    :header-rows: 1

    *   -   2.3 identifier
        -   3.0 identifier
    *   -   `academic-study-plan`
        -   `tx-academicstudyplan-plugin-study-plan`
    *   -   `academic-study-plan-category`
        -   `tx-academicstudyplan-record-category`
    *   -   `academic-study-plan-semester`
        -   `tx-academicstudyplan-record-semester`
    *   -   `academic-study-plan-module`
        -   `tx-academicstudyplan-record-module`
    *   -   `plus`
        -   `tx-academicbase-action-expand`
    *   -   `minus`
        -   `tx-academicbase-action-collapse`
    *   -   `close-button`
        -   `tx-academicbase-action-close`

The identifiers `plus`, `minus` and `close-button` of 2.3 were registered by
neither TYPO3 nor this extension, so 2.3 rendered the placeholder of a missing
icon in their place. A site package that registered icons under these names for
this element can drop them. Replace the other previous identifiers in TCA
overrides, page TSconfig, templates and selectors such as :css:`.icon-plus`. A
site package that wants another drawing for a control registers it under the 3.0
identifier in its :file:`Configuration/FrontendIcons.php`, not in
:file:`Configuration/Icons.php`, and depends on :guilabel:`academic_base`. The
replacement then shows in every academic extension that renders the icon. See
:ref:`breaking-study-plan-control-icons-moved-to-the-frontend-icon-registry` and
:ref:`templates-glyphs`.

..  _upgrade-from-2-3-to-3-0-assets:

The script
----------

The script of the study plan is an ES module now, which the template of the
content element loads with :html:`<f:asset.module>`, and only on a page that
renders a study plan. Remove own :typoscript:`includeCSS` or
:typoscript:`includeJSFooter` entries for
:file:`EXT:academic_study_plan/Resources/Public/Css/academic-study-plan.css`
and :file:`EXT:academic_study_plan/Resources/Public/JavaScript/academic-study-plan.js`.
The :file:`frontend/` paths 2.4 used in between are gone as well, so there is
nothing to move them to. A classic script tag does not run the module. See
:ref:`breaking-frontend-assets-are-es-modules`.

An installation that overrode the template only to leave out the script, or to
bring its own, sets :yaml:`plugin.tx_academicstudyplan.assets.js` to off
instead and drops the copy, see :ref:`asset-switches`.

..  _upgrade-from-2-3-to-3-0-template-overrides:

Template overrides
------------------

The template
:file:`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html`
changed in 2.4 and in 3.0, and four partials are new in 3.0. A copy of the 2.3
template in a site package keeps rendering, but misses all of these changes.
Replace it with an override of the one partial it is really about, or start the
copy again from the 3.0 files. The paths below are relative to
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
        -   :ref:`important-study-plan-renders-through-the-default-layout`
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html` and its
            partials
        -   Translate the labels with
            :html:`extensionName: 'AcademicStudyPlan'`, which is what makes the
            label overrides of the previous step apply.
        -   :ref:`important-label-overrides-use-the-documented-path`
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
        -   Renders the filter, the semesters, the modules and the dialogs
            through the partials :file:`StudyPlan/Filter`,
            :file:`StudyPlan/Semester`, :file:`StudyPlan/Module` and
            :file:`StudyPlan/ModuleDialog`, and marks every part the script
            drives with a :html:`data-study-plan-*` attribute.
        -   :ref:`feature-study-plan-partials-and-data-attributes`
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
        -   Loads the script with :html:`<f:asset.module>` inside an
            :html:`<f:if>` on :html:`settings.assets.js`, and registers no
            stylesheet.
        -   :ref:`asset-switches`
    *   -   :file:`Frontend/Default/Partials/StudyPlan/Filter.html`
        -   Renders the item the script clones with the attribute
            :html:`hidden`, so its placeholder text never shows.
        -   :ref:`important-study-plan-filter-template-item-is-hidden`
    *   -   :file:`Frontend/Default/Partials/StudyPlan/Semester.html`,
            :file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html`
        -   Render the controls with :html:`<ab:icon>` of
            :guilabel:`academic_base` and the 3.0 identifiers, no longer with
            :html:`<core:icon>`.
        -   :ref:`templates-glyphs`
    *   -   :file:`Frontend/Default/Partials/StudyPlan/Semester.html`,
            :file:`Frontend/Default/Partials/StudyPlan/Module.html`,
            :file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html`
        -   Receive :html:`idPrefix` and start the dialog id with it, so a
            plan shown again through an "Insert records" element opens its own
            dialogs.
        -   :ref:`important-study-plan-dialog-ids-in-inserted-records`
    *   -   :file:`Frontend/Default/Partials/StudyPlan/Module.html`
        -   The hidden text of the module button names the note and the credit
            points of a semester only when the semester has them.
        -   :ref:`important-ace-836-academic-study-plan`

The script still finds the parts of an override by the class names of 2.3 when
it carries none of the data attributes, but that fallback is deprecated and is
removed in version 4.0. Add the attributes on the elements
:ref:`templates-attributes` names. See
:ref:`deprecation-study-plan-class-selectors`.

The upgrade check with the extension key and the site lists the overrides of a
site that no longer match a file of the extension, see
:ref:`upgrade-from-2-3-to-3-0-upgrade-check`.

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

..  _upgrade-from-2-3-to-3-0-own-script:

A script of your own
--------------------

An installation that drives the study plan with a script of its own, with
:yaml:`plugin.tx_academicstudyplan.assets.js` switched off or from a copy of
the template, takes the attribute :html:`hidden` off every filter item it
builds from the template item, as the shipped script does:

..  code-block:: javascript

    item.removeAttribute('hidden');

Without it, the filter shows no button at all. See
:ref:`important-study-plan-filter-template-item-is-hidden` and
:ref:`feature-study-plan-assets-can-be-switched-off`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The new content element wizard offers the element as
    :guilabel:`Study Plan` on every site that should offer it, with a new
    description, see :ref:`important-content-element-titles-and-descriptions`.
*   The backend list of the study plan categories opens, and a translated
    study plan renders. Both fail while the workspace columns are missing.
*   Existing study plans render, with their credit points unchanged. A
    semester or module accepts a credit point value such as 2.5 in the
    backend.
*   The study plan renders inside the frame of the :file:`Default` layout. The
    settings of the tab :guilabel:`Appearance` take effect now, and on an
    installation whose editors used that tab long ago, spacing, frames and the
    :guilabel:`Link to top` link may appear where they were chosen.
*   The category, semester and module records show icons in the text colour of
    the backend, also in a dark colour scheme, see
    :ref:`important-study-plan-record-icons-follow-the-colour-scheme`.
*   A semester header shows one glyph, the filter highlights the modules of a
    category, and a module opens its dialog. The browser loads the module
    :js:`@fgtclb/academic-study-plan/frontend/academic-study-plan.js` only on
    pages with a study plan, and no stylesheet of the extension.
*   On an installation that uses workspaces, the live site no longer shows
    semesters, modules or categories that only exist as a draft in a
    workspace, and a workspace preview shows the workspace state, see
    :ref:`important-1787924557`.
*   The upgrade check exits with ``0``, without the extension key and with
    it for every site that renders a study plan, see
    :ref:`upgrade-from-2-3-to-3-0-upgrade-check`.
