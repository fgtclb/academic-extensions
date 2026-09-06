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
attributes and no `<style>` element - the markup may appear several times in
one document, and both an `id` and a style rule are document global once
inlined - and `width` and `height` of `1em` for frontend use, which both core
versions keep.

The content is sanitised on both core versions. TYPO3 v14 does that itself;
TYPO3 v13 removes `<script>` elements and nothing else, so the provider runs
:php:`\TYPO3\CMS\Core\Resource\Security\SvgSanitizer` there before inlining -
an `onload` attribute, an `onclick` attribute and a `javascript:` href would
otherwise reach the markup, which was harmless while the default markup was an
`<img>` and is not once the file is part of the document. The sources are still
meant to be files an extension ships and registers itself, never uploads: the
sanitiser closes a hole, it does not make an arbitrary file safe to inline.

A comment does not survive the sanitiser on either core, so a licence
attribution inside the file stays in the source and never reaches the rendered
page.

A source the provider cannot inline renders empty markup rather than raising an
error, on both core versions and whatever the reason: the file does not exist,
cannot be read, is empty, is not XML, or is XML whose root element is not an
`<svg>`. The last one is worth naming, because it is where the sanitiser throws
rather than answering: a `<symbol>` fragment or an HTML document saved under an
:file:`.svg` name is well-formed XML and passes every parse check, and a record
list or a page tree carrying such an icon would answer with an error instead of
rendering one icon less. An icon is decoration and must not be able to fail the
request that renders it.

The provider needs no configuration of its own and changes nothing until an
icon is registered with it. Two groups of icons of this release are registered
with it. Nineteen control icons: the six of the public profile of
`EXT:academic_persons` and the thirteen of the profile editing view of
`EXT:academic_persons_edit`. And every icon a TCA record type resolves - the
record icons of the academic extensions, the two academic page type icons and
the twenty category type icons of the three academic extensions that ship
category types, which ask for it with `inlineIcon: true` in their
:file:`Configuration/CategoryTypes.yaml`. Brand icons, which are drawn in fixed
colours and are meant to look the same on every background, stay with the core
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`.

Impact
======

Extensions building on `EXT:academic_base` can register colour-scheme-aware
backend icons and theme-aware frontend icons with one provider on TYPO3 v13
and v14 alike. See :ref:`Icons that follow the text colour
<configuration-icon-provider>`.

.. index:: Backend, Frontend, PHP-API, NotScanned
