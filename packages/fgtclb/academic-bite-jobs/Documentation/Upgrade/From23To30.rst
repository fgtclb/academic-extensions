..  _upgrade-from-2-3-to-3-0:

=========================
Upgrading from 2.3 to 3.0
=========================

This page takes an installation of :guilabel:`academic_bite_jobs` 2.3 straight
to 3.0, without the step to 2.4. Version 3.0 drops TYPO3 12, makes the
controller of the job list final, renders the job list with speaking classes
and gives the content element a new icon. From 2.4 it takes over the
configuration per component, the content element that stays hidden until a site
enables it, and the upgrade wizard for the stored plugin settings of older job
lists. It changes no database table of this extension. Version 3.0 replaces no
step of 2.4 for this extension, so the page leaves none out. Work through the
sections in order. Skip a step that does not apply to the installation.

..  _upgrade-from-2-3-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_bite_jobs` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, new since 2.4, which
    :guilabel:`academic_base` 3.0 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` 2.x
    before it activates :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

The extension declares the system extension :guilabel:`install` as a
dependency since 2.4, for its upgrade wizard. TYPO3 keeps that extension
active in every installation, so this needs no action.

These changes have to ship in the same deployment as the update:

*   A subclass of the controller of the job list goes, or the site fails with
    a fatal error as soon as the update is deployed, see
    :ref:`upgrade-from-2-3-to-3-0-code`.
*   A site without the site set includes the page TSconfig of the component,
    or the content element is not offered for new content, see
    :ref:`upgrade-from-2-3-to-3-0-components`.

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
        'fgtclb/academic-bite-jobs':'~3.0.0'

On a classic installation, install :guilabel:`environment_state_manager` 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_bite_jobs` there, in this
order. Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3`
instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-3-0-database:

Update the database
===================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

Or apply the changes in :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`.

The comparison changes no table of this extension. It applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring, and the columns other
academic extensions add since 2.4. The upgrade wizard of the next section needs
them.

..  _upgrade-from-2-3-to-3-0-wizards:

Run the upgrade wizards
=======================

#.  ``academicBiteJobs_listViewFlexFormUpgradeWizard``, :guilabel:`Migrate the
    plugin settings of academic_bite_jobs job lists stored before 2.1.`, is new
    since 2.4. It rewrites the stored view of every job list content element,
    hidden ones included, from `ListView`, `CardView` or `TableView`, or an
    empty or unknown value, to `List`, `Card` or `Table`. It also removes the
    settings `settings.jobs.groupBy` and `settings.jobs.custom.zuordnung`,
    which version 2.1 dropped from the plugin but left in the stored records.
    A stored grouping setting keeps a list grouped by a field the job postings
    no longer carry until the wizard has run. Run it in
    :guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or on the command line:

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:run academicBiteJobs_listViewFlexFormUpgradeWizard

    Content elements created with version 1.x are plugins stored with the
    content type :sql:`list`. Neither this version nor the wizard renders or
    migrates them. See :ref:`important-1789660801`.

A wizard that TYPO3 recorded as done is offered again once it is marked
undone. TYPO3 13 marks a wizard undone with
``vendor/bin/typo3 upgrade:mark:undone <identifier>``.

..  _upgrade-from-2-3-to-3-0-configuration:

Adapt the configuration
=======================

The TypoScript and the page TSconfig are delivered per content element since
2.4, and the job list content element is hidden in the whole installation until
the page TSconfig of its component is included. See
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports the static
templates, page TSconfig imports and site sets that no longer reach an academic
extension, and an XCLASS of the controller. Run it on the staging copy right
after the package update, and again once the steps of this section are done:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the template overrides of
the site with the templates this extension ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_bite_jobs --site=<site identifier>

Each finding, and what to do about it, is described in the :ref:`upgrade check
<fgtclb/academic-base:upgrade-check>`
of :guilabel:`academic_base`.

..  _upgrade-from-2-3-to-3-0-components:

Include the components
----------------------

*   **Site set.** A site that depends on the set `fgtclb/academic-bite-jobs`
    needs no change. The set keeps its name and brings the page TSconfig
    along.
*   **Static template.** The stored entry of 2.3 keeps working as
    :guilabel:`Academic Bite Jobs: Path up to 2.3 (deprecated, use All
    components) (academic_bite_jobs)`, which is removed in version 4.0. Replace
    it with :guilabel:`Academic Bite Jobs: All components (academic_bite_jobs)`
    in the field :guilabel:`Include TypoScript sets` on the tab
    :guilabel:`Advanced Options` of the template record, see
    :ref:`deprecation-legacy-static-template-path`.
*   **Page TSconfig.** A site without the site set adds
    :guilabel:`Academic Bite Jobs: All components (academic_bite_jobs)` in the
    field :guilabel:`Include static Page TSconfig (from extensions)` on the tab
    :guilabel:`Resources` of its root page, see :ref:`static-pagetsconfig`.
    Without it the content element is not offered for new content.
*   Use one of the two mechanisms per site, see
    :ref:`one-mechanism-per-site`.

Until a component is included, an existing content element of it keeps
rendering and keeps its type: :guilabel:`academic_base` shows the stored type
in the field :guilabel:`Type` followed by
:guilabel:`(not enabled on this page)`, and saving keeps it. The warning of the
2.4 changelog entry that saving such a record rewrites its type describes the
state before a fix that :guilabel:`academic_base` 2.4 carries as well, see
:ref:`its changelog entry <fgtclb/academic-base:important-ace-666-academic-base>`.
A content element that a page TSconfig of your own already turned into another
type is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-3-0-own-configuration:

Adjust your own configuration
-----------------------------

*   The page TSconfig directory is :file:`Configuration/TSconfig/` now, and the
    TypoScript moved into :file:`Configuration/TypoScript/List/`. Take the new
    path of every :typoscript:`@import` from the table of the changelog entry
    linked above. An import of a missing file fails silently.
*   A site whose content element layout renders no header sets
    :typoscript:`plugin.tx_academicbitejobs.renderContentElementHeader = 1`.
    The plugin no longer renders the header itself, see
    :ref:`important-1790323401`.
*   The job list is grouped only when TypoScript names a field of the job
    postings in :typoscript:`plugin.tx_academicbitejobs.settings.jobs.groupBy`.
    Set it where a list has to stay grouped, see
    :ref:`configuration-general-group-by`.
*   Label overrides move from
    :typoscript:`plugin.tx_academic_bite_jobs._LOCAL_LANG` to
    :typoscript:`plugin.tx_academicbitejobs._LOCAL_LANG`, see
    :ref:`important-label-overrides-use-the-documented-path`.

..  _upgrade-from-2-3-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-3-to-3-0-classes:

Classes and styles
------------------

The templates render speaking classes now, shared by all academic extensions,
and no classes of the theme they were developed with. The extension ships no
stylesheet, so a site that relied on the card, list group and table classes of
a Bootstrap theme styles the new classes itself. Move the selectors of the
site stylesheet to the classes in the table of
:ref:`breaking-bite-jobs-speaking-frontend-classes`. The chapter
:ref:`styling` describes the markup, and :ref:`styling-example` shows rules to
start from.

..  _upgrade-from-2-3-to-3-0-icons:

Icons
-----

The content element has a new icon, registered as
``tx-academicbitejobs-plugin-bite-jobs`` in :file:`Configuration/Icons.php`.
The identifier ``bitejobs_list`` and the file
:file:`Resources/Public/Icons/jobs_bite_icon.svg` are removed. Replace the
identifier in TCA, page TSconfig and templates, and register a replacement
icon under the new identifier in the :file:`Configuration/Icons.php` of a site
package that depends on :guilabel:`academic_bite_jobs`. See
:ref:`breaking-bite-jobs-plugin-icon-renamed`.

..  _upgrade-from-2-3-to-3-0-overrides:

Template overrides
------------------

Versions 2.4 and 3.0 change these templates and partials. A copy of one of them
in a site package keeps rendering the copy, so compare each copy with the
shipped file of 3.0 and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/BiteJobs/List.html`
        -   The header renders only with
            :typoscript:`settings.renderContentElementHeader`, the list is
            grouped only by the field :typoscript:`settings.jobs.groupBy`
            names, and the message for an empty list carries a speaking class.
        -   :ref:`important-1790323401`,
            :ref:`important-1789660801`,
            :ref:`upgrade-from-2-3-to-3-0-classes`
    *   -   :file:`Partials/BiteJobs/View/List.html`,
            :file:`Partials/BiteJobs/View/Card.html`,
            :file:`Partials/BiteJobs/View/Table.html`
        -   Speaking classes instead of the card, list group and table classes,
            and the translations pass the extension name
            :html:`AcademicBiteJobs`.
        -   :ref:`upgrade-from-2-3-to-3-0-classes`,
            :ref:`important-label-overrides-use-the-documented-path`
    *   -   :file:`Partials/BiteJobs/Header.html`
        -   It takes the class of the heading as `class` instead of
            `positionClass`, and the heading carries `ace-title`. A copy of a
            view that renders it with `positionClass` renders the heading
            without that class.
        -   :ref:`upgrade-from-2-3-to-3-0-classes`

..  _upgrade-from-2-3-to-3-0-code:

Adapt your code
===============

:php:`\FGTCLB\AcademicBiteJobs\Controller\BiteJobsController` is final. A class
that extends it stops loading with a fatal error. Remove the subclass, and the
:php:`configurePlugin()` call or the XCLASS registration that points at it, in
the same deployment as the update, and move each override to an event. The
two events of the job list are new since 2.4, see :ref:`feature-1790774401`:

*   The request to B-ITE: a listener of
    :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsRequestEvent`,
    see :ref:`developers-event-modify-bite-job-postings-request-event`.
*   The postings it returns: a listener of
    :php:`\FGTCLB\AcademicBiteJobs\Event\ModifyBiteJobPostingsEvent`, see
    :ref:`developers-event-modify-bite-job-postings-event`.
*   Additional view variables: a listener of
    :php:`\FGTCLB\AcademicBase\Event\ModifyPluginViewEvent`, see
    :ref:`feature-plugin-view-event` and
    :ref:`developers-event-modify-plugin-view-event`.

See :ref:`breaking-1791043409`.

The FlexForm file moved from :file:`Configuration/FlexForms/Core12/` and
:file:`Configuration/FlexForms/Core13/` to
:file:`Configuration/FlexForms/AcademicBiteJobsList.xml`. Own TCA that names
the data structure of this extension by its path takes over the new one, see
:ref:`important-bite-jobs-flexform-file-moved`.

..  _upgrade-from-2-3-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-3-to-3-0-upgrade-check`.
*   The content element, called :guilabel:`Job List b-ite` now, is offered for
    new content on the pages that should have it, and existing ones keep their
    type. See :ref:`important-content-element-titles-and-descriptions`.
*   Content elements saved with version 2.0 render, and their backend form shows
    the view the frontend renders.
*   Job titles of an ungrouped list moved up one heading level, an
    :html:`<h2>` instead of an :html:`<h3>` for header layout 1. Check the
    styles of the job list, or override :file:`Partials/BiteJobs/Header.html`.
*   The header of the content element appears once.
