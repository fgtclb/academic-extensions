..  _feature-editor-validation-character-limit-metadata:

===================================================
Feature: Editor validation character-limit metadata
===================================================

Description
===========

The shared edit-settings normalizer now preserves both a positive
``editor.limit`` from CKEditor document-field configuration and a positive
``characterLimit`` from CKEditor profile-field configuration as typed
``Validation.characterLimit`` metadata. The value survives the cached
``var_export()`` round trip and remains zero for non-CKEditor controls, invalid
values and fields without a limit.

Impact
======

The paired :guilabel:`academic_persons_edit` extension can use one normalized
value for its character counter and server-side validation. Public-profile
settings and rendering are unchanged. The metadata is not copied into TCA, so
backend FormEngine in TYPO3 13 and 14 is unaffected.

..  index:: Configuration, Frontend, Validation
