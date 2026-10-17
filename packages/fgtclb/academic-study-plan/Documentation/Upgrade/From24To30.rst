..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 drops TYPO3 12, makes the three record tables of the study plan
workspace aware, ships no stylesheet any more, loads the script from the
template as an ES module, renders speaking classes and the shared icons of
:guilabel:`academic_base`, and splits the template into partials with a markup
contract of data attributes. Until the database is updated, the backend list of
the study plan categories and every study plan fail. Work through the sections
in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_study_plan` 3.0 declares no new dependency of its own. It
needs these packages. Upgrade them first or in the same deployment, in this
order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 requires. Version 2.4 required 1.x. Composer
    updates it on its own. A classic installation updates
    :guilabel:`environment_state_manager` before it activates
    :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update:

*   The database update, without which the backend list of the study plan
    categories and every study plan fail, see
    :ref:`upgrade-from-2-4-to-3-0-database`.
*   The rules of the site stylesheet for the study plan. From 3.0 on the site
    package styles the study plan, and without its rules a semester header
    shows both of its glyphs and the filter does not collapse, see
    :ref:`upgrade-from-2-4-to-3-0-stylesheet`.

The module paths on this page are those of TYPO3 13, where the upgrade runs.

..  _upgrade-from-2-4-to-3-0-typo3:

Update TYPO3 first
==================

Update TYPO3 to 13.4.35 or later while the site still runs 2.4, which supports
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

..  _upgrade-from-2-4-to-3-0-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~3.0.0' \
        'fgtclb/academic-study-plan':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_study_plan` there, in this
order. Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3`
instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-4-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

The tables :sql:`tx_academicstudyplan_domain_model_category`,
:sql:`tx_academicstudyplan_domain_model_semester` and
:sql:`tx_academicstudyplan_domain_model_module` are workspace aware now and get
the columns :sql:`t3ver_oid`, :sql:`t3ver_wsid`, :sql:`t3ver_state` and
:sql:`t3ver_stage` with an index. Until the columns are there, the backend
list of the study plan categories and the frontend of every study plan fail
with a database error. No stored record is changed. See
:ref:`important-1787907823`. The comparison also applies the changes of the
index definitions TYPO3 13.4.35 and 14.3.7 bring.

The column :sql:`credit_points` of the semester and module tables is no longer
declared in :file:`ext_tables.sql`. TYPO3 derives it from the TCA, a decimal
column with two decimals, as in 2.4. On MariaDB, MySQL and PostgreSQL the
comparison leaves it alone. An installation that redefined the column in an
:file:`ext_tables.sql` of its own removes that definition before the
comparison, because a declared column wins over the derived one. See
:ref:`important-ace-722-academic-study-plan-derived-column`.

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

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports what no longer
reaches the academic extensions. Run it on the staging copy right after the
package update. For this extension it reports, among others, a site that
depends on the alias set :yaml:`fgtclb/academic-study-plan-default`, and a site
that delivers this extension through its site set and a static template at
once:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also lists the template overrides of the
site that no longer match a file of this extension, or that are identical to
the shipped file:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_study_plan --site=<site identifier>

Each finding, and what to do about it, is described in the :ref:`upgrade check
<fgtclb/academic-base:upgrade-check>`
of :guilabel:`academic_base`.

..  _upgrade-from-2-4-to-3-0-site-sets:

Site sets
---------

A site that depends on :yaml:`fgtclb/academic-study-plan-default` keeps
working. The set is an alias of :yaml:`fgtclb/academic-study-plan`, which the
upgrade check reports as a notice and which goes away in version 4.0. Depend
on :yaml:`fgtclb/academic-study-plan` instead, see
:ref:`configuration-components`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-stylesheet:

Site stylesheet
---------------

The study plan ships no stylesheet any more. The file
:file:`EXT:academic_study_plan/Resources/Public/Css/frontend/academic-study-plan.css`
of 2.4 is removed, and the page loads no stylesheet for the element. Style it
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

The rules 2.4 shipped live on as an example, the stylesheet of the development
instances of the academic extensions. Copy what the site needs, or the rules of
the stylesheet the installation loaded so far, and adapt them to the theme. See
:ref:`breaking-study-plan-ships-no-stylesheet`, :ref:`styling` and the example
in :ref:`styling-example`.

..  _upgrade-from-2-4-to-3-0-classes:

Speaking classes
----------------

The templates carry speaking classes with the prefix `ace-` now, instead of
the classes of the theme they were developed with. The content element keeps
`academic-study-plan`, but loses `container`. The filter, the semesters, the
modules and the dialog carry the classes the table of
:ref:`breaking-study-plan-speaking-frontend-classes` lists, the grid classes
`row` and `col` stay. Move the selectors of the site stylesheet to the new
classes. How the markup of the study plan is built up is described in
:ref:`styling-markup`, the class system of all academic extensions in the
:ref:`Styling chapter of academic_base <fgtclb/academic-base:styling>`.

..  _upgrade-from-2-4-to-3-0-icons:

Icons
-----

The icons of the study plan are renamed, and the three controls of the
frontend are the shared action icons of :guilabel:`academic_base` now:

..  list-table::
    :header-rows: 1

    *   -   2.4 identifier
        -   3.0 identifier
    *   -   `academic-study-plan`
        -   `tx-academicstudyplan-plugin-study-plan`
    *   -   `academic-study-plan-category`
        -   `tx-academicstudyplan-record-category`
    *   -   `academic-study-plan-semester`
        -   `tx-academicstudyplan-record-semester`
    *   -   `academic-study-plan-module`
        -   `tx-academicstudyplan-record-module`
    *   -   `academic-study-plan-plus`
        -   `tx-academicbase-action-expand`
    *   -   `academic-study-plan-minus`
        -   `tx-academicbase-action-collapse`
    *   -   `academic-study-plan-close`
        -   `tx-academicbase-action-close`

Replace the previous identifiers in TCA overrides, page TSconfig, templates and
selectors such as :css:`.icon-academic-study-plan-plus`. A site package that
replaced a control registers its replacement under the 3.0 identifier in its
:file:`Configuration/FrontendIcons.php`, not in :file:`Configuration/Icons.php`,
and depends on :guilabel:`academic_base`. The replacement then shows in every
academic extension that renders the icon. See
:ref:`breaking-study-plan-control-icons-moved-to-the-frontend-icon-registry`
and :ref:`templates-glyphs`.

..  _upgrade-from-2-4-to-3-0-assets:

The script
----------

The script of the study plan is an ES module now, which the template of the
content element loads with :html:`<f:asset.module>`, and only on a page that
renders a study plan. The TypoScript of the extension no longer adds it, or
the stylesheet, to the page. Remove own :typoscript:`includeCSS` or
:typoscript:`includeJSFooter` entries for the files of 2.4 below
:file:`frontend/`. A classic script tag does not run the module. See
:ref:`breaking-frontend-assets-are-es-modules`.

An installation that overrode the template only to leave out the script, or to
bring its own, sets :yaml:`plugin.tx_academicstudyplan.assets.js` to off
instead and drops the copy, see :ref:`asset-switches`.

..  _upgrade-from-2-4-to-3-0-template-overrides:

Template overrides
------------------

One template file changed in 3.0,
:file:`Resources/Private/Frontend/Default/Templates/AcademicStudyPlan.html`,
and four partials are new. A copy of the 2.4 template in a site package keeps
rendering, but misses these changes. Compare it with the 3.0 files, or better,
replace it with an override of the one partial it is really about. The paths
below are relative to :file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
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

The script still finds the parts of an override by the class names of 2.4 when
it carries none of the data attributes, but that fallback is deprecated and is
removed in version 4.0. Add the attributes on the elements
:ref:`templates-attributes` names. See
:ref:`deprecation-study-plan-class-selectors`.

The upgrade check with the extension key and the site lists the overrides of a
site that no longer match a file of the extension, see
:ref:`upgrade-from-2-4-to-3-0-upgrade-check`.

..  _upgrade-from-2-4-to-3-0-code:

Adapt your code
===============

..  _upgrade-from-2-4-to-3-0-own-script:

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

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The backend list of the study plan categories opens, and a translated
    study plan renders. Both fail while the workspace columns are missing.
*   Existing study plans render with their semesters, modules and credit
    points. The new content element wizard offers the element as
    :guilabel:`Study Plan` now, with a new description, see
    :ref:`important-content-element-titles-and-descriptions`.
*   The category, semester and module records show icons in the text colour of
    the backend, also in a dark colour scheme, see
    :ref:`important-study-plan-record-icons-follow-the-colour-scheme`.
*   A semester header shows one glyph, the filter highlights the modules of a
    category, and a module opens its dialog. The browser loads the module
    :js:`@fgtclb/academic-study-plan/frontend/academic-study-plan.js` only
    on pages with a study plan, and no stylesheet of the extension.
*   On an installation that uses workspaces, the live site no longer shows
    semesters, modules or categories that only exist as a draft in a
    workspace, and a workspace preview shows the workspace state, see
    :ref:`important-1787924557`.
*   The upgrade check exits with ``0``, without the extension key and with
    it for every site that renders a study plan, see
    :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.
