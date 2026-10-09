..  _breaking-bite-jobs-speaking-frontend-classes:

====================================================
Breaking: B-ITE job templates carry speaking classes
====================================================

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

The header partials take the class of the heading as the argument `class`,
in place of `positionClass`. An override that renders one of them with
`positionClass` renders the heading without that class.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   List view
        -   `academic-bite-jobs-itemlist`
        -   `ace-itemlist ace-list-view`
    *   -   Card view
        -   `academic-bite-jobs-itemcards`
        -   `ace-itemlist ace-card-view`
    *   -   Item
        -   `academic-bite-jobs-item card` with `card-body`
        -   `<article class="ace-item">` with `ace-item-content`
    *   -   Attributes
        -   `list-group list-group-flush` with `list-group-item` entries
        -   `ace-list` with `ace-list-item` entries, `ace-label` and `ace-value`
    *   -   Table view
        -   `table table-striped academic-bite-jobs-itemtable`, `table-primary` on the head, `table-group-divider table-secondary` on the body
        -   `ace-table`, `ace-head`, `ace-body`, rows `ace-item`, cells `ace-label` and `ace-value`
    *   -   Link
        -   `card-link`
        -   `ace-link`
    *   -   No result
        -   a plain `<span>`
        -   `ace-empty`

Impact
======

*   A site stylesheet that styles the three views through the classes of the
    column *Before* no longer matches, and the table renders without the
    striping of a Bootstrap theme.

Affected Installations
======================

Every installation that renders the B-ITE job list.

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
#.  Replace `positionClass` with `class` where an override renders
    :file:`BiteJobs/Header.html`.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_bite_jobs
