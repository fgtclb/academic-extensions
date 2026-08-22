..  _feature-decomposed-inline-profile-view-and-image-modal:

=========================================================
Feature: Decomposed inline profile view and image workflow
=========================================================

Description
===========

The inline profile view is split into focused Fluid partials. The index
template now contains only endpoint/data setup, the existing responsive grid
and component composition. Image card and modal, synchronization settings,
forms, sections, field preview/control/actions, status toast and button
templates can be overridden independently. ``Field.html`` remains the shared
orchestrator for validation and delegates textfield/textarea rendering to
``Field/Control.html``.

The profile image modal no longer switches between :guilabel:`Add` and
:guilabel:`Replace` buttons. It consistently provides delete, cancel and save.
A selected file is previewed locally inside the modal. Only a successful AJAX
save updates the page preview and closes the modal; validation and request
errors are shown inside the open modal.

The multipart body is created before the pending state disables the file
input. The controller also rejects requests in which native Extbase mapping did
not produce a new image. Such requests return ``image_upload_missing`` with
HTTP status ``422`` instead of ``success: true`` with ``hasImage: false``.

Impact
======

Projects overriding the inline profile view should compare their overrides
with the new partial hierarchy. Existing grid classes and documented
JavaScript data hooks are retained. No additional CSS or inline style is
introduced.

..  index:: AJAX, File upload, Fluid, Frontend, Modal, Partials
