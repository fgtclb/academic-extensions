..  _breaking-projects-speaking-frontend-classes:

==================================================
Breaking: Project templates carry speaking classes
==================================================

Description
===========

The frontend templates of this extension carried the classes of the theme they
were developed with - cards, list groups, form controls, spacing and display
utilities - next to block and element classes of the extension. They carry
speaking classes now, named after what an element is and shared by all academic
extensions: `ace-item`, `ace-list`, `ace-attribute`, `ace-label`, `ace-value`,
`ace-link` and so on. The outermost element of a plugin keeps the class of the
plugin. The classes of Bootstrap that a site builds on stay on purpose where
the templates render them: `container` on the outermost element of a page of
the page type, the grid classes `row` and `col-*`, the button classes `btn` and
`btn-*` and `visually-hidden`. No other theme class is rendered any more, and
the extension ships no styles for the new classes.

The header partials take the class of the heading as the argument `class`,
in place of `positionClass`. An override that renders one of them with
`positionClass` renders the heading without that class.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Item list
        -   `row academic-projects-itemlist`
        -   `ace-itemlist` around the `row`
    *   -   Item
        -   `card flex-column-reverse academic-projects-item mt-4` with `card-content` and `card-body`
        -   `<article class="ace-item">` with `ace-item-content`
    *   -   Attributes of an item
        -   `list-group list-group-flush` with `list-group-item` entries
        -   `ace-list ace-attributes` with `ace-list-item ace-attribute` entries, `ace-icon`, `ace-label` and `ace-value` inside
    *   -   No result
        -   a plain `<span>`
        -   `ace-empty`
    *   -   Filter form
        -   `academic-projects-filtersorting`
        -   `ace-form`, the fields in an `ace-filters`
    *   -   Filter cell
        -   the grid column only
        -   `ace-filter ace-field ace-select-wrap` inside the column
    *   -   Label and select
        -   `form-label`, `form-select`
        -   `ace-label`, `ace-control ace-select`
    *   -   More filters
        -   `<details class="col-12 academic-projects-more-filters">`
        -   `<details class="ace-more">` inside a `col-12`, with `<summary class="ace-toggle">`
    *   -   Result count
        -   `academic-projects-result-count`
        -   `ace-count`
    *   -   Active filters
        -   `academic-projects-active-filters` with `__tags`, `__tag`, `__remove`
        -   `ace-filters` around `<ul class="ace-list ace-active-filters">` with `ace-list-item ace-active-filter` entries, the remove mark `ace-icon`
    *   -   Reset link
        -   `academic-projects-active-filters__reset`
        -   `ace-link`
    *   -   Page
        -   `academic-projects-detail container`
        -   `academic-projects-page container`
    *   -   Header and media
        -   `d-flex flex-column-reverse`
        -   `ace-hero`
    *   -   Page header
        -   `academic-projects-detail__header`
        -   `<header class="ace-header">` with `ace-title` on the heading
    *   -   Subtitle
        -   `academic-projects-detail__subtitle`
        -   `ace-subtitle`
    *   -   Short description of an item
        -   `card-text`
        -   `ce-bodytext`
    *   -   Active state
        -   `<p class="mb-2">` around a `badge text-bg-success` or `badge text-bg-secondary` with `academic-projects-item__state--<state>`
        -   `<p class="ace-state <state>">`, `active` or `completed`
    *   -   Facts and categories of the page
        -   plain lists
        -   `ace-list ace-attributes` with `ace-attribute` entries, `ace-label` and `ace-value`

Impact
======

*   A site stylesheet that styles the project list, the filters or the
    project page through the classes of the column *Before* no longer
    matches.
*   The state of a project is a paragraph without the badge classes of a
    Bootstrap theme.

Affected Installations
======================

Every installation that renders the project list, the selected projects or a
page of the project page type.

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
    :file:`Project/Header.html`.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_projects
