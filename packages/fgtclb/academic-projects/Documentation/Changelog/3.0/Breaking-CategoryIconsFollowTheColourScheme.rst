..  _breaking-projects-category-icons-follow-the-colour-scheme:

=========================================================
Breaking: Category icons follow the backend colour scheme
=========================================================

Description
===========

The four category type icons of this extension, which
:php:`EXT:category_types` registers as :php:`category_types.projects.*`, were
registered with the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`. It renders the
default markup - the markup a :php:`sys_category` type icon reaches - as an
:html:`<img>` tag. An image is opaque to CSS, so the icon kept the ink of its
file whatever the backend colour scheme said.

The four category types now ask for inlining with `inlineIcon: true` in
:file:`Configuration/CategoryTypes.yaml`, so :php:`EXT:category_types` registers
them with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file in both markups, and the four files are drawn in
`currentColor` with no colour of their own. A category type without that flag
keeps the core provider.

Impact
======

The four icons reach the **frontend**, through
:html:`<core:icon identifier="category_types.projects.{type}" />` in
:file:`Pages/AcademicProject.html` and :file:`Partials/Project/Item.html`.
Neither call asks for the `inline` markup, so their rendered markup changes: an
:html:`<img>` of a fixed pixel size becomes an inlined :html:`<svg>` with
:html:`width="1em" height="1em"`, which follows the font size and the colour of
the text around it. Every site using the project plugins sees those icons resize
and recolour.

Site CSS or JavaScript that sized, coloured or addressed the :html:`<img>` has
to address the :html:`<svg>` instead.

In the backend, the four category type icons take the text colour around them,
so they stay legible in a dark backend colour scheme. The academic project page
type keeps the core icon :php:`actions-code-merge` and is unchanged.

Affected Installations
======================

Every installation of this extension. Installations that render the project
plugins in the frontend are affected visibly.

Migration
=========

Replace an image selector with an element selector in the site CSS, for example

..  code-block:: css

    /* before */
    .project-categories .icon img { width: 32px; }

    /* after */
    .project-categories .icon svg { width: 1.25em; }

The icon element keeps the surrounding
:html:`<span class="t3js-icon icon" data-identifier="…">` wrapper, so a
selector written against the wrapper needs no change.

.. index:: Backend, Frontend, ext:academic_projects
