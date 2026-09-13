..  _breaking-contacts4pages-icons-replaced-by-font-awesome:

======================================================
Breaking: Icons replaced and their identifiers renamed
======================================================

Description
===========

The academic extensions now draw every icon from one set, Font Awesome Free
(solid), registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`
and drawn in `currentColor`, so each icon takes the colour of the surrounding
text in both backend colour schemes (ACE-589).

In this extension that fixes two inconsistencies. The content element icon
:php:`academic_contacts4pages` was registered with the core provider, which
renders an :html:`<img>`, so its `currentColor` drawing came out black on the
dark backend. And the new content element wizard did not show that icon at all
but the core icon :php:`actions-user`, so the same content element looked
different in the wizard and in the page module. Both now use one identifier.

The identifiers follow the scheme
`tx-<extension key without underscores>-<group>-<name>` of all academic
extensions. The previous identifiers are removed without an alias:

..  list-table::
    :header-rows: 1

    *   -   Previous identifier
        -   New identifier
        -   Used for
    *   -   :php:`academic_contacts4pages`
        -   :php:`tx-academiccontacts4pages-plugin-contacts`
        -   Content element "Contact list" (page module)
    *   -   :php:`actions-user` (core icon, wizard only)
        -   :php:`tx-academiccontacts4pages-plugin-contacts`
        -   Content element "Contact list" (new content element wizard)
    *   -   :php:`tx_academiccontacts4pages_domain_model_contact`
        -   :php:`tx-academiccontacts4pages-record-contact`
        -   Record type :sql:`tx_academiccontacts4pages_domain_model_contact`
    *   -   :php:`tx_academiccontacts4pages_domain_model_role`
        -   :php:`tx-academiccontacts4pages-record-role`
        -   Record type :sql:`tx_academiccontacts4pages_domain_model_role`
    *   -   :php:`tx_academiccontacts4pages_domain_model_contract`
        -   *removed*
        -   Nothing - the table it was named after does not exist

The files :file:`Resources/Public/Icons/Contract.svg` and
:file:`Resources/Public/Icons/Role.svg` are removed. The contact list and the
contact record share :file:`Resources/Public/Icons/plugin/contacts.svg`; the
role record uses the shared role icon of `EXT:academic_base`.
:file:`Resources/Public/Icons/Extension.svg` stays, as the extension icon
only. The origin and licence of the Font Awesome file (CC BY 4.0) are listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

Impact
======

An icon requested with one of the previous identifiers - in a template, in
page TSconfig, in TCA or through :php:`IconFactory::getIcon()` - renders the
core placeholder `default-not-found`. A :file:`Configuration/Icons.php` of a
project that overrides one of the previous identifiers no longer has any
effect. CSS addressing the generated class `.icon-academic_contacts4pages`
or `.icon-tx_academiccontacts4pages_domain_model_*` no longer matches.

The content element and record icons are inlined :html:`<svg>` markup now,
never an :html:`<img>`.

Affected Installations
======================

Installations that reference one of the previous identifiers in their own
code or configuration, override one of them, or style the generated
`.icon-*` classes. Installations that only use the extension as shipped
need no change.

Migration
=========

Replace the previous identifiers with the new ones from the table above, in
templates, page TSconfig, TCA overrides and :file:`Configuration/Icons.php`.
To show a different drawing, register the new identifier again in the
:file:`Configuration/Icons.php` of the project. Replace
`.icon-<previous identifier>` selectors with
`.icon-<new identifier>`.

..  index:: Backend, TCA, TSConfig, ext:academic_contacts4pages
