..  _feature-project-active-state-badge:

===========================================
Feature: The state of a project on its card
===========================================

Description
===========

Every project now tells a template whether it is active or completed, by the
same rule the :guilabel:`Active state` filter of the project lists uses: no end
date or an end date still ahead is active, an end date that has passed is
completed.

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Extensions/academic_projects/Partials/Project/Item.html

    <f:if condition="{project.activeState} == 'completed'">
        <p class="text-muted">This project has ended.</p>
    </f:if>

The :guilabel:`Projects` and the :guilabel:`Projects (selected)` content
elements have a new option, :guilabel:`Show active state badge`
(:typoscript:`settings.showActiveStateBadge`). Switched on, every project card
shows its state as a badge with the labels "Active" and "Completed" the
extension already ships, translated into the language of the page.

Impact
======

Nothing changes until an editor switches the option on: it is off for new
content elements and for every content element saved before it existed.

A template of a site package that already reads
:html:`{project.activeState}` rendered nothing so far, and renders `active` or
`completed` now.

A project whose end date column was never written is active, while the
:guilabel:`Active` filter does not list it yet. See
:ref:`configuration-active-state`.

..  index:: Frontend, FlexForm, Fluid
