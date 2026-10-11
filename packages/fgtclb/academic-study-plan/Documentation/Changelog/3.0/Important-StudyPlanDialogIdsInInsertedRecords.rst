..  _important-study-plan-dialog-ids-in-inserted-records:

==============================================================
Important: A study plan shown again gets dialog ids of its own
==============================================================

Description
===========

An editor can show a study plan a second time on the same page, through an
"Insert records" element. Both copies rendered the same dialog ids,
:html:`popup-<module uid>`, and the script looked a dialog up in the whole
document. A module of the second copy therefore opened the dialog of the first
one, and when a theme hid the first copy, in a tab or an accordion, the visitor
got a modal dialog of zero size and a page that did not react until Escape.

Two things change:

*   The script looks the dialog a trigger names up inside its own plan first,
    and in the whole document only when the plan has none.
*   A plan rendered inside another content element gives its dialogs ids that
    carry that element: :html:`popup-c<uid>-<module uid>`, where :html:`<uid>`
    is the uid of the content element it is rendered inside. The partials
    receive the prefix as :html:`idPrefix`. A plan placed on the page renders
    the ids it rendered before.

Impact
======

A plan placed on the page renders unchanged.

A plan shown by an "Insert records" element renders prefixed dialog ids, and so
does a plan inside a grid element of a container extension that renders its
children through :typoscript:`RECORDS`, :typoscript:`CONTENT`,
:html:`f:cObject` or, on TYPO3 v14, :html:`f:render.record`:

..  code-block:: html
    :caption: Before, inside "Insert records" element 69

    <button data-study-plan-dialog-trigger data-dialog-id="popup-25">…</button>
    <dialog id="popup-25" data-study-plan-dialog>…</dialog>

..  code-block:: html
    :caption: After

    <button data-study-plan-dialog-trigger data-dialog-id="popup-c69-25">…</button>
    <dialog id="popup-c69-25" data-study-plan-dialog>…</dialog>

Two arrangements still repeat a dialog id: one "Insert records" element that
lists the same plan twice, and an "Insert records" element that inserts another
"Insert records" element which is on the page as well. The dialogs open in the
right copy there too. The frame id :html:`c<uid>` the core renders around every
content element repeats for every copy, it is not part of the study plan.

Migration
=========

An installation that overrides :file:`AcademicStudyPlan.html`,
:file:`StudyPlan/Semester.html`, :file:`StudyPlan/Module.html` or
:file:`StudyPlan/ModuleDialog.html` passes :html:`idPrefix` on and starts the
dialog id with it, as the shipped partials do:

..  code-block:: html

    <f:render partial="StudyPlan/ModuleDialog" arguments="{module: module, idPrefix: idPrefix}" />

    <dialog id="popup-{idPrefix}{module.uid}" data-study-plan-dialog>

An override that does not pass it on keeps the previous ids and keeps working:
its dialogs open in the right copy, they only repeat their ids when the plan is
shown twice. A stylesheet or script that addresses a dialog of an inserted plan
by its id adds the prefix.

Affected Installations
======================

Installations that show a study plan through an "Insert records" element or a
grid element, and installations that override the template or the semester,
module or dialog partial.

.. index:: Frontend, Fluid, JavaScript, ext:academic_study_plan
