.. _feature-configurable-date-input-granularity:

==================================================
Feature: A date field asks for as much as it needs
==================================================

Description
===========

A date field of the profile editor declares how much of a date its editor is
asked for, and how the parts nobody was asked for are completed:

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
            input:
              granularity: year      # date (the default), month or year
              completeMonth: first   # first or last month of the year
              completeDay: last      # first or last day of the month

The granularity chooses the control, and the control decides what a browser
offers:

..  code-block:: text

    date    <input type="date">     submits  2019-03-14
    month   <input type="month">    submits  2019-03
    year    <input type="number">   submits  2019

A year is a number control because **no browser has a year input**, and it
carries the bounds ``1000`` to ``9999``. The lower bound is the lowest
four-digit year rather than the ``0`` the integer years allowed: a year is
exchanged as ``Y`` and read back strictly, and a number control cannot emit the
leading zeros a year below 1000 needs. A record that needs an earlier year asks
for a full date instead, where the browser's own control writes ``0800-01-01``.

..  note::
    Desktop Firefox has never implemented :html:`<input type="month">` and
    degrades it to a plain text field. The value it submits is the same
    ``2019-03``, and the server validates it either way, so a form stays usable
    there — it simply has no calendar.

The two completion rules are the four an integrator can name, on their two
axes: a first or last month of the year, and a first or last day of the month.
A last day is the last day of the *resulting* month, so a last day of a last
month of 2019 is the 31st of December, and a last day of February 2020 is the
29th. A part the editor did give is never completed over.

Where a field publishes less than it asks for — the shipped timeline sections
enter a full date and publish the year alone — the editor is told so at the
field, from the labels ``profileEditing.date.published.yearOnly``,
``...yearAndMonth`` and ``...partial``. That hint is what the dropped
``year_only`` column would have needed a place for anyway.

A submitted value is read strictly: :php:`createFromFormat()` accepts
``2019-02-31`` and answers with the third of March, so the parsed date is
formatted back and compared with what came in. A value that does not survive
that is refused with an error at the field, and nothing is written.

Impact
======

A field with no :yaml:`input` block asks for a full date and completes nothing,
which is what every shipped field does. An installation that wants a year or a
month asks for one, and says which end of the period fills the rest.

..  index:: Frontend, ext:academic_persons_edit
