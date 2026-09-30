..  _breaking-project-page-renders-inside-the-site-layout:

=====================================================
Breaking: Project pages render inside the site layout
=====================================================

Description
===========

The page template of the page type :guilabel:`Academic project`,
:file:`Resources/Private/Pages/AcademicProject.html`, declared no Fluid layout.
On a site package that renders its header, navigation and footer through a page
layout, as :composer:`bk2k/bootstrap-package` does, a project page therefore
rendered without any of them. The template rendered every part inline, so a
project that wanted to change one of them had to replace the whole template.
And its paths were registered at the key `100` of the page object, which on a
:typoscript:`PAGEVIEW` site package replaced the package's own paths of that key
on project pages - its layouts and partials were gone there.

From 3.0 on:

*   The template renders the section :html:`Main` of the layout named by the
    new setting :typoscript:`plugin.tx_academicprojects.page.layout`, `Default`
    by default. A site package without a layout :file:`Default` gets a fallback
    layout of this extension, which renders the section alone.
*   The section renders five partials: :file:`Project/Page/Header.html`,
    :file:`Project/Page/Media.html`, :file:`Project/Page/Categories.html`,
    :file:`Project/Page/Facts.html` and :file:`Project/Page/Content.html`.
*   The header renders the subtitle of the page between the title and the
    short description, in an element with the class
    `academic-projects-detail__subtitle`. The element around title and short
    description gets the class `academic-projects-detail__header`.
*   Without a project title, the heading shows the title of the page on a
    :typoscript:`PAGEVIEW` page object as well. It stayed empty there, because
    the template read the page from :html:`{data}`, which only a
    :typoscript:`FLUIDTEMPLATE` page object assigns.
*   :typoscript:`paths`, :typoscript:`templateRootPaths` and
    :typoscript:`partialRootPaths` of the page object use the key `50` instead of
    `100` on project pages. :typoscript:`layoutRootPaths.100`, which named a
    directory that does not exist, is removed.

The setting is a site setting of the aggregate set `fgtclb/academic-projects`
and a constant for the static templates, see :ref:`project-page-layout`. The
program page made the same move in 3.0, so the three academic page types
follow one pattern.

Impact
======

*   A project page on a site package with a layout :file:`Default` renders
    inside it: with the header, navigation and footer of the site.
*   A site package whose layout :file:`Default` renders no section
    :html:`Main` renders the project page without its content.
*   A site package without a layout :file:`Default` renders the project page
    as before, without the frame of the site. A layout named by the setting
    that the site package does not have fails the page.
*   A project page with a subtitle shows it below the title.
*   A project page without a project title shows the page title as its heading
    on a :typoscript:`PAGEVIEW` page object as well.
*   A :typoscript:`PAGEVIEW` site package that assigns a variable
    :typoscript:`data` of its own no longer breaks project pages. The page
    record now comes from :html:`{page}` first, in the template and in the
    data processor.
*   A path a project registered at a key between `50` and `100` now wins over
    the extension, where it lost before. A site package path at `100` is no
    longer replaced on project pages.
*   A project that set, read or cleared :typoscript:`page.10.paths.100`,
    :typoscript:`templateRootPaths.100`, :typoscript:`partialRootPaths.100` or
    :typoscript:`layoutRootPaths.100` inside the condition on the project page
    type reaches nothing of this extension there any more.

Affected Installations
======================

Every installation that renders project pages on a site package with page
layouts, and every installation that styles the markup of the project page or
changes its paths at the key `100`.

An override of the whole :file:`AcademicProject.html` keeps rendering, without a
layout, as long as it does not render :typoscript:`styles.content.getContent`,
see :ref:`breaking-projects-content-load-set-removed`.

Migration
=========

*   A site package whose layout for project pages has another name sets
    :typoscript:`plugin.tx_academicprojects.page.layout` to it. One whose
    layout renders another section than :html:`Main` overrides
    :file:`Pages/AcademicProject.html`.
*   A project that overrides :file:`AcademicProject.html` to change one part
    moves that change to the matching partial below
    :file:`Project/Page/` and removes the template override.
*   A project that added a subtitle of its own renders the page field
    :guilabel:`Subtitle` instead, or overrides :file:`Project/Page/Header.html`.
*   Move a path set at the key `100` inside the project page condition to
    `50`, or to a key above it to win over the extension.

..  index:: Frontend, Fluid, TypoScript, ext:academic_projects
