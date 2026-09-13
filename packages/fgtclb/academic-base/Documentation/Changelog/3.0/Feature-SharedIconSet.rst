..  _feature-shared-icon-set:

==========================================
Feature: Icon set shared by the extensions
==========================================

Description
===========

This extension now ships the icons the academic extensions have in common: 17
actions (add, back, clear, close, collapse, delete, drag, edit, expand, help,
move up, move down, save, undo, upload an image, view, close the view), two
states (visible, hidden) and 17 information glyphs (calendar, company,
contract, degree, department, e-mail, employment, information, international,
link, location, partnership, phone, recommendation, role, room, sector)
(ACE-584).

Before, every extension brought its own drawing of the same thing - from
Bootstrap Icons, Material Symbols, Font Awesome and hand-made files, some drawn
in fixed colours that stayed dark on a dark backend - and a project that wanted
another icon for the same thing had to replace it in every extension that drew
its own. Now an icon that means the same exists once, and replacing it
replaces it everywhere.

All of them are Font Awesome Free icons of the solid style, drawn in
`currentColor`, sized `1em` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
so they follow the text colour and the font size in the frontend and in both
backend colour schemes. The identifiers follow the scheme
`tx-<extension key without underscores>-<group>-<name>`, for example
`tx-academicbase-info-phone`, with the file
:file:`Resources/Public/Icons/info/phone.svg`.

The notice :file:`Resources/Public/Icons/LICENSE-font-awesome.txt` gives the
attribution the Creative Commons Attribution 4.0 license of Font Awesome Free
requires, and lists every file.

Impact
======

The icons are available to every extension and site package that depends on
this extension:

..  code-block:: html

    <core:icon identifier="tx-academicbase-info-phone" alternativeMarkupIdentifier="inline" />

A project replaces one of them by registering the same identifier in its own
:file:`Configuration/Icons.php`. The academic extensions use the shared set
for their own templates; what changes for each of them is described in its own
changelog. See :ref:`Icons <icons>` for the complete list and for replacing an
icon.

.. index:: Backend, Frontend, NotScanned
