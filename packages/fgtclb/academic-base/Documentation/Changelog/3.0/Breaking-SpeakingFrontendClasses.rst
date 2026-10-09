..  _breaking-base-speaking-frontend-classes:

========================================================
Breaking: Form and image partials carry speaking classes
========================================================

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

The form field partials (:file:`Partials/Academic/Form/`) and the image partial
(:file:`Partials/Academic/Image.html`) are shared by the other academic
extensions, so their classes appear in the new job form and in every image of
a list item or a page, those of `EXT:academic_persons` included.

..  list-table::
    :header-rows: 1

    *   -   Element
        -   Before
        -   After
    *   -   Field wrapper
        -   the class of the field type, `is-invalid` on error
        -   `ace-field`, `invalid` on error, `ace-text-wrap`, `ace-textarea-wrap`, `ace-select-wrap`, `ace-checkbox-wrap`, `ace-date-wrap` or `ace-upload-wrap`
    *   -   Field wrapper class of a type
        -   `form-textfield-wrap`, `form-textarea-wrap`, `form-select-wrap`, `form-check-wrap`, `form-date-wrap`, `form-upload-wrap`
        -   see the line above
    *   -   Label
        -   `form-label`
        -   `ace-label`, the required mark `ace-required`
    *   -   Field
        -   `form-control`, `form-select` or `form-check-input`
        -   `ace-control` and `ace-text`, `ace-textarea`, `ace-select`, `ace-checkbox`, `ace-date` or `ace-upload`
    *   -   Field that failed validation
        -   `is-invalid`
        -   `invalid`
    *   -   Messages of a field
        -   `<div id="<elementId>-error" class="invalid-feedback">`
        -   `<div id="<elementId>-error" class="ace-field-errors">`, one `ace-field-error` per message
    *   -   Rich text area
        -   `rich-text`
        -   `ace-ckeditor`
    *   -   Help text
        -   `form-text`
        -   `ace-help`
    *   -   Errors of the form
        -   `alert alert-danger`
        -   `ace-errors`
    *   -   Image
        -   the class passed in
        -   `ace-image` followed by the class passed in, `ace-picture` on the picture
    *   -   Caption
        -   `academic-image` with `__caption`, `__description` and `__copyright`
        -   `ace-figure` with `ace-caption`, `ace-description` and `ace-copyright`

Impact
======

*   A site stylesheet that styles the form fields, their messages or the
    images through the classes of the column *Before* no longer matches.
*   The rich text editor of the job form starts on the text areas with the
    class `ace-ckeditor`. An override of :file:`Form/Textarea.html` that kept
    `rich-text` shows a plain text area.
*   A Bootstrap theme no longer shows the messages of a field next to it,
    because it shows `invalid-feedback` only.

Affected Installations
======================

Every installation that renders a form of an academic extension, or an image
through the shared image partial, and styles it through its classes.

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
#.  An override of a field partial that passes `errorClass` passes
    `invalid`, and an override of :file:`Form/Textarea.html` sets
    `ace-ckeditor` on a rich text area.
#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, ext:academic_base
