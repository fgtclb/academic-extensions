.. _feature-shared-date-primitives:

=================================================
Feature: Shared date primitives for a site locale
=================================================

Description
===========

Three extensions of this repository render and read dates, and each of them
used to spell its own format. The namespace ``FGTCLB\AcademicBase\Date`` holds
the pieces they share now. Everything in it is :php:`@internal`; the value objects carry a
:php:`__set_state()` because the settings graph is cached as a
:php:`var_export()`, and the two services are stateless.

..  code-block:: text

    DateDisplay             which parts of a date are shown; produces an ICU skeleton
    DateGranularity         DATE, MONTH, YEAR - the control type and the exchange format
    DateCompletionEdge      FIRST, LAST
    DateCompletion          the month and day edges; completes a partial date
    DateFieldSettings       a display, a granularity and a completion, as one field's configuration
    LocalizedDateFormatter  a date plus a DateDisplay plus a Locale, as a string
    DateValueParser         a submitted string at a granularity, as a date

:php:`LocalizedDateFormatter` formats a complete date with TYPO3's
``MEDIUMDATE`` keyword and anything narrower from the skeleton
:php:`DateDisplay` produces, through :php:`\IntlDatePatternGenerator`, so the
order and the separators are the locale's own rather than ours. ``ext-intl`` is
an unconditional requirement of :composer:`typo3/cms-core` on both supported
TYPO3 versions, so nothing guards for it.

:php:`DateValueParser` reads a value strictly: :php:`createFromFormat()` accepts
``2019-02-31`` and answers with the third of March, so the parsed date is
formatted back and compared with what came in. It accepts the granularity's own
format at every granularity, always the full ISO format, and the German
``d.m.Y`` notation at the full one.

The ViewHelper
:php:`\FGTCLB\AcademicBase\ViewHelpers\Format\LocalizedDateViewHelper` renders a
date for the locale of the matched site language, resolving it the way TYPO3
core's own :php:`f:format.date` does:

..  code-block:: html

    <html xmlns:b="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers">
        <b:format.localizedDate date="{job.employmentStartDate}"/>
        <b:format.localizedDate date="{item.date}" month="0" day="0"/>
    </html>

:php:`FGTCLB\AcademicBase\Settings\Validation` carries a
:php:`DateFieldSettings` for every field, never null, so a consumer that already
resolves a validation resolves the date configuration with it. Its new
:php:`getControlInputType()` turns a ``date`` field type into the concrete
input type of its granularity, for everything the server renders.

Impact
======

Nothing existing changes its behaviour. The classes are :php:`@internal` and
exist for :composer:`fgtclb/academic-persons`,
:composer:`fgtclb/academic-persons-edit` and :composer:`fgtclb/academic-jobs`,
which use them from 3.0.0 on.

..  index:: Fluid, Localization, ext:academic_base
