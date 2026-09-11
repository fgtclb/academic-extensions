..  _breaking-persons-icons-use-the-shared-icon-set:

=======================================
Breaking: Icons use the shared icon set
=======================================

..  seealso::
    :ref:`upgrade` is the order in which the 3.0 changes have to be applied.

Description
===========

Every icon of this extension is replaced by a Font Awesome Free solid icon,
drawn in ``currentColor`` and registered with
:php:`\FGTCLB\AcademicBase\Imaging\IconProvider\CurrentColorSvgIconProvider`,
which inlines the file in both markups (ACE-585). The icons take the text
colour of the backend in both colour schemes, and the text colour of the page
in the frontend.

The identifiers are renamed to the scheme the academic extensions share,
``tx-academicpersons-<group>-<name>``. The old identifiers are removed, there
are no aliases:

*   The nine **record icons** become ``tx-academicpersons-record-*``. Five of
    them draw a shared file of `EXT:academic_base`; the identifier stays
    this extension's own.
*   The **content element icons** become ``tx-academicpersons-plugin-*``, in
    the TCA and in the new content element wizard alike. The plugin icon
    ``persons_icon`` was a fixed-colour brand mark; its replacement follows the
    text colour like every other icon. The profile card, the selected profiles
    and the selected contracts elements had no icon of their own before: the
    page module showed the ``tt_content`` default and the wizard the core icon
    ``actions-user``. Each now has its own identifier, and both places show it.
*   The six **frontend glyphs** of the public profile are no longer registered
    by this extension. The partials below
    :file:`Resources/Private/Partials/Profile/PublicProfile/` render the shared
    ``tx-academicbase-*`` identifiers of `EXT:academic_base` instead.

..  list-table:: Old and new identifiers
    :header-rows: 1

    *   -   Old identifier
        -   New identifier
        -   Used for
    *   -   ``tx_academicpersons_domain_model_address``
        -   ``tx-academicpersons-record-address``
        -   Record :sql:`tx_academicpersons_domain_model_address`
    *   -   ``tx_academicpersons_domain_model_contract``
        -   ``tx-academicpersons-record-contract``
        -   Record :sql:`tx_academicpersons_domain_model_contract`
    *   -   ``tx_academicpersons_domain_model_email``
        -   ``tx-academicpersons-record-email``
        -   Record :sql:`tx_academicpersons_domain_model_email`
    *   -   ``tx_academicpersons_domain_model_function_type``
        -   ``tx-academicpersons-record-function-type``
        -   Record :sql:`tx_academicpersons_domain_model_function_type`
    *   -   ``tx_academicpersons_domain_model_location``
        -   ``tx-academicpersons-record-location``
        -   Record :sql:`tx_academicpersons_domain_model_location`
    *   -   ``tx_academicpersons_domain_model_organisational_unit``
        -   ``tx-academicpersons-record-organisational-unit``
        -   Record :sql:`tx_academicpersons_domain_model_organisational_unit`
    *   -   ``tx_academicpersons_domain_model_phone_number``
        -   ``tx-academicpersons-record-phone-number``
        -   Record :sql:`tx_academicpersons_domain_model_phone_number`
    *   -   ``tx_academicpersons_domain_model_profile``
        -   ``tx-academicpersons-record-profile``
        -   Record :sql:`tx_academicpersons_domain_model_profile`
    *   -   ``tx_academicpersons_domain_model_profile_information``
        -   ``tx-academicpersons-record-profile-information``
        -   Record :sql:`tx_academicpersons_domain_model_profile_information`
    *   -   ``persons_icon``
        -   ``tx-academicpersons-plugin-persons``
        -   Content elements ``academicpersons_list``,
            ``academicpersons_listanddetail`` and ``academicpersons_detail``
    *   -   ``actions-user`` (wizard), none (TCA)
        -   ``tx-academicpersons-plugin-card``
        -   Content element ``academicpersons_card``
    *   -   ``actions-user`` (wizard), none (TCA)
        -   ``tx-academicpersons-plugin-selected-profiles``
        -   Content element ``academicpersons_selectedprofiles``
    *   -   ``actions-user`` (wizard), none (TCA)
        -   ``tx-academicpersons-plugin-selected-contracts``
        -   Content element ``academicpersons_selectedcontracts``
    *   -   ``academic-persons-envelope``
        -   ``tx-academicbase-info-email``
        -   Public profile, email addresses (3.0 development only)
    *   -   ``academic-persons-phone``
        -   ``tx-academicbase-info-phone``
        -   Public profile, phone numbers (3.0 development only)
    *   -   ``academic-persons-address``
        -   ``tx-academicbase-info-location``
        -   Public profile, postal addresses (3.0 development only)
    *   -   ``academic-persons-room``
        -   ``tx-academicbase-info-room``
        -   Public profile, location and room (3.0 development only)
    *   -   ``academic-persons-detail-plus``
        -   ``tx-academicbase-action-expand``
        -   Public profile, closed fold-out entry (3.0 development only)
    *   -   ``academic-persons-detail-minus``
        -   ``tx-academicbase-action-collapse``
        -   Public profile, open fold-out entry (3.0 development only)

The icon files below :file:`Resources/Public/Icons/` are replaced as well. The
nine :file:`tx_academicpersons_domain_model_*.svg`, :file:`persons_icon.svg`,
the six Bootstrap Icons files :file:`address.svg`, :file:`detail-minus.svg`,
:file:`detail-plus.svg`, :file:`envelope.svg`, :file:`phone.svg`,
:file:`room.svg` and :file:`LICENSE-bootstrap-icons.txt` are deleted. The six
frontend glyph identifiers, the six Bootstrap Icons files and their licence
file only existed during the 3.0 development and were never part of a 2.x
release. The files
this extension ships now live in :file:`record/` and :file:`plugin/`, and their
origin and licence (CC BY 4.0) are listed in
:file:`Resources/Public/Icons/LICENSE-font-awesome.txt`.
:file:`Extension.svg` is unchanged.

Impact
======

An old identifier is not an error anywhere: :php:`IconFactory` answers it with
the ``default-not-found`` placeholder, so the page renders and the icon is
wrong.

*   A template, TSconfig entry, TCA override or PHP call that names an old
    identifier renders the placeholder.
*   A :file:`Configuration/Icons.php` of a site package that re-registers an old
    identifier to replace an icon of this extension no longer has any effect.
*   CSS that selects on the class the core renders for an icon, such as
    :css:`.icon-academic-persons-envelope` or
    :css:`.icon-tx_academicpersons_domain_model_profile`, matches nothing.
*   A reference to one of the deleted files, such as
    :file:`EXT:academic_persons/Resources/Public/Icons/persons_icon.svg`, points
    at nothing.
*   CSS that sizes :css:`.academic-persons-detail__contact-icon` for the old
    glyphs draws the new ones a fifth smaller.

Records are not affected: an icon identifier is not stored in the database.

Affected Installations
======================

Installations that name one of the old identifiers or files in their own
templates, TSconfig, TCA, :file:`Configuration/Icons.php` or CSS. The shipped
templates, TCA and TSconfig are migrated.

Migration
=========

#.  Search the site package for ``persons_icon``, ``academic-persons-`` in
    icon identifiers and CSS classes, the table names above where they are used
    as an icon identifier (``iconIdentifier``, ``typeicon_classes``,
    ``identifier=``, ``.icon-``) and the deleted file names, and replace each
    with its new identifier from the table.
#.  Move an override in :file:`Configuration/Icons.php` to the new identifier.
    Re-registering one of the shared ``tx-academicbase-*`` identifiers replaces
    the glyph in every academic extension; to replace it on the public profile
    only, override the partial and render an identifier of your own.
#.  Adapt CSS that selects on :css:`.icon-<old identifier>` to
    :css:`.icon-<new identifier>`, and CSS that addressed an :html:`<img>` of an
    icon to the inlined :html:`<svg>`.
#.  Flush the TYPO3 caches.

..  index:: Backend, Frontend, TCA, TSConfig, ext:academic_persons
