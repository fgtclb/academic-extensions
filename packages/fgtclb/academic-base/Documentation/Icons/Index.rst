:navigation-title: Icons

..  _icons:

=====
Icons
=====

This extension ships the icons the academic extensions share: the actions of a
control, the states a control shows, and the glyphs in front of a piece of
information such as a phone number or an address. An icon that means the same
in several extensions exists once, here, so a site package that replaces it
replaces it everywhere.

They are frontend icons. They are registered in the frontend icon registry of
this extension, in its :file:`Configuration/FrontendIcons.php`, and the
templates of the academic extensions render them with the view helper
``ab:icon``, see :ref:`Frontend icons <configuration-frontend-icons>`. The
backend does not show them, and the icon registry of TYPO3 does not know them.

Every icon is a Font Awesome Free icon of the solid style, drawn in
`currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
(see :ref:`Icons that follow the text colour <configuration-icon-provider>`).
It therefore takes the colour of the text around it, and it sizes itself by
the font size: every file carries `width="1em"` and `height="1em"`.

..  _icons-naming:

Identifiers and files
=====================

The identifiers follow `tx-<extension key without underscores>-<group>-<name>`,
the files :file:`Resources/Public/Icons/<group>/<name>.svg`. This extension
uses three groups:

*   `action`, something a control does,
*   `state`, a state a control shows,
*   `info`, the glyph in front of a piece of information.

The other academic extensions name their icons the same way. The group decides
the registry: an `action`, `state` or `info` icon is a frontend icon,
registered in :file:`Configuration/FrontendIcons.php`, and a `record`,
`plugin` or `doktype` icon, the icon of a record type, a content element or a
page type, is a backend icon, registered in :file:`Configuration/Icons.php`.
The icons of category types and category groups are the exception: their
identifiers are derived by `EXT:category_types` from the
:file:`Configuration/CategoryTypes.yaml` that declares them, and they reach
both registries. Their files live in the directories :file:`category-type/`
and :file:`category-group/`, or are files of this set.

The tables below give the file relative to
:file:`EXT:academic_base/Resources/Public/Icons/` and the name of the icon in
Font Awesome.

..  _icons-actions:

Actions
=======

..  list-table::
    :header-rows: 1
    :widths: 40 25 20 15

    *   -   Identifier
        -   Meaning
        -   File
        -   Font Awesome
    *   -   `tx-academicbase-action-add`
        -   Add an item
        -   :file:`action/add.svg`
        -   `plus`
    *   -   `tx-academicbase-action-back`
        -   Go back
        -   :file:`action/back.svg`
        -   `arrow-left`
    *   -   `tx-academicbase-action-clear`
        -   Clear a field
        -   :file:`action/clear.svg`
        -   `eraser`
    *   -   `tx-academicbase-action-close`
        -   Close or dismiss
        -   :file:`action/close.svg`
        -   `xmark`
    *   -   `tx-academicbase-action-collapse`
        -   Collapse a section
        -   :file:`action/collapse.svg`
        -   `minus`
    *   -   `tx-academicbase-action-delete`
        -   Delete an item
        -   :file:`action/delete.svg`
        -   `trash-can`
    *   -   `tx-academicbase-action-drag`
        -   Drag-and-drop handle
        -   :file:`action/drag.svg`
        -   `grip-lines`
    *   -   `tx-academicbase-action-edit`
        -   Edit
        -   :file:`action/edit.svg`
        -   `pen`
    *   -   `tx-academicbase-action-expand`
        -   Expand a section
        -   :file:`action/expand.svg`
        -   `plus`
    *   -   `tx-academicbase-action-help`
        -   Show or hide a help text
        -   :file:`action/help.svg`
        -   `circle-question`
    *   -   `tx-academicbase-action-move-down`
        -   Move one position down
        -   :file:`action/move-down.svg`
        -   `arrow-down`
    *   -   `tx-academicbase-action-move-up`
        -   Move one position up
        -   :file:`action/move-up.svg`
        -   `arrow-up`
    *   -   `tx-academicbase-action-save`
        -   Save
        -   :file:`action/save.svg`
        -   `floppy-disk`
    *   -   `tx-academicbase-action-undo`
        -   Undo or reset
        -   :file:`action/undo.svg`
        -   `rotate-left`
    *   -   `tx-academicbase-action-upload-image`
        -   Upload or replace an image
        -   :file:`action/upload-image.svg`
        -   `camera`
    *   -   `tx-academicbase-action-view`
        -   Show a preview
        -   :file:`action/view.svg`
        -   `eye`
    *   -   `tx-academicbase-action-view-close`
        -   Close the preview
        -   :file:`action/view-close.svg`
        -   `eye-slash`

..  _icons-states:

States
======

..  list-table::
    :header-rows: 1
    :widths: 40 25 20 15

    *   -   Identifier
        -   Meaning
        -   File
        -   Font Awesome
    *   -   `tx-academicbase-state-hidden`
        -   Item is hidden (switch off)
        -   :file:`state/hidden.svg`
        -   `toggle-off`
    *   -   `tx-academicbase-state-visible`
        -   Item is visible (switch on)
        -   :file:`state/visible.svg`
        -   `toggle-on`

..  _icons-information:

Information
===========

..  list-table::
    :header-rows: 1
    :widths: 40 25 20 15

    *   -   Identifier
        -   Meaning
        -   File
        -   Font Awesome
    *   -   `tx-academicbase-info-calendar`
        -   Date
        -   :file:`info/calendar.svg`
        -   `calendar-days`
    *   -   `tx-academicbase-info-company`
        -   Company or institution
        -   :file:`info/company.svg`
        -   `building`
    *   -   `tx-academicbase-info-contract`
        -   Contract
        -   :file:`info/contract.svg`
        -   `file-contract`
    *   -   `tx-academicbase-info-degree`
        -   Degree
        -   :file:`info/degree.svg`
        -   `graduation-cap`
    *   -   `tx-academicbase-info-department`
        -   Department or faculty
        -   :file:`info/department.svg`
        -   `building-columns`
    *   -   `tx-academicbase-info-email`
        -   E-mail
        -   :file:`info/email.svg`
        -   `envelope`
    *   -   `tx-academicbase-info-employment`
        -   Employment or job
        -   :file:`info/employment.svg`
        -   `briefcase`
    *   -   `tx-academicbase-info-information`
        -   Additional information
        -   :file:`info/information.svg`
        -   `circle-info`
    *   -   `tx-academicbase-info-international`
        -   International
        -   :file:`info/international.svg`
        -   `earth-europe`
    *   -   `tx-academicbase-info-link`
        -   Link
        -   :file:`info/link.svg`
        -   `link`
    *   -   `tx-academicbase-info-location`
        -   Location or address
        -   :file:`info/location.svg`
        -   `location-dot`
    *   -   `tx-academicbase-info-partnership`
        -   Partnership or cooperation
        -   :file:`info/partnership.svg`
        -   `handshake`
    *   -   `tx-academicbase-info-person`
        -   Person, a contact
        -   :file:`info/person.svg`
        -   `user`
    *   -   `tx-academicbase-info-phone`
        -   Phone
        -   :file:`info/phone.svg`
        -   `phone`
    *   -   `tx-academicbase-info-recommendation`
        -   Recommendation
        -   :file:`info/recommendation.svg`
        -   `star`
    *   -   `tx-academicbase-info-role`
        -   Role
        -   :file:`info/role.svg`
        -   `user-tag`
    *   -   `tx-academicbase-info-room`
        -   Room
        -   :file:`info/room.svg`
        -   `door-open`
    *   -   `tx-academicbase-info-sector`
        -   Sector or industry
        -   :file:`info/sector.svg`
        -   `industry`
    *   -   `tx-academicbase-info-time`
        -   Time, office hours
        -   :file:`info/time.svg`
        -   `clock`

`expand` and `add` are the same drawing in two files, so a site package can
replace one without the other.

Five of the nineteen information identifiers are rendered by a template of
the academic extensions: `-info-email`, `-info-phone`, `-info-location`,
`-info-room` and `-info-time`. The other fourteen stay registered on purpose,
so a site package can render and replace them like the others. The job views
render identifiers of :guilabel:`academic_jobs` that draw the same files, and
several files are also the drawings of record and category type icons, which
other academic extensions register under identifiers of their own. A
replacement of one of the fourteen therefore changes neither the job views nor
those record and category type icons.

..  _icons-usage:

Rendering an icon
=================

In a Fluid template of the frontend, with the view helper of this extension:

..  code-block:: html
    :caption: EXT:my_sitepackage/Resources/Private/Partials/Contact.html

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:icon identifier="tx-academicbase-info-phone" alternativeMarkupIdentifier="inline" />

    </html>

The markup is the inlined file with or without
`alternativeMarkupIdentifier="inline"`, because the provider inlines both
markups. The argument keeps the icon inlined where a site package replaces it
with the core :php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`,
which inlines the file only when it is asked to.

The rendered icon carries the CSS class `icon-<identifier>`, for example
`icon-tx-academicbase-info-phone`, and the attribute `data-identifier`. A
stylesheet can address it through either. :html:`<core:icon>` does not render
these icons: it reads the icon registry of TYPO3 and shows its not-found icon
for them.

..  _icons-override:

Replacing an icon in a site package
===================================

Register the same identifier again in
:file:`Configuration/FrontendIcons.php` of the site package. The later
registration wins, and every template of every academic extension that renders
the identifier shows the replacement:

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/FrontendIcons.php

    return [
        'tx-academicbase-info-phone' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/phone.svg',
        ],
    ];

*   The site package has to load after this extension: it requires
    :composer:`fgtclb/academic-base`, or an academic extension, which requires
    it, in its :file:`composer.json`, and in classic mode names it under
    `depends` in its :file:`ext_emconf.php` as well. The files are read in the
    loading order of the packages, and without the dependency the site package
    may be read first and lose.
*   The entry replaces the whole registration, provider and source alike.
*   Keep the replacement drawable for inlining if it stays with
    :php:`CurrentColorSvgIconProvider`: a `viewBox`, `currentColor`, no `id`
    and no `<style>`, see
    :ref:`What the SVG file must look like <configuration-icon-provider-svg>`.
*   Flush the system caches afterwards.

An entry in :file:`Configuration/Icons.php` changes nothing in the frontend,
the frontend registry does not read that file. The record, content element and
page type icons of the academic extensions are the other way round: they are
backend icons, and a site package replaces them in its
:file:`Configuration/Icons.php`.

..  _icons-frontend-javascript:

Icons in frontend JavaScript
============================

The backend JavaScript icon API of TYPO3 cannot be used on a frontend page: it
asks a backend route that answers a logged-in backend user only, and it serves
the icon registry of TYPO3. Frontend JavaScript therefore takes the markup of
an icon of the frontend icon registry from the server.

..  note::

    The contracts this section documents are public API of this extension,
    as the :ref:`extension points page <developers-extension-points-api>`
    lists them. They change only in a major release, with a Breaking
    changelog entry. The PHP classes behind them are not public API.

Where the JavaScript stamps out a piece of markup anyway, a list item or a row
with its buttons, render the icon with ``ab:icon`` into a :html:`<template>`
element and clone it. Where it picks an icon by its identifier at runtime,
hand it the icons it may need as a JSON map:

..  code-block:: html

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add', 1: 'tx-academicbase-action-delete'}" />

This renders a JSON data block that the browser neither executes nor checks
against the Content Security Policy:

..  code-block:: html
    :caption: Shortened

    <script type="application/json" data-academic-icons data-academic-icons-size="small">{"tx-academicbase-action-add":"\u003Cspan class=\u0022t3js-icon icon icon-size-small ...\u0022 ...\u003E...\u003C/span\u003E","tx-academicbase-action-delete":"..."}</script>

Each value is the markup `<ab:icon identifier="..."
alternativeMarkupIdentifier="inline" />` renders, so the replacement of an icon
in a site package arrives in the JavaScript as it does in a template. The JSON
escapes `<`, `>`, `&` and both quotes as unicode escapes, so no markup can end
the element, and :js:`JSON.parse()` returns the plain markup.

Arguments:

`identifiers` (array, required)
    The identifiers to render. One that is not served is left out of the map,
    see below.

`size` (string, default `small`)
    `default`, `small`, `medium`, `large` or `mega`. It sets the `icon-size-*`
    class of the markup. The icons of the academic extensions size themselves
    by the font size.

..  _icons-frontend-served:

Which icons are served
----------------------

The icons of the frontend icon registry, and no other: what the
:file:`Configuration/FrontendIcons.php` of an extension or a site package
registers, what a listener of
:php:`\FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent` contributes, and
the icons of category types and category groups. The icon registry of TYPO3 is
never read, so a core icon or the backend icon of a record type is not served,
even when a template asks for its identifier. A site package hands an icon of
its own to its JavaScript by registering it in its
:file:`Configuration/FrontendIcons.php`, see
:ref:`Frontend icons <configuration-frontend-icons>`.

Of the frontend icons, three kinds are left out of the answer rather than
answered with the placeholder of an unknown icon:

*   the placeholder `default-not-found` itself, and an identifier the frontend
    registry does not know,
*   an icon whose provider does not inline an SVG file: a bitmap icon, a
    sprite icon or a font icon. Served are the icons of
    :php:`CurrentColorSvgIconProvider` and of the core
    :php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`,
*   an identifier that is longer than 100 characters, holds anything but
    lowercase letters, digits, `_`, `.` and `-`, or starts with `.` or `-`.

The markup is what the provider of the icon renders, sanitised as far as that
provider sanitises. :php:`CurrentColorSvgIconProvider` sanitises on TYPO3 v13
and v14, the inline markup of the core :php:`SvgIconProvider` is sanitised on
TYPO3 v14 only. That is the same markup, with the same exposure, as the icon
rendered inline by a template.

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
