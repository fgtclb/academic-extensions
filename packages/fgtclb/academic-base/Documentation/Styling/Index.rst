..  index:: ! Styling
..  _styling:

=======
Styling
=======

The academic extensions render their frontend markup without a look of its
own. Every element carries a class that says what it is, the classes follow the
same system in every extension, and the extensions ship no stylesheet for them.
The look is the job of the site package of a project.

This chapter describes that system once for all academic extensions. The
chapter :guilabel:`Styling` of each extension shows how the markup of its
plugins is built up, see :ref:`styling-extensions`.

..  contents::
    :local:
    :depth: 2

..  _styling-shipped:

What the extensions ship
========================

*   **Markup and classes.** The templates, partials and layouts below
    :file:`Resources/Private/` of each extension. Their classes are described
    below.
*   **Behaviour.** The JavaScript modules that make a list filter in place,
    open a dialog of the study plan or run the profile editor. They find their
    elements by `data-*` attributes, with one exception, and each extension
    can switch its scripts off per site, see :ref:`styling-scripts`.
*   **No styling.** No extension ships a stylesheet for its markup. The
    stylesheets an extension loads are those of a library one of its scripts
    needs to work, the map library of the partner map, and they are loaded and
    switched off together with that script. The one other case is the
    standalone set `fgtclb/academic-persons-standalone` of
    :guilabel:`EXT:academic_persons`, an opt-in page object for an
    installation without a site package of its own, which loads the
    stylesheet and the script of Bootstrap 5 from jsDelivr. It renders a plain
    Bootstrap page around the plugins and does not style their markup.

A project that installs an academic extension and adds no styles of its own
sees the markup as its theme styles plain HTML: headings, lists, links, form
fields and images, laid out by the grid classes the markup keeps
(:ref:`styling-kept-classes`).

..  _styling-class-system:

The class system
================

An element carries up to three kinds of class:

#.  The **outermost element** of a plugin or page names the extension and the
    plugin, `academic-<extension>-<plugin>`.
#.  **Speaking classes** with the prefix `ace-` name what an element is, the
    same in every extension.
#.  A few **classes of Bootstrap and TYPO3** are kept on purpose, because a
    TYPO3 site styles them already.

..  _styling-outermost-element:

The outermost element
---------------------

Every plugin renders one outermost element with a class of the pattern
`academic-<extension>-<plugin>`, every page of a page type of an extension one
with `academic-<extension>-page`:

..  code-block:: html

    <div class="academic-jobs-list">…</div>
    <div class="academic-programs-page container">…</div>

A few outermost classes do not follow the pattern exactly:

*   The partnerships list and teaser of :guilabel:`EXT:academic_partners`,
    `academic-partnerships-list` and `academic-partnerships-teaser`, name the
    partnerships rather than the extension.
*   The profile editor of :guilabel:`EXT:academic_persons_edit` is
    `academic-persons-profile-editing`. Its profile list carries two classes,
    `academic-persons-edit academic-persons-profile-editing-list`.
*   The study plan is a content element without plugins and carries
    `academic-study-plan`.

The outermost class is the only class that tells the extensions and their
plugins apart, and it is therefore the scope of every rule of a project
stylesheet, see :ref:`styling-project`. The outermost element of each plugin is
listed in the chapter :guilabel:`Styling` of its extension.

A plugin leaves its width to the layout of its content element and carries no
`container`. A page of a page type, a program, partner or project page, renders
the main content of the site layout and carries `container` next to its class.

..  _styling-speaking-classes:

Speaking classes
----------------

A speaking class names the thing an element is, not the look it has in a
theme: `ace-item` is one record of a list, whatever extension renders it, and
`ace-label` the label of a value, whether it sits in a card, a table or a form.
`ace` stands for academic extensions.

An element carries the general class first and a more specific one next to it
where there is one: the attributes of an item are an `ace-list ace-attributes`
of `ace-list-item ace-attribute` entries, a filter is an
`ace-filter ace-field ace-select-wrap`. A stylesheet that styles every list of
a plugin selects `ace-list`, one that styles only the attributes selects
`ace-attributes`.

..  list-table::
    :header-rows: 1
    :widths: 22 38 40

    *   -   Family
        -   Classes
        -   Meaning
    *   -   Content
        -   `ace-content`, `ace-section`, `ace-hero`
        -   The content of a plugin below the header of its content element,
            or of a part of it below its own head, a section with a title of
            its own, and the head of a page with its title and media.
    *   -   Items
        -   `ace-itemlist`, `ace-item`, `ace-item-content`, `ace-empty`
        -   A list of records, one record, an :html:`<article>` or a table
            row, the text of a record next to its image, and the message of a
            list without records.
    *   -   Lists
        -   `ace-list`, `ace-list-item`
        -   Any list, and one entry of it, also the parts of a value that has
            several, the addresses of a contact for example.
    *   -   Attributes
        -   `ace-attributes`, `ace-attribute`, `ace-label`, `ace-value`,
            `ace-icon`
        -   The properties of a record, one property, its label, its value and
            the icon in front of it.
    *   -   Headings
        -   `ace-header`, `ace-title`, `ace-subtitle`, `ace-name`
        -   The head of an element, mostly the :html:`<header>` around a
            heading, the heading or title of a record, a section or a dialog,
            the subtitle below it, and a name of a person.
    *   -   Links
        -   `ace-link`
        -   A link that is not a button: the link back to a list, the link of
            an active filter, the link that resets the filters.
    *   -   Actions
        -   `ace-actions`, `ace-controls`
        -   The group of buttons or controls of an element.
    *   -   Dialogs
        -   `ace-dialog`, `ace-close`
        -   A :html:`<dialog>`, and the button that closes it or a message.
    *   -   Forms
        -   `ace-form`, `ace-field`, `ace-<type>-wrap`, `ace-label`,
            `ace-required`, `ace-control`, `ace-<type>`, `ace-help`,
            `ace-field-errors`, `ace-field-error`, `ace-errors`
        -   A form, one field with its label, the required mark, the control
            and its type, the help text and the messages of a field that failed
            validation, and the message above a form with such a field. See
            :ref:`styling-markup-form`.
    *   -   Filters
        -   `ace-filters`, `ace-filter`, `ace-more`, `ace-toggle`
        -   The filter and sorting bar of a list, one filter, the
            :html:`<details>` element that holds the filters beyond the visible
            ones, and its :html:`<summary>`.
    *   -   Active filters
        -   `ace-active-filters`, `ace-active-filter`
        -   The list of the filters a visitor applied, next to `ace-list`, and
            one of them, next to `ace-list-item`. Each holds an `ace-link`
            that removes it, with an `ace-icon`.
    *   -   Count and status
        -   `ace-count`, `ace-status`
        -   A number, the number of results of a list for example, and the
            live region a script announces a changed result in. The live
            region is `visually-hidden` as well.
    *   -   Pagination
        -   `f3-widget-paginator`
        -   The pages of a list, see :ref:`styling-kept-classes`.
    *   -   State
        -   `ace-state`
        -   The state of a record shown as text, with the state as a second
            class, `ace-state active` for a running project for example.
    *   -   Contact
        -   `ace-contact`, `ace-role`
        -   A block of contact data, and the role of a contact person.
    *   -   Media
        -   `ace-image`, `ace-picture`, `ace-figure`, `ace-caption`,
            `ace-description`, `ace-copyright`, `ace-map`
        -   An image, the :html:`<picture>` around its sources, the
            :html:`<figure>` of an image with a caption, the caption with its
            description and copyright, and a map. See
            :ref:`styling-markup-image`.
    *   -   Tables
        -   `ace-table`, `ace-item`, `ace-label`, `ace-value`
        -   A table view, its rows and its header and data cells.

An extension with markup of its own adds a family with the same prefix, the
semesters and modules of the study plan, `ace-semester` and `ace-module`, for
example. Those are described in the chapter of that extension.

..  _styling-states:

States
------

A state an element is in is a class without prefix, or an HTML attribute:

..  list-table::
    :header-rows: 1
    :widths: 25 75

    *   -   State
        -   Meaning
    *   -   `active`
        -   The current entry of a navigation or pagination.
    *   -   `disabled`
        -   An entry of a navigation or pagination that cannot be followed.
    *   -   `invalid`
        -   A form field, and its wrapper, that failed validation, with
            `aria-invalid="true"` on the field. The profile editor of
            :guilabel:`EXT:academic_persons_edit` names this state
            `is-invalid`, the name of Bootstrap, see its chapter.
    *   -   :html:`open`
        -   The attribute of an open :html:`<details>` or :html:`<dialog>`.
    *   -   :html:`hidden`
        -   The attribute of an element a script shows when it is needed.

A state a script sets is named in the chapter of the extension the script
belongs to.

..  _styling-kept-classes:

Classes of Bootstrap and TYPO3 kept on purpose
----------------------------------------------

The markup keeps a few classes it did not invent. They belong to systems a
TYPO3 site provides already, either through Bootstrap, which the most used
site packages build on, or through TYPO3 itself. A project with such a theme
gets layout, buttons and accessible hiding without a line of its own. The
markup uses no other class of Bootstrap: no cards, no spacing or display
utilities, no form classes. The one exception is the flash message of the job
list and the job detail of :guilabel:`EXT:academic_jobs`, the confirmation
after a submitted job. It is rendered by TYPO3 itself, as a
:html:`<ul class="typo3-messages">` of :html:`<li class="alert alert-<severity>">`
entries, with the alert classes of Bootstrap.

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Classes
        -   Why they are kept
    *   -   `container`
        -   The content width of a page of a page type. A plugin carries none,
            its width is the one of its content element layout.
    *   -   `row`, `col-*`
        -   The grid of the item lists, the filter bars, the forms and the
            columns of a detail view. A responsive grid is layout, not look,
            and every Bootstrap theme provides it.
    *   -   `btn`, `btn-*`
        -   The buttons of a form and links that act as a button, the
            application link of a program for example, with the button group
            `btn-group` and the close button `btn-close` of the profile editor.
            A theme styles its buttons through these classes, so the buttons of
            the plugins look like those of the site. Where the plugin has to
            tell its button from others, a speaking class stands next to them.
    *   -   `visually-hidden`
        -   Text that a screen reader reads and a visitor does not see, the
            live region of a list for example. Hiding it is accessibility, not
            look, and must work on every site.
    *   -   `f3-widget-paginator`, `page-item`, `page-link`
        -   The list of pages of a pagination is an `f3-widget-paginator`, the
            name the pagination of TYPO3 uses. The paginations of the job and
            partner lists name their entries `page-item` and `page-link`, as
            Bootstrap does. The pagination of the profile list names them
            `ace-list-item` and `ace-link` and its :html:`<nav>`
            `ace-pagination`. The entries carry `first`, `previous`, `next` and
            `last`, the current one `active`, the gaps `disabled`.
    *   -   `ce-bodytext`
        -   The class the core templates of :guilabel:`fluid_styled_content`
            give the text of a content element. Every field the extensions
            render from the rich text editor is wrapped in an element with this
            class, so the content styles of the site apply to it.

A site without Bootstrap defines these few classes itself. `visually-hidden`
is the one that must not be left out, or text meant for screen readers shows on
the page:

..  code-block:: css

    .visually-hidden {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
    }

..  _styling-icons:

Icons
-----

The icons of the frontend are the shared icons of this extension, the
`tx-academicbase-*` identifiers, and a few of the extensions themselves, see
:ref:`icons`. The icon view helper of this extension renders them with the
markup of the icon API of TYPO3, the same as :html:`<core:icon>` renders: a
:html:`<span>` with `icon`, `icon-size-<size>` and `icon-<identifier>` around
an `icon-markup` holding the inline SVG.

The shipped icons are drawn in `currentColor` and are `1em` wide and high, so
they take the colour and the font size of the text around them. The templates
wrap an icon in an `ace-icon` where it belongs to an attribute or a contact
line, so a stylesheet colours, sizes and aligns the icons of a plugin through
`.ace-icon`. A site package that wants another glyph replaces the icon itself,
see :ref:`icons-override`, not the template.

..  _styling-scripts:

Scripts and classes
-------------------

The JavaScript modules of the extensions find the elements they work with by
`data-*` attributes and ids. A project may therefore add, remove or rename a
class in a template override without breaking a script. An override has to
keep the `data-*` attributes and ids of the elements it copies.

The one exception is the rich text editor of the new job form of
:guilabel:`EXT:academic_jobs`: its script starts the editor on every text area
with the class `ace-ckeditor`. An override of the form keeps that class on the
text areas that get the editor.
A few classes are written by a script, to mark a state a stylesheet should
show. The chapter of the extension names them.

Every extension that loads a script has one switch for it per site, the site
setting or TypoScript constant `plugin.tx_<extension>.assets.js`, for example
`plugin.tx_academicprograms.assets.js`. Switched off, the page loads neither
the script nor the stylesheets of a library it needs, and the markup stays as
it is, for a script of the project to drive. Each extension describes its
switch in its chapter :guilabel:`Configuration`.

..  _styling-project:

Styling the markup in a project
===============================

..  _styling-project-stylesheet:

A stylesheet in the site package
--------------------------------

The styles belong in the site package of the project, as one more stylesheet
of it, loaded like the others. With TypoScript:

..  code-block:: typoscript

    page.includeCSS.academic = EXT:site_package/Resources/Public/Css/academic.css

Or in the page template of the site package, so it is only loaded with the
page layout that needs it:

..  code-block:: html

    <f:asset.css
        identifier="siteAcademic"
        href="EXT:site_package/Resources/Public/Css/academic.css"
    />

..  _styling-project-scope:

Scope every rule by the outermost element
-----------------------------------------

The speaking classes mean the same in every extension, so a rule for `.ace-item`
alone styles the items of every academic plugin on the site. Scope a rule by
the outermost element of the plugin it is meant for, and leave it unscoped only
where every plugin should look the same:

..  code-block:: css

    /* The same for every academic plugin */
    .ace-attribute .ace-label {
        font-weight: 600;
    }

    /* The program cards only */
    .academic-programs-list .ace-item {
        display: flex;
        flex-direction: column-reverse;
        height: 100%;
        border: 1px solid var(--bs-border-color, #dee2e6);
    }

    /* The job detail only */
    .academic-jobs-detail .ace-attributes {
        list-style: none;
        padding-left: 0;
    }

A theme built with SCSS on Bootstrap can give the speaking classes the look of
its own components rather than repeating their declarations:

..  code-block:: scss

    .academic-programs-list .ace-item {
        @extend .card;
    }

    .academic-programs-list .ace-item-content {
        @extend .card-body;
    }

..  _styling-project-reference:

A complete stylesheet as a reference
------------------------------------

The mono repository the academic extensions are developed in carries a
development site package, `academics_dev_site`, and its stylesheet is a
working example of the approach above. It is never shipped with an extension.
Its sources are one SCSS partial per extension below
:file:`packages-dev/dev-site/Resources/Private/Scss/frontend/`, collected by
:file:`academic-extensions.scss` and compiled into
:file:`packages-dev/dev-site/Resources/Public/Css/frontend/academic-extensions.css`,
which the development instances include on every page with
:typoscript:`page.includeCSS`:

..  list-table::
    :header-rows: 1
    :widths: 40 60

    *   -   Partial
        -   Styles
    *   -   `_academic-partners.scss <https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-partners.scss>`__
        -   The partner map.
    *   -   `_academic-persons.scss <https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons.scss>`__
        -   The profile detail.
    *   -   `_academic-persons-list.scss <https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons-list.scss>`__
        -   The profile lists and cards.
    *   -   `_academic-persons-edit.scss <https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-persons-edit.scss>`__
        -   The profile editor.
    *   -   `_academic-study-plan.scss <https://github.com/fgtclb/academic-extensions/blob/main/packages-dev/dev-site/Resources/Private/Scss/frontend/_academic-study-plan.scss>`__
        -   The study plan.

The stylesheet is written against the markup and the Bootstrap 5 classes it
carries, on top of the Bootstrap Package, the theme of the main page tree of
the instances. The plugins of the other extensions have no partial: there, the
theme alone styles them, through the grid and button classes they keep.

..  _styling-project-override:

Override a template only to change the markup
---------------------------------------------

A look is changed with a stylesheet. A template override is the tool for a
change of the markup itself, a field the template does not render or an
element in another order, and it has a price: the copy misses every correction
made to the original. The extensions therefore build their templates from small
partials, so an override replaces the piece that has to change and nothing
else. Each extension describes its partials in its chapter
:guilabel:`Templates`, the shared partials of this extension are described in
:ref:`templates`.

The classes are part of the public API of an extension. A class is renamed or
removed only with a breaking changelog entry, which names the class before and
after, so a project stylesheet can follow.

..  _styling-markup:

The markup of this extension
============================

This extension renders no plugin of its own. It ships partials the other
extensions render inside their plugins: the fields of a frontend form and the
responsive image. Their arguments are described in :ref:`templates`.

..  _styling-markup-form:

A form field
------------

A field partial of :file:`Partials/Academic/Form/` renders a field like this,
here a text field that failed validation:

..  code-block:: html

    <div class="ace-field invalid ace-text-wrap">
        <label class="ace-label" for="job.title">
            Title
            <abbr class="ace-required" title="required">*</abbr>
        </label>
        <input class="ace-control ace-text invalid" id="job.title" aria-invalid="true"
            aria-describedby="job.title-error" … />
        <div id="job.title-error" class="ace-field-errors">
            <div class="ace-field-error">…</div>
        </div>
        <div class="ace-help">…</div>
    </div>

The wrapper carries the class of the type of the field, `ace-text-wrap`,
`ace-textarea-wrap`, `ace-select-wrap`, `ace-checkbox-wrap`, `ace-date-wrap` or
`ace-upload-wrap`, and the control `ace-control` with its type, `ace-text`,
`ace-textarea`, `ace-select`, `ace-checkbox`, `ace-date` or `ace-upload`. A
text area with the rich text editor carries `ace-ckeditor` as well. The
messages and the help text are only rendered when there are any. A form with a
field that failed validation shows its message above the form in an
`ace-errors`.

The filter selects of the list plugins follow the same names, an `ace-filter`
next to `ace-field ace-select-wrap` with an `ace-control ace-select`, so a
stylesheet that styles the form fields styles the filters as well.

..  _styling-markup-image:

An image
--------

The image partial :file:`Partials/Academic/Image.html` renders a raster image
as a :html:`<picture>` and an SVG image or the placeholder as a single
:html:`<img>`:

..  code-block:: html

    <picture class="ace-picture">
        <source … />
        <img class="ace-image" … />
    </picture>

An image with a caption or a copyright is wrapped in a figure:

..  code-block:: html

    <figure class="ace-figure">
        <picture class="ace-picture">…</picture>
        <figcaption class="ace-caption">
            <span class="ace-description">…</span>
            <small class="ace-copyright">© …</small>
        </figcaption>
    </figure>

A template that renders the partial may pass a class of its own, which the
:html:`<img>` carries after `ace-image`.

..  _styling-extensions:

The markup of each extension
============================

How the plugins of an extension are built up, with their outermost element and
the classes they add to the system above, is described in the chapter
:guilabel:`Styling` of each manual:

*   `academic_bite_jobs <https://docs.typo3.org/p/fgtclb/academic-bite-jobs/main/en-us/Styling/Index.html>`__
*   `academic_contacts4pages <https://docs.typo3.org/p/fgtclb/academic-contacts4pages/main/en-us/Styling/Index.html>`__
*   `academic_jobs <https://docs.typo3.org/p/fgtclb/academic-jobs/main/en-us/Styling/Index.html>`__
*   `academic_partners <https://docs.typo3.org/p/fgtclb/academic-partners/main/en-us/Styling/Index.html>`__
*   `academic_persons <https://docs.typo3.org/p/fgtclb/academic-persons/main/en-us/Styling/Index.html>`__
*   `academic_persons_edit <https://docs.typo3.org/p/fgtclb/academic-persons-edit/main/en-us/Styling/Index.html>`__
*   `academic_programs <https://docs.typo3.org/p/fgtclb/academic-programs/main/en-us/Styling/Index.html>`__
*   `academic_projects <https://docs.typo3.org/p/fgtclb/academic-projects/main/en-us/Styling/Index.html>`__
*   `academic_study_plan <https://docs.typo3.org/p/fgtclb/academic-study-plan/main/en-us/Styling/Index.html>`__
