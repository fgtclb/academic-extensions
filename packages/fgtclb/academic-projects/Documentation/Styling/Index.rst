..  index:: ! Styling
..  _styling:

=======
Styling
=======

The plugins and the project page of this extension render their markup with
the class system of the academic extensions and ship no styling for it. The
system, the classes of Bootstrap it keeps and how a site package styles the
markup are described once, in the chapter `Styling of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
This chapter shows how the markup of this extension is built up.

..  contents::
    :local:
    :depth: 1

..  _styling-outermost-element:

The outermost elements
======================

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Plugin or page
        -   Content element or page type
        -   Outermost element
    *   -   Project list
        -   `academicprojects_projectlist`
        -   `academic-projects-list`
    *   -   Project list of selected projects
        -   `academicprojects_projectlistsingle`
        -   `academic-projects-list`
    *   -   Project page
        -   the page type of a project
        -   `academic-projects-page container`

Both plugins render the same template, so a rule for one applies to the other.

..  _styling-list:

The project list
================

..  code-block:: html

    <div class="academic-projects-list">
        <!-- the header of the content element, when the plugin renders it -->
        <div class="ace-content">
            <form class="ace-form" …>
                <div class="ace-filters">
                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                            <div class="ace-filter ace-field ace-select-wrap">
                                <label class="ace-label" for="activeState">Active state</label>
                                <select class="ace-control ace-select" id="activeState" …>…</select>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="ace-filters">
                <ul class="ace-list ace-active-filters" aria-label="…">
                    <li class="ace-list-item ace-active-filter">
                        <a class="ace-link" href="…">Active <span class="ace-icon" aria-hidden="true"> ×</span></a>
                    </li>
                </ul>
                <a class="ace-link" href="…">Reset filters</a>
            </div>
            <p class="ace-count">12 projects</p>
            <div class="ace-itemlist">
                <div class="row">
                    <div class="col-12 col-md-6 col-lg-4 col-xl-3">
                        <article class="ace-item">…</article>
                    </div>
                </div>
            </div>
        </div>
    </div>

*   The filter bar holds the sorting selects, one filter per category type,
    the further filters inside the :html:`<details class="ace-more">` with its
    :html:`<summary class="ace-toggle">`, and the select of the active state.
    The form submits itself when a select changes.
*   The active filters and the reset link are wrapped in an `ace-filters` of
    their own, which the partner and program lists do not render. A rule for
    `.ace-filters` therefore applies to the filter bar and to the active
    filters of this list. An active state other than :guilabel:`All` is an
    active filter as well.
*   The active filters, the reset link and the count are only rendered when the
    content element switches them on.
*   A list without projects renders the message in a
    :html:`<span class="ace-empty">` in place of the item list.

..  _styling-item:

A project
---------

..  code-block:: html

    <article class="ace-item">
        <div class="ace-item-content">
            <header class="ace-header">
                <h2 class="ace-title"><a href="…">Project title</a></h2>
            </header>
            <p class="ace-state active">Active</p>
            <div class="ce-bodytext">…</div>
            <ul class="ace-list ace-attributes">
                <li class="ace-list-item ace-attribute">
                    <span class="ace-icon">…</span>
                    <b class="ace-label">Department:</b>
                    <span class="ace-value">…</span>
                </li>
            </ul>
        </div>
        <!-- the first image of the project, preset "card" -->
    </article>

*   The state is only rendered when the content element switches the state
    badge on. Its second class is the state of the project, `active` or
    `completed`.
*   The short description is a rich text field and therefore a `ce-bodytext`.
*   The attributes are the categories of the project, one `ace-attribute` per
    category type.

..  _styling-page:

The project page
================

..  code-block:: html

    <div class="academic-projects-page container">
        <div class="ace-hero">
            <header class="ace-header">
                <h1 class="ace-title">Project title</h1>
                <p class="ace-subtitle">…</p>
                <div class="ce-bodytext">…</div>
            </header>
            <!-- the first image of the project, preset "detail" -->
        </div>
        <ul class="ace-list ace-attributes">…</ul>
        <ul class="ace-list ace-attributes">
            <li class="ace-list-item ace-attribute">
                <b class="ace-label">Runtime:</b>
                <span class="ace-value">01.2025 - 12.2027</span>
            </li>
            <li class="ace-list-item ace-attribute">
                <b class="ace-label">Funders:</b>
                <div class="ace-value ce-bodytext">…</div>
            </li>
        </ul>
        <!-- the content elements of the page -->
    </div>

*   The first attribute list holds the categories of the project, the second
    its facts: the runtime, the budget and the funders.
*   The funders are a rich text field, so their value is an
    `ace-value ce-bodytext`, a :html:`<div>` rather than a :html:`<span>`.
*   The page renders inside the layout of the site.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-projects-list .ace-state {
        display: inline-block;
        padding: 0 0.5rem;
        border-radius: 1rem;
        background: #e9ecef;
    }

    .academic-projects-list .ace-state.active {
        background: #d1e7dd;
    }

    .academic-projects-page .ace-attributes {
        list-style: none;
        padding-left: 0;
    }

To change the markup itself, override the partials as described in
:ref:`templates`.
