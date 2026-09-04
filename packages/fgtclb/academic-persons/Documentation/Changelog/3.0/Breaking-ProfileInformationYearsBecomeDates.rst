.. _breaking-profile-information-years-become-dates:

================================================
Breaking: Profile information years become dates
================================================

Description
===========

Profile information records - the timeline entries of a profile such as
publications, memberships or the curriculum vitae - stored their point in time
as four-digit years in the integer columns :sql:`year`, :sql:`year_start` and
:sql:`year_end`. Those columns are replaced by three nullable native
:sql:`DATE` columns under new names:

======================  ======================  =====================================
Column until 2.x        Column from 3.0.0       ``ProfileInformation`` property
======================  ======================  =====================================
:sql:`year`             :sql:`date`             :php:`getDate()` / :php:`setDate()`
:sql:`year_start`       :sql:`date_start`       :php:`getDateStart()` / :php:`setDateStart()`
:sql:`year_end`         :sql:`date_end`         :php:`getDateEnd()` / :php:`setDateEnd()`
======================  ======================  =====================================

The properties are typed :php:`?\DateTime`. Only a calendar date is stored,
without a time and without a time zone conversion: the TCA declares the three
columns as :php:`type => datetime` with :php:`format => date` and
:php:`dbType => date`, which renders the backend date picker and persists a
plain :sql:`YYYY-MM-DD` value on TYPO3 v13 and v14 alike.

A fourth column, :sql:`year_only` (:php:`isYearOnly()` / :php:`setYearOnly()`),
controls the presentation of a record: when it is set, every date of that
record is rendered as its four-digit year while the complete date stays stored.
The shipped public profile honours the flag, in
:file:`Partials/Profile/PublicProfile/TimelineItem.html`. Records default to
the complete date.

The validation flag of the profile information form changes with it: the
shipped :file:`Configuration/AcademicPersons/Settings.yaml` now lists
:yaml:`date: [required, date]` instead of :yaml:`year: [required, number]`,
and the new ``date`` flag renders a date input in the frontend forms without
touching the TCA type of the column.

Impact
======

**Existing data is migrated by an upgrade wizard.** The wizard
``academicPersons_migrateProfileInformationDates`` reads every record whose
legacy year has no date yet, writes the first of January of that year into the
new column and sets :sql:`year_only`, so a record that showed "2019" before
shows "2019" afterwards, and the original value stays recoverable from the
date. A legacy value of :sql:`0` or :sql:`NULL` meant "no year" and is left
alone, and so is a value above 9999, which the old TCA never allowed; a date
that is already set is never overwritten. A record an editor has already
dated in 3.0 - one of its three date columns is set - gets its remaining
years converted but keeps :sql:`year_only` as the editor left it. Hidden,
deleted and translated records are migrated as well.

**The old columns are not dropped.** The three integer columns left
:file:`ext_tables.sql`, and the TYPO3 database analyzer lists a column that is
no longer declared as *unused* - it never drops it without being told to. That
is what makes the migration safe: the schema update adds the new columns and
keeps the old ones, the wizard converts the values, and the integer columns
are removed as a separate, last step.

**The wizard survives being run too early.** It is registered as repeatable, so
TYPO3 never marks it as done: it stays in the upgrade module and on the command
line however often it ran and whatever it found. That matters for a deployment
whose whole update is :bash:`composer install && vendor/bin/typo3 upgrade:run`,
because :php:`UpgradeWizardRunCommand` asks a wizard whether an update is
necessary *before* it handles the prerequisites that would run the schema
update. A wizard that answered "no" at that moment would be recorded as done
and never offered again, and the last migration step below would then drop
three columns whose values were never converted. This wizard answers "yes"
while a legacy column holds a year the new columns cannot hold yet, so the
schema update it declares as its prerequisite is performed and the conversion
follows in the same run.

**Code and templates that read the old properties break.** :php:`getYear()`,
:php:`getYearStart()` and :php:`getYearEnd()` no longer exist, and the new
accessors return :php:`\DateTime` objects, not integers. A project template
that overrides :file:`Resources/Private/Templates/Profile/Detail.html` (or the
partials of :composer:`fgtclb/academic-persons-edit`) and prints
``{item.year}`` renders nothing for the removed property; one that is adapted
to ``{item.date}`` without formatting fails at render time with

..  code-block:: text

    Object of class DateTime could not be converted to string

Format the value explicitly, and honour the flag:

..  code-block:: html

    <f:variable name="format" value="{f:if(condition: item.yearOnly, then: 'Y', else: 'd.m.Y')}" />
    {item.date -> f:format.date(format: format)}

Code that called :php:`setYear()` with an integer passes a :php:`\DateTime` to
:php:`setDate()` instead. Custom queries, CSV fixtures and data imports that
address the columns by name use the new names.

**The column labels change with the columns.**
:file:`Resources/Private/Language/locallang_tca.xlf` loses
``tx_academicpersons_domain_model_profile_information.columns.year.label``,
``...columns.year_start.label`` and ``...columns.year_end.label``, and gains
``...columns.date.label``, ``...columns.date_start.label``,
``...columns.date_end.label`` and ``...columns.year_only.label``. A site
package that translated or overrode one of the three removed keys through
:typoscript:`locallangXMLOverride` overrides a key nothing reads any more, and
sees the shipped label until it moves its override to the new key.

Affected Installations
======================

Every installation with profile information records, and every project that
overrides the detail template or reads the three properties in its own code.

Migration
=========

#.  Run the database analyzer once after updating, in
    :guilabel:`Admin Tools > Maintenance` or with
    :bash:`vendor/bin/typo3 extension:setup`. It adds :sql:`date`,
    :sql:`date_start`, :sql:`date_end` and :sql:`year_only` and keeps the
    integer columns. Between this step and the next one the new columns are
    empty, so every timeline entry renders without a date - run the two steps
    together.
#.  Run the upgrade wizard ``academicPersons_migrateProfileInformationDates``,
    in :guilabel:`Admin Tools > Upgrade > Upgrade Wizard` or with
    :bash:`vendor/bin/typo3 upgrade:run academicPersons_migrateProfileInformationDates`.
    It is offered while a legacy column still holds a year that has no date,
    and it can be run again at any time: the wizard is repeatable, is never
    marked as done, and never overwrites a date that is already set.

    **Name the wizard rather than running a bare**
    :bash:`vendor/bin/typo3 upgrade:run`. This one would survive a bare run:
    it declares the schema update as its prerequisite, so steps 1 and 2 are
    covered in one pass, and it is repeatable. A bare run also executes every
    other wizard of every installed extension, in the order the wizard
    registry happens to have them - and the plugin migration of these
    extensions has to run before the TYPO3 v14 update and is *not* repeatable.
    On v14 a bare run asks it whether it has work to do, is told "no" because
    :sql:`tt_content.list_type` is gone, and records it as done although it
    migrated nothing.
#.  Drop :sql:`year`, :sql:`year_start` and :sql:`year_end` through the
    database analyzer's *unused* section once the wizard has run. Until then
    they stay in place, so a migration can be verified before the old values
    are gone.
#.  Adapt overridden templates and custom code as described under *Impact*.

.. index:: Database, TCA, Frontend, ext:academic_persons
