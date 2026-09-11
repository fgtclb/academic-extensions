:navigation-title: Icons

..  _icons:

=====
Icons
=====

This extension ships the icons the academic extensions share: the actions of a
control, the states a control shows, and the glyphs in front of a piece of
information such as a phone number or an address. An icon that means the same
in several extensions exists once, here, so a project that replaces it replaces
it everywhere.

Every icon is a Font Awesome Free icon of the solid style, drawn in
`currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
(see :ref:`Icons that follow the text colour <configuration-icon-provider>`).
It therefore takes the colour of the text around it, in both backend colour
schemes as well as in the frontend, and it sizes itself by the font size: every
file carries `width="1em"` and `height="1em"`.

..  _icons-naming:

Identifiers and files
=====================

The identifiers follow `tx-<extension key without underscores>-<group>-<name>`,
the files :file:`Resources/Public/Icons/<group>/<name>.svg`. This extension
uses three groups:

*   `action` - something a control does,
*   `state` - a state a control shows,
*   `info` - the glyph in front of a piece of information.

The other academic extensions name their own icons the same way, with the
groups `record`, `plugin` and `doktype` for their record types, content
elements and page types. The files a :file:`Configuration/CategoryTypes.yaml`
names live in the directories :file:`category-type/` and
:file:`category-group/`; their identifiers are derived by
`EXT:category_types`.

..  _icons-actions:

Actions
=======

..  list-table::
    :header-rows: 1
    :widths: 35 20 30 15

    *   -   Identifier
        -   File
        -   Meaning
        -   Font Awesome
    *   -   `tx-academicbase-action-add`
        -   :file:`action/add.svg`
        -   Add an item
        -   `plus`
    *   -   `tx-academicbase-action-back`
        -   :file:`action/back.svg`
        -   Go back
        -   `arrow-left`
    *   -   `tx-academicbase-action-clear`
        -   :file:`action/clear.svg`
        -   Clear a field
        -   `eraser`
    *   -   `tx-academicbase-action-close`
        -   :file:`action/close.svg`
        -   Close / dismiss
        -   `xmark`
    *   -   `tx-academicbase-action-collapse`
        -   :file:`action/collapse.svg`
        -   Collapse a section
        -   `minus`
    *   -   `tx-academicbase-action-delete`
        -   :file:`action/delete.svg`
        -   Delete an item
        -   `trash-can`
    *   -   `tx-academicbase-action-drag`
        -   :file:`action/drag.svg`
        -   Drag-and-drop handle
        -   `grip-lines`
    *   -   `tx-academicbase-action-edit`
        -   :file:`action/edit.svg`
        -   Edit
        -   `pen`
    *   -   `tx-academicbase-action-expand`
        -   :file:`action/expand.svg`
        -   Expand a section
        -   `plus`
    *   -   `tx-academicbase-action-help`
        -   :file:`action/help.svg`
        -   Toggle help text
        -   `circle-question`
    *   -   `tx-academicbase-action-move-down`
        -   :file:`action/move-down.svg`
        -   Move one position down
        -   `arrow-down`
    *   -   `tx-academicbase-action-move-up`
        -   :file:`action/move-up.svg`
        -   Move one position up
        -   `arrow-up`
    *   -   `tx-academicbase-action-save`
        -   :file:`action/save.svg`
        -   Save
        -   `floppy-disk`
    *   -   `tx-academicbase-action-undo`
        -   :file:`action/undo.svg`
        -   Undo / reset
        -   `rotate-left`
    *   -   `tx-academicbase-action-upload-image`
        -   :file:`action/upload-image.svg`
        -   Upload or replace an image
        -   `camera`
    *   -   `tx-academicbase-action-view`
        -   :file:`action/view.svg`
        -   Show a preview
        -   `eye`
    *   -   `tx-academicbase-action-view-close`
        -   :file:`action/view-close.svg`
        -   Close the preview
        -   `eye-slash`

..  _icons-states:

States
======

..  list-table::
    :header-rows: 1
    :widths: 35 20 30 15

    *   -   Identifier
        -   File
        -   Meaning
        -   Font Awesome
    *   -   `tx-academicbase-state-hidden`
        -   :file:`state/hidden.svg`
        -   Item is hidden (switch off)
        -   `toggle-off`
    *   -   `tx-academicbase-state-visible`
        -   :file:`state/visible.svg`
        -   Item is visible (switch on)
        -   `toggle-on`

..  _icons-information:

Information
===========

..  list-table::
    :header-rows: 1
    :widths: 35 20 30 15

    *   -   Identifier
        -   File
        -   Meaning
        -   Font Awesome
    *   -   `tx-academicbase-info-calendar`
        -   :file:`info/calendar.svg`
        -   Date
        -   `calendar-days`
    *   -   `tx-academicbase-info-company`
        -   :file:`info/company.svg`
        -   Company / institution
        -   `building`
    *   -   `tx-academicbase-info-contract`
        -   :file:`info/contract.svg`
        -   Contract
        -   `file-contract`
    *   -   `tx-academicbase-info-degree`
        -   :file:`info/degree.svg`
        -   Degree
        -   `graduation-cap`
    *   -   `tx-academicbase-info-department`
        -   :file:`info/department.svg`
        -   Department / faculty
        -   `building-columns`
    *   -   `tx-academicbase-info-email`
        -   :file:`info/email.svg`
        -   E-mail
        -   `envelope`
    *   -   `tx-academicbase-info-employment`
        -   :file:`info/employment.svg`
        -   Employment / job
        -   `briefcase`
    *   -   `tx-academicbase-info-information`
        -   :file:`info/information.svg`
        -   Additional information
        -   `circle-info`
    *   -   `tx-academicbase-info-international`
        -   :file:`info/international.svg`
        -   International
        -   `earth-europe`
    *   -   `tx-academicbase-info-link`
        -   :file:`info/link.svg`
        -   Link
        -   `link`
    *   -   `tx-academicbase-info-location`
        -   :file:`info/location.svg`
        -   Location / address
        -   `location-dot`
    *   -   `tx-academicbase-info-partnership`
        -   :file:`info/partnership.svg`
        -   Partnership / cooperation
        -   `handshake`
    *   -   `tx-academicbase-info-phone`
        -   :file:`info/phone.svg`
        -   Phone
        -   `phone`
    *   -   `tx-academicbase-info-recommendation`
        -   :file:`info/recommendation.svg`
        -   Recommendation
        -   `star`
    *   -   `tx-academicbase-info-role`
        -   :file:`info/role.svg`
        -   Role
        -   `user-tag`
    *   -   `tx-academicbase-info-room`
        -   :file:`info/room.svg`
        -   Room
        -   `door-open`
    *   -   `tx-academicbase-info-sector`
        -   :file:`info/sector.svg`
        -   Sector / industry
        -   `industry`

The file column is relative to
:file:`EXT:academic_base/Resources/Public/Icons/`. `expand` and `add` are the
same drawing in two files, so that a project can replace one without the other.

Four identifiers are registered although no template, TCA or TSconfig of the
academic extensions names them today: `tx-academicbase-info-department`,
`tx-academicbase-info-information`, `tx-academicbase-info-partnership` and
`tx-academicbase-info-role`. They stay registered on purpose. They are part of
the shared set a project can render and override, and their files are the
drawings of record and category type icons of other academic extensions. Those
icons are registered under identifiers of their own and point at the file, so
overriding one of the four identifiers does not change them.

..  _icons-usage:

Using an icon
=============

In a Fluid template, frontend or backend:

..  code-block:: html

    <core:icon identifier="tx-academicbase-info-phone" alternativeMarkupIdentifier="inline" />

`core` is a global Fluid namespace, so no namespace declaration is needed. The
`inline` alternative markup is what makes the icon follow the text colour even
where a project has registered the identifier with the core
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`, which inlines
the file only when it is asked to.

The rendered icon carries the CSS class `icon-<identifier>`, for example
`icon-tx-academicbase-info-phone`, and the attribute `data-identifier`. A
stylesheet can address it through either.

..  _icons-frontend-javascript:

Icons in frontend JavaScript
============================

The backend JavaScript icon API of TYPO3 cannot be used on a frontend page: it
asks a backend route that answers a logged-in backend user only. Frontend
JavaScript therefore takes the markup of an icon from the server.

..  important::

    Everything in this section except the :html:`<template>` is **internal and
    experimental**: the ViewHelper, the icon endpoint, the event and the PHP
    classes behind them.
    They may change, or go away, without a breaking change entry until a module
    outside the academic extensions uses them.

Where the JavaScript stamps out a piece of markup anyway - a list item, a row
with its buttons - render the icon with `<core:icon>` into a
`<template>` element and clone it. Where it picks an icon by its
identifier at runtime, hand it the icons it may need as a JSON map:

..  code-block:: html

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add', 1: 'tx-academicbase-action-delete'}" />

This renders a JSON data block that the browser neither executes nor checks
against the Content Security Policy:

..  code-block:: html

    <script type="application/json" data-academic-icons data-academic-icons-size="small">
        {"tx-academicbase-action-add": "<span class=\"t3js-icon icon ...\">...</span>", ...}
    </script>

Each value is the markup `<core:icon identifier="..."
alternativeMarkupIdentifier="inline" />` renders, so a project's replacement of
an icon arrives in the JavaScript as it does in a template.

Arguments:

`identifiers` (array, required)
    The identifiers to render. One that may not be served is left out of the
    map - see below.

`size` (string, default `small`)
    `default`, `small`, `medium`, `large` or `mega`. It sets the `icon-size-*`
    class of the markup; the icons of the academic extensions size themselves
    by the font size.

`endpoint` (bool, default `false`)
    Adds `data-academic-icons-url` and
    `data-academic-icons-version` to the element: the
    :ref:`icon endpoint <icons-frontend-endpoint>` of the current site language
    and the version token of the icon set.

..  _icons-frontend-endpoint:

The icon endpoint
-----------------

Where the JavaScript learns an identifier only after the page was rendered - from
data it loads, or from a choice of the visitor - it asks the icon endpoint of
the site:

..  code-block:: text

    GET https://example.com/_academic/icons.json?i=tx-academicbase-action-add,tx-academicbase-info-phone&s=small&v=<token>

The answer is a JSON object in the same shape as the JSON map, for the
identifiers that may be served, in the order asked for. The endpoint is
available below the base of every site and site language, for example
`https://example.com/de/_academic/icons.json`. Its parameters and its answer
are internal and experimental like the rest of this section.

`i` (required)
    Up to 32 icon identifiers, separated by commas.

`s` (optional, default `small`)
    `default`, `small`, `medium`, `large` or `mega`.

`v` (optional)
    The version token of the icon set, as `data-academic-icons-version` of the
    JSON map carries it. With the current token the answer may be cached for a
    year (`Cache-Control: public, max-age=31536000, immutable`), otherwise for
    five minutes. The answer carries an `ETag` and answers a matching
    `If-None-Match` with `304 Not Modified`. The `ETag` only helps the
    five-minute answers: an immutable one is never revalidated.

A malformed identifier, more than 32 of them or an unknown size are answered
with `400 Bad Request`, any method other than `GET` and `HEAD` with
`405 Method Not Allowed`. The endpoint answers before the frontend user
authentication: it never starts a session and never sets a cookie, so a proxy
or a CDN can cache it like a file.

The token changes when an icon registration or the modification time of an
icon file changes, on a TYPO3 update and whenever :file:`composer.lock`
changes. A change to the rendering code that arrives with none of these - a
file edited in place on the server - is not seen; touch the SVG files of the
affected icons then.

..  important::

    **Web server requirement.** The path ends in `.json`. A web server or CDN
    rule that serves `*.json` as static files - in nginx for example a
    :code:`location ~* \.(...|json)$` with :code:`try_files $uri =404` - answers
    the endpoint with a `404` before TYPO3 sees it. Exclude
    `_academic/icons.json` from such a rule, the way `sitemap.xml` is usually
    excluded.

..  _icons-frontend-allow-list:

Which icons are served
----------------------

The JSON map and the endpoint serve the same icons. Only identifiers starting
with `tx-academic` or `category_types.` are served, and of those only the ones
that are registered, not deprecated and registered with a provider that
inlines an SVG file: the
:php:`CurrentColorSvgIconProvider` of this extension or the core
:php:`SvgIconProvider`. Any other identifier, an icon of the core icon set
among them, is left out rather than answered with the placeholder of an
unknown icon.

The markup is what the provider of the icon renders, sanitised as far as that
provider sanitises. :php:`CurrentColorSvgIconProvider` sanitises on TYPO3 v13
and v14; the inline markup of the core :php:`SvgIconProvider` is sanitised on
TYPO3 v14 only. That is the same markup, with the same exposure, as the icon
rendered inline by a template.

A project serves the icons of its own extensions the same way by adding the
prefix of their identifiers with a listener to
:php:`\FGTCLB\AcademicBase\Event\ModifyFrontendIconAllowListEvent`. A
prefix opens every identifier it matches: some system extensions register
icons of their own with the core :php:`SvgIconProvider`, so a prefix such as
`module-` also serves the icons of the install tool modules. Sprite, bitmap and
font icons stay refused whatever the list holds.

..  code-block:: php
    :caption: EXT:my_sitepackage/Classes/EventListener/AllowFrontendIcons.php

    namespace MyVendor\MySitepackage\EventListener;

    use FGTCLB\AcademicBase\Event\ModifyFrontendIconAllowListEvent;
    use TYPO3\CMS\Core\Attribute\AsEventListener;

    #[AsEventListener(identifier: 'my-sitepackage/frontend-icons')]
    final readonly class AllowFrontendIcons
    {
        public function __invoke(ModifyFrontendIconAllowListEvent $event): void
        {
            $event->addPrefix('tx-mysitepackage-');
        }
    }

..  _icons-override:

Replacing an icon in a project
==============================

Register the same identifier again in :file:`Configuration/Icons.php` of the
site package. The later registration wins, and every template and every
extension that renders the identifier shows the replacement:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/Icons.php

    return [
        'tx-academicbase-info-phone' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/phone.svg',
        ],
    ];

*   The site package has to depend on :composer:`fgtclb/academic-base` (or on
    an academic extension, which depends on it): `require` in its
    :file:`composer.json` and `depends` in its :file:`ext_emconf.php`. The
    :file:`Icons.php` files are read in the order of the loaded extensions,
    which follows the dependencies, and without the dependency the site package
    may be read first and lose.
*   The entry replaces the whole registration, provider and source alike.
*   Keep the replacement drawable for inlining if it stays with
    :php:`CurrentColorSvgIconProvider`: a `viewBox`, `currentColor`, no `id`
    and no `<style>` - see
    :ref:`What the SVG file must look like <configuration-icon-provider-svg>`.
*   Flush the caches afterwards.

The same works for every icon of the academic extensions. The category type
icons are the exception: `EXT:category_types` registers them after every
:file:`Icons.php` has been read, so an entry for a `category_types.*`
identifier is overwritten.

..  _icons-licence:

Third-party icons
=================

The SVG icons below :file:`Resources/Public/Icons/`, except
:file:`Extension.svg`, are `Font Awesome Free <https://fontawesome.com>`__
icons by Fonticons, Inc., licensed under the `Creative Commons Attribution 4.0
International license <https://creativecommons.org/licenses/by/4.0/>`__. The
notice :file:`Resources/Public/Icons/LICENSE-font-awesome.txt` lists every file
with its Font Awesome name and the changes made to it.

The files are taken from version 7.3.1. They are modified: the fill colour
moved to the root element, `width` and `height` were added, and each file is
named after its purpose. Each file keeps Font Awesome's attribution comment,
which the icon provider removes from the rendered markup together with every
other comment. :file:`Extension.svg` is the icon of the extension itself and is
not part of the set.
