..  _important-frontend-icons:

========================================================
Important: Experimental icon API for frontend JavaScript
========================================================

Description
===========

Frontend JavaScript that picks an icon by its identifier at runtime had no way
to get the markup of that icon (ACE-595). The backend JavaScript icon API of
TYPO3 asks a backend route that answers a logged-in backend user only, so it
fails on every frontend page, and the only way left was a :html:`<template>`
per icon that the JavaScript clones.

This extension now ships a first, **experimental** answer to that. Everything
listed below is marked internal: it may change, or go away, without a breaking
change entry until a module outside the academic extensions uses it. Use it in
a project at your own risk, and expect to adapt.

The ViewHelper `frontendIconMap` renders the markup of the icons a template
names into a JSON data block the JavaScript reads:

..  code-block:: html

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add', 1: 'tx-academicbase-action-delete'}" />

..  code-block:: javascript

    const element = document.querySelector('script[data-academic-icons]');
    const icons = JSON.parse(element.textContent);
    button.insertAdjacentHTML('afterbegin', icons['tx-academicbase-action-add']);

Each value is the markup `<core:icon ... alternativeMarkupIdentifier="inline" />`
renders for the identifier, so a project's replacement of an icon reaches the
JavaScript as it reaches a template, sanitised as far as the provider of the
icon sanitises. A JSON data block is not executed and not subject to the
Content Security Policy.

Only icons of the academic extensions are served - identifiers starting with
`tx-academic` or `category_types.` - and of those only the ones that are
registered, not deprecated and inlined from an SVG file. Anything else, an icon
of the core icon set among them, is left out. A project serves the icons of
its own extensions by adding their prefix with a listener to the PSR-14 event
:php:`\FGTCLB\AcademicBase\Event\ModifyFrontendIconAllowListEvent`; a prefix
opens every identifier it matches, including icons some system extensions
register with the core :php:`SvgIconProvider`.

Where the identifier is known only after the page was rendered - from data the
JavaScript loads, or from a choice of the visitor - it asks the new icon
endpoint below the base of the site or site language:

..  code-block:: text

    GET https://example.com/_academic/icons.json?i=tx-academicbase-action-add,tx-academicbase-info-phone&s=small&v=<token>

It answers the same JSON object for up to 32 identifiers, serves the same icons
as the ViewHelper, and is a middleware of this extension that answers before
the frontend user authentication: no session, no cookie, publicly cacheable -
for a year when `v` is the current version token of the icon set, which the
ViewHelper hands out with `endpoint="1"`, and for five minutes otherwise, with
an `ETag` for revalidation. The token changes with the icon registrations, the
modification time of their files and every change of :file:`composer.lock`. A
web server or CDN rule that serves `*.json` as static files has to pass this
path on to TYPO3, see :ref:`icons-frontend-endpoint`.

The decision and the rendering are
:php:`\FGTCLB\AcademicBase\Imaging\FrontendIconRenderer`.

Impact
======

Nothing changes for existing templates. Frontend JavaScript of the academic
extensions can render the shared icons, and a project's replacement of them,
without a :html:`<template>` per icon. The path `_academic/icons.json` below
the base of every site is answered by this extension; a page with that slug can
no longer be reached there. See
:ref:`Icons in frontend JavaScript <icons-frontend-javascript>`.

.. index:: Frontend, Fluid, PHP-API, NotScanned
