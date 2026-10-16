..  _important-project-categories-description:

=========================================================
Important: The description of Categories names their rule
=========================================================

Description
===========

The description below :guilabel:`Categories` of the project list content
elements said that a main category selects all its subcategories with OR, and
that several categories select with AND. Only the second part held.

The description now says what the lists do, in English and in German: a
project is shown only when it carries every selected category, also where two
of them are of the same category type. The subcategories of a selected
category are not included.

Impact
======

Only the text changed, the lists show the same projects as before. The label
key :xml:`flexform.categories.description` is unchanged, so an override of it
in a language file keeps its own text.

Affected Installations
======================

Every installation with a project list that has categories selected. A
project that carries only a subcategory of a selected category is not listed.
Give such a project the selected category as well.

..  index:: Backend, FlexForm, ext:academic_projects
