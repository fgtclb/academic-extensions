..  _feature-configured-document-rich-text-character-limit:

======================================================
Feature: Configured document rich-text character limit
======================================================

Description
===========

Document-section descriptions configured with ``editor.type: ckeditor`` may
now declare a positive integer ``editor.limit``. The document form endpoint
exposes the normalized value as field metadata and the modal renders an
accessible live ``current / limit`` counter below CKEditor. Only normalized
visible text is counted; HTML tags do not consume the allowance.

The editor keeps the last accepted value when a change would exceed the limit.
Existing over-limit content can still be shortened. Both the JSON document
operations and the Extbase form-data validator enforce the same rule on the
server before persistence.

Impact
======

The shipped document descriptions use a limit of 500 visible characters.
Removing ``editor.limit``, using a non-positive value or configuring it on a
non-CKEditor field disables this feature. The setting belongs exclusively to
frontend editing metadata and never modifies backend TCA in TYPO3 13 or 14.

..  index:: CKEditor, Configuration, Frontend, JavaScript, Validation
