.. _breaking-profile-editing-uses-native-date-controls:

==================================================
Breaking: A date is edited in the browser's picker
==================================================

Description
===========

Every date field of the frontend profile editor is the browser's own date
control now — the three dates of a timeline entry, which
`became real dates <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Changelog/3.0/Breaking-ProfileInformationTimelineUsesDates.html>`__
in :composer:`fgtclb/academic-persons`, and the two contract dates
:php:`validFrom` and :php:`validTo` alike.

The two contract dates were a plain text control showing ``d.m.Y`` behind a
``dd.mm.yyyy`` placeholder, because the native control had been tried and
reverted while a picker was still an open decision. It is decided: the editor
uses the browser's calendar. The display format of that control follows the
browser's own locale and cannot be influenced by a page — no attribute, no
stylesheet, no script — so what the site language governs is everything the
server renders instead: the compact rows, the display values of the JSON
responses and the field hints.

What changes for a client of the editor's JSON endpoints:

*   A date field descriptor carries a new key :js:`granularity`, one of
    ``date``, ``month`` or ``year``. Its :js:`type` is unchanged and still
    ``date`` — that is the field type, not the control type.
*   :js:`value` is the date in that granularity's own format: ``2019-03-14``,
    ``2019-03`` or ``2019``. It was ``d.m.Y`` for the contract dates.
*   :js:`placeholder` is empty for a date field. The ``dd.mm.yyyy`` hint is
    gone, and so is the constant that held it.
*   :js:`min`, :js:`max` and :js:`step` are ``1000``, ``9999`` and ``1`` for
    the ``year`` granularity and :js:`null` for the other two. They were ``0``,
    ``9999`` and ``1`` on the three timeline year fields, which were number
    controls; a number control cannot emit the leading zeros a year below 1000
    needs, so a field that has to carry one asks for the complete-date
    granularity instead.
*   :js:`displayValue` shows the parts the field publishes, formatted for the
    locale of the matched site language.

The endpoint keeps accepting the full ISO format at every granularity, and the
German ``d.m.Y`` notation at the full one, so a client written against either
shape keeps working.

Three labels are renamed with the record's properties:
``profileInformation.year.label`` becomes ``profileInformation.date.label``,
``profileInformation.yearStart.label`` becomes ``.dateStart.label`` and
``profileInformation.yearEnd.label`` becomes ``.dateEnd.label``. The column
heading ``profileEditing.documents.year`` becomes
``profileEditing.documents.date``.

The partial :file:`Partials/Profile/Documents/YearValue.html` is replaced by
:file:`Partials/Profile/Documents/DateValue.html`, which takes the record type
and the field name so that it can resolve how much of the date its section
publishes. The compact timeline row emits
:html:`data-pe-document-value="dateStart"`, ``"dateEnd"`` and ``"date"`` in
place of the three year names.

Impact
======

An installation that overrode :file:`YearValue.html`, or that styled or scripted
against the three ``data-pe-document-value`` names, has to follow the rename. A
stylesheet that assumed a text input where a date field is rendered meets a
native control, which a browser draws in its own way and which cannot be styled
to look like a text field.

A client of the JSON endpoints that read ``d.m.Y`` out of a contract date
descriptor now reads ``Y-m-d``. Sending it either way keeps working.

Affected Installations
======================

Every installation of :composer:`fgtclb/academic-persons-edit` that ships the
profile editor, and every site package that overrides its date partials or
styles its date controls.

..  index:: Frontend, Fluid, JavaScript, ext:academic_persons_edit
