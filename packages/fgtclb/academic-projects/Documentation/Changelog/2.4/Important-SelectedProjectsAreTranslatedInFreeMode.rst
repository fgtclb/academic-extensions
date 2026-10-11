.. _important-ace-341-academic-projects:

=================================================================
Important: Selected projects are translated in free fallback mode
=================================================================

Description
===========

The :guilabel:`Projects (selected)` content element lists the project pages an
editor picks in :guilabel:`Selected projects to display`. The backend stores
such a selection as uids of the default language pages, so the query that
fetches it does not restrict the language, otherwise nothing would match on a
translated page.

On a site language with :yaml:`fallbackType: free`, TYPO3 switches the overlay
of records off. The default language project pages the query returned were
therefore rendered as they were, and a German page listed the English titles
of its projects.

The query now switches the overlay on for such a selection before it drops the
language restriction, the same way :guilabel:`EXT:academic_persons` treats a
selection of profiles. A selected project is rendered in the language of the
page.

Impact
======

On a site language with :yaml:`fallbackType: free`, the
:guilabel:`Projects (selected)` content element shows the translation of every
selected project page that has one. TYPO3 v12 and v13 behave alike.

The :guilabel:`Projects` content element, which lists the project pages below
its starting points, and the other fallback types are not affected.

Affected Installations
======================

Multi-language installations with a site language that uses
:yaml:`fallbackType: free` and a :guilabel:`Projects (selected)` content
element.

.. index:: Frontend, ext:academic_projects
