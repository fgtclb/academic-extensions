..  _feature-study-plan-partials-and-data-attributes:

==================================================
Feature: Study plan partials and a markup contract
==================================================

Description
===========

The study plan content element rendered every part of itself from one template,
and its script found those parts by class name. An installation that wanted a
different filter, a different module or a module that opens its dialog when it
is clicked anywhere had to replace the whole template - and with it the class
names the script looks for, so it had to fork the script as well.

The template is split into four partials now:

..  list-table::
    :header-rows: 1

    *   -   Partial
        -   Renders
    *   -   :file:`StudyPlan/Filter`
        -   The category filter.
    *   -   :file:`StudyPlan/Semester`
        -   One semester column, with its header and its modules.
    *   -   :file:`StudyPlan/Module`
        -   One module, with its dialog trigger.
    *   -   :file:`StudyPlan/ModuleDialog`
        -   The dialog of one module.

Each of them receives exactly the variables it renders, and each can be
replaced on its own through
:typoscript:`tt_content.academic_study_plan.partialRootPaths` or the constant
:typoscript:`plugin.tx_academicstudyplan.view.partialRootPath`.

The script no longer looks for class names. Every part it drives carries a
:html:`data-study-plan-*` attribute - the filter and the item it clones, the
semester and its header, the module, the dialog and the control that opens it -
and an override that keeps those attributes keeps the whole interaction,
whatever it does to the elements and the classes around them. The attributes,
the element each belongs on and the arguments of each partial are documented in
:ref:`Templates <templates>`.

Two things the contract makes possible without a fork:

*   :html:`data-study-plan-dialog-trigger` may sit on the module element
    itself, which makes the whole module open its dialog. The script pairs it
    with the dialog of that very module. It is not the shipped markup, and
    :ref:`Templates <templates-module-as-trigger>` says why.
*   The category filter can be collapsed behind a toggle button, with the new
    site setting :yaml:`plugin.tx_academicstudyplan.filter.collapsible`
    (:yaml:`false` by default). The toggle carries :html:`aria-expanded` and
    :html:`aria-controls` and works by keyboard. See
    :ref:`Collapse the category filter <collapsible-filter>`.

Impact
======

**The default output is unchanged.** An installation that configures nothing
and overrides nothing renders the same text, the same elements and the same
classes as before, with the data attributes added - and behaves the same by
mouse and by keyboard.

Affected Installations
======================

Every installation that renders the study plan content element. None of them
has anything to do on update.

An installation that forked :file:`AcademicStudyPlan.html` or the script can
move onto the partials and the attributes instead, and stop carrying the copy.
Markup that identifies its parts only by the class names of 3.0 keeps working
for the whole 3.x line - see
:ref:`Deprecation: The study plan class selectors
<deprecation-study-plan-class-selectors>`.

.. index:: Frontend, Fluid, JavaScript, TypoScript, ext:academic_study_plan
