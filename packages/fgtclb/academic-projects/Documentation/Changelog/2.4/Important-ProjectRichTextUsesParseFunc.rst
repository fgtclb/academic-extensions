.. _important-project-rich-text-uses-parse-func:

========================================================
Important: The rich text of a project resolves its links
========================================================

Description
===========

The short description and the funders of a project are rich text fields, but
the project page type printed both, and the project list printed the short
description, with :html:`<f:format.raw>`. The stored HTML reached the visitor
unprocessed: a link an editor set to a page, a file or a record in the rich
text editor stayed a literal `t3://` reference, and none of the site's rich
text processing was applied.

The page template :file:`Resources/Private/Pages/AcademicProject.html` and the
list partial :file:`Resources/Private/Partials/Project/Item.html` render the
three fields through :html:`<f:format.html>` now, which is how TYPO3 renders
rich text everywhere else:

..  code-block:: html

    <f:format.html>{project.shortDescription}</f:format.html>

That ViewHelper applies the site's :typoscript:`lib.parseFunc_RTE`.

Impact
======

*   Links to pages, files and records in the short description and the funders
    resolve to URLs on the project page and in the project list.
*   The fields follow the site's rich text configuration: the HTML sanitizer,
    the allowed tags and the paragraph wrapping of loose text apply. Markup
    written in the rich text editor already carries paragraphs and renders
    unchanged; markup imported or written around the editor may differ
    slightly from the stored HTML.

Affected Installations
======================

All installations that render project pages or project lists with the
templates shipped by this extension.

**TYPO3 v13** defines :typoscript:`lib.parseFunc_RTE` for every site in the
default TypoScript of :guilabel:`EXT:frontend`. Nothing has to be added.

**TYPO3 v12** does not. There the site has to provide
:typoscript:`lib.parseFunc_RTE`, as :guilabel:`EXT:fluid_styled_content`,
:guilabel:`EXT:bootstrap_package` and common site packages do. A v12 site
without it now gets the core exception 1641989097 (*Invoked
ContentObjectRenderer::parseFunc without any configuration*) on project pages
and lists. :guilabel:`EXT:academic_jobs` and :guilabel:`EXT:academic_persons`
already require the same for their detail views.

An installation that overrides :file:`Resources/Private/Pages/AcademicProject.html`
or :file:`Resources/Private/Partials/Project/Item.html` is not affected. A copy
that was taken only to render these fields through
:html:`<f:format.html>` can be dropped, as long as it carries no other change.

.. index:: Fluid, Frontend, ext:academic_projects
