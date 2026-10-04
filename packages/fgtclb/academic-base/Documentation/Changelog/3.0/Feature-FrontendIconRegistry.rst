..  _feature-1791061865:

=========================================================
Feature: A frontend icon registry and an icon view helper
=========================================================

Description
===========

`EXT:academic_base` now keeps a registry of its own for the icons a visitor
sees, separate from the icon registry of TYPO3. That registry is built for the
backend: it loads every core icon, the record icons and the flags with it, and
a site could only replace a frontend icon by registering it for the backend as
well.

*   Every active extension and site package can ship
    :file:`Configuration/FrontendIcons.php`, in the format of
    :file:`Configuration/Icons.php`. The files are read in the loading order of
    the packages, and a package loaded later replaces an identifier.
*   An extension contributes icons from code through the new event
    :php:`\FGTCLB\AcademicBase\Event\CollectFrontendIconsEvent`. An entry of a
    :file:`Configuration/FrontendIcons.php` with the same identifier wins over
    a contributed icon.
*   The new view helper ``icon``, in the namespace
    ``http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers``, renders an icon of
    this registry with the arguments of :html:`<core:icon>` and the same markup.
    It does not read the icon registry of TYPO3.
*   An identifier the frontend registry does not know renders the
    `default-not-found` drawing of TYPO3, which `EXT:academic_base` registers
    in its own :file:`Configuration/FrontendIcons.php`, so a site package can
    replace it.
*   The registry is kept with the system caches, per set of active packages,
    and :bash:`typo3 cache:warmup` builds it. A change to a file takes effect
    after the system caches are flushed.

..  code-block:: php
    :caption: EXT:my_sitepackage/Configuration/FrontendIcons.php

    return [
        'my-sitepackage-download' => [
            'provider' => \FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/download.svg',
        ],
    ];

..  code-block:: html
    :caption: A Fluid template of the site package

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:icon identifier="my-sitepackage-download" alternativeMarkupIdentifier="inline" />

    </html>

Impact
======

Nothing changes for an existing site through this extension alone.
`EXT:academic_persons` and `EXT:academic_persons_edit` register their frontend
icons in the new registry and render them with the new view helper, each with a
Breaking entry in its own changelog. The other academic extensions keep their
:file:`Configuration/Icons.php` and :html:`<core:icon>` for now. Every
extension that moves its frontend icons to the new registry says so in its own
changelog, with what a site package has to move.

A site package may ship :file:`Configuration/FrontendIcons.php` already. It
takes effect for every template that renders an icon with the new view helper.
See :ref:`Frontend icons <configuration-frontend-icons>`.

..  index:: Frontend, Fluid, PHP-API, ext:academic_base
