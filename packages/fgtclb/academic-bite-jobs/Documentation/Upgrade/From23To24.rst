..  _upgrade-from-2-3-to-2-4:

=========================
Upgrading from 2.3 to 2.4
=========================

Version 2.4 delivers the configuration of :guilabel:`academic_bite_jobs` per
component, hides its content element until a site enables it and migrates the
stored plugin settings of older job lists with a new upgrade wizard. It changes
no database table of this extension. Work through the sections in order. Skip a
step that does not apply to the installation.

..  _upgrade-from-2-3-to-2-4-before-you-start:

Before you start
================

Version 2.4 runs on TYPO3 12.4 from 12.4.22 on and on TYPO3 13.4, with PHP 8.1
or later. That is the same as for 2.3, so the TYPO3 core does not have to
change for this upgrade.

:guilabel:`academic_bite_jobs` 2.4 needs these packages. Upgrade them first or
in the same deployment, in this order:

*   :composer:`fgtclb/environment-state-manager` 1.x, new in 2.4, which
    :guilabel:`academic_base` 2.4 requires. Composer installs it on its own. A
    classic installation installs :guilabel:`environment_state_manager` before
    it activates :guilabel:`academic_base` 2.4.
*   :composer:`fgtclb/academic-base` 2.4, the same release as this extension.

The extension now declares the system extension :guilabel:`install` as a
dependency, for its upgrade wizard. TYPO3 keeps that extension active in every
installation, so this needs no action.

..  _upgrade-from-2-3-to-2-4-package:

Update the package
==================

..  code-block:: bash

    composer require -W \
        'fgtclb/academic-base':'~2.4.0' \
        'fgtclb/academic-bite-jobs':'~2.4.0'

A project that requires branch ``2`` as ``2.x-dev`` keeps an inline alias such
as ``2.x-dev as 2.4.x-dev`` working, see :ref:`important-1790796660`.

On a classic installation, install :guilabel:`environment_state_manager` 1.x
from the TER in :guilabel:`Admin Tools > Extensions` first, then update
:guilabel:`academic_base` and :guilabel:`academic_bite_jobs` there, in this
order. Run the commands of this page with :bash:`typo3/sysext/core/bin/typo3`
instead of :bash:`vendor/bin/typo3`.

..  code-block:: bash

    vendor/bin/typo3 cache:flush

..  _upgrade-from-2-3-to-2-4-wizards:

Run the upgrade wizards
=======================

The wizard requires the database comparison of the installation to be applied
first, in :guilabel:`Admin Tools > Maintenance > Analyze Database Structure` or
with :bash:`vendor/bin/typo3 extension:setup`. The other academic extensions
add columns in 2.4.

``academicBiteJobs_listViewFlexFormUpgradeWizard``, :guilabel:`Migrate the
plugin settings of academic_bite_jobs job lists stored before 2.1.`, is new. It
rewrites the stored view of every :guilabel:`Bite Jobs List` content element,
hidden ones included, from `ListView`, `CardView` or `TableView`, or an empty
or unknown value, to `List`, `Card` or `Table`. It also removes the settings
`settings.jobs.groupBy` and `settings.jobs.custom.zuordnung`, which version 2.1
dropped from the plugin but left in the stored records. A stored grouping
setting keeps a list grouped by a field the job postings no longer carry until
the wizard has run.

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicBiteJobs_listViewFlexFormUpgradeWizard

Content elements created with version 1.x are plugins stored with the content
type :sql:`list`. Neither this version nor the wizard renders or migrates
them. See :ref:`important-1789660801`.

..  _upgrade-from-2-3-to-2-4-configuration:

Adapt the configuration
=======================

The TypoScript and the page TSconfig are now delivered per content element,
and the :guilabel:`Bite Jobs List` content element is hidden in the whole
installation until the page TSconfig of its component is included. See
:ref:`breaking-site-sets-and-static-templates-restructured`.

..  _upgrade-from-2-3-to-2-4-components:

Include the components
----------------------

*   **Site set, TYPO3 13 only.** A site that depends on the set
    `fgtclb/academic-bite-jobs` needs no change. The set keeps its name and now
    brings the page TSconfig along.
*   **Static template.** The stored entry of 2.3 keeps working as
    :guilabel:`Academic Bite Jobs: Path up to 2.3 (deprecated, use All
    components) (academic_bite_jobs)`, which is removed in version 4.0. Replace
    it with :guilabel:`Academic Bite Jobs: All components (academic_bite_jobs)`
    in the field :guilabel:`Include TypoScript sets` on the tab
    :guilabel:`Advanced Options` of the template record, see
    :ref:`deprecation-legacy-static-template-path`.
*   **Page TSconfig.** A site without the site set, which is every site on
    TYPO3 12, adds :guilabel:`Academic Bite Jobs: All components
    (academic_bite_jobs)` in the field :guilabel:`Include static Page TSconfig
    (from extensions)` on the tab :guilabel:`Resources` of its root page, see
    :ref:`static-pagetsconfig`. Without it the content element is not offered
    for new content. On TYPO3 12 the same file brings the entry of the new
    content element wizard, which version 2.3 delivered through the site set
    only, so TYPO3 12 never offered it, see :ref:`important-1787054400`.
*   Use one of the two mechanisms per site, see
    :ref:`one-mechanism-per-site`.

Until the component is included, an existing content element keeps rendering
and keeps its type: :guilabel:`academic_base` 2.4 shows the stored type in the
field :guilabel:`Type` followed by :guilabel:`(not enabled on this page)`, and
saving keeps it. The warning of the changelog entry that saving such a record
rewrites its type describes the state before that fix. A content element that
an older version or a page TSconfig of your own already turned into another type
is not repaired. Correct it by hand from its record history.

..  _upgrade-from-2-3-to-2-4-own-configuration:

Adjust your own configuration
-----------------------------

*   The page TSconfig directory is :file:`Configuration/TSconfig/` now, and the
    TypoScript moved into :file:`Configuration/TypoScript/List/`. Take the new
    path of every :typoscript:`@import` from the table of
    :ref:`breaking-site-sets-and-static-templates-restructured`. An import of a
    missing file fails silently.
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

..  _upgrade-from-2-3-to-2-4-templates:

Adapt templates and assets
==========================

Version 2.4 changes these templates and partials. A copy of one of them in a
site package keeps rendering the copy, so compare each copy with the shipped
file and take over the changes. The paths below are relative to
:file:`Resources/Private/`:

..  list-table::
    :header-rows: 1

    *   -   File
        -   What changed
        -   Entry
    *   -   :file:`Templates/BiteJobs/List.html`
        -   The header renders only with
            :typoscript:`settings.renderContentElementHeader`, and the list is
            grouped only by the field :typoscript:`settings.jobs.groupBy`
            names.
        -   :ref:`important-1790323401`,
            :ref:`important-1789660801`
    *   -   :file:`Partials/BiteJobs/View/List.html`,
            :file:`Partials/BiteJobs/View/Card.html`,
            :file:`Partials/BiteJobs/View/Table.html`
        -   The translations pass the extension name :html:`AcademicBiteJobs`.
        -   :ref:`important-label-overrides-use-the-documented-path`

No asset of this extension changed.

..  _upgrade-from-2-3-to-2-4-check:

Check the result
================

*   The :guilabel:`Bite Jobs List` content element is offered for new content
    on the pages that should have it, and existing ones keep their type.
*   Content elements saved with version 2.0 render, and their backend form shows
    the view the frontend renders.
*   Job titles of an ungrouped list moved up one heading level, an
    :html:`<h2>` instead of an :html:`<h3>` for header layout 1. Check the
    styles of the job list, or override :file:`Partials/BiteJobs/Header.html`.
*   The header of the content element appears once.
