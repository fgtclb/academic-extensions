:navigation-title: Templates

..  _templates:

=========
Templates
=========

The study plan content element renders from one template and four partials, and
its script finds every part it drives by a :html:`data-study-plan-*` attribute.
Together those are the contract: replace any partial, with your own elements and
your own class names, keep the attributes where this page puts them, and the
filter, the highlighting, the semester accordion and the module dialogs keep
working.

..  contents::
    :local:
    :depth: 1

..  _templates-where:

Where they live
===============

..  list-table::
    :header-rows: 1

    *   -   File
        -   Renders
    *   -   :file:`Frontend/Default/Templates/AcademicStudyPlan.html`
        -   The container, and the assets of
            :ref:`Skip the stylesheet or the script <asset-switches>`.
    *   -   :file:`Frontend/Default/Partials/StudyPlan/Filter.html`
        -   The category filter.
    *   -   :file:`Frontend/Default/Partials/StudyPlan/Semester.html`
        -   One semester column, with its header and its modules.
    *   -   :file:`Frontend/Default/Partials/StudyPlan/Module.html`
        -   One module, with its dialog trigger.
    *   -   :file:`Frontend/Default/Partials/StudyPlan/ModuleDialog.html`
        -   The dialog of one module.

All five sit below
:file:`EXT:academic_study_plan/Resources/Private/`. Point the element at
templates of your own with the three constants
:typoscript:`plugin.tx_academicstudyplan.view.templateRootPath`,
:typoscript:`.partialRootPath` and :typoscript:`.layoutRootPath`, or add a
higher entry to :typoscript:`tt_content.academic_study_plan.partialRootPaths`.
A path that holds one file replaces that one file; everything else keeps coming
from the extension.

..  _templates-arguments:

What a partial is given
=======================

Each partial receives exactly the variables it renders, so an override has an
argument list it can rely on rather than whatever the template happened to have
in scope.

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Arguments
    *   -   :file:`StudyPlan/Filter`
        -   :html:`collapsible` - whether the site asked for the collapsible
            filter, see
            :ref:`Collapse the category filter <collapsible-filter>`.
    *   -   :file:`StudyPlan/Semester`
        -   :html:`semester` - the semester, with its :html:`modules`.
    *   -   :file:`StudyPlan/Module`
        -   :html:`module` and the :html:`semester` it belongs to, the latter
            only for the label the dialog trigger announces.
    *   -   :file:`StudyPlan/ModuleDialog`
        -   :html:`module`.

The :html:`credit_points` of a semester and of a module are a number, not the
value the database returns: printed as they are, they read "2.5", "30" and
"0" rather than "2.50", "30.00" and "0.00". They render with a decimal point on
every page language; format them in the override for a decimal comma.

..  _templates-attributes:

The data attributes
===================

..  list-table::
    :header-rows: 1

    *   -   Attribute
        -   Belongs on
        -   What the script does with it
    *   -   :html:`data-study-plan`
        -   The element that wraps the whole plan.
        -   Starts one instance on it. The value is the uid of the content
            element; several plans on a page are told apart by the element
            itself, not by the value, so it may repeat.
    *   -   :html:`data-study-plan-filter`
        -   The list the filter buttons go into.
        -   Empties it and fills it with one button per category the rendered
            modules carry.
    *   -   :html:`data-study-plan-filter-template`
        -   The single item inside that list.
        -   Clones it per category, substituting
            :html:`category-id-placeholder`,
            :html:`category-color-placeholder` and
            :html:`category-label-placeholder`, and takes :html:`hidden` off
            every clone.
    *   -   :html:`data-study-plan-filter-collapsible`
        -   The same list as :html:`data-study-plan-filter`, and only when the
            site switched the collapsible filter on. The extension renders it
            for you.
        -   Hides the list and puts a toggle button in front of it, labelled
            from :html:`data-filter-label`. Without that label, or with no
            category in the filter, no toggle is inserted and the filter stays
            expanded.
    *   -   :html:`data-study-plan-semester`
        -   The element that holds one semester with its modules.
        -   Opens and closes it, and highlights it with its modules.
    *   -   :html:`data-study-plan-semester-header`
        -   The header inside a semester.
        -   Makes it the accordion control below 768 pixels and inert above it.
    *   -   :html:`data-study-plan-module`
        -   One module inside a semester.
        -   Reads its :html:`data-categories`, highlights it, levels its height
            on a wide viewport, and looks for its dialog and trigger inside it.
    *   -   :html:`data-study-plan-dialog-trigger`
        -   The control that opens the dialog of a module, inside that module -
            or the module element itself, see below.
        -   Opens the dialog of that module, by pointer and by keyboard. The
            dialog is the one named by :html:`data-dialog-id`, and the module's
            own when the trigger names none.
    *   -   :html:`data-study-plan-dialog`
        -   The :html:`<dialog>` of a module, inside that module.
        -   Opens it as a modal and closes it again from the first
            :html:`<button>` inside it, stopping whatever it was playing.

Two class names are **not** part of this list and are written by the script
rather than looked up: :html:`highlighted` and :html:`open`, on the container,
the semesters and the modules. Style them, do not rely on them being absent.

The script also writes :html:`hidden` on the filter list while it is collapsed.
That attribute only hides anything because the shipped stylesheet says
:css:`.filter[hidden] { display: none }` - the browser's own rule for it loses
to any author rule that gives the list a :css:`display`. An installation that
replaces the stylesheet has to carry a rule of its own.

..  _templates-attribute-values:

The attributes the script reads a value from
============================================

The attributes above are markers. These four carry values the script needs, and
an override that renders modules or filter buttons itself has to produce them:

..  list-table::
    :header-rows: 1

    *   -   Attribute
        -   Belongs on
        -   Value
    *   -   :html:`data-filter-label`
        -   The container.
        -   The label of the collapsible filter's toggle button. The extension
            renders the translation of :html:`filter.label` into it.
    *   -   :html:`data-categories`
        -   A module.
        -   A JSON array of the categories the module carries, each an object
            with :json:`uid`, :json:`label` and :json:`colour` - for example
            :json:`[{"uid":1,"label":"Mandatory","colour":"#cc0000"}]`. An
            absent attribute, an empty string and :json:`[]` all mean "no
            category"; anything that does not parse is treated the same way.
            The title is rendered as text, never as markup.
    *   -   :html:`data-category-id`, :html:`data-category-color`
        -   The :html:`<button>` inside the filter template item.
        -   Substituted from :html:`category-id-placeholder` and
            :html:`category-color-placeholder` when the item is cloned, and read
            back when the button is activated. A button without them highlights
            nothing. A colour that is not a hexadecimal value, a plain keyword
            or an :css:`rgb()` / :css:`rgba()` function is dropped.
    *   -   :html:`data-dialog-id`
        -   A dialog trigger.
        -   The :html:`id` of the dialog it opens. Without it the trigger opens
            the dialog inside its own module.

The three placeholder strings - :html:`category-id-placeholder`,
:html:`category-color-placeholder` and :html:`category-label-placeholder` - are
substituted wherever they appear in the item, in an attribute value or in text.
Keep them in an override of the filter, in the attributes above and wherever the
label is to be read.

..  _templates-module-as-trigger:

The module element as the trigger
=================================

:html:`data-study-plan-dialog-trigger` may sit on the module element itself
instead of on a control inside it, which makes the whole module open its
dialog. The script pairs it with the dialog of that very module, so a plan full
of such modules opens the right one each time.

The script does not make that element focusable, and an override that wants
this has to: give it :html:`tabindex="0"` and :html:`role="button"` itself,
or the dialog opens by pointer only. A key pressed on something *inside* the
module - a link, an audio control - is left to that control and does not open
the dialog.

The extension does not render it that way, and does not offer a setting for it:
a module that is itself a control nests its heading, its credits and its dialog
inside interactive content, which assistive technology reads differently and
which no keyboard order describes well. It is supported because an installation
that wants it should not have to fork the script for it.

..  _templates-3-0:

Markup of version 3.0 keeps working
===================================

Before these attributes existed the script found its parts by class name:
:html:`.academic-study-plan`, :html:`.filter`, the first :html:`<li>` inside it,
:html:`.col`, :html:`.header`, :html:`.module`, :html:`.modal-trigger` and the
:html:`<dialog>` inside a module. Each of those *inside* a plan is still read,
per part, when the attribute of that part is nowhere in that plan - so an
override written for 3.0 keeps working, and one that carries the attributes on
some parts and not on others works too.

The container is the exception: :html:`.academic-study-plan` is read **next to**
:html:`data-study-plan` rather than instead of it, because two plans of one page
can come from different templates and a 3.0 one must not disappear because the
other carries the attribute.

That fallback is deprecated and is removed in 4.0. See
:ref:`Deprecation: The study plan class selectors
<deprecation-study-plan-class-selectors>`.

..  _templates-no-script:

What a page without the script shows
====================================

The markup is not a fallback: the filter, the accordion and the dialogs need a
script. The one part that would otherwise be visible is handled - the item
inside the filter list is rendered :html:`hidden`, because its placeholder text
is not meant for anybody. An installation that brings a script of its own has to
take that attribute off the items it builds.

..  _templates-see-also:

See also
========

*   :ref:`Configuration <configuration>` - the site settings and constants this
    page refers to.
