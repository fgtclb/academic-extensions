:navigation-title: Configuration

..  _configuration:

=============
Configuration
=============

This extension ships no frontend TypoScript. What it does ship is the backend
page TSconfig that labels the content element group :guilabel:`Academic`, the
group every academic extension sorts its content elements into.

That page TSconfig is delivered in three ways, and the first one needs no
configuration at all:

*   :file:`Configuration/page.tsconfig` of this extension, which TYPO3 includes
    for the whole installation. The group is therefore labelled on every
    installation, whatever the site configuration says.
*   The site set :yaml:`fgtclb/academic-base-ctype-group`, for a site
    configuration that lists it — usually because another academic extension
    depends on it.
*   The page TSconfig file registered for the page field
    :guilabel:`Page TSconfig`.

All three read the same file, so nothing changes when more than one of them
applies.

..  _configuration-components:

What the sets contain
=====================

..  list-table::
    :header-rows: 1

    *   -   Set
        -   Delivers
    *   -   `fgtclb/academic-base-ctype-group`
        -   The label of the content element group :guilabel:`Academic` in the
            new content element wizard.
    *   -   `fgtclb/academic-base`
        -   Everything above. This is the set to use unless you deliberately
            want a subset.

Every academic extension that ships content elements depends on
`fgtclb/academic-base-ctype-group`, so a site that includes one of those sets
gets this one with it and needs no entry of its own.

..  _configuration-hidden-by-default:

No content element is hidden by default
=======================================

This extension ships no content element of its own, so it hides none. The
content elements of the other academic extensions are hidden for the whole
installation and brought back per component by the extension that ships them —
see the :guilabel:`Configuration` chapter of that extension.

..  _site-set:

Include the site set
====================

Add the set to the :file:`config.yaml` of the site:

..  code-block:: diff
    :caption: config/sites/my-site/config.yaml (diff)

     base: 'https://example.com/'
     rootPageId: 1
    +dependencies:
    +  - fgtclb/academic-base

See also `TYPO3 Explained, Using a site set as dependency in a site
<https://docs.typo3.org/permalink/t3coreapi:site-sets-usage>`__.

..  _static-templates:

Include static templates
========================

For an installation that configures itself through records rather than through
a site configuration, the same file is registered as a selectable page TSconfig
file.

..  _static-typoscript:

Include static TypoScript
-------------------------

This extension ships no TypoScript and therefore registers no static template.

..  _static-pagetsconfig:

Include static page TSconfig
----------------------------

Edit the page record of the site root, tab :guilabel:`Resources`, field
:guilabel:`Page TSconfig`, and add the entry:

..  list-table::
    :header-rows: 1

    *   -   Entry
        -   Delivers
    *   -   :guilabel:`Academic Base: Content element group (academic_base)`
        -   The label of the content element group :guilabel:`Academic`.
    *   -   :guilabel:`Academic Base: All components (academic_base)`
        -   Every component this extension ships, in one entry.

The setting is inherited by every page below the one it is set on.

..  _one-mechanism-per-site:

Do not combine both
===================

For this extension both mechanisms assign the same two values, so combining
them is harmless. For the academic extensions that also ship TypoScript it is
not — see the :guilabel:`Configuration` chapter of the extension in question.
Use one mechanism per site.

..  _configuration-icon-provider:

Icons that follow the text colour
=================================

This extension ships an icon provider for the other academic extensions and
for site packages:
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`.
It inlines the SVG file as the icon markup, in the default markup as well as
in the `inline` alternative markup, so an icon drawn in `currentColor` takes
the colour of the text around it - the backend colour scheme, or the theme of
the frontend.

The core provider :php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`
renders the default markup as an `<img>` tag. An image is opaque to CSS, so
such an icon keeps the colours of its file. That is right for a record or a
brand icon drawn in fixed colours, and wrong for an action icon that should
match the text next to it.

..  _configuration-icon-provider-opt-in:

Opting in
---------

Register the icon in :file:`Configuration/Icons.php` of the extension that
ships it, with this provider instead of the core one:

..  code-block:: php
    :caption: EXT:my_extension/Configuration/Icons.php

    return [
        'my-extension-add' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_extension/Resources/Public/Icons/add.svg',
        ],
    ];

Nothing else changes: the icon is rendered as before, with the
:html:`<core:icon identifier="my-extension-add" />` ViewHelper, with
:php:`IconFactory::getIcon()`, or as a `typeicon_classes` entry of a TCA
table. Whether the `inline` alternative markup is requested or not, the
markup is the inlined file.

..  _configuration-icon-provider-svg:

What the SVG file must look like
--------------------------------

The file is inlined into the HTML of the page, possibly several times, so it
has to be drawn for that:

*   A `viewBox` attribute on the root element; without it the element cannot
    scale.
*   `width="1em"` and `height="1em"` for an icon used in the frontend. Both
    core versions keep the two attributes, and with them the icon follows the
    font size of the text around it. The backend needs neither: its CSS sizes
    every icon through the wrapper.
*   `fill="currentColor"` or `stroke="currentColor"` on every drawable
    element, and no hardcoded colour anywhere - not as an attribute and not
    in a `<style>` element. A hardcoded colour is exactly what this provider
    exists to avoid.
*   No `id` attributes. The markup may appear more than once in one
    document, and a duplicated `id` is invalid HTML.
*   No `<script>` element and no event handler attributes. TYPO3 v14 sanitises
    the file before inlining it; TYPO3 v13 strips `<script>` elements only. The
    provider inlines files an extension ships and registers itself, never
    uploads - the same trust boundary as the `inline` markup of the core
    provider.
*   A comment is kept on TYPO3 v13 and removed on TYPO3 v14, where the core
    sanitises the file before inlining it. A licence attribution the icon set
    requires can stay inside the file for the source, but the rendered page
    does not carry it on v14 - give it in the documentation or a credits line
    where the licence requires attribution in the output.

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Public/Icons/add.svg

    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
        <!-- Attribution of the icon set, when its licence requires one -->
        <path fill="currentColor" d="M7 2h2v5h5v2H9v5H7V9H2V7h5z"/>
    </svg>

A file that does not exist renders empty markup rather than a broken image,
so check a new registration once in the backend or with a rendering test.
