.. _important-1787924557:

=========================================================
Important: The study plan frontend is now workspace aware
=========================================================

Description
===========

:php:`\FGTCLB\AcademicStudyPlan\Service\StudyPlanService` builds the queries for
semesters, modules and categories itself. They carried no workspace constraint and
no version overlay, with two consequences.

**Unpublished drafts were served to the public.** A workspace version is an
ordinary row in the database, told apart from a live record only by
:sql:`t3ver_wsid`. With no condition on that column every draft was selected
alongside the record it is a draft of, and rendered next to it — including
records created in a workspace and never published, which have no live
counterpart at all.

**A workspace preview was not a preview.** It showed the live records and the
workspace records together, rather than the workspace state.

Both are fixed by the two pieces TYPO3 provides for it: a
:php:`WorkspaceRestriction` on each query, which in the live workspace constrains
to :sql:`t3ver_wsid = 0`, and :php:`PageRepository::versionOL()` on each fetched
row, which replaces a record with its draft in a preview and drops it where the
workspace deletes it.

Impact
======

**On the live site, records disappear that should never have been there.** If a
study plan showed duplicated or unexpected semesters, modules or categories,
those were workspace drafts and they are gone now. No live record is affected.

In a workspace preview the study plan now shows the workspace state.

What still is not previewed is a **relation** changed in a workspace. Attaching
or detaching a category from a module inside a workspace is not reflected: the
overlay keeps the live uid of the module, so the categories are looked up
through the live relations. The content of a record — its label, colour, note,
credit points — is previewed correctly. Only the wiring between records is not.

Affected Installations
======================

Every installation that uses workspaces for study plan content. An installation
that never created a workspace version of a semester, module or category renders
exactly as before.

.. index:: Frontend, Database, ext:academic_study_plan
