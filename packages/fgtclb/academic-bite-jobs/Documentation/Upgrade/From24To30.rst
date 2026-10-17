..  _upgrade-from-2-4-to-3-0:

=========================
Upgrading from 2.4 to 3.0
=========================

Version 3.0 drops TYPO3 12, makes the controller of the job list final,
renders the job list with speaking classes and gives the content element a new
icon. It changes no database table of this extension and brings no upgrade
wizard. Work through the sections in order. Skip a step that does not apply to
the installation.

..  _upgrade-from-2-4-to-3-0-before-you-start:

Before you start
================

Version 3.0 runs on TYPO3 13.4 from 13.4.35 on and on TYPO3 14.3 from 14.3.7
on, with PHP 8.2 or later.

:guilabel:`academic_bite_jobs` 3.0 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 2.x, which
    :guilabel:`academic_base` 3.0 requires instead of 1.x. Composer updates it
    on its own. A classic installation replaces
    :guilabel:`environment_state_manager` 1.x with 2.x before it activates
    :guilabel:`academic_base` 3.0.
*   :composer:`fgtclb/academic-base` 3.0, the same release as this extension.

These changes have to ship in the same deployment as the update:

*   A subclass of the controller of the job list goes, or the site fails with
    a fatal error as soon as the update is deployed, see
    :ref:`upgrade-from-2-4-to-3-0-code`.

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
        'fgtclb/academic-bite-jobs':'~3.0.0'

On a classic installation, update :guilabel:`environment_state_manager` to 2.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_bite_jobs` there, in this
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

The comparison changes no table of this extension. It applies the changes of
the index definitions TYPO3 13.4.35 and 14.3.7 bring, and the columns other
academic extensions add in 3.0.

..  _upgrade-from-2-4-to-3-0-configuration:

Adapt the configuration
=======================

..  _upgrade-from-2-4-to-3-0-upgrade-check:

The upgrade check
-----------------

The console command :bash:`academic:upgrade:check` of :guilabel:`academic_base`
reads the stored configuration of the installation and reports the static
templates, page TSconfig imports and site sets that no longer reach an academic
extension, and an XCLASS of the controller. Run it on the staging copy right
after the package update:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check

With the extension key and a site it also compares the template overrides of
the site with the templates this extension ships:

..  code-block:: bash

    vendor/bin/typo3 academic:upgrade:check academic_bite_jobs --site=<site identifier>

Each finding, and what to do about it, is described in the :ref:`upgrade check
<fgtclb/academic-base:upgrade-check>`
of :guilabel:`academic_base`.

..  _upgrade-from-2-4-to-3-0-own-configuration:

Adjust your own configuration
-----------------------------

The site set, the static templates and the page TSconfig of this extension keep
their names and paths from 2.4.

The page TSconfig of the content element no longer adds it to the
:typoscript:`show` list of the new content element wizard, which TYPO3 13 and
14 do not read. A site package that copied that line can drop it, see
:ref:`important-ace-458-academic-bite-jobs-wizard-show`.

..  _upgrade-from-2-4-to-3-0-templates:

Adapt templates and assets
==========================

..  _upgrade-from-2-4-to-3-0-classes:

Classes and styles
------------------

The templates render speaking classes now, shared by all academic extensions,
and no classes of the theme they were developed with. The extension shipped no
stylesheet in 2.4 and ships none in 3.0, so a site that relied on the card,
list group and table classes of a Bootstrap theme styles the new classes
itself. Move the selectors of the site stylesheet to the classes in the table
of :ref:`breaking-bite-jobs-speaking-frontend-classes`. The chapter
:ref:`styling` describes the markup, and :ref:`styling-example` shows rules to
start from.

..  _upgrade-from-2-4-to-3-0-icons:

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

..  _upgrade-from-2-4-to-3-0-overrides:

Template overrides
------------------

Version 3.0 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Partials/BiteJobs/View/List.html`,
            :file:`Partials/BiteJobs/View/Card.html`,
            :file:`Partials/BiteJobs/View/Table.html`,
            :file:`Templates/BiteJobs/List.html`
        -   Speaking classes instead of the card, list group and table classes.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`
    *   -   :file:`Partials/BiteJobs/Header.html`
        -   It takes the class of the heading as `class` instead of
            `positionClass`, and the heading carries `ace-title`. A copy of a
            view that renders it with `positionClass` renders the heading
            without that class.
        -   :ref:`upgrade-from-2-4-to-3-0-classes`

..  _upgrade-from-2-4-to-3-0-code:

Adapt your code
===============

:php:`\FGTCLB\AcademicBiteJobs\Controller\BiteJobsController` is final. A class
that extends it stops loading with a fatal error. Remove the subclass, and the
:php:`configurePlugin()` call or the XCLASS registration that points at it, in
the same deployment as the update, and move each override to an event:

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

..  _upgrade-from-2-4-to-3-0-check:

Check the result
================

*   The upgrade check exits with ``0``, without and with the extension key,
    see :ref:`upgrade-from-2-4-to-3-0-upgrade-check`.
*   The content element is offered as :guilabel:`Job List b-ite` now, see
    :ref:`important-content-element-titles-and-descriptions`.
*   The job list renders, in each of its three views, with the styles of the
    site.
