..  _feature-configured-profile-rich-text-character-limit:

=====================================================
Feature: Configured profile rich-text character limit
=====================================================

Description
===========

Profile fields using ``renderType: ckeditor`` may now declare a positive
integer ``characterLimit``. Fluid renders the corresponding limit metadata and
an accessible live ``current / limit`` counter directly below CKEditor. The
existing rich-text module counts normalized visible text without counting HTML
tags and keeps the last accepted value when an addition would exceed the limit.
Existing over-limit content can still be shortened.

The Extbase profile-form validator applies the same normalized limit after
rich-text sanitization and before a partial AJAX update is persisted. The
shipped ``miscellaneous`` field uses 500 visible characters.

Impact
======

The profile syntax intentionally remains separate from the nested
``description.editor.limit`` syntax used by document sections. Both variants
use the same typed validation metadata, counter implementation and server-side
character counting. Invalid or non-positive values and limits on non-CKEditor
fields are ignored. Backend TCA remains unchanged in TYPO3 13 and 14.

..  index:: CKEditor, Configuration, Frontend, JavaScript, Validation
