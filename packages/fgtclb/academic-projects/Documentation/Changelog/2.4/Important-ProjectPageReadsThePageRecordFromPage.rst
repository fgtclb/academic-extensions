.. _important-project-page-reads-the-page-record-from-page:

=======================================================
Important: Project page reads the page record from page
=======================================================

Description
===========

The data processor :typoscript:`project-data` builds the project of a project
page from the page record. It took that record from the variable
:typoscript:`data` first, the one a :typoscript:`FLUIDTEMPLATE` page object
assigns, and from the page information object :html:`{page}` of a
:typoscript:`PAGEVIEW` page object only when :typoscript:`data` was empty.

:typoscript:`PAGEVIEW`, which TYPO3 v13 offers, reserves :html:`{page}` but not
:typoscript:`data`, so a :typoscript:`PAGEVIEW` site package may assign a
:typoscript:`data` of its own. A text there failed every project page with a
type error, and the records of a query were read as if they were the page
record.

The processor now takes the record from :html:`{page}` when it is the page
information object, and from :typoscript:`data` otherwise. A value that is not
a non-empty array adds no project.

The heading of a project page falls back to the title of the page when the
project has no project title. It read :html:`{data.title}`, which is empty on a
:typoscript:`PAGEVIEW` page object. The template now takes the page record from
:html:`{page.pageRecord}` first and from :html:`{data}` otherwise, too.

Impact
======

*   A :typoscript:`PAGEVIEW` site package that assigns a variable
    :typoscript:`data` of its own, a text or the records of a query for
    example, no longer breaks project pages on TYPO3 v13.
*   A :typoscript:`PAGEVIEW` site package that assigned another page record as
    :typoscript:`data` on purpose now gets the project of the page it renders.
*   A project without a project title shows the title of the page as its
    heading on a :typoscript:`PAGEVIEW` page object, where the heading was
    empty.
*   A :typoscript:`FLUIDTEMPLATE` page object on TYPO3 v12 or v13 is not
    affected, also when its site package assigns a variable :html:`{page}` of
    its own.

Affected Installations
======================

Installations on TYPO3 v13 that render project pages with a
:typoscript:`PAGEVIEW` page object, in particular when it assigns a variable
:typoscript:`data`.

.. index:: TypoScript, Frontend, ext:academic_projects
