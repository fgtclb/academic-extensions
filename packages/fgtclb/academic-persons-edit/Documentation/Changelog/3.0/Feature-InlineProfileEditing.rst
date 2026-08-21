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
field in TCA. The empty string remains valid so an existing selection can be
cleared. Validation reads the raw TCA values independently from the translated
value-to-label map used to render the Fluid select.

The synchronization checkbox has a dedicated JSON endpoint and is persisted
immediately without submitting unrelated fields. Clicking the profile image or
its placeholder opens an accessible Bootstrap 5 modal without inline styles or
additional CSS. Depending on the current state, the modal allows users to add,
replace or delete the image exclusively through dedicated AJAX requests. The
active action shows a Bootstrap spinner, duplicate requests are prevented and
the image previews and actions are updated without reloading the page.

Image uploads use Extbase file handling and the configured MIME type, maximum
size and target-folder settings. Profile ownership is checked before a file is
mapped or stored. Deleting or replacing an image removes the physical file only
when no other record references it.

Impact
======

Installations using the shipped template receive a usable inline profile form.
Projects overriding the inline template should compare their markup with the
new template and preserve the JavaScript hooks described in
:ref:`inline-profile-editing`.

..  index:: AJAX, Fluid, Frontend, JSON, Profile image
