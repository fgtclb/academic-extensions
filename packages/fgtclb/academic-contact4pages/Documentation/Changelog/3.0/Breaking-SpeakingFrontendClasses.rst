..  _breaking-contacts4pages-speaking-frontend-classes:

===============================================
Breaking: Contact list carries speaking classes
===============================================

Description
===========

The frontend templates of this extension carried the classes of the theme they
were developed with - cards, list groups, form controls, spacing and display
utilities - next to block and element classes of the extension. They carry
speaking classes now, named after what an element is and shared by all academic
extensions: `ace-item`, `ace-list`, `ace-attribute`, `ace-label`, `ace-value`,
`ace-link` and so on. The outermost element of a plugin keeps the class of the
plugin. The classes of Bootstrap that a site builds on stay on purpose where
the templates render them: the grid classes `row` and `col-*`, the button
classes `btn` and `btn-*` and `visually-hidden`. No other theme class is
rendered any more, and the extension ships no styles for the new classes.

The card of a contact is the profile card of `EXT:academic_persons`, which
keeps its classes.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Contact list
        -   `academic-contacts4pages`
        -   `academic-contacts4pages-list`, the contacts in an `ace-content`
    *   -   Group of contacts
        -   a `row` of grid columns
        -   `ace-itemlist` around the `row`, every card in an `ace-item` inside its column
    *   -   Role of a contact
        -   `academic-contacts4pages__role`
        -   `ace-role`

Impact
======

*   A site stylesheet that selects `academic-contacts4pages` or the role
    through `academic-contacts4pages__role` no longer matches.

Affected Installations
======================

Every installation that renders the contacts content element.

Migration
=========

#.  Move the selectors of the site stylesheet from the classes in the
    column *Before* to those in the column *After*. A site that styled the
    plugins through the classes of its Bootstrap theme alone gives the new
    classes the look it wants, for example with :css:`@extend` in SCSS or
    with rules of its own.
#.  A template override is compared with the shipped template of 3.0 and
    adopts its markup. An override keeps working as it is, but renders its
    own classes, and nothing in the extension relies on them.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_contacts4pages
