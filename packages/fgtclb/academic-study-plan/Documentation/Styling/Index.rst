..  index:: ! Styling
..  _styling-markup:

=======
Styling
=======

The study plan renders its markup with the class system of the academic
extensions and ships no styling for it. The system, the classes of Bootstrap it
keeps and how a site package styles the markup are described once, in the
chapter `Styling of academic_base
<https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/Styling/Index.html>`__.
This chapter shows how the markup of the study plan is built up, and which
classes its script writes. The `data-study-plan-*` attributes the script works
with are described in :ref:`templates`, the example stylesheet to start from
and the rules the element cannot do without in :ref:`styling`.

..  contents::
    :local:
    :depth: 1

..  _styling-outermost-element:

The outermost element
=====================

..  list-table::
    :header-rows: 1
    :widths: 30 35 35

    *   -   Content element
        -   Content element type
        -   Outermost element
    *   -   Study plan
        -   `academic_study_plan`
        -   `academic-study-plan`

The element is rendered inside the layout :file:`Default` of
:guilabel:`fluid_styled_content`, with the frame and the header every content
element has. The study plan is a content element without plugins, so its
outermost class has no plugin part, unlike the pattern
`academic-<extension>-<plugin>` of the plugins of the other extensions.

..  _styling-plan:

The study plan
==============

..  code-block:: html

    <div class="academic-study-plan" data-study-plan="…" data-filter-label="…">
        <nav class="ace-filter" role="navigation" aria-label="…">
            <ul class="ace-list" data-study-plan-filter>
                <li class="ace-list-item">
                    <button class="ace-control" data-category-id="…" style="--category-color: #…;">Category</button>
                </li>
            </ul>
        </nav>
        <div class="ace-semesters">
            <div role="list" class="row">
                <div role="listitem" class="ace-semester col" data-study-plan-semester>
                    <div class="ace-semester-header" data-study-plan-semester-header …>
                        <div class="ace-semester-content">
                            <div class="ace-semester-heading">
                                <span class="ace-title"><b>1st semester</b></span>
                                <span class="ace-semester-credits">30 CP</span>
                            </div>
                            <span class="ace-actions"><!-- the expand and collapse icons --></span>
                        </div>
                        <span class="ace-semester-note">…</span>
                    </div>
                    <ul class="ace-list ace-modules">
                        <li class="ace-list-item ace-module ace-interactive" data-study-plan-module …>…</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="ce-bodytext"><!-- the footer note --></div>
    </div>

*   The filter list is filled by the script, one `ace-list-item` with a button
    `ace-control` per category of the rendered modules. Each button carries the
    colour of its category as the custom property `--category-color`, for the
    site stylesheet to use.
*   Every semester is a grid column `col` of the `row` with the role
    :html:`list`, so the semesters share the width of the plan.
*   The `ace-actions` of a semester header hold the icons
    `tx-academicbase-action-expand` and `tx-academicbase-action-collapse`. The
    site stylesheet shows one of them, by the classes
    `icon-tx-academicbase-action-expand` and
    `icon-tx-academicbase-action-collapse`, see :ref:`templates-glyphs`.
*   The footer note is a rich text field and therefore a `ce-bodytext`. It is
    only rendered when the content element has one.

..  _styling-module:

A module and its dialog
-----------------------

..  code-block:: html

    <li class="ace-list-item ace-module ace-interactive" data-study-plan-module data-categories="…">
        <span class="ace-title">Module title</span>
        <span class="ace-module-credits">5 CP</span>
        <span class="ace-module-note">…</span>
        <button class="ace-module-trigger" data-study-plan-dialog-trigger …>
            <span class="visually-hidden">…</span>
        </button>
        <dialog class="ace-dialog" data-study-plan-dialog …>
            <header class="ace-header">
                <span class="ace-title" aria-hidden="true"><b>Module title</b></span>
                <span class="ace-dialog-credits" aria-hidden="true">5 CP</span>
                <button class="ace-close" aria-label="…"><!-- the icon tx-academicbase-action-close --></button>
            </header>
            <span class="ace-dialog-note" aria-hidden="true">…</span>
            <p class="ace-dialog-description">…</p>
            <audio class="ace-dialog-audio" controls …>…</audio>
        </dialog>
    </li>

*   Only a module with a description or an audio file has a dialog. It carries
    `ace-interactive`, the trigger and the dialog. A module without one renders
    title, credits and note only.
*   The trigger holds no visible text, its label for a screen reader is
    `visually-hidden`. A site stylesheet usually stretches it over the whole
    module, so the module opens its dialog wherever it is clicked.

..  _styling-script-classes:

What the script writes
======================

..  list-table::
    :header-rows: 1
    :widths: 30 70

    *   -   Class or attribute
        -   Written on
    *   -   `highlighted`
        -   The plan, the modules of the categories chosen in the filter, the
            semesters holding them, and the chosen filter buttons.
    *   -   `open`
        -   A semester opened in the accordion of a narrow viewport, and a
            semester holding a highlighted module.
    *   -   `ace-toggle`
        -   The button the script inserts in front of the filter list when the
            filter is collapsible, see :ref:`collapsible-filter`.
    *   -   :html:`hidden`
        -   The filter list while the collapsible filter is closed.
    *   -   `--highlight-color`
        -   The plan, as a custom property, when a category is chosen in the
            filter: the colour of the category at a quarter of its opacity,
            for the site stylesheet to colour the highlighted modules and
            semesters with.

The attribute :html:`hidden` only hides the list as long as no rule of the site
gives the list a :css:`display`. A site stylesheet that lays out the filter
list keeps it hidden with a rule of its own, as in the example below.

..  _styling-script-switch:

The script
==========

`plugin.tx_academicstudyplan.assets.js` switches the script off per site, see
:ref:`asset-switches`. The markup stays the same, but the filter, the
accordion of the semesters and the dialogs need a script, see
:ref:`templates-no-script`. A script of the project drives them through the
`data-study-plan-*` attributes.

..  _styling-example:

An example
==========

Rules in the stylesheet of the site package, scoped by the outermost element:

..  code-block:: css

    .academic-study-plan .ace-filter .ace-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        list-style: none;
        padding-left: 0;
    }

    .academic-study-plan .ace-filter .ace-list[hidden] {
        display: none;
    }

    .academic-study-plan .ace-filter .ace-control.highlighted {
        background: var(--category-color);
    }

    .academic-study-plan .ace-module {
        position: relative;
    }

    .academic-study-plan .ace-module-trigger {
        position: absolute;
        inset: 0;
        background: none;
        border: 0;
    }

    .academic-study-plan .ace-module.highlighted {
        outline: 2px solid currentColor;
    }

The complete stylesheet of the development instances is linked in
:ref:`styling`.

To change the markup itself, override the partials as described in
:ref:`templates`. The script keeps working with other elements and classes as
long as the `data-study-plan-*` attributes stay where that chapter puts them.
