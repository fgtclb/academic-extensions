..  _breaking-adopted-section-based-validation:

==========================================
Breaking: Adopted section-based validation
==========================================

Description
===========

Frontend editing no longer reads the removed global validation sets from
:guilabel:`academic_persons`. Profile fields receive the validation set of their
``information`` or ``aboutme`` section. Address, email and telephone validators
derive their fields from ``contracts.contactSections.<section>.fields``. Direct
Profile
email/phone values therefore cannot inherit Contract contact rules. Profile-information
validation selects exactly one document section by its stored record type.

The inline update and rich-text services also derive writable and CKEditor
fields from the typed profile sections. Read-only and disabled profile fields
are rejected by the JSON endpoint. Direct profile validation processes only
properties submitted in the current partial request or registered as explicit
overrides. Required sibling fields that were not submitted do not reject an
otherwise independent inline update, and override values are validated after
normalization instead of validating the previously persisted value.

Impact
======

Projects must migrate their edit configuration to
:file:`EXT:academic_persons/Configuration/AcademicPersons/Settings.yaml` using
the ``profile``,
``special``, ``contracts`` and ``documentSections`` schema supplied by
:guilabel:`academic_persons_edit`. Custom
controllers, validators or tests using global validation-set identifiers must
switch to the profile- or document-section accessors.

The supported editing target remains the ProfileEditing content element and
its ``ProfileController``.

..  index:: AJAX, Configuration, Frontend, Validation, NotScanned, ext:academic_persons_edit
