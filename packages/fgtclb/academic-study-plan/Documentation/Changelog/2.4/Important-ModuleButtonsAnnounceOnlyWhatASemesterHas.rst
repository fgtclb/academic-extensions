..  _important-ace-836-academic-study-plan:

===========================================================
Important: Module buttons announce only what a semester has
===========================================================

Description
===========

Every module of a study plan that has details opens them with a button. The
button carries a visually hidden label for screen readers, which names the
semester, its note and its credit points, followed by the module. The template
printed the note and the credit points without a condition, so a semester
without them was announced as "Semester 2, , 0 CP." with an empty item and
credit points the visible plan does not show.

The note and the credit points are now announced only when the semester has
them. A semester with neither is announced as "Semester 2.", one with credit
points as "Semester 2, 30 CP.".

Impact
======

Screen reader users hear no empty item and no "0 CP" for a semester without a
note or credit points. The visible output does not change.

Affected Installations
======================

Every installation that renders the study plan content element with the
shipped template. Nothing has to be done on update.

A site that overrides :file:`Templates/AcademicStudyPlan.html` keeps the old
label. To take over the change, replace the line of the semester inside
:html:`<span class="visually-hidden">` of the module button in the copy:

..  code-block:: html

    {semester.label}<f:if condition="{semester.note}">, {semester.note}</f:if><f:if condition="{semester.credit_points}">, {semester.credit_points} {f:translate(key: 'credits', extensionName: 'AcademicStudyPlan')}</f:if>.

..  index:: Frontend, Fluid, ext:academic_study_plan
