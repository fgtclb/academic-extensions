..  _feature-profile-editing-icon-set:

==========================================
Feature: Icons of the profile editing view
==========================================

Description
===========

The profile editing frontend addresses its action and state icons through
identifiers, rendered with ``core:icon`` and inlined into the markup. They are
the shared icons of `EXT:academic_base`, which every academic extension uses
for the same action:

..  list-table::
    :header-rows: 1

    *   -   Identifier
        -   Action
    *   -   ``tx-academicbase-action-add``
        -   Add an entry to a list
    *   -   ``tx-academicbase-action-back``
        -   Leave the editor
    *   -   ``tx-academicbase-action-clear``
        -   Clear the value of a field
    *   -   ``tx-academicbase-action-delete``
        -   Delete an entry
    *   -   ``tx-academicbase-action-drag``
        -   Drag handle of a sortable entry
    *   -   ``tx-academicbase-action-edit``
        -   Start editing a field or an entry
    *   -   ``tx-academicbase-action-help``
        -   Show the help text of a field
    *   -   ``tx-academicbase-action-move-down``
        -   Move an entry down
    *   -   ``tx-academicbase-action-move-up``
        -   Move an entry up
    *   -   ``tx-academicbase-action-save``
        -   Apply an edit
    *   -   ``tx-academicbase-action-undo``
        -   Undo an edit
    *   -   ``tx-academicbase-action-upload-image``
        -   Upload or replace the profile image
    *   -   ``tx-academicbase-action-view``
        -   Open the public view of a record
    *   -   ``tx-academicbase-action-view-close``
        -   Close the read view a row action opened
    *   -   ``tx-academicbase-state-visible``
        -   The visibility toggle of an entry that is shown in the frontend
    *   -   ``tx-academicbase-state-hidden``
        -   The visibility toggle of an entry that is hidden in the frontend

Identifier and file name are the action, never the drawing: a later icon set
changes the glyph, not the API the templates address.

The files are Font Awesome Free solid icons (CC BY 4.0, see
:file:`EXT:academic_base/Resources/Public/Icons/LICENSE-font-awesome.txt`).
They are drawn in ``currentColor`` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file instead of rendering an :html:`<img>` - so a glyph takes
the colour of the button it sits in, in the frontend as much as in a dark
backend colour scheme.

Impact
======

The identifiers are public API. A site package that wants different artwork
re-registers one of them in its own :file:`Configuration/Icons.php` with its
own file and needs no template override; a file registered that way is drawn
in ``currentColor`` as well, or it will not follow the surrounding text.
Because the identifiers are shared, such a registration changes the icon in
every academic extension that renders it.

The identifiers of the icons 2.x shipped are gone - see
:ref:`breaking-profile-editing-uses-the-shared-icon-set`.

Affected Installations
======================

All installations using the `EXT:academic_persons_edit` extension starting
with version 3.0.

Migration
=========

No migration is required for the feature itself. The renamed identifiers are
migrated as described in
:ref:`breaking-profile-editing-uses-the-shared-icon-set`.

..  index:: Frontend, Fluid, Template
