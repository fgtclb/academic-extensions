..  _feature-responsive-image-partial:

============================================
Feature: A responsive image partial to share
============================================

Description
===========

This extension ships its first Fluid partial,
:file:`Resources/Private/Partials/Academic/Image.html`. It renders an image of
a record as a :html:`<picture>` with WebP sources per breakpoint, applies a
named crop variant, shows a placeholder when there is no image, passes SVG
files through unprocessed and adds the caption and the copyright on request.
It accepts Extbase and core file references alike.

The breakpoints and widths come from four presets - `card`, `detail`, `logo`
and `teaser` - each a section of the partial. A project that overrides the one
file therefore changes the image markup of every plugin that renders it.

The partial, its arguments and its presets are public API, described in
:ref:`templates-image`.

`EXT:academic_persons` renders the profile image of its cards and of the
public profile through it, and so does `EXT:academic_contacts4pages`, which
renders the same cards.

Impact
======

An extension or a project template renders an image responsively with one
:html:`<f:render partial="Academic/Image" />` and the path
:file:`EXT:academic_base/Resources/Private/Partials/` in its partial root
paths. Nothing changes for a view that does not register that path.

..  index:: Fluid, Frontend, ext:academic_base
