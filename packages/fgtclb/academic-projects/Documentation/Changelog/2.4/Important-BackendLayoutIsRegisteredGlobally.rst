.. _important-1786969200:

==================================================================
Important: The backend layout is registered for every installation
==================================================================

Description
===========

The backend layout of the page type this extension registers, and the
descriptions of its content elements, were imported only by
:file:`Configuration/Sets/AcademicProjects/page.tsconfig`.

:file:`Configuration/page.tsconfig` of an extension is auto-included for the
whole installation since TYPO3 v12.0 (Feature: #96614); a site set is opt-in per
site. So on a site that does not enable the set :yaml:`fgtclb/academic-projects`
the layout :typoscript:`pagets__AcademicProject` resolved nowhere: the page
properties showed :guilabel:`[ MISSING LABEL ]` for it and it could not be
selected for a new page at all.

On TYPO3 v12 the loss is total rather than partial: site sets do not exist on
that version at all (they arrived in v13.1, Feature: #103437), so there was no
delivery path for the layout whatsoever.

Both imports moved to :file:`Configuration/page.tsconfig`, where
:composer:`fgtclb/academic-programs` already had them, and the copy in the site
set was removed rather than left to be applied twice. The
:file:`Configuration/TsConfig/page.tsconfig` that carried nothing but a
:php:`@todo` asking for exactly this move was removed with it.

Impact
======

The backend layout and the content element descriptions are available on every
installation, whether or not it uses site sets.

Affected Installations
======================

All installations of this extension. Nothing has to be done: pages already
carrying :typoscript:`pagets__AcademicProject` resolve the layout from now on.

.. index:: TSConfig, Backend, ext:academic_projects
