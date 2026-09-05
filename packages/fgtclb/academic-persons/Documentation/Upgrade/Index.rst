..  index:: ! Upgrade
..  _upgrade:

===========================
Upgrading from 2.4 to 3.0.0
===========================

Version 3.0.0 changes the database schema of the timeline entries, the shape of
:file:`Configuration/AcademicPersons/Settings.yaml`, the public detail template
and - together with :composer:`fgtclb/academic-persons-edit` - the complete
profile editing frontend. Each of those changes carries its own changelog entry
with the detail; this page is the **order** they have to be applied in.

The order matters. Two steps destroy data when they are taken too early, and
one wizard converts nothing when it runs before the schema update. Work through
the steps from top to bottom, on a copy of the production database first.

..  _upgrade-overview:

The steps at a glance
=====================

..  list-table::
    :header-rows: 1

    *   -   Step
        -   What it does
        -   What happens if it is skipped
    *   -   1. Update the packages
        -   Installs 3.0.0 of every academic extension of the installation.
        -   Nothing below applies.
    *   -   2. Run the plugin migration wizards, still on TYPO3 v13
        -   Moves the ``list_type`` plugin records onto their own ``CType``.
        -   The content elements stop rendering on TYPO3 v14, where the column
            the wizards read no longer exists.
    *   -   3. Update the database schema
        -   Adds :sql:`date`, :sql:`date_start`, :sql:`date_end` and
            :sql:`year_only`, and keeps the three integer year columns.
        -   Step 4 finds no columns to write into and converts nothing.
    *   -   4. Convert the timeline years
        -   Writes every stored year as the first of January of that year.
        -   Every timeline entry renders without a date, and step 8 destroys
            the years for good.
    *   -   5. Repair the profile image relations
        -   Reduces duplicate references, corrects relation counters and marks
            the translations that carry an image of their own.
        -   A translation loses its own image at the next synchronisation, and
            duplicate references keep rendering the wrong file.
    *   -   6. Migrate the settings override
        -   Replaces the pre-3.0 keys of a site package with the section maps.
        -   The installation runs on the legacy overlay, which is removed in
            4.0 - and a renamed ``type`` or ``fieldName`` stays silently
            broken.
    *   -   7. Adapt templates, icons and TypoScript
        -   Re-applies project overrides to the new template tree and makes the
            JSON page type reachable.
        -   The editor cannot save, and an overridden detail view loses every
            timeline date.
    *   -   8. Drop the legacy year columns
        -   Removes :sql:`year`, :sql:`year_start` and :sql:`year_end`.
        -   Nothing - this step is optional and deliberately last.

..  note::
    The TYPO3 core update itself is not a step of this page; it is an upgrade
    of its own with its own manual. Where it belongs matters for exactly one
    step: the plugin migration wizards of step 2 read a column TYPO3 v14 no
    longer declares, so the core update comes **after** step 2. Every other
    step works the same on TYPO3 v13 and on v14.

..  _upgrade-step-packages:

1. Update the packages
======================

The academic extensions are released together and depend on each other, so they
are updated in one go:

..  code-block:: bash

    composer require 'fgtclb/academic-persons':'^3' 'fgtclb/academic-persons-edit':'^3'

In a classic (non-Composer) installation, update every academic extension in
the Extension Manager and activate :guilabel:`rte_ckeditor`, which
:guilabel:`academic_persons_edit` requires for its rich text fields since
3.0.0.

Flush all caches afterwards. The settings graph is cached in the core cache and
the cache identifier changed with the file format, so a stale entry is not read
back - but the TypoScript and TCA caches are.

..  _upgrade-step-plugins:

2. Run the plugin migration wizards while still on TYPO3 v13
============================================================

``academicPersons_MigrateListTypeToCTypeContentElements`` and
``academicPersonsEdit_pluginContent`` move the content elements of both
extensions from ``CType = list`` plus ``list_type`` onto their own ``CType``.
Both read :sql:`tt_content.list_type`, and **TYPO3 v14 removed that column**.

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicPersons_MigrateListTypeToCTypeContentElements
    vendor/bin/typo3 upgrade:run academicPersonsEdit_pluginContent

Run them on TYPO3 v13, before the core update. TYPO3 v14 removed
:sql:`tt_content.list_type` from the TCA, so the database analyzer of a v14
installation offers the column for removal and a fresh v14 installation never
has it. Both wizards ask the live schema: once the column is gone their content
element half finds nothing to do, and the records they would have migrated keep
a content type nothing renders. The ``academicPersons_`` wizard also migrates
the ``explicit_allowdeny`` values of the backend user groups, and that half
stays available on both versions.

..  warning::
    ``academicPersons_MigrateListTypeToCTypeContentElements`` is **not
    repeatable**: a wizard that reports "nothing to do" is recorded as done and
    disappears from the upgrade module. That is why this step names both
    wizards instead of running a bare :bash:`vendor/bin/typo3 upgrade:run` - a
    bare run on TYPO3 v14, with the column already removed, marks the wizard as
    done although it migrated nothing. The flag is cleared with

    ..  code-block:: bash

        vendor/bin/typo3 upgrade:mark:undone academicPersons_MigrateListTypeToCTypeContentElements

    but that only helps while :sql:`tt_content.list_type` still holds the
    values. Once the column is dropped, nothing records which plugin such a
    content element was, and the records have to be repaired by hand.

``academicPersonsEdit_removeProfileSwitcherContent`` deletes the content
elements of the removed profile switcher plugin and handles both shapes, so it
can be run on either core version.

..  _upgrade-step-schema:

3. Update the database schema
=============================

..  code-block:: bash

    vendor/bin/typo3 extension:setup

In the backend the same thing is :guilabel:`Admin Tools > Maintenance > Analyze
Database Structure`. It adds the four new columns of the profile information
table - :sql:`date`, :sql:`date_start`, :sql:`date_end` and :sql:`year_only` -
and **keeps** :sql:`year`, :sql:`year_start` and :sql:`year_end`: the database
analyzer reports a column that left :file:`ext_tables.sql` as *unused* and never
drops it on its own.

..  warning::
    Do not accept the analyzer's offer to remove the three unused integer
    columns here. That is step 8, and it comes after the conversion has been
    verified.

Between this step and the next one the new columns are empty, so every timeline
entry renders without a date. Run the two steps together.

..  _upgrade-step-dates:

4. Convert the timeline years
=============================

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicPersons_migrateProfileInformationDates

In the backend: :guilabel:`Admin Tools > Upgrade > Upgrade Wizard`.

Name the wizard explicitly rather than running a bare :bash:`upgrade:run`.
This wizard alone would survive one - it is repeatable and declares the schema
update as its prerequisite, so it converts what step 3 has just added - but a
bare run walks every wizard of every installed extension in one pass, in the
order the wizard registry happens to have them, which is not the order of this
page, and one of them is the non-repeatable wizard of step 2.

The wizard reads every profile information record whose legacy year has no date
yet, writes the first of January of that year into the new column and sets
:sql:`year_only`, so a record that showed "2019" before shows "2019" afterwards
and the original value stays recoverable. A legacy value of :sql:`0` or
:sql:`NULL` meant "no year" and is left alone, and so is a value above 9999,
which the old TCA never allowed. Hidden, deleted and translated records are
converted as well.

**A date that is already set is never overwritten**, and the wizard stays
available after a run, so it can be run again - after a second import, or after
a first run that happened before step 3 and therefore converted nothing.

..  note::
    An installation that ran a 3.0.0 pre-release in which this wizard was not
    yet repeatable carries it as done in :sql:`sys_registry`. Offer it again
    with :bash:`vendor/bin/typo3 upgrade:mark:undone
    academicPersons_migrateProfileInformationDates`.

Verify it before going on:

..  code-block:: sql

    SELECT COUNT(*) FROM tx_academicpersons_domain_model_profile_information
    WHERE (year       > 0 AND year       <= 9999 AND date       IS NULL)
       OR (year_start > 0 AND year_start <= 9999 AND date_start IS NULL)
       OR (year_end   > 0 AND year_end   <= 9999 AND date_end   IS NULL);

The result has to be ``0``. Anything else means there are records left to
convert - do not proceed to step 8.

See :ref:`breaking-profile-information-years-become-dates` for the schema
change itself and for the template changes it forces.

..  _upgrade-step-images:

5. Repair the profile image relations
=====================================

Only relevant where :guilabel:`academic_persons_edit` is installed, and only
worth running where profiles have images.

..  code-block:: bash

    vendor/bin/typo3 upgrade:run academicPersonsEdit_repairLocalizedProfileImages

The profile image column is translatable from 3.0.0 on. Until then it was
excluded from localisation and the upload path wrote the relation rows by hand,
which left three shapes behind that are defects under the new model: duplicate
references on one profile, a relation counter that disagrees with the number of
references, and a translation carrying its own reference without the ``custom``
localisation state - the state that keeps the next synchronisation from
replacing it with a localisation of the default-language image.

The wizard repairs all three through the TYPO3 DataHandler, so the reference
index, the record history and the localisation state are the core's. **No file
is deleted**; a file left without a relation is for the *unused files* tooling
of the Install Tool to report. Prefer the command line over the Install Tool
module for it.

Run it after step 3 and before editors start working in the new editor of a
multilingual installation. It is repeatable as well; where a 3.0.0 pre-release
recorded it as done, :bash:`vendor/bin/typo3 upgrade:mark:undone
academicPersonsEdit_repairLocalizedProfileImages` offers it again. See
:ref:`breaking-profile-image-is-translatable`.

..  _upgrade-step-settings:

6. Migrate the settings override
================================

Only relevant for an installation whose site package ships
:file:`Configuration/AcademicPersons/Settings.yaml`.

The pre-3.0 top-level keys :yaml:`validations` and
:yaml:`profileInformationsTypes` are mapped onto the four section maps at
runtime, with a warning in the log, so such a file keeps working. The overlay is
transitional and is removed in academic_persons 4.0.

..  code-block:: bash

    vendor/bin/typo3 academic:persons:settings:migrate

The command prints, for every active package that still ships a legacy key, the
:yaml:`profile`, :yaml:`special`, :yaml:`contracts` and :yaml:`documentSections`
maps the overlay produces - the document that replaces the legacy keys of that
package. It never writes the file, and it exits with ``1`` while such a package
exists, so a deployment pipeline can gate on it. Paste the printed maps into the
site package, review them, and flush all caches.

..  warning::
    Read the notes the command prints. A renamed :yaml:`type` or
    :yaml:`fieldName` of a legacy :yaml:`profileInformationsTypes` entry is
    **not applied**, and is reported instead: since 3.0.0 the seven profile
    relations and the record type each of them selects are declared by the TCA
    of the profile table, and applying such a rename would move the editing
    frontend alone, leaving the backend column and the editor writing
    different record types. The section keeps the values that match the TCA,
    so the installation is consistent - but the intention of the override is
    silently not honoured. A timeline type of your own needs its own column in
    a TCA override of the profile table, and its section under
    :yaml:`documentSections` - see :ref:`configuration-sections-documents`.
    The same applies to a :yaml:`type` or :yaml:`fieldName` written directly
    into the new :yaml:`documentSections` map: that one *is* read, and it is
    the shape that diverges from the TCA.

:ref:`configuration-validations-migration` has the complete key mapping and what
is deliberately not mapped;
:ref:`breaking-section-based-academic-persons-settings` describes the new shape.

..  _upgrade-step-templates:

7. Adapt templates, icons and TypoScript
========================================

The public detail view
----------------------

:file:`Resources/Private/Templates/Profile/Detail.html` was rewritten: it is a
dispatcher over the :yaml:`profile.structure` and :yaml:`profile.details`
layout, and every element is one partial below
:file:`Resources/Private/Partials/Profile/PublicProfile/`.

**An installation that overrides the detail template keeps rendering its own
copy**, so nothing looks broken - and it loses the configurable layout
completely, together with every timeline date, because ``{item.year}``,
``{item.yearStart}`` and ``{item.yearEnd}`` no longer exist and Fluid renders a
missing property as an empty string. Two of the partials the old detail
template rendered are now only used by the list and card views, and the third,
:file:`Partials/Profile/DataHeader.html`, is deleted - a project template that
still renders it fails at render time. See
:ref:`configuration-sections-detail-override` and
:ref:`breaking-public-profile-detail-partials`.

The profile editing view
------------------------

The editing plugin of :guilabel:`academic_persons_edit` was replaced in place.
Every Fluid file of the removed form flow is gone, so an override of one of them
renders nothing; the new tree is :file:`Templates/Profile/Index.html` with the
partials below :file:`Partials/Profile/`, and the markup of the two regions the
browser builds is authored in Fluid as ``<template data-pe-proto>`` prototypes.
The `profile editing chapter
<https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/ProfileEditing/Index.html>`__
of that extension names every file, every hook and the four prototype
attributes an override has to keep.

Icons
-----

The icon set of the editor was replaced. Five identifiers of the form flow are
gone - ``academic-persons-edit-add-image``, ``-add-item``, ``-cancel``,
``-sort`` and ``-to-top`` - and the thirteen action icons of the new set are
registered in :file:`Configuration/Icons.php` of
:guilabel:`academic_persons_edit`, under the identifiers listed in the `icon
table
<https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/ProfileEditing/Index.html#profile-editing-icons>`__.
A template or PHP file addressing a removed identifier renders TYPO3's
``default-not-found`` placeholder. The new icons are inlined as ``<svg>`` rather
than emitted as ``<img>``, so they follow the text colour - and a site
stylesheet that selects ``.t3js-icon img`` no longer matches them.

TypoScript and the JSON page type
---------------------------------

The write path of the editor is a :typoscript:`PAGE` object with
:typoscript:`typeNum = 1733735`, which is delivered by the site set
``fgtclb/academic-persons-edit-profile-editing`` or by the static template
:guilabel:`Academic Persons Edit: Profile editing`. There was no such page type
in 2.4, because the old editor was a server-rendered form flow.

**Check that the site really includes one of the two.** A site package that
copied the extension's TypoScript into its own instead of including it renders
the new editor and would answer every save with the page's HTML instead of
JSON. The editor detects that case: where the request carries no such
:typoscript:`PAGE` object it renders a ``role="alert"`` message above itself
and logs the cause, naming the site set. Such a site package has to add the
:typoscript:`academicPersonsProfileEditingAjax` object by hand, or include the
delivered TypoScript.

A ``PageType`` route enhancer has to map the page type, and a web application
firewall or reverse proxy has to let it and the ``X-Requested-With`` header
through - see the `page type section
<https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/ProfileEditing/Index.html#profile-editing-page-type>`__.

..  _upgrade-step-drop:

8. Drop the legacy year columns
===============================

Last, and only after the query of step 4 returned ``0``.

:guilabel:`Admin Tools > Maintenance > Analyze Database Structure` lists
:sql:`year`, :sql:`year_start` and :sql:`year_end` of
:sql:`tx_academicpersons_domain_model_profile_information` as unused columns and
removes them on request. :bash:`extension:setup` never does it, which is what
makes the earlier steps recoverable.

..  warning::
    Dropping the three columns before step 4 has run **destroys every timeline
    date in the installation**, silently and unrecoverably: the new columns are
    empty at that point and the values only exist in the old ones. There is no
    wizard that can bring them back.

The step is optional. An installation that keeps the columns keeps three unused
integer columns and nothing else.

..  _upgrade-verify:

Verifying the result
====================

#.  A timeline entry of a profile shows its date in the frontend and in the
    backend record editor, and an entry that showed a year still shows a year.
#.  The profile editing plugin loads without the "cannot be saved" alert above
    it, a field can be saved, and the browser console shows no failed request
    to the page type ``1733735``.
#.  A profile image is shown in every language of a translated profile, and
    uploading a new one in one language does not change the other.
#.  :bash:`vendor/bin/typo3 academic:persons:settings:migrate` exits with
    ``0``, and the log carries no legacy settings warning.
#.  The database analyzer offers only the three year columns as unused.
