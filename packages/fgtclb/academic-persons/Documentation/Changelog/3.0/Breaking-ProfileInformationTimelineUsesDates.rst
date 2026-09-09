.. _breaking-profile-information-timeline-uses-dates:

===============================================
Breaking: The timeline entries carry real dates
===============================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

A timeline entry of a profile stored a year, a start year and an end year as
four digit integers. A year is not a date: it cannot be formatted for a locale,
it cannot be compared with a contract date, and it forced the frontend editor to
render a number field where every other date renders a date control.

The three columns of :sql:`tx_academicpersons_domain_model_profile_information`
are therefore replaced. :sql:`year`, :sql:`year_start` and :sql:`year_end`,
which were :sql:`int(11) unsigned DEFAULT NULL`, become

..  code-block:: sql

    date date DEFAULT NULL,
    date_start date DEFAULT NULL,
    date_end date DEFAULT NULL,

and their TCA declares

..  code-block:: php

    'config' => [
        'dbType' => 'date',
        'type' => 'datetime',
        'format' => 'date',
        'nullable' => true,
        'default' => null,
    ],

:php:`'dbType' => 'date'` keeps a native SQL :sql:`DATE`, so no timestamp and no
timezone conversion is involved, and it is identical on TYPO3 v13 and v14. All
three stay optional: an entry without any date is valid, and :sql:`NULL` is the
empty value.

The Extbase model follows. :php:`ProfileInformation::getYear()`,
:php:`getYearStart()` and :php:`getYearEnd()`, and their setters, are replaced by
:php:`getDate()`, :php:`getDateStart()` and :php:`getDateEnd()`, all typed
:php:`?\DateTime` instead of :php:`?int`. The columns are renamed rather than
only retyped, because a column called :sql:`year` holding the 14th of March 2019
is a trap for every later reader.

The configuration vocabulary follows the record. In
:file:`Configuration/AcademicPersons/Settings.yaml` the document row field and
validator key :yaml:`year` becomes :yaml:`date`, and the aliases :yaml:`from`
and :yaml:`to` address the properties :php:`dateStart` and :php:`dateEnd`
instead of :php:`yearStart` and :php:`yearEnd`. The shipped sections declare a
:yaml:`number` flag for these fields no longer; they declare :yaml:`date`.

The three labels are renamed with the columns:
:file:`locallang_tca.xlf` carries
``tx_academicpersons_domain_model_profile_information.columns.date.label``,
``...columns.date_start.label`` and ``...columns.date_end.label``, and
:file:`locallang.xlf` carries ``helptext.documentSections.date`` in place of
``helptext.documentSections.year``.

Impact
======

The TYPO3 database analyzer offers to **drop three columns and add three
others**. Applying it without preparing the data loses every stored year.

An integer year carries no month and no day, so this extension deliberately
does not convert it for an installation and does not register an upgrade wizard
that would invent one. What it ships instead is a starting point for a project's
own migration — see
:ref:`important-migrating-profile-information-years-to-dates`, which also says
what a project has to decide before it runs anything.

A site package that configures the timeline sections has to rename its keys: a
:yaml:`year` entry under a section's :yaml:`validators` or :yaml:`rowFields` has
no effect any more, exactly like any other unknown field name. A package still
shipping the whole 2.x shape is overlaid by the legacy migrator as before, which
maps its :yaml:`year` onto :yaml:`date` and drops the :yaml:`number` flag it
brings, reporting that in its notes.

A template or a Fluid override that prints :html:`{item.year}`,
:html:`{item.yearStart}` or :html:`{item.yearEnd}` renders nothing. The
properties are :html:`{item.date}`, :html:`{item.dateStart}` and
:html:`{item.dateEnd}`, and they are date objects, so they are rendered through
:php:`\FGTCLB\AcademicPersons\ViewHelpers\Format\ProfileInformationDateViewHelper`
rather than printed — see
:ref:`feature-configurable-profile-information-date-display`.

Affected Installations
======================

Every installation of :composer:`fgtclb/academic-persons` that stores timeline
entries, and every site package that configures or renders them.

..  index:: Backend, Database, Frontend, TCA, ext:academic_persons
