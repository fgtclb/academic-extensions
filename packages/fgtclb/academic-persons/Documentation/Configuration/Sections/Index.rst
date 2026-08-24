..  index:: Configuration; Profile sections, Configuration; Special fields, Configuration; Contract contacts, Configuration; Document sections
..  _configuration-sections:

====================================
Profile and related settings sections
====================================

:file:`Configuration/AcademicPersons/Settings.yaml` contains four ordered
top-level maps:

``profile``
    Direct properties of ``Profile``, grouped into the visual inline sections.
``special``
    Inline components which are not one ordinary generated field row, such as
    the composed profile name, image and synchronization switch.
``contractContact``
    Fields of address, email and telephone records owned by a ``Contract``.
``documentSections``
    Contracts and the structured profile-information collections rendered
    below the direct profile fields.

The settings factory preserves map order and creates typed settings objects.
Validation stays attached to the field and section in which it is declared.

Direct profile fields
=====================

Every key below :yaml:`profile` is a stable field identifier:

..  code-block:: yaml

    profile:
      emailAddress:
        section: information
        fieldType: input
        renderType: email
        validators:
          - email
      publishEmailAddress:
        section: information
        fieldType: check
        renderType: checkbox
      miscellaneous:
        section: aboutme
        fieldType: textarea
        renderType: ckeditor
        validators:
          - html

The available properties are:

..  list-table::
    :header-rows: 1

    *   - Property
        - Purpose
    *   - :yaml:`section`
        - Required visual section identifier. The shipped inline template
          places :yaml:`information` and :yaml:`aboutme`.
    *   - :yaml:`fieldType`
        - Base TCA type, for example :yaml:`input`, :yaml:`select`,
          :yaml:`textarea` or :yaml:`check`.
    *   - :yaml:`renderType`
        - Inline presentation such as :yaml:`text`, :yaml:`phone`,
          :yaml:`email`, :yaml:`select`, :yaml:`checkbox`,
          :yaml:`combinedLink` or :yaml:`ckeditor`.
    *   - :yaml:`propertyName`
        - Optional DTO/domain property when it differs from the map key.
    *   - :yaml:`fieldName`
        - Optional database/TCA field when it differs from the underscored
          property name.
    *   - :yaml:`validators`
        - Flags belonging only to this field in its declared section.

The shipped ``emailAddress`` and ``phoneNumber`` entries are direct Profile
properties. Their matching ``publishEmailAddress`` and ``publishPhoneNumber``
flags are opt-in switches for the public detail profile. They are independent
of all contacts stored below an employment contract and are not populated by
contract synchronization.

Special inline components
=========================

The :yaml:`special` map contains components whose placement is decided by
:file:`academic-persons-edit/Resources/Private/Templates/InlineProfile/Index.html`:

..  code-block:: yaml

    special:
      title:
        type: special
        renderType: title
        fields:
          - title
          - firstName
          - middleName
          - lastName
      image:
        type: special
        renderType: image
      skipSync:
        type: special
        fieldType: check
        renderType: checkbox

``special.title`` is the composed display name, not a second persisted title
property. Its ``fields`` list controls both the initial Fluid heading and its
JavaScript re-render after a successful inline update. A special entry without
``fields`` and with a ``fieldType`` represents a direct Profile property; the
shipped ``skipSync`` entry is the current example.

Contract contact fields
=======================

:yaml:`contractContact` is deliberately separate from :yaml:`profile`:

..  code-block:: yaml

    contractContact:
      emailAddress:
        section: emailAddresses
        propertyName: email
        fieldName: email
        fieldType: input
        renderType: email
        validators:
          - required
          - email
      emailAddressType:
        section: emailAddresses
        propertyName: type
        fieldName: type
        fieldType: select
        renderType: select

The shipped section identifiers are ``physicalAddresses``, ``emailAddresses``
and ``phoneNumbers``. Address, email and telephone validators and their TCA
overrides select exactly one of these sections. The type fields use distinct
identifiers (for example ``emailAddressType``) while mapping to the common DTO
property and database field ``type``.

Document sections
=================

Every key below :yaml:`documentSections` identifies one collection:

..  code-block:: yaml

    documentSections:
      publications:
        label: "LLL:EXT:site_package/Resources/Private/Language/locallang.xlf:profile.publications"
        type: publication
        fieldName: publications
        validators:
          title:
            - required
          link:
            - url
          year:
            - required
            - date
          description:
            - textarea

The available properties are:

..  list-table::
    :header-rows: 1

    *   - Property
        - Purpose
    *   - :yaml:`label`
        - Required LLL reference used for the relation and section heading.
    *   - :yaml:`type`
        - Required profile-information discriminator. ``contracts`` is the
          reserved marker for Contract entities.
    *   - :yaml:`fieldName`
        - Required Profile relation/database field.
    *   - :yaml:`readonly`
        - Optional frontend capability metadata. The shipped contracts section
          is read only.
    *   - :yaml:`validators`
        - Field flags used only for records of this section and type.

The document aliases :yaml:`from`, :yaml:`to` and :yaml:`description` map to
``yearStart``, ``yearEnd`` and ``bodytext`` and to :sql:`year_start`,
:sql:`year_end` and :sql:`bodytext`.

The special :yaml:`contracts` section uses :yaml:`fieldName: contracts`,
matching ``Profile::$contracts``, ``Profile::getContracts()`` and the
:sql:`contracts` relation. The singular ``contract`` denotes one related
record or form argument.

Overrides
=========

Settings from active packages are merged at the top level. A site package that
defines :yaml:`profile`, :yaml:`special`, :yaml:`contractContact` or
:yaml:`documentSections` replaces that complete map and must repeat every entry
it wants to keep. Flush TYPO3 caches after a change so the typed settings graph
is rebuilt.
