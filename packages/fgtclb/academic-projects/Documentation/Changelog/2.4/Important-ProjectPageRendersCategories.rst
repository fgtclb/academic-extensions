.. _important-project-page-renders-categories:

=======================================================
Important: The project page type renders its categories
=======================================================

Description
===========

A page of the project page type can carry categories of the category types
this extension registers, and its page template has always contained a block
that lists them, grouped by category type. That block never appeared.

It read the categories as :html:`{project.categories.allCategoriesByType}`,
and the model behind :html:`{project}` has no such property — it exposes the
collection as :php:`getAttributes()`. Fluid resolves an unknown path to
:php:`null` without raising anything, so the surrounding :html:`<f:if>` was
false on every project page and the whole list was skipped. The list partials
of this extension already read the working path.

The page template now reads it as well:

..  code-block:: html

    <f:for each="{project.attributes.allCategoriesByType}" as="categories" key="type">

Impact
======

A project page with at least one category assigned now shows its category
types and the categories of each, in the markup the template already
contained — for example :guilabel:`Competence field` followed by
:guilabel:`Photonics`.

A category type without an assigned category produces no entry, and a project
page without categories renders exactly as before.

Affected Installations
======================

All installations that render project pages with the page template shipped by
this extension and assign categories to them.

An installation that overrides
:file:`Resources/Private/Pages/AcademicProject.html` is not affected. A copy
that was taken only to repair this defect can be dropped, as long as it
carries no other change.

.. index:: Fluid, Frontend, ext:academic_projects
