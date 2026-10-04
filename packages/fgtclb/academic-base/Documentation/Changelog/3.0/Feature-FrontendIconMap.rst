..  _feature-frontend-icon-map:

====================================================
Feature: Icons for frontend JavaScript as a JSON map
====================================================

Description
===========

Frontend JavaScript that picks an icon by its identifier at runtime had no way
to get the markup of that icon (ACE-595). The backend JavaScript icon API of
TYPO3 asks a backend route that answers a logged-in backend user only, and it
serves the icon registry of TYPO3, so it fails on every frontend page. The only
way left was a :html:`<template>` per icon that the JavaScript clones.

The new view helper ``frontendIconMap``, in the namespace
``http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers``, renders the markup of
the icons a template names into a JSON data block the JavaScript reads:

..  code-block:: html
    :caption: The template

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:frontendIconMap identifiers="{0: 'tx-academicbase-action-add', 1: 'tx-academicbase-action-delete'}" />

    </html>

..  code-block:: html
    :caption: What it renders, shortened

    <script type="application/json" data-academic-icons data-academic-icons-size="small">{"tx-academicbase-action-add":"\u003Cspan class=\u0022t3js-icon icon icon-size-small ...\u0022 ...\u003E...\u003C/span\u003E","tx-academicbase-action-delete":"..."}</script>

..  code-block:: javascript
    :caption: The script

    const element = document.querySelector('script[data-academic-icons]');
    const icons = JSON.parse(element.textContent);
    button.insertAdjacentHTML('afterbegin', icons['tx-academicbase-action-add']);

`identifiers` names the icons, `size` sets the `icon-size-*` class of the
markup: `default`, `small` (the default), `medium`, `large` or `mega`. Each
value is the markup `<ab:icon ... alternativeMarkupIdentifier="inline" />`
renders for the identifier, so the replacement of an icon in a site package
reaches the JavaScript as it reaches a template, sanitised as far as the
provider of the icon sanitises. A JSON data block is not executed and not
subject to the Content Security Policy, and the JSON escapes `<`, `>`, `&` and
both quotes as unicode escapes, so no markup can end the element.
:js:`JSON.parse()` returns the plain markup.

Only icons of the frontend icon registry of this extension are served: what a
:file:`Configuration/FrontendIcons.php` or a listener of
:php:`\FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent` registers, the
icons of category types and category groups among them. The icon registry of
TYPO3 is never read, so no core icon and no backend icon of an extension leaves
the server this way. Of the frontend icons, the placeholder
`default-not-found` and every icon whose provider does not inline an SVG file,
a bitmap or a sprite icon, are left out of the map, and so is an identifier the
frontend registry does not know or that is not an icon identifier at all.

The view helper by its tag name and arguments, and the JSON it renders, are
public API of this extension, see :ref:`developers-extension-points-api`.

Impact
======

Nothing changes for existing templates. Frontend JavaScript of the academic
extensions can render the shared icons, and the replacement of one in a site
package, without a :html:`<template>` per icon. An icon a site package wants to
hand to its own JavaScript is registered in its
:file:`Configuration/FrontendIcons.php`, as for a template. See
:ref:`Icons in frontend JavaScript <icons-frontend-javascript>`.

..  index:: Frontend, Fluid, JavaScript, ext:academic_base
