..  _feature-inline-profile-editing:

=========================================
Feature: JSON based inline profile editor
=========================================

Description
===========

The :guilabel:`Inline profile editing` content element now ships a responsive
Fluid form and an ES module that persists profile changes without reloading the
page. Only properties changed in the browser are included in the JSON request.
The frontend module discovers editable fields across the complete component
root and supports separate field forms in responsive grid sections. Modal,
toast and button-template elements remain scoped to the same editor instance.

The endpoint validates the request method and JSON structure, requires an
authenticated frontend user, verifies that the requested profile is assigned
to that user and applies the configured profile validators. Expected failures
are returned as JSON with an appropriate HTTP status code. Field-specific
validation errors are rendered next to the corresponding control.

Gender values are restricted to the options configured for the profile gender
field in TCA. The empty string passes payload normalization so a configuration
without ``required`` can clear an existing selection; the shipped profile
section marks gender as required and therefore rejects that value during
section validation. Option checks read the raw TCA values independently from
the translated value-to-label map used to render the Fluid select.

The synchronization checkbox has a dedicated JSON endpoint and is persisted
immediately without submitting unrelated fields. Clicking the profile image or
its placeholder opens an accessible Bootstrap 5 modal without inline styles or
additional CSS. The image flow exposes only :guilabel:`Delete`,
:guilabel:`Cancel` and
:guilabel:`Save`: selecting a file immediately changes the modal preview,
whereas the page preview changes only after a successful upload. Failures stay
inside the open modal. The active action shows a Bootstrap spinner and
duplicate requests are prevented.

Image uploads use Extbase file handling and the configured MIME type, maximum
size and target-folder settings. Profile ownership is checked before a file is
mapped or stored. Deleting or replacing an image removes the physical file only
when no other record references it.

The main template is composed from focused partials for image UI, settings,
forms, sections, field preview/control/actions, status output and button
templates. The responsive grid and JavaScript data hooks remain unchanged.

Profile fields configured with ``renderType: ckeditor`` use TYPO3's bundled
CKEditor 5. Editor instances are initialized only when a field is opened and
expose a deliberately small formatting toolbar. The existing JSON update
endpoint remains the only persistence path. Rich-text input is sanitized
server-side with an explicit allowlist before validation and persistence, and
the response returns the normalized markup used to update the editor state.

Rich-text read mode renders the formatted value directly with a compact edit
control. In edit mode every field has separate delete, cancel and save actions.
Delete clears the local draft without closing or saving, cancel restores the
last persisted value and closes the field, and save uses the JSON AJAX
endpoint. Bootstrap sizing and alignment utilities keep the action group from
stretching to the editor height without additional CSS or inline styles. The
former bulk footer actions are no longer rendered. The header toggle opens all
editors and changes from :guilabel:`Edit all` to :guilabel:`Close all`; closing
them retains browser-side drafts and performs no request.

Impact
======

Installations using the shipped template receive a usable inline profile form.
Projects overriding the inline template should compare their markup with the
new template and preserve the JavaScript hooks described in
:ref:`inline-profile-editing`.

..  index:: AJAX, CKEditor, Fluid, Frontend, JSON, Profile image, Rich text
