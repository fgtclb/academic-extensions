..  _feature-current-color-svg-icon-provider:

============================================================
Feature: Icon provider for icons that follow the text colour
============================================================

Description
===========

The new icon provider
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
inlines an SVG file as the icon markup - in the default markup as well as in
the `inline` alternative markup. An icon drawn in `currentColor` therefore
takes the colour of the text around it: in the backend it follows the colour
scheme, in the frontend the theme.

The core provider :php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`
renders the default markup as an `<img>` tag. An image is opaque to CSS, so
such an icon keeps the colours of its file and stays dark on a dark backend.

An icon opts in from :file:`Configuration/Icons.php` of the extension that
ships it:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Icons.php

    return [
        'my-extension-add' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_extension/Resources/Public/Icons/add.svg',
        ],
    ];

The file has to be drawn for inlining: a `viewBox`, `fill="currentColor"` or
`stroke="currentColor"` on the shapes, no hardcoded colours, no `id`
attributes - the markup may appear several times in one document - `width`
and `height` of `1em` for frontend use, which both core versions keep, and no
`<script>` element and no event handler attributes. TYPO3 v14 sanitises the
file before inlining it; TYPO3 v13 strips `<script>` elements only - the
sources are files an extension ships and registers itself, never uploads,
which is the same trust boundary as the core provider's `inline` markup.
A comment is kept on TYPO3 v13 and removed on TYPO3 v14, where the core
sanitises the file before inlining it - a licence attribution inside the file
reaches the rendered page on v13 only. A source file that does not exist
renders empty markup, as it does with the core provider's `inline` markup.

The provider needs no configuration of its own and changes nothing until an
icon is registered with it. Nineteen icons of this release are registered with
it: the six control icons of the public profile of `EXT:academic_persons` and
the thirteen of the profile editing view of `EXT:academic_persons_edit`. Every
other icon of the academic extensions stays with the core
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`.

Impact
======

Extensions building on `EXT:academic_base` can register colour-scheme-aware
backend icons and theme-aware frontend icons with one provider on TYPO3 v13
and v14 alike. See :ref:`Icons that follow the text colour
<configuration-icon-provider>`.

.. index:: Backend, Frontend, PHP-API, NotScanned
