..  _breaking-projects-images-render-as-picture:

===============================================
Breaking: Images render as a responsive picture
===============================================

Description
===========

The project list item and the project page template render their image
through the responsive image partial of `EXT:academic_base`,
:file:`Partials/Academic/Image.html`, from 3.0 on. Every raster image is
therefore a :html:`<picture>` with WebP sources and a fallback :html:`<img>`
that carries the classes it carried before, and an SVG file is rendered as one
:html:`<img>` of the original file without processing.

The list item asks for the preset `card`, the page template for `detail`.

The views register :file:`EXT:academic_base/Resources/Private/Partials/`:
the plugin view with the partial root path key `-1`, below the key `0` it uses
already, and the page object of the project page type with the key
`-1758484803`, below every theme and project path. The page object registers it
twice, because a :typoscript:`PAGEVIEW` page object reads no
`partialRootPaths`: it derives those from `paths` by appending
:file:`Partials/`, so `paths` carries
:file:`EXT:academic_base/Resources/Private/` under the same key.

Impact
======

*   CSS that selects the image as a direct child of the card, or as the direct
    child of the detail container, no longer matches: it is wrapped in a
    :html:`<picture>`.
*   The fallback image is requested with `loading="lazy"`, which it was not
    before, and its `width` and `height` are those of the preset rather than
    those of the original file.
*   A project that empties the partial root paths of the plugin, or a page
    object of a project that renders `Project/Item` or the page template in a
    view of its own, fails with an exception on the partial `Academic/Image`
    that the view cannot resolve.

Setting the constant `plugin.tx_academicprojects.view.partialRootPath`, which
is the only partial root path this plugin configures, is **not** affected:
Extbase prepends the partial directory of the extension when the configured
paths do not list it, at the lowest precedence, so `Project/Item` and
`Academic/Image` both stay resolvable and a project override still wins.

Affected Installations
======================

Every installation that renders a project list, or a page of the project page
type with an image.

Migration
=========

#.  Adjust CSS that addresses the project image.
#.  A plugin view whose partial root paths were replaced needs the path of
    `EXT:academic_base` below the others:

    ..  code-block:: typoscript

        plugin.tx_academicprojects.view.partialRootPaths {
            -1 = EXT:academic_base/Resources/Private/Partials/
        }

#.  If a page object of the project renders the page template or
    `Project/Item` itself, list the path there with a key of your own - under
    `partialRootPaths` for a :typoscript:`FLUIDTEMPLATE` page object, under
    `paths` for a :typoscript:`PAGEVIEW` one:

    ..  code-block:: typoscript

        page.10 {
            partialRootPaths.-1700000001 = EXT:academic_base/Resources/Private/Partials/
            paths.-1700000001 = EXT:academic_base/Resources/Private/
        }

#.  Flush the TYPO3 caches, so the Fluid template cache is rebuilt.

..  index:: Fluid, Frontend, TypoScript, ext:academic_projects
