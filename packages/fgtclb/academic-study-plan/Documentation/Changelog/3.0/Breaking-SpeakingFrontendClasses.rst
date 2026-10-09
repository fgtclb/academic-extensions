..  _breaking-study-plan-speaking-frontend-classes:

=====================================================
Breaking: Study plan templates carry speaking classes
=====================================================

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
rendered any more. The stylesheet the extension ships selects the new classes.

The script finds the parts of the plan by their :html:`data-study-plan-*`
attributes, which are unchanged, and writes `highlighted` and `open` as
before.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Content element
        -   `academic-study-plan container`
        -   `academic-study-plan`
    *   -   Filter
        -   a :html:`<nav>` without class around `<ul class="filter">`
        -   `<nav class="ace-filter">` with an `ace-list` of `ace-list-item` entries, the buttons `ace-control`, the toggle `ace-toggle`
    *   -   Semesters
        -   `<ul class="semesters row">`
        -   `<div class="ace-semesters">` around a `<div role="list" class="row">`
    *   -   Semester
        -   `<li class="col">`
        -   `<div role="listitem" class="ace-semester col">`
    *   -   Labels of semesters and modules
        -   `<h3 class="h6">` and `<h4 class="h6">`
        -   `<span class="ace-title">` for both
    *   -   Header of a semester
        -   `header` with `wrapper`, `credits small` and `note small text-muted`
        -   `ace-semester-header` with `ace-semester-content`, `ace-semester-heading`, `ace-semester-credits`, `ace-actions` and `ace-semester-note`
    *   -   Modules
        -   a list without class, `module` entries, `clickable` on a module with a dialog
        -   `ace-list ace-modules` with `ace-list-item ace-module` entries, `ace-interactive` on a module with a dialog
    *   -   Parts of a module
        -   `credits small`, `note small text-muted`, `modal-trigger`, `visually-hidden`
        -   `ace-module-credits`, `ace-module-note`, `ace-module-trigger`, `visually-hidden`
    *   -   Dialog
        -   `wrapper`, the module name in a `<span class="h6">`, `credits small`, `note small text-muted`
        -   `ace-dialog` with `<header class="ace-header">`, the module name in a `<span class="ace-title">`, `ace-dialog-credits`, `ace-dialog-note`, `ace-dialog-description`, `ace-dialog-audio`, the close button `ace-close`
    *   -   Footer
        -   the text without an element of its own
        -   `<div class="ce-bodytext">`

Impact
======

*   A site stylesheet that styles the plan through the classes of the column
    *Before* no longer matches. The shipped stylesheet selects the new classes.
*   The collapsed filter is hidden by the rule
    :css:`.ace-filter .ace-list[hidden] { display: none }` of the shipped
    stylesheet. An installation that switches
    :typoscript:`plugin.tx_academicstudyplan.assets.css` off brings that rule
    in its own stylesheet.
*   An override of a study plan partial keeps working with the script as long
    as it keeps the data attributes.

Affected Installations
======================

Every installation that renders the study plan content element.

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

..  index:: Fluid, Frontend, ext:academic_study_plan
