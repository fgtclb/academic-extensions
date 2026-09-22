..  index:: ! Templates
..  _templates:

=========
Templates
=========

This extension ships one Fluid partial: the responsive image the academic
extensions render their images through. Its path, its arguments and its four
presets are public API, and change only with a breaking changelog entry.

..  _templates-image:

The responsive image partial
============================

:file:`EXT:academic_base/Resources/Private/Partials/Academic/Image.html`
renders one image of a record for the frontend:

*   a raster image as a :html:`<picture>` with one WebP :html:`<source>` per
    breakpoint of the preset, and a fallback :html:`<img>` in the original
    format that loads lazily and states its width and height;
*   an SVG image as one :html:`<img>` of the original file, without sources and
    without processing;
*   the placeholder as one :html:`<img>` when there is no image, and nothing
    when there is no placeholder either.

A template of an academic extension renders it like this:

..  code-block:: html
    :caption: EXT:academic_persons/Resources/Private/Partials/Profile/Item/Image.html

    <f:render
        partial="Academic/Image"
        arguments="{
            image: profile.image,
            preset: 'card',
            placeholder: settings.image.placeholder.default,
            class: 'academic-persons-item__image card-img-top img-fluid'
        }"
    />

..  _templates-image-arguments:

Arguments
---------

..  list-table::
    :header-rows: 1
    :widths: 20 80

    *   -   Argument
        -   Meaning
    *   -   `image`
        -   An Extbase or a core file reference. Empty renders the placeholder.
    *   -   `preset`
        -   `card`, `detail`, `logo` or `teaser`. Required: an unknown preset
            fails the rendering instead of silently rendering no image.
    *   -   `cropVariant`
        -   The crop variant applied to every source and to the fallback image,
            `default` when empty. A variant without a crop area on the image
            renders the image uncropped.
    *   -   `placeholder`
        -   An `EXT:` path to a public file, rendered when there is no image.
            Empty renders nothing. A path that does not resolve fails the
            rendering, as :html:`<f:image>` does.
    *   -   `alt`
        -   The alternative text. Empty uses the alternative text of the file.
    *   -   `class`
        -   The class of the image element: the fallback :html:`<img>`, the SVG
            or the placeholder.
    *   -   `showCaption`
        -   Renders the description of the file in a :html:`<figcaption>`.
    *   -   `showCopyright`
        -   Renders the copyright of the file in the :html:`<figcaption>`. The
            field exists with the core extension `filemetadata` only; without
            it, nothing is rendered and no error is raised.

The :html:`<figure>` around the image is rendered only when a caption or a
copyright is requested and the file has one.

..  _templates-image-presets:

Presets
-------

Every preset is a section of the partial holding its sources and the width of
its fallback image. A width is a maximum: an image is never scaled up.

..  list-table::
    :header-rows: 1

    *   -   Preset
        -   Sources (media query: maximum width)
        -   Fallback
    *   -   `card`
        -   `min-width: 992px`: 390, `min-width: 768px`: 370,
            `min-width: 576px`: 690, otherwise 535
        -   690
    *   -   `detail`
        -   `min-width: 992px`: 1200, `min-width: 576px`: 960, otherwise 540
        -   1200
    *   -   `logo`
        -   `min-width: 768px`: 320, otherwise 240
        -   320
    *   -   `teaser`
        -   `min-width: 992px`: 600, `min-width: 576px`: 720, otherwise 540
        -   720

`teaser` has no upstream consumer yet; it gives a project a stable name for
teaser images rather than a section of its own in an override.

WebP output needs `webp` in :php:`$GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']`,
which the core default configuration contains on TYPO3 v13 and v14. An
installation that removed it gets the exception 1618992262 of the core image
view helpers for every raster image the partial renders.

..  _templates-image-override:

Overriding the partial
----------------------

The academic extensions that render the partial register the path
:file:`EXT:academic_base/Resources/Private/Partials/` in their plugin view
with the key `-1`, below every key they use themselves. A project overrides the
partial like any other partial of the plugin: it places its own
:file:`Academic/Image.html` in the partial root path it configures through the
constant of that plugin. One override per plugin changes the markup, the
presets and the breakpoints of every image that plugin renders.

The extensions that ship a page template for their page type -
`EXT:academic_partners`, `EXT:academic_programs` and `EXT:academic_projects` -
register the same path in :typoscript:`page.10.partialRootPaths`, and
`EXT:academic_jobs` does it for the partials it registers there. That array
belongs to the site package, so the key is a negative one of its own per
extension rather than `-1`: it sorts below every theme and project path, and
replaces none of them.

A project that replaces the partial root paths of such a plugin completely
has to list the academic_base path itself:

..  code-block:: typoscript

    plugin.tx_academicpersons.view.partialRootPaths {
        -1 = EXT:academic_base/Resources/Private/Partials/
    }

Without it, the plugin fails on the partial it cannot resolve.
