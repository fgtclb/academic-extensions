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

..  _configuration-hidden-type-stays-selected:

An existing content element keeps its hidden type
=================================================

A content element that already exists keeps its content type on a page where
that type is not enabled — a site that does not include the component, or a
page that restricts the content types with its own page TSconfig. The
:guilabel:`Type` field shows the stored type as the selected option, followed
by :guilabel:`(not enabled on this page)`, so saving the record leaves the type
as it is.

Choosing another type and saving changes it as usual; the hidden type is not
offered again afterwards. A new content element is only offered the types
enabled on its page.

This covers every content element in the :guilabel:`Academic` group, without
any configuration. Content types of other extensions keep the behaviour of
TYPO3, which selects the first available option instead. See
:ref:`important-1789465200`.

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
such an icon keeps the colours of its file. That is right for a brand icon
drawn in fixed colours and meant to look the same on every background, and
wrong for anything that should match the text next to it - a record icon in
the record list as much as an action icon in a button.

The academic extensions register every icon they ship with it, starting with
the shared icon set of this extension, see :ref:`Icons <icons>`. Category type
and group icons are registered programmatically by `EXT:category_types` and ask
for it with `inlineIcon: true` in the :file:`Configuration/CategoryTypes.yaml`
that declares them.

..  _configuration-icon-provider-opt-in:

Opting in
---------

Register the icon with this provider instead of the core one, in the file of
the registry that shows it: :file:`Configuration/FrontendIcons.php` of the
extension that ships it for an icon a frontend template renders (see
:ref:`configuration-frontend-icons`), :file:`Configuration/Icons.php` for an
icon the backend shows. The academic extensions decide by the group of the
identifier, see :ref:`icons-naming`: an action, a state or the glyph in front
of a piece of information is a frontend icon, the icon of a record type, a
content element or a page type a backend icon.

..  code-block:: php
    :caption: EXT:my_extension/Configuration/FrontendIcons.php

    return [
        'tx-myextension-action-add' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_extension/Resources/Public/Icons/action/add.svg',
        ],
    ];

Nothing else changes: the icon is rendered as before, in a frontend template
with :html:`<ab:icon identifier="tx-myextension-action-add" />`, and a backend
icon with the :html:`<core:icon>` ViewHelper, with
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
*   No `id` attributes and no `<style>` element. The markup may appear more
    than once in one document, and once inlined an `id` and a style rule are
    global: two files that both carry Adobe Illustrator's defaults
    (`id="SVGID_1_"`, `.st0`) will paint each other's shapes and resolve each
    other's `clip-path`.
*   No `<script>` element and no event handler attributes. The provider
    sanitises the content on both core versions - TYPO3 v14 does it itself, and
    on TYPO3 v13, whose `getInlineSvg()` removes `<script>` elements and nothing
    else, the provider runs
    :php:`\TYPO3\CMS\Core\Resource\Security\SvgSanitizer` before inlining.
    That is a filter, not a licence: the provider is still meant for files an
    extension ships and registers itself, never uploads.
*   A comment does not survive the sanitiser on either core version. A licence
    attribution the icon set requires can stay inside the file for whoever reads
    the repository, but the rendered page never carries it - give it in the
    documentation or a credits line where the licence requires attribution in
    the output.

..  code-block:: xml
    :caption: EXT:my_extension/Resources/Public/Icons/add.svg

    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16">
        <!-- Attribution of the icon set, when its licence requires one -->
        <path fill="currentColor" d="M7 2h2v5h5v2H9v5H7V9H2V7h5z"/>
    </svg>

A file that does not exist renders empty markup rather than a broken image,
so check a new registration once in the backend or with a rendering test.

The icons of the academic extensions follow a stricter house format on top of
these rules: Font Awesome Free solid, one 640 unit grid for every icon and
`fill="currentColor"` on the root element, see :ref:`Icons <icons>`.

..  _configuration-frontend-icons:

Frontend icons
==============

This extension keeps a registry of its own for the icons a visitor sees. The
icon registry of TYPO3 is built for the backend: it loads every core icon,
the record icons and the flags with it, and its icons are styled by the
backend stylesheet. The frontend registry holds only what extensions and site
packages register for the frontend. Neither registry reads the other.

..  _configuration-frontend-icons-register:

Registering an icon
-------------------

Every active extension and site package can ship
:file:`Configuration/FrontendIcons.php`. It has the format of
:file:`Configuration/Icons.php`: an array of icon identifiers to the icon
provider and its options. Every icon provider of TYPO3 can be used, and
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
for an SVG drawn in `currentColor`. An entry without a provider gets the one
TYPO3 derives from the file name, the SVG provider for a file ending in `svg`
and the bitmap provider for every other file.

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/FrontendIcons.php

    return [
        'my-sitepackage-download' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/download.svg',
        ],
    ];

The files are read in the loading order of the packages, and a package loaded
later replaces the whole configuration of an identifier an earlier one
registered. A site package replaces an icon of an academic extension by
registering the same identifier, provided it loads after that extension: it
requires the extension in its :file:`composer.json`, and in classic mode names
it under `depends` in its :file:`ext_emconf.php` as well.

An icon that both the frontend and the backend show, such as a category type
icon, is registered in both files with the same configuration. A site that
replaces it replaces it in both.

An entry whose provider is not an icon provider makes every icon of the view
helper fail with an error that names the entry, so a typo in a class name is
found on the first page rather than shipped.

..  _configuration-frontend-icons-render:

Rendering an icon
-----------------

The view helper ``icon`` of this extension renders an icon of the frontend
registry. It takes the arguments of :html:`<core:icon>` with the same
defaults: `identifier`, `size` (default `small`), `overlay`, `state` (default
`default`), `alternativeMarkupIdentifier`, for example `inline`, and `title`.
The markup is the one :html:`<core:icon>` renders for an icon registered with
the same provider and options, so a stylesheet written for one fits the other.

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Download.html

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:icon identifier="my-sitepackage-download" alternativeMarkupIdentifier="inline" />

    </html>

Unlike :html:`<core:icon>`, a `title` appears only on the icon rendered with
it, also when the same icon is rendered several times on one page.

The view helper reads the frontend registry only. An icon registered in
:file:`Configuration/Icons.php` alone, or one of TYPO3's own icons, is unknown
to it. An unknown identifier renders the placeholder `default-not-found`, the
red drawing TYPO3 shows for an unknown icon, marked
`data-identifier="default-not-found"`. This extension registers it in its own
:file:`Configuration/FrontendIcons.php`, so a site package replaces it like
any other icon.

..  _configuration-frontend-icons-code:

Icons from code
---------------

An extension that computes its icons, from a configuration file of its own for
example, contributes them through the event
:php:`\FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent`. It is
dispatched while the registry is built, before the files are read, so an entry
of a :file:`Configuration/FrontendIcons.php` with the same identifier replaces
a contributed icon whatever the order of the two packages.

..  code-block:: php
    :caption: EXT:my_extension/Classes/EventListener/ContributeFrontendIcons.php

    namespace MyVendor\MyExtension\EventListener;

    use FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;
    use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

    #[AsEventListener(identifier: 'my-extension/contribute-frontend-icons')]
    final readonly class ContributeFrontendIcons
    {
        public function __invoke(CollectFrontendIconsEvent $event): void
        {
            $event->addIcon(
                'my-extension-badge',
                SvgIconProvider::class,
                ['source' => 'EXT:my_extension/Resources/Public/Icons/badge.svg'],
            );
        }
    }

:php:`addIcon()` rejects a provider that is not an icon provider with an error
that names the icon.

..  _configuration-frontend-icons-cache:

Caching
-------

The registry is built once and kept with the system caches. A change to a
:file:`Configuration/FrontendIcons.php`, or to what a listener contributes,
takes effect after the system caches are flushed, as a change to
:file:`Configuration/Icons.php` does:

..  code-block:: bash

    vendor/bin/typo3 cache:flush --group system

Installing or removing an extension needs no flush, the registry is kept per
set of active packages. :bash:`vendor/bin/typo3 cache:warmup` builds it, so the
first frontend request after a deployment does not.
