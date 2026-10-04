..  _breaking-contacts4pages-icon-identifiers-renamed:

======================================================
Breaking: Icons replaced and their identifiers renamed
======================================================

Description
===========

The academic extensions draw their icons from one set, Font Awesome Free
(solid), drawn in `currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
so every icon takes the colour of the surrounding text in both backend colour
schemes (ACE-589).

In this extension that removes two inconsistencies. The content element icon
``academic_contacts4pages`` was registered with the core provider
:php:`\TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider`, which renders an
:html:`<img>`, so it kept a fixed ink on the dark backend while the record
icons next to it followed the colour scheme. And the new content element
wizard did not show that icon at all but the core icon ``actions-user``, so the
same content element looked different in the wizard and in the page module.
Both now use one identifier.

The identifiers follow the scheme
``tx-<extension key without underscores>-<group>-<name>`` of all academic
extensions. All of them are backend icons, registered in
:file:`Configuration/Icons.php`. This extension renders no icon in the
frontend and ships no :file:`Configuration/FrontendIcons.php`. The previous
identifiers are removed without an alias:

..  list-table::
    :header-rows: 1

    *   -   2.x identifier
        -   3.0 identifier
        -   Registry
        -   Used for
    *   -   ``academic_contacts4pages``
        -   ``tx-academiccontacts4pages-plugin-contacts``
        -   :file:`Icons.php`
        -   Content element "Contact list" (page module, type select)
    *   -   ``actions-user`` (core icon)
        -   ``tx-academiccontacts4pages-plugin-contacts``
        -   :file:`Icons.php`
        -   Content element "Contact list" (new content element wizard)
    *   -   ``tx_academiccontacts4pages_domain_model_contact``
        -   ``tx-academiccontacts4pages-record-contact``
        -   :file:`Icons.php`
        -   Records of :sql:`tx_academiccontacts4pages_domain_model_contact`
    *   -   ``tx_academiccontacts4pages_domain_model_role``
        -   ``tx-academiccontacts4pages-record-role``
        -   :file:`Icons.php`
        -   Records of :sql:`tx_academiccontacts4pages_domain_model_role`
    *   -   ``tx_academiccontacts4pages_domain_model_contract``
        -   *removed*
        -   none
        -   Nothing, the table it was named after does not exist

The files :file:`Resources/Public/Icons/Contract.svg` and
:file:`Resources/Public/Icons/Role.svg` are removed. The content element and
the contact record share :file:`Resources/Public/Icons/plugin/contacts.svg`,
the role record uses the role drawing of `EXT:academic_base`,
:file:`EXT:academic_base/Resources/Public/Icons/info/role.svg`.
:file:`Resources/Public/Icons/Extension.svg` stays, as the extension icon
only. Origin and licence (CC BY 4.0) of the Font Awesome file are listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

Impact
======

An icon requested with one of the previous identifiers, in a template, in page
TSconfig, in TCA or through :php:`IconFactory::getIcon()`, renders the core
placeholder ``default-not-found``. An entry for one of the previous
identifiers in the :file:`Configuration/Icons.php` of a project no longer has
any effect. CSS addressing the generated class ``.icon-academic_contacts4pages``
or ``.icon-tx_academiccontacts4pages_domain_model_*`` no longer matches.

The content element icon is an inlined :html:`<svg>` now in both markups,
never an :html:`<img>`, like the record icons already were.

Affected Installations
======================

Installations that name one of the previous identifiers in their own code or
configuration, replace one of them in their own
:file:`Configuration/Icons.php`, or style the generated ``.icon-*`` classes.
Installations that use the extension as shipped need no change.

Migration
=========

#.  Replace the previous identifiers with the 3.0 identifiers of the table
    above, in templates, page TSconfig, TCA overrides and PHP code.
#.  Move a replacement of one of these icons in the
    :file:`Configuration/Icons.php` of a site package to the 3.0 identifier.
    The site package has to depend on :guilabel:`academic_contacts4pages`, so
    its entry is read after the shipped one:

    ..  code-block:: php
        :caption: EXT:mysitepackage/Configuration/Icons.php

        <?php

        use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

        return [
            'tx-academiccontacts4pages-plugin-contacts' => [
                'provider' => CurrentColorSvgIconProvider::class,
                'source' => 'EXT:mysitepackage/Resources/Public/Icons/contacts.svg',
            ],
        ];

    These are backend icons, a :file:`Configuration/FrontendIcons.php` does
    not reach them.
#.  Replace ``.icon-<previous identifier>`` selectors with
    ``.icon-<3.0 identifier>``.
#.  Flush the TYPO3 caches, so the icon registry is rebuilt.

..  index:: Backend, TCA, TSConfig, ext:academic_contacts4pages
