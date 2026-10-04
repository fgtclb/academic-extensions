..  _feature-shared-icon-set:

==========================================
Feature: Icon set shared by the extensions
==========================================

Description
===========

This extension now ships the icons the academic extensions have in common, as
frontend icons in its :file:`Configuration/FrontendIcons.php` (ACE-584):

*   17 actions: add, back, clear, close, collapse, delete, drag, edit, expand,
    help, move down, move up, save, undo, upload an image, view, close the view.
*   2 states: hidden, visible.
*   19 information glyphs: calendar, company, contract, degree, department,
    e-mail, employment, information, international, link, location,
    partnership, person, phone, recommendation, role, room, sector, time.

Before, every extension brought its own drawing of the same thing, from
Bootstrap Icons, Material Symbols, Font Awesome and hand-made files, some drawn
in fixed colours, and a site package that wanted another icon for the same
thing had to replace it in every extension that drew its own. Now an icon that
means the same exists once, and replacing it replaces it everywhere.

All of them are Font Awesome Free icons of the solid style, drawn in
`currentColor`, sized `1em` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
so they follow the text colour and the font size of the page. The identifiers
follow the scheme `tx-<extension key without underscores>-<group>-<name>`, for
example `tx-academicbase-info-phone` with the file
:file:`Resources/Public/Icons/info/phone.svg`. The group decides the registry:
`action`, `state` and `info` icons are frontend icons, registered in
:file:`Configuration/FrontendIcons.php`, while the `record`, `plugin` and
`doktype` icons of the other academic extensions are backend icons, registered
in :file:`Configuration/Icons.php`.

The notice :file:`Resources/Public/Icons/LICENSE-font-awesome.txt` gives the
attribution the Creative Commons Attribution 4.0 license of Font Awesome Free
requires, and lists every file with its Font Awesome name.

Impact
======

The icons are available to every extension and site package that depends on
this extension, in a frontend template:

..  code-block:: html

    <html xmlns:ab="http://typo3.org/ns/FGTCLB/AcademicBase/ViewHelpers"
          data-namespace-typo3-fluid="true">

    <ab:icon identifier="tx-academicbase-info-phone" alternativeMarkupIdentifier="inline" />

    </html>

A site package replaces one of them by registering the same identifier in its
own :file:`Configuration/FrontendIcons.php`. The academic extensions render the
shared set in their own templates, and what changes for each of them is
described in its own changelog. See :ref:`Icons <icons>` for the complete list
and for replacing an icon.

..  index:: Frontend, Fluid, NotScanned, ext:academic_base
