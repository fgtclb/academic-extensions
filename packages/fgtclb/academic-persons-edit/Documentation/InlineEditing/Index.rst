..  index:: Inline editing, AJAX, CKEditor, JSON, Profile image, Rich text
..  _inline-profile-editing:

======================
Inline profile editing
======================

The :guilabel:`Inline profile editing` content element renders the profile
assigned to the authenticated frontend user. The shipped
:file:`Resources/Private/Templates/InlineProfile/Index.html` template contains
three independently persisted areas:

*   profile fields using the generic JSON update endpoint,
*   the synchronization checkbox using its own JSON endpoint, and
*   the profile image modal using dedicated upload and delete endpoints.

The small frontend entry
:file:`Resources/Public/JavaScript/frontend/profile.js` initializes every
``data-academic-persons-inline-edit`` component on the page. Feature modules in
:file:`Resources/Public/JavaScript/frontend/profile/` separately own common
requests/status output, field editing, rich text, synchronization, image
editing and sticky positioning. All changes are
saved through AJAX without reloading the page. Editable fields are discovered
across the complete component root, even when the responsive page layout
places them in separate ``data-ie-fields-form`` elements. Modal, toast and
compatibility-template elements live in the same component scope.

View data
=========

The controller assigns the following variables to the Fluid template:

..  list-table::
    :header-rows: 1

    *   - Variable
        - Description
    *   - ``{profile}``
        - Profile assigned to the authenticated frontend user, or
          :php:`null` when no editable profile exists.
    *   - ``{profileSections}``
        - Ordered Fluid view models generated from :yaml:`profile`. Every
          section contains regular fields and inserted composite special items.
    *   - ``{specialFields}``
        - Typed :yaml:`special` components, including composed title, image and
          synchronization metadata.
    *   - ``{profileFieldOptions}``
        - Options for every configured :yaml:`renderType: select` field. The
          option source remains the matching Profile TCA field.
    *   - ``{documentSections}``
        - Ordered structured-section view models derived from
          :yaml:`documentSections`, including mapping, read-only and validation
          metadata plus the typed records.
    *   - ``{imageAllowedMimeTypes}``
        - Comma-separated MIME types accepted by the image input. The server
          validates the configured values independently.
    *   - ``{data}`` and ``{record}``
        - Current content element data and its record object.

View structure
==============

The template is intentionally a composition root. The main partial groups are:

..  list-table::
    :header-rows: 1

    *   - Partial group
        - Responsibility
    *   - ``Image/Card.html`` and ``Image/Modal.html``
        - Profile-name heading above the sticky page preview, dedicated edit
          overlay, file selection, modal preview and image actions.
    *   - ``Settings/Sync.html``
        - Independently persisted synchronization switch immediately left of
          the edit-all toggle.
    *   - ``Forms/*``
        - Personal-data and about-section form boundaries. Persistence actions
          live beside their respective fields.
    *   - ``Profile/Items.html`` and ``Field/Renderer.html``
        - Ordered iteration and ``renderType`` dispatch for settings-driven
          profile fields.
    *   - ``Field/Types/*``
        - One focused partial for input, textarea, CKEditor, select, checkbox
          and combined-link controls.
    *   - ``Sections/Documents.html`` and ``Documents/*``
        - Read-only structured document composition.
    *   - ``Field.html``, ``Field/Group.html`` and shared ``Field/*``
        - Preview, control, grouped fields and per-field actions.
    *   - ``Header.html`` and ``StatusToast.html``
        - Personal-section heading, synchronization/edit controls and scoped
          status output.
          ``ButtonTemplates.html`` remains a compatibility fallback for
          existing template overrides; the shipped read view does not use its
          button-shaped value controls.

Layout and responsive behavior
==============================

The view uses Bootstrap 5 grid, spacing, typography, background, positioning
and form utilities. The small :file:`Resources/Public/Css/additional.css`
compatibility layer only releases a surrounding ``.section`` overflow,
normalizes one frame spacing variable and keeps the sticky card below the page
header; the Fluid templates contain no inline style declarations.

On ``lg`` and larger viewports the first row uses a ``4 / 8`` column split.
The profile image block has ``sticky-top`` so the image and its edit action
stay visible while the profile data scrolls. Below ``lg`` both columns stack in
document order. The about section follows the complete first row and therefore
never overlaps the sticky column.

At runtime ``initializeStickyImageOffset()`` reads the visible outer height of
``#page-header`` through ``getBoundingClientRect().height``, adds a 10-pixel
visual gap and assigns the result to the ``top`` property of
``data-ie-sticky-image``. A
``ResizeObserver`` watches the header's ``border-box`` and keeps the offset
synchronized whenever the navbar changes height, including height or padding
changes caused by a scroll-dependent header state. Environments without it use
the window ``resize`` event as a fallback. If the page header is absent,
Bootstrap's regular ``sticky-top`` value remains in control.

The two columns live in their own ``align-items-stretch`` row. The image column
inherits the stretched cross-axis size, giving the sticky image a containing
block as tall as the adjacent profile data. The full-width about section keeps
its own ``col-12`` in a separate sibling row below it.

The complete profile name is the page's ``h1`` above the image. Both Fluid and
JavaScript use the ordered ``fields`` list from :yaml:`special.title`. Fluid
renders the initial name; ``data-ie-profile-name-field-ids`` lets JavaScript
recompose the same name after a successful update without reloading the page.

Profile values are rendered as readable text rows with alternating
``bg-body-tertiary`` surfaces. The only read-mode action is a borderless pencil
button with an accessible label. Name components and the URL/title pair of
each link share one preview row and open as one inline-edit group.
The special name editor retains the established responsive grid (academic
title / first name at ``4 / 8`` and middle / last name at ``6 / 6``) without
putting layout metadata into YAML.

Settings-driven controls
========================

``ProfileSectionProvider`` converts the typed :yaml:`profile` and
:yaml:`special` settings into an ordered Fluid view model. Section placement
itself remains explicit in :file:`Templates/InlineProfile/Index.html`: the
template decides where ``profileSections.information`` and
``profileSections.aboutme`` appear. It does not enumerate their individual
fields.

``Field/Renderer.html`` chooses a partial from :file:`Field/Types/` solely from
``renderType``. Option values are not duplicated in YAML: for a select field,
``ProfileFieldOptionsService`` reads the corresponding Profile TCA items.
Preview behavior likewise follows the rendered control type.
Removing a special component removes its controls; marking the image or
synchronization special ``readonly`` or ``disabled`` also blocks the matching
write endpoint, not just its Fluid control.

The shared :file:`Resources/Private/Partials/InlineProfile/Field.html` partial
composes three focused partials:

*   :file:`Field/Preview.html` renders the text preview and pencil trigger,
*   :file:`Field/Control.html` renders either ``f:form.textfield`` or
    ``f:form.textarea``, including the CKEditor hook, and
*   :file:`Field/Actions.html` renders delete, cancel and save.

:file:`Field/Group.html` composes related textfields below one preview. Its
``data-ie-field-ids`` value defines which fields open, cancel and save together;
``data-ie-display-field-ids`` and ``data-ie-display-mode`` control whether the
preview joins values (the name) or uses the first non-empty value (link title
falling back to its URL).

``validation.inputType`` supplies the concrete HTML input type. Select and
checkbox controls save immediately on change. :file:`Settings/Sync.html`
contains the separately persisted special checkbox.

..  list-table::
    :header-rows: 1

    *   - Requirement
        - Implementation
    *   - Telephone number
        - Textfield with ``inputType: 'tel'`` or a validation input type of
          ``tel``.
    *   - Website address
        - Textfield with ``inputType: 'url'`` or a validation input type of
          ``url``.
    *   - Free text input
        - Default textfield.
    *   - Select
        - :file:`Field/Types/Select.html`; options come from the configured
          field's TCA items and changes save immediately.
    *   - Checkbox
        - :file:`Field/Types/Checkbox.html` for direct Profile flags. The
          synchronization special uses its own form and endpoint.
    *   - Multiline text
        - Field partial with ``textarea: true``. Passing ``richText: true``
          additionally turns the textarea into the TYPO3 CKEditor 5 when the
          field is opened.

Generic field update
====================

The URL is generated by ``f:uri.action`` for ``updateAction()`` with page type
:php:`1733735`. Requests must use ``POST`` and
``Content-Type: application/json``. Only values changed since the last
successful save are sent. An empty string clears a property when its
section-local validation permits an empty value; omitted properties remain
unchanged.

..  code-block:: json
    :caption: Partial profile update

    {
      "profile": 123,
      "data": {
        "gender": "female",
        "website": ""
      }
    }

Unknown profile properties, configured read-only/disabled fields and select
values not configured in the matching TCA field are rejected. The direct academic/honorific
``profile.title`` field is an ordinary configured Profile property; the
composed display name is :yaml:`special.title`. ``skipSync`` is a direct special
property. A configured ``combinedLink`` additionally enables its matching
``*Title`` companion. All other writable Profile properties come from
:yaml:`profile`.
Extbase validation errors are returned in an ``errors`` object keyed by
property path and are mapped back to the corresponding form controls by the
frontend module.

``ProfileFieldOptionsService`` supplies both the presentation options and the
strict allow-list validation for every configured select. Thus another select
does not require a new Fluid partial, validator service or JavaScript branch.

Direct public-profile contacts
==============================

The inline ``information`` section edits four direct Profile properties:

*   ``emailAddress`` and ``publishEmailAddress``;
*   ``phoneNumber`` and ``publishPhoneNumber``.

These are not Contract contact records. The values are persisted on
:sql:`tx_academicpersons_domain_model_profile`; each publication checkbox is a
separate opt-in flag and defaults to false. The public Profile detail template
renders an email or telephone link only when both the value and its matching
flag are set. Contract email/phone/address collections continue to render in
their own contract section and use :yaml:`contractContact` validation.

The direct contact values are deliberately not copied from synchronized
contract contacts. This prevents an employment contact update from silently
changing what a person explicitly chose to publish as general profile contact
data.

Rich-text content fields
========================

The shipped configuration marks five profile properties with
:yaml:`renderType: ckeditor`. Four are displayed with the ``information``
section, while ``miscellaneous`` belongs to :yaml:`aboutme`:

*   ``coreCompetences``,
*   ``teachingArea``,
*   ``supervisedDoctoralThesis``,
*   ``supervisedThesis``, and
*   ``miscellaneous``.

The editor is TYPO3's shipped CKEditor 5 from the
``typo3/cms-rte-ckeditor`` package. The ``profile/rich-text.js`` module imports
its CKEditor modules through TYPO3's JavaScript import map; it does not load an
editor from a CDN. An editor instance is created lazily when a rich-text field is opened.
If initialization fails, the original textarea remains available and the
component reports an error.

The toolbar intentionally exposes only undo, redo, bold, italic, bulleted
lists, numbered lists and links. Editor changes are mirrored into the
underlying textarea, so required-field validation, changed-value detection and
the existing JSON request remain the single persistence path. CKEditor's
initial HTML normalization becomes the local comparison baseline; merely
opening an editor therefore does not submit or rewrite legacy content.

Outside edit mode, each rich-text field renders its formatted content directly
and provides a borderless pencil action on the right. Empty values show a
localized placeholder. The preview is initially rendered through TYPO3's
HTML formatting pipeline and is replaced after a successful save with the
sanitized markup returned by the server. The frontend applies the same strict
tag, attribute and URI-scheme allowlist without assigning markup through
``innerHTML``.

Each open text or rich-text field has three explicit actions. Selects and
checkboxes save immediately when their value changes.

*   :guilabel:`Delete` (``data-ie-dismiss``) clears the current browser-side
    draft. The editor stays open and no request is sent.
*   :guilabel:`Cancel` (``data-ie-cancel``) restores the last successfully
    persisted value and closes only that field. No request is sent.
*   :guilabel:`Save` (``data-ie-save``) sends that field through the JSON AJAX
    endpoint. It closes the field only after a successful response or when
    there is no changed value to persist.

The action group uses Bootstrap utility classes to remain content-sized and
align itself to the end of the editor row instead of stretching to the
CKEditor height. No additional stylesheet or inline style is required. The
:guilabel:`Edit all` toggle beside the personal-data heading opens both regular
fields and grouped rows, receives Bootstrap's ``active`` state and changes its
label to :guilabel:`Close all`. Activating it again collapses every editor
without saving or discarding browser-side drafts. There is no global footer
action area; save and undo remain explicit per-field actions.

The pencil is rendered through TYPO3's ``core:icon`` ViewHelper. Template
overrides may replace the icon but must retain the button's edit hook,
``data-ie-for`` target and accessible label. The profile value itself must not
be placed back inside the button.

Server-side sanitization
------------------------

Client-side editor configuration is not treated as a security boundary.
``ProfileUpdateValidationService`` derives writable properties from the
configured profile sections. It passes every configured
:yaml:`renderType: ckeditor` property through ``ProfileRichTextSanitizer``
before validation and persistence. The sanitizer uses TYPO3's allow-list based
HTML sanitizer and permits only:

*   the tags ``p``, ``br``, ``strong``, ``em``, ``ul``, ``ol``, ``li`` and
    ``a``;
*   the ``href`` attribute on ``a``; and
*   local links and the URI schemes ``http``, ``https``, ``mailto`` and
    ``tel``.

Scripts, event-handler attributes, style attributes, images, unknown tags and
unsafe URI schemes are removed. The successful JSON response contains the
normalized, sanitized values. The frontend replaces its local editor and
preview state with exactly those returned values rather than trusting the
submitted markup.

Each AJAX request is validated as a partial profile submission. The validator
selects only submitted or explicitly overridden DTO properties from their
configured profile sections. A ``required`` rule on an omitted sibling field
therefore does not block a field update or the dedicated ``skipSync`` request.
Submitted inline overrides are validated after normalization and sanitization.

The extension requires at least TYPO3 13.4.31 or TYPO3 14.3.6. These constraints
include the HTML-sanitizer fixes published with TYPO3-CORE-SA-2026-006. Projects
must still keep TYPO3 security updates current.

Read-only structured profile sections
=====================================

The inline profile view renders the structured records directly below the
:guilabel:`About me` field. ``ProfileDocumentSectionProvider`` supplies one
ordered view model instead of duplicating relation mapping in Fluid. It reads
all sections, including ``contracts``, directly from
``AcademicPersonsSettings::documentSections``. That settings object is built
from the active packages'
:file:`Configuration/AcademicPersons/Settings.yaml` files, so configured order
is also presentation order.

For every document section the provider reuses the configured ``identifier``,
``fieldName``, record ``type``, LLL ``label``, ``readonly`` state and the
section-local validations. The heading is translated directly from that label.
A newly configured type consequently does not require another section registry
in ``academic_persons_edit``. The generic localized empty state is used until
an identifier-specific message is added.

``contracts`` contains ``FGTCLB\AcademicPersons\Domain\Model\Contract``
objects. Every other collection contains
``FGTCLB\AcademicPersons\Domain\Model\ProfileInformation`` objects. Contract
rows show the start date and position. Profile-information rows use a range,
year or start-date presentation appropriate to the section and retain the
stored title, link and formatted body text. All sections remain visible when
empty and display a localized empty state.

The current inline view presents structured records without mutation controls.
The section's configured ``readonly`` state and validations are exposed as view
metadata, but the markup contains no add, edit, delete, visibility or sorting
controls and :guilabel:`Edit all` targets only profile fields. The InlineProfile
plugin registers only ``InlineProfileController``; legacy contract,
profile-information and contact controllers are not exposed through its normal
or non-cacheable action maps.

Section order is centralized and every section emits ``data-section-key`` and
``data-section-position`` together with the configured
``data-section-field-name`` and ``data-section-record-type``. Records
additionally emit ``data-item-uid``, ``data-item-sorting`` and
``data-item-position``. These attributes are passive metadata in this release:
there is no ``draggable`` state, handle, sorting JavaScript or persistence
endpoint yet. A later drag-and-drop implementation can replace the settings
order and attach behavior to these stable boundaries without restructuring the
templates.

The presentation uses Bootstrap rows, spacing, rounded corners and alternating
``bg-body-tertiary`` records. No additional extension-specific CSS is required.

Inline-only development boundary
================================

``academicpersonsedit_inlineprofile`` is the only profile-editing content
element offered in the backend CType selector and new-content-element wizard.
All new profile-editing behavior must be implemented through the
``InlineProfile`` template and partial tree, ``InlineProfileController`` AJAX
actions and the inline JavaScript component. Inline code must not render a
``Profile``, ``ProfileInformation`` or ``Contract`` template from the legacy
``ProfileEditing`` plugin and must not route to one of its controllers.

The old controllers, templates, language keys and functional reference tests
remain in the package during the migration so their previous behavior can be
consulted without reconstructing it. The legacy ``ProfileEditing`` Extbase
configuration also remains temporarily for existing content records and its
reference tests, but it is not selectable for new records. This compatibility
block is not an implementation source for InlineProfile and can be removed as
one isolated cleanup step after the inline migration is complete.

The InlineProfile functional test setup reflects the same boundary. It uses a
dedicated ``academicpersonsedit_inlineprofile`` fixture and the neutral
``AbstractFrontendProfilePluginTestCase`` base. It does not create a
``ProfileEditing`` record and change its CType afterwards.

Synchronization checkbox
========================

The synchronization checkbox appears as the compact
:guilabel:`Disable profile sync` switch
immediately left of the :guilabel:`Edit all`/:guilabel:`Close all` toggle in
the personal-section header and is persisted immediately through
``updateSkipSyncAction()``. Its form is a sibling of the profile form, not a
nested form. Its presence and writable metadata follow the
``special.skipSync`` configuration; the underlying data and endpoint semantics
remain ``skipSync``. It does not submit or mutate any other field. The endpoint
accepts exactly one boolean property:

..  code-block:: json
    :caption: Synchronization update

    {
      "profile": 123,
      "data": {
        "skipSync": true
      }
    }

Any additional property or a non-boolean value returns ``invalid_payload``.
On failure the JavaScript restores the last successfully persisted checkbox
state.

Profile image modal
===================

Clicking the compact pencil button in the upper-right corner of the current
profile image or its placeholder opens the Bootstrap 5 modal from
:file:`Partials/InlineProfile/Image/Modal.html`. It uses only Bootstrap utility
and component classes; the shipped view requires neither inline styles nor
additional CSS.

The image wrapper uses ``position-relative`` and the ``btn-sm`` edit action uses
``position-absolute top-0 end-0``. Its visible label is the registered pencil
icon; localized ``title`` and ``aria-label`` attributes retain an accessible
name.

The modal deliberately has no state-dependent :guilabel:`Add` or
:guilabel:`Replace` action. Selecting a file immediately replaces only the
modal preview with a local object URL. The page preview and persisted profile
remain unchanged until :guilabel:`Save` succeeds. A successful upload replaces
both previews, closes the modal and shows the saved image directly. Cancel or
closing the modal discards the selected preview and restores the persisted
image. :guilabel:`Delete` is shown only while an image is persisted.

The save button remains disabled until a file is selected. While an upload or
deletion is pending, the image controls and modal close buttons are disabled
and the active action displays a Bootstrap spinner. This prevents duplicate
requests and closing the modal during a running operation.

Upload
------

The image form is intercepted by the frontend module and sends
``multipart/form-data`` to ``uploadImageAction()`` through ``fetch()``.
The ``FormData`` object is built before the file control is disabled for the
pending state. Disabled controls are omitted by the browser and would otherwise
produce an apparently valid request without an uploaded image.
Extbase's file handling service validates the configured maximum file size and
allowed MIME types, stores the file in the configured target folder and updates
the FAL relation. Authorization is checked before Extbase maps or stores the
uploaded file. A replaced physical file is removed only when it has no other
references.

The modal does not render ``f:form.validationResults``. Upload validation
failures are returned as JSON and displayed in an alert inside the still-open
modal. The controller additionally compares the submitted image with the
persisted FAL reference. It returns ``image_upload_missing`` with status
``422`` instead of reporting success when no new file arrived.

All inline AJAX actions propagate non-successful JSON responses out of TYPO3's
Extbase ``USER`` content rendering with ``PropagateResponseException``. A
``JsonResponse`` returned by the action alone would contribute its body to the
surrounding ``PAGE`` object while the outer frontend response retained status
``200``. Propagation therefore preserves the documented non-``200`` status
codes for the AJAX client.

The relevant TypoScript settings are:

..  code-block:: typoscript

    plugin.tx_academicpersonsedit.settings.editForm.profileImage {
        targetFolder = 1:/user_upload/
        validation {
            maxFileSize = 5M
            allowedMimeTypes = image/jpeg,image/png,image/webp
        }
    }

Delete
------

The delete button calls ``deleteImageAction()`` exclusively through AJAX. The
endpoint accepts a ``POST`` JSON request without profile field changes:

..  code-block:: json
    :caption: Image deletion

    {
      "profile": 123,
      "data": {}
    }

The profile relation is cleared first. The physical file is deleted only if no
other record references it. The response includes ``deleted`` and
``hasImage`` so clients can synchronize their local state.

Authentication and responses
============================

All four endpoints require an authenticated frontend user and accept only the
profile assigned to that user. The generic update, synchronization and delete
endpoints propagate machine-readable JSON errors. Image upload validation is
also converted to and propagated as JSON by the controller's error action.

..  list-table:: Response status codes
    :header-rows: 1

    *   - Status
        - Error identifier
        - Meaning
    *   - ``200``
        - —
        - The request was persisted successfully.
    *   - ``400``
        - ``invalid_json`` or ``invalid_payload``
        - Invalid JSON or request structure.
    *   - ``401``
        - ``authentication_required``
        - No frontend user is authenticated.
    *   - ``403``
        - ``profile_not_editable``
        - The profile is not assigned to the frontend user.
    *   - ``405``
        - ``method_not_allowed``
        - A JSON endpoint was called with a method other than ``POST``.
    *   - ``415``
        - ``unsupported_media_type``
        - A JSON endpoint was called without ``Content-Type:
          application/json``.
    *   - ``422``
        - ``invalid_profile_data``, ``validation_failed`` or
          ``image_upload_missing``
        - A field value or uploaded file is invalid.
    *   - ``500``
        - ``internal_server_error``
        - An unexpected error occurred. Details are logged but not exposed in
          the JSON response.

Customizing the view
====================

Override :file:`InlineProfile/Index.html` and the partials below
:file:`Resources/Private/Partials/InlineProfile/` through the regular template
and partial root paths. The index keeps URL/data setup, the responsive main
grid and composition. Forms, sections, image UI, field controls and status
toast are separate partials. Keep the following contracts when reusing the
shipped JavaScript:

..  list-table::
    :header-rows: 1

    *   - Selector or attribute
        - Purpose
    *   - ``data-academic-persons-inline-edit``
        - Root component and scope for all queries.
    *   - ``data-profile-uid``
        - Positive profile identifier.
    *   - ``data-update-url``
        - Generic field update endpoint.
    *   - ``data-skip-sync-url``
        - Synchronization endpoint.
    *   - ``data-delete-image-url``
        - Image deletion endpoint.
    *   - ``data-ie-fields-form`` and
          ``academic-persons-inline-edit__field``
        - Generic field forms and controls. Separate forms preserve valid markup
          across the personal-data and about-section grid areas.
    *   - ``data-ie-rich-text`` and ``data-ie-editor-container``
        - Marks a textarea for lazy CKEditor initialization and its wrapper for
          show/hide handling.
    *   - ``data-ie-rich-text-preview`` and
          ``data-ie-rich-text-preview-content``
        - Direct formatted read preview and its safely replaceable content
          container.
    *   - ``data-ie-field-preview`` and ``data-ie-field-editor``
        - Plain read row and the inline control region for one field.
    *   - ``data-ie-profile-name`` and
          ``data-ie-profile-name-field-ids``
        - Main heading and the name controls used to refresh it after saving.
    *   - ``data-ie-sticky-image``
        - Sticky image container receiving the measured ``#page-header`` height
          plus a 10-pixel visual gap as its runtime ``top`` offset.
    *   - ``data-ie-document-sections`` and ``data-ie-document-section``
        - Read-only structured-section list and stable boundary for each future
          reorderable section.
    *   - ``data-section-key`` and ``data-section-position``
        - Stable section identity and current zero-based presentation position.
    *   - ``data-section-field-name`` and ``data-section-record-type``
        - Field and relation type taken from ``AcademicPersonsSettings`` for a
          future section-specific persistence endpoint.
    *   - ``data-ie-document-items`` and ``data-ie-document-item``
        - Read-only item collection and record boundaries inside a section.
    *   - ``data-item-uid``, ``data-item-sorting`` and ``data-item-position``
        - Persisted record identity, domain sorting value and current zero-based
          presentation position reserved for a later drag-and-drop workflow.
    *   - ``data-ie-document-empty-state``
        - Localized placeholder rendered when a structured collection is empty.
    *   - ``data-ie-field-group``, ``data-ie-field-ids`` and
          ``data-ie-display-field-ids``
        - Grouped preview/editor and the controls participating in it.
    *   - ``data-ie-group-edit``, ``data-ie-group-dismiss``,
          ``data-ie-group-cancel`` and ``data-ie-group-save``
        - Open, clear the draft, restore and persist a grouped field row.
    *   - ``data-ie-field-actions``
        - Content-sized Bootstrap group for the three per-field actions.
    *   - ``data-ie-autosave-on-change``
        - Saves configured select and checkbox controls immediately after a
          change.
    *   - ``data-ie-autosave-undo`` and ``data-ie-cancel``
        - Marks the undo action beside an editable select or checkbox. It
          restores the last successfully persisted value and closes the editor
          without sending another request.
    *   - ``data-academic-persons-inline-edit-edit-all-btn``
        - Toggles all editable single fields and grouped rows between open and
          collapsed states.
    *   - ``data-ie-edit-all-label``, ``data-ie-close-all-label`` and
          ``data-ie-edit-all-button-label``
        - Localized labels and replaceable label container for the edit-all
          toggle.
    *   - ``data-ie-dismiss``
        - Deletes the current draft value without closing or saving it.
    *   - ``data-ie-cancel``
        - Restores the last persisted value and closes one field without a
          request.
    *   - ``data-ie-save``
        - Persists one field through the generic JSON endpoint.
    *   - ``data-ie-sync-form`` and
          ``academic-persons-inline-edit__sync-checkbox``
        - Synchronization control.
    *   - ``academic-persons-inline-edit__image-form`` and
          ``data-ie-image-modal``
        - AJAX-only multipart upload form and Bootstrap modal.
    *   - ``data-ie-upload-image`` and ``data-ie-image-error``
        - Save action and modal-local error output. The save action is enabled
          only after selecting a file.
    *   - ``data-ie-image-preview`` and
          ``data-ie-image-modal-preview``
        - Image locations updated after upload or deletion.
    *   - ``data-ie-status-toast``
        - Scoped status feedback for the component.

Every editable field needs one ``invalid-feedback`` element in its closest
``data-ie-field-wrapper``, ``data-ie-group-control`` or ``.form-check`` wrapper.
Modal, toast and compatibility-template elements must remain inside the
component root. All DOM lookups are scoped to that root, so multiple components
remain independent.

JavaScript tests
================

The standalone Jest/jsdom project in
:file:`Resources/Public/Development/` covers the frontend entry modules and
their exported helpers. TYPO3-provided CKEditor imports are represented by
local test doubles, so no TYPO3 instance is required. Run it independently:

..  code-block:: bash

    cd packages/fgtclb/academic-persons-edit/Resources/Public/Development
    npm i
    npm test

``npm run test:coverage`` additionally creates the HTML report in
:file:`Resources/Public/Development/coverage/`.

Tests
=====

Controller unit tests cover method, payload and authentication errors for the
JSON actions. Sanitizer unit tests cover the supported profile properties,
allowed editor markup and rejection of scripts, event attributes, styles,
unknown tags and unsafe link schemes. Validation-service unit tests verify that
sanitization happens before a value is registered for persistence.

Functional plugin tests render both Bootstrap modal states, verify the
decomposed Fluid contracts, AJAX-only controls, direct rich-text previews and
the separate delete, cancel and save actions. The section-provider unit test
verifies that order, identifiers, field names, relation types and labels come
from ``AcademicPersonsSettings`` while presentation modes and typed records are
preserved. Functional fixtures cover contracts and every configured
profile-information relation; the rendered page test derives the expected
order and metadata from the same settings service, then checks placement below
:guilabel:`About me`, alternating records, empty states, passive sorting
metadata and the absence of write controls. It also guards the InlineProfile
plugin against accidentally exposing legacy mutation controllers. Registration
tests ensure that InlineProfile is the only editing content element offered to
editors while the complete legacy implementation remains present as a
temporary source reference. The architecture unit test scans the InlineProfile
controller, Fluid tree and JavaScript for forbidden legacy controller, template
and plugin references.

The AJAX tests persist malicious rich-text input through the real update
endpoint and assert that only the sanitized response is stored. The inline
image tests verify that a missing file can never return success and that a real
multipart upload returns ``hasImage: true`` and creates the FAL relation. They
also exercise the dedicated image deletion endpoint through the generated
action URL. Form submissions reuse the complete rendered action URL, including
the JSON page type, so the tests exercise the same routing contract as the
browser. Upload tests are assigned to the ``not-core-13`` PHPUnit group because
TYPO3 v13's CLI upload permission check requires a real HTTP upload.
