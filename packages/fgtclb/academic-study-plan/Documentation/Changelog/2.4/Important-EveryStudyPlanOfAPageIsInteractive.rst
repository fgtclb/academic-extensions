..  _important-every-study-plan-of-a-page-is-interactive:

====================================================
Important: Every study plan of a page is interactive
====================================================

Description
===========

The script of the study plan content element starts one instance per plan, and
kept the instances it had started in a map keyed by the **value** of
:html:`data-study-plan` - the uid of the content element.

Two plans of one page that carry the same value therefore collapsed onto one
instance, and only the first of them was ever started: no category filter, no
semester accordion and no module dialogs on the second. The same happened for
two containers carrying no :html:`data-study-plan` at all, because both then
keyed on the empty string.

The instances are keyed by the container element now, so every plan of a page
is started.

Impact
======

A page that renders the same study plan twice - through an
:guilabel:`Insert records` element or a shortcut - now has two working plans
instead of one working and one inert. A page with one plan is unaffected.

The resize handler is affected in the same way: it iterates the instances, so a
plan that never entered the map was never levelled again on a viewport change
either.

Affected Installations
======================

Every installation that renders the study plan content element more than once
on a page, and every installation whose template override leaves the
:html:`data-study-plan` attribute out. Nothing has to be done on update.

.. index:: Frontend, JavaScript, ext:academic_study_plan
