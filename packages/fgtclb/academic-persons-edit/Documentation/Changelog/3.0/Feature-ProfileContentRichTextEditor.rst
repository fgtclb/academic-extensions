..  _feature-profile-content-rich-text-editor:

================================================
Feature: Secure profile content rich-text editor
================================================

Description
===========

Every inline profile field configured with ``renderType: ckeditor`` now uses
TYPO3's bundled CKEditor 5. The frontend module resolves CKEditor through the
TYPO3 JavaScript import map and creates each editor lazily. No CDN asset or
additional stylesheet is introduced.

Only paragraphs, line breaks, bold and italic text, lists and links are offered
by the editor. The server independently sanitizes every submitted CKEditor value
before validation and persistence. Its explicit allowlist accepts the matching
HTML tags and allows only local, HTTP, HTTPS, email and telephone links. The
AJAX success response contains the sanitized values, which become the new
browser-side persisted state.

All JSON update routes enforce ``POST`` with ``Content-Type:
application/json`` before parsing or authenticating the request.

Read mode now renders the formatted rich-text content directly with a compact
edit control instead of putting the complete value into a button. Empty fields
show a localized placeholder. The preview is updated with the sanitized server
response after saving.

Every open field has separate delete, cancel and save buttons. Delete
(``data-ie-dismiss``) empties the local draft without closing or issuing a
request. Cancel (``data-ie-cancel``) restores the last persisted value and
closes the field. Save (``data-ie-save``) uses the existing JSON AJAX endpoint.
Bootstrap alignment and sizing utilities keep this action group from stretching
to the CKEditor height. For ``renderType: ckeditor`` only, the group now uses
``ms-auto`` beside the field label instead of occupying a column next to the
editor. Other field types retain their previous action placement; no additional
stylesheet or inline style is introduced.
The header's :guilabel:`Edit all`/:guilabel:`Close all` toggle controls all
editors without a global footer save or cancel action.

Impact
======

``typo3/cms-rte-ckeditor`` is a required dependency. The minimum supported core
versions are TYPO3 13.4.31 and 14.3.6 so installations include the sanitizer
security fixes published with TYPO3-CORE-SA-2026-006. Template overrides for
sections containing fields with ``renderType: ckeditor`` must retain the
rich-text and editor-container data
attributes, preview hooks and three-action group documented in
:ref:`inline-profile-editing`.

..  index:: CKEditor, Frontend editing, HTML sanitizer, Rich text, Security
