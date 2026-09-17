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

That ViewHelper applies the site's :typoscript:`lib.parseFunc_RTE`. TYPO3
defines it for every site in the default TypoScript of
:guilabel:`EXT:frontend`, on sites with and without site sets, and
:guilabel:`EXT:fluid_styled_content` or a site package refines it. Nothing has
to be added to render the fields.

Impact
======

*   Links to pages, files and records in the short description and the funders
    resolve to URLs on the project page and in the project list.
*   The fields follow the site's rich text configuration: the HTML sanitizer,
    the allowed tags and the paragraph wrapping of loose text apply. Markup
    written in the rich text editor already carries paragraphs and renders
    unchanged; markup imported or written around the editor may differ
    slightly from the stored HTML.
*   A site that removed :typoscript:`lib.parseFunc_RTE` itself gets the core
    exception 1641989097 on project pages and lists, as it already does for
    every other rich text field.

Affected Installations
======================

All installations that render project pages or project lists with the
templates shipped by this extension.

An installation that overrides :file:`Resources/Private/Pages/AcademicProject.html`
or :file:`Resources/Private/Partials/Project/Item.html` is not affected. A copy
that was taken only to render these fields through
:html:`<f:format.html>` can be dropped, as long as it carries no other change.

.. index:: Fluid, Frontend, ext:academic_projects
