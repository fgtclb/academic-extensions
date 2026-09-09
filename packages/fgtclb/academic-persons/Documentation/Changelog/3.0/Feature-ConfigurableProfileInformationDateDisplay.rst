.. _feature-configurable-profile-information-date-display:

=======================================================
Feature: A timeline date shows as much as it is told to
=======================================================

Description
===========

A timeline entry stores a calendar date since 3.0.0
(:ref:`breaking-profile-information-timeline-uses-dates`), but a publication
list that suddenly reads ``01.01.2019`` instead of ``2019`` would be a
regression. How much of a stored date a visitor sees is therefore configuration,
per field and per section, and the parts that are shown are formatted for the
locale of the **matched site language** rather than for the locale of the
visitor's browser.

A document section declares it in a :yaml:`dates` map, keyed by field identifier
next to :yaml:`validators` and :yaml:`helptext`:

..  code-block:: yaml

    documentSections:
      publications:
        rowFields:
          - date
          - title
        validators:
          date:
            - required
            - date
        dates:
          date:
            display:
              year: true
              month: false
              day: false

A profile or contract field carries the same block under a :yaml:`date` key on
the field entry itself. Everything in it is optional, and an unreadable value
falls back to its own default, so a typo narrows nothing and publishes nothing
by accident. An unconfigured field shows the whole date.

The three switches are independent, and the shown parts are written the way the
locale writes exactly those parts — not by cutting a longer format short. For
the 14th of March 2019:

..  code-block:: text

    year, month, day    de-DE  14.03.2019      en-US  Mar 14, 2019
    year, month         de-DE  März 2019       en-US  Mar 2019
    year                de-DE  2019            en-US  2019
    month, day          de-DE  14. März        en-US  Mar 14

A complete date deliberately keeps the notation TYPO3's ``MEDIUMDATE`` produces,
which is what the contract dates and the editor already rendered, so nothing
that is fully published moves.

**The shipped configuration publishes the year alone** for all three dates of all
seven timeline sections, which is exactly what the integer years showed. An
installation that wants the full date switches :yaml:`month` and :yaml:`day` on.

Rendering it needs no format in a template. The ViewHelper
:php:`\FGTCLB\AcademicPersons\ViewHelpers\Format\ProfileInformationDateViewHelper`
resolves the section from the record type and the field from the property name,
so the public profile and the editor's compact rows cannot drift apart:

..  code-block:: html

    <html xmlns:ap="http://typo3.org/ns/FGTCLB/AcademicPersons/ViewHelpers">
        <ap:format.profileInformationDate date="{item.dateStart}" type="{item.type}" field="dateStart"/>
    </html>

An empty date renders nothing at all — no placeholder, no zero, no current date.

Impact
======

An installation that does nothing keeps seeing years, as before. An installation
that wants more switches the parts on where it wants them.

The site language decides the notation, so the same entry reads differently on a
German and on an English page without any per-language configuration, and a
visitor's own browser settings do not change it.

..  index:: Frontend, Fluid, Localization, ext:academic_persons
