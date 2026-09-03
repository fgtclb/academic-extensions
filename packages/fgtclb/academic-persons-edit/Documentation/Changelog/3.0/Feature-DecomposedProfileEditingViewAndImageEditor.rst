..  _feature-decomposed-profile-editing-view-and-image-editor:

=========================================================
Feature: Decomposed profile editing view and image workflow
=========================================================

Description
===========

The profile editing view is split into focused Fluid partials. The index
template now contains only endpoint/data setup, the existing responsive grid
and component composition. Image card and editor, synchronization settings,
forms, sections, field preview/control/actions, status toast and button
templates can be overridden independently. ``Field/Editable.html`` is the shared
orchestrator for validation and delegates textfield/textarea rendering to
``Field/Control.html``.

The profile image editor no longer switches between :guilabel:`Add` and
:guilabel:`Replace` buttons. It consistently provides delete, cancel and save.
A selected file is previewed locally inside the editor. Only a successful AJAX
save updates the page preview and closes the editor; validation and request
errors are shown inside the open editor.

The multipart body is created before the pending state disables the file
input. The controller also rejects requests in which native Extbase mapping did
not produce a new image. Such requests return ``image_upload_missing`` with
HTTP status ``422`` instead of ``success: true`` with ``hasImage: false``.

Impact
======

Projects overriding the profile editing view should compare their overrides
with the new partial hierarchy. Existing grid classes and documented
JavaScript data hooks are retained. No additional CSS or inline style is
introduced.

..  index:: AJAX, File upload, Fluid, Frontend, Partials
