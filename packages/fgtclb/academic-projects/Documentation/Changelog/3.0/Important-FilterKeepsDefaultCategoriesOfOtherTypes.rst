.. _important-project-list-keeps-default-categories-of-other-types:

==============================================================
Important: A filter keeps the preset categories of other types
==============================================================

Description
===========

A selection submitted to one of the project lists replaced all of its preset
:guilabel:`Categories`, also those of a category type the form did not offer.
The form offers the category types
:typoscript:`plugin.tx_academicprojects.filter.categoryTypes` names, so a list
preset to a category of a type left out there lost that category as soon as
the visitor filtered by another type.

A submitted selection now replaces the preset categories of the category types
the form offers, and the preset categories of every other type stay. Choosing
"All" in a select still clears the preset category of that type. A link that
carries a category filter, as the address of a filtered list and the active
filter tags do, is still the whole selection.

A list whose category selects are hidden keeps all its preset categories, see
:ref:`important-hidden-project-form-parts-keep-their-presets`.

Impact
======

*   A list preset to a category of a type the filter does not offer keeps that
    category when the visitor filters, and the address of the filtered list
    carries it.
*   A list whose filter offers the types of all its preset categories behaves
    as before.
*   Behaviour is identical on TYPO3 v13 and v14.

.. index:: Frontend, ext:academic_projects
