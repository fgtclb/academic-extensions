.. _feature-1790946475:

============================================================
Feature: Active filter tags, a reset link and a result count
============================================================

Description
===========

A visitor who filtered the :guilabel:`Projects` or the
:guilabel:`Projects (selected)` content element saw the selects and nothing else. Three
settings add what was missing, each off by default:

*   :typoscript:`plugin.tx_academicprojects.filter.showActiveFilters` shows one tag per
    selected category, and one for an active state
    other than "All", which links to the state "All". Each tag links to the list without that
    selection, every other selection and the sorting kept, in the URL shape
    of :ref:`feature-1790226101`.
*   :typoscript:`plugin.tx_academicprojects.filter.showReset` shows a
    :guilabel:`Reset all filters` link to the page without any list argument,
    where the list shows what the content element presets. It is offered
    after the visitor selected something, while a category or an active state other than "All" is selected
    or preset, also once the visitor removed the preset.
*   :typoscript:`plugin.tx_academicprojects.filter.showResultCount` shows the number of
    projects found, with a singular and a plural label.

They are site settings of the aggregate set and constants of the static
template, see :ref:`configuration-active-filters`. The category tags follow
the category filter and the state tag the state select: where the content
element hides one, its tags are not shown.

Impact
======

*   Nothing changes until a site switches a setting on.
*   :file:`Project/SortingAndFilters.html` renders the new partials
    :file:`Project/ActiveFilters.html` and :file:`Project/ResultCount.html`.
    A project that overrides :file:`Project/SortingAndFilters.html` shows
    neither until it renders them too, with :html:`{_all}`. The list action
    assigns the new variable :html:`{visitorSelection}` for the reset link: whether
    the request carried a list argument at all.
*   A project that renders tags, a reset link or a count in its own templates
    can switch the settings on and remove its own markup. The classes are
    `academic-projects-active-filters` and `academic-projects-result-count`, without styles.

.. index:: Frontend, TypoScript, NotScanned
