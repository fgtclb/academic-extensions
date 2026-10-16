.. _important-project-sorting-given-as-a-list-is-ignored:

==========================================================
Important: A sorting or a state given as a list is ignored
==========================================================

Description
===========

Both project lists read their sorting and their active state as one value
each. Given a list of values instead, the content element stopped with a
:php:`TypeError`:

*   TypoScript turns the setting :typoscript:`settings.sorting` into a list as
    soon as it has a child, such as
    :typoscript:`plugin.tx_academicprojects.settings.sorting.field = title`.
    Extbase does not replace such a list with the :guilabel:`Sorting` an
    element stores, so every project list the TypoScript applies to failed on
    every request without a selection of the visitor.
*   A submission or a link whose demand carries a list where the form sends
    one value, such as
    `tx_academicprojects_projectlist[demand][sortingField][]=title`, failed
    for the sorting field, the sorting direction, the combined sorting and the
    active state alike.

An :guilabel:`Active state` given as a list by TypoScript raised a PHP
warning, an exception with the debug preset, and otherwise showed all
projects, whatever state the element stores.

Such a list is now ignored like an unknown value. The element sorts by title,
A to Z, the default of the extension, and shows the projects of every state,
unless another value of the request or of the element applies.

Impact
======

*   An element that failed with a :php:`TypeError` of
    :php:`ProjectDemand::setSorting()`, :php:`setSortingField()`,
    :php:`setSortingDirection()` or :php:`ActiveState::tryFromDefault()`
    renders its projects again, sorted by title, A to Z, also where it stores
    a :guilabel:`Sorting` of its own. Remove the child from the TypoScript
    setting, or replace the whole setting by a single value such as
    :typoscript:`plugin.tx_academicprojects.settings.sorting = title desc`,
    to get the sorting of the element back. A value next to a child keeps the
    setting a list.
*   A list given as the active state of the TypoScript settings no longer
    raises a PHP warning, nor an exception with the debug preset.
*   A value of the selects of the element and of the form is applied as
    before.
*   Behaviour is identical on every supported TYPO3 version.

.. index:: Frontend, ext:academic_projects
