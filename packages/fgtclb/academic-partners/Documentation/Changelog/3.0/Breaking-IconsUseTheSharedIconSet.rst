..  _breaking-partners-icons-use-the-shared-icon-set:

=======================================
Breaking: Icons use the shared icon set
=======================================

Description
===========

Up to now one drawing - a copy of the core icon `actions-user-emulate`, two
people with arrows - stood for six different things: the academic partner page
type, the four content elements, the partnership record, the role record and
the collaboration type category. The region, partner type and SDG category
types were copies of further core drawings, the SDG one a folder.

Every icon of this extension is now a Font Awesome Free solid icon, drawn in
`currentColor` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
the provider that inlines the file in both markups (ACE-590). The partnership
and role records, and the collaboration type, use the shared glyphs of
:php:`EXT:academic_base`. The identifiers follow the scheme
`tx-<extension key without underscores>-<group>-<name>` of the academic
extensions, and the page type and the content elements get an identifier each,
although they share one drawing.

The old identifiers are removed. There are no deprecated aliases.

..  list-table:: Renamed icon identifiers
    :header-rows: 1

    *   - Old identifier
        - New identifier
        - Used for
    *   - `academic-partners`
        - `tx-academicpartners-doktype-partner`
        - Page type 40 (page tree, doktype select, page module)
    *   - `academic-partners`
        - `tx-academicpartners-plugin-partners`
        - Content elements `academicpartners_list`, `academicpartners_map`,
          `academicpartners_partnershipslist` and
          `academicpartners_partnershipsteaser`, and their new content element
          wizard entries
    *   - `tx_academicpartners_domain_model_partnership`
        - `tx-academicpartners-record-partnership`
        - Partnership records
    *   - `tx_academicpartners_domain_model_role`
        - `tx-academicpartners-record-role`
        - Role records

The items of the partner select of a partnership record list partner pages and
now show the page type icon `tx-academicpartners-doktype-partner` instead of
the partnership record icon.

The identifiers of the category type icons, which :php:`EXT:category_types`
derives from :file:`Configuration/CategoryTypes.yaml`, are unchanged. Their
files are replaced:

..  list-table:: Category type icons
    :header-rows: 1

    *   - Identifier
        - Old file
        - New file
    *   - `category_types.partners.region`
        - :file:`Icons/CategoryTypes/Region.svg`
        - :file:`Icons/category-type/region.svg` (a map)
    *   - `category_types.partners.partner_type`
        - :file:`Icons/CategoryTypes/PartnerType.svg`
        - :file:`Icons/category-type/partner-type.svg` (a group of people)
    *   - `category_types.partners.collaboration_type`
        - :file:`Icons/CategoryTypes/CollaborationType.svg`
        - :file:`EXT:academic_base/Resources/Public/Icons/info/partnership.svg`
          (a handshake)
    *   - `category_types.partners.sdg`
        - :file:`Icons/CategoryTypes/Sdg.svg`
        - :file:`Icons/category-type/sdg.svg` (a seedling)

The files :file:`Resources/Public/Icons/Partnership.svg`,
:file:`Resources/Public/Icons/Role.svg` and the four files below
:file:`Resources/Public/Icons/CategoryTypes/` are removed.
:file:`Resources/Public/Icons/Extension.svg` stays the extension icon, but no
icon identifier points at it any more. The new files and their licence - Font
Awesome Free, CC BY 4.0 - are listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.

Impact
======

An old identifier is no longer registered. Wherever it is still named - a
:html:`<core:icon>` in a template override, a TCA override, the
`iconIdentifier` of a new content element wizard entry in a project's page
TSconfig - the backend and the frontend render the `default-not-found`
placeholder instead.

A project that replaced one of the icons by registering an old identifier in
its own :file:`Configuration/Icons.php` loses that override silently: the
identifier is registered again, but nothing asks for it.

The core renders the identifier into the class of the icon element, so a
stylesheet selecting on :css:`.icon-academic-partners`,
:css:`.icon-tx_academicpartners_domain_model_partnership` or
:css:`.icon-tx_academicpartners_domain_model_role` matches nothing any more.

A reference to one of the removed files answers with 404.

The four category type icons keep their identifiers, so the templates of this
extension and any override rendering
:html:`<core:icon identifier="category_types.partners.{type}" />` keep working,
but they show a different drawing.

Affected Installations
======================

Every installation of this extension sees the new drawings in the backend,
and the category type icons of the partner and partnership plugins in the
frontend. Installations are affected beyond that only if they name one of the
old identifiers or files in their own code or configuration.

Migration
=========

Replace every old identifier by its new one from the table above, in
templates, TCA overrides, page TSconfig and stylesheets. The page type and the
content elements are two identifiers now, so an override that was meant for
both has to be registered for both:

..  code-block:: php

    // EXT:my_sitepackage/Configuration/Icons.php
    use FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider;

    return [
        'tx-academicpartners-doktype-partner' => [
            'provider' => CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/partner.svg',
        ],
        'tx-academicpartners-plugin-partners' => [
            'provider' => CurrentColorSvgIconProvider::class,
            'source' => 'EXT:my_sitepackage/Resources/Public/Icons/partner.svg',
        ],
    ];

Copy a removed file into the site package and reference it from there if a
project needs the old drawing.

.. index:: Backend, Frontend, TCA, TSConfig, ext:academic_partners
