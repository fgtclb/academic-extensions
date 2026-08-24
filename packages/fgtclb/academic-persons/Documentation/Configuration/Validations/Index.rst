..  index:: Configuration; Validations
..  _configuration-validations:

===================
Validation settings
===================

Validation is declared on a field in :yaml:`profile`, :yaml:`special` or
:yaml:`contractContact`, or below one :yaml:`documentSections` entry. There is
no global validation registry and no fallback to a sibling section.

The same normalized metadata supplies TCA overrides, Fluid input metadata and
server-side Extbase validation. See :ref:`configuration-sections` for the full
schema.

Profile validation
==================

..  code-block:: yaml

    profile:
      website:
        section: information
        fieldType: input
        renderType: combinedLink
        validators:
          - url

The inline JSON endpoint accepts only writable configured Profile properties.
Validation is submission-aware: only a property sent in the current request or
registered as an explicit override is validated. A required sibling omitted by
a one-field AJAX update therefore cannot reject that request. An override is
validated with its submitted value rather than the persisted value.

Direct special properties, currently ``skipSync``, use the validation attached
to their :yaml:`special` entry. Composite special components such as
``special.title`` reuse their listed regular profile fields and do not create a
second persisted property.

Contract contact validation
===========================

Contact data belongs to address, email and telephone records below a Contract.
Its validation is configured independently from direct Profile properties.

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

Contract-contact validators select exactly ``physicalAddresses``,
``emailAddresses`` or ``phoneNumbers``. A rule from one section is never used
for another contact record and is never merged into Profile validation.
Type-field read-only/disabled rules are included in controller, validator,
factory and TCA validation sets as well.

Document validation
===================

Document validators belong to their containing section:

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
      lectures:
        label: "LLL:EXT:site_package/Resources/Private/Language/locallang.xlf:profile.lectures"
        type: lecture
        fieldName: lectures
        validators:
          title:
            - readonly

The record type selects exactly one document section. FormEngine receives the
same isolation through type-specific ``columnsOverrides``.

Document field aliases
----------------------

..  list-table::
    :header-rows: 1

    *   - YAML key
        - DTO property
        - Database field
    *   - :yaml:`from`
        - ``yearStart``
        - :sql:`year_start`
    *   - :yaml:`to`
        - ``yearEnd``
        - :sql:`year_end`
    *   - :yaml:`description`
        - ``bodytext``
        - :sql:`bodytext`

Available flags
===============

Flag names are case-insensitive. Unknown flags remain metadata but add no
built-in behavior.

..  list-table::
    :header-rows: 1

    *   - Flag
        - Effect
    *   - :yaml:`required`
        - Adds TYPO3's ``NotEmptyValidator`` and required input/TCA metadata.
    *   - :yaml:`readonly`
        - Shows the value but prevents frontend and backend writes.
    *   - :yaml:`disabled`
        - Disables frontend editing and implies read-only TCA metadata.
    *   - :yaml:`email`
        - Adds ``EmailAddressValidator`` and email input metadata.
    *   - :yaml:`url`
        - Adds ``UrlValidator`` and URL input metadata.
    *   - :yaml:`number`
        - Selects numeric input/TCA metadata.
    *   - :yaml:`tel`
        - Selects telephone input metadata; no format-specific validator is
          added.
    *   - :yaml:`date`
        - Selects date input metadata. Year values remain integer-backed.
    *   - :yaml:`textarea`
        - Selects multiline input metadata and TCA's text type.
    *   - :yaml:`html`
        - Selects multiline metadata. Profile fields rendered as CKEditor are
          sanitized before persistence.

:yaml:`readonly` and :yaml:`disabled` cancel :yaml:`required`; a field which
cannot be edited is never demanded from the editor.

Shipped defaults
================

``firstName``, ``middleName`` and ``lastName`` are read only because they are
normally synchronized from the frontend user. Contract email, phone and the
required physical-address parts keep their own section-local requirements. The
contracts document section is read only; every other document section owns its
rules independently.

Overrides
=========

A site package overriding one of the four top-level maps must repeat that
complete map. Flush TYPO3 caches after changing settings.
