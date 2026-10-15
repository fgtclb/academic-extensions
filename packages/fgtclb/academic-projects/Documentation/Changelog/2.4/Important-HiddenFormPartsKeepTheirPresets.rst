.. _important-hidden-project-form-parts-keep-their-presets:

===========================================================
Important: A hidden part of the form keeps the preset value
===========================================================

Description
===========

Both project lists applied the :guilabel:`Categories`, the :guilabel:`Sorting`
and the :guilabel:`Active state` of the content element only to a request
without any selection of the visitor. Once a visitor used the visible part of
the form, every part the editor hid lost its preset as well:

*   With :guilabel:`Show filter options` switched off and another part shown, a
    visitor who changed the sorting or the state got the projects of every
    category instead of those of the preset categories.
*   With :guilabel:`Show sorting options` switched off, a visitor who chose a
    category or a state got the projects sorted by title, A to Z, instead of
    by the preset sorting.
*   With :guilabel:`Show active state options` switched off, a visitor who
    changed the sorting or chose a category got the projects in every state,
    the completed ones of a list of running projects included.

A part of the form the content element hides now keeps the value of the
content element. The sorting of hidden sorting selects and the state of a
hidden state select are always the preset ones, also where a link or a
hand-made URL carries another value. The preset categories of hidden category
selects always apply. Categories a submission, a link or a hand-made URL
carries for such a list are added to them, so they can narrow the list further
but never replace a preset category. For a part of the form the content
element shows, the selection of the visitor applies as before.

Impact
======

*   A list preset to the active projects with the state select hidden shows
    only active projects, also after the visitor chose a category or changed
    the sorting.
*   A list with hidden category selects keeps its preset categories, and a list
    with hidden sorting selects keeps its preset sorting, when the visitor uses
    the rest of the form. A link that filters a list with hidden category
    selects by another category shows the projects that carry the preset
    categories and that category.
*   A list with hidden category selects and no preset categories is still
    filtered by the categories of a link, as before.
*   An installation that hid the whole form to keep one preset can show the
    other parts again.
*   Behaviour is identical on every supported TYPO3 version.

.. index:: Frontend, ext:academic_projects
