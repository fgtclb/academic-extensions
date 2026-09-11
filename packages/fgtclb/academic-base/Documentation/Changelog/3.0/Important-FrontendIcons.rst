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

The decision and the rendering are
:php:`\FGTCLB\AcademicBase\Imaging\FrontendIconRenderer`.

Impact
======

Nothing changes for existing templates. Frontend JavaScript of the academic
extensions can render the shared icons, and a project's replacement of them,
without a :html:`<template>` per icon. See
:ref:`Icons in frontend JavaScript <icons-frontend-javascript>`.

.. index:: Frontend, Fluid, PHP-API, NotScanned
