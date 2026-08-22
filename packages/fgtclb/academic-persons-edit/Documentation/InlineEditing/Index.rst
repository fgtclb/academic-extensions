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

The frontend module
:file:`Resources/Public/JavaScript/frontend/profile.js` initializes every
``data-academic-persons-inline-edit`` component on the page. All changes are
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
    *   - ``{genderOptions}``
        - Associative select options from the profile gender TCA field. TCA
          values are option keys and translated labels are option values.
    *   - ``{validations}``
        - Effective ``profile`` validation set. It controls required,
          disabled, read-only and input-type attributes.
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
          the bulk edit button.
    *   - ``Forms/*``
        - Personal-data and about-section form boundaries plus the retained
          bulk footer actions.
    *   - ``Sections/*``
        - Personal, link and rich-text field composition in display order.
    *   - ``Field.html``, ``Field/Group.html`` and ``Field/*``
        - Shared field orchestration, plain read previews, grouped fields,
          textfield/textarea controls, gender select and per-field actions.
    *   - ``Header.html`` and ``StatusToast.html``
        - Personal-section heading, synchronization/edit controls and scoped
          status output.
          ``ButtonTemplates.html`` remains a compatibility fallback for
          existing template overrides; the shipped read view does not use its
          button-shaped value controls.

Layout and responsive behavior
==============================

The view uses only Bootstrap 5 grid, spacing, typography, background,
positioning and form utilities. It ships no extension-specific stylesheet and
the Fluid templates contain no inline style declarations.

On ``lg`` and larger viewports the first row uses a ``4 / 8`` column split.
The profile image block has ``sticky-top`` so the image and its edit action
stay visible while the profile data scrolls. Below ``lg`` both columns stack in
document order. The about section follows the complete first row and therefore
never overlaps the sticky column.

At runtime ``initializeStickyImageOffset()`` reads the visible outer height of
``#page-header`` through ``getBoundingClientRect().height`` and assigns that
pixel value to the ``top`` property of ``data-ie-sticky-image``. A
``ResizeObserver`` watches the header's ``border-box`` and keeps the offset
synchronized whenever the navbar changes height, including height or padding
changes caused by a scroll-dependent header state. Environments without it use
the window ``resize`` event as a fallback. If the page header is absent,
Bootstrap's regular ``sticky-top`` value remains in control.

The two columns live in their own ``align-items-stretch`` row. The image column
inherits the stretched cross-axis size, giving the sticky image a containing
block as tall as the adjacent profile data. The full-width about section keeps
its own ``col-12`` in a separate sibling row below it.

The complete profile name is the page's ``h1`` above the image and replaces the
former :guilabel:`Profile image` heading. The heading uses
``data-ie-profile-name-field-ids`` so a successful inline name update is shown
without reloading the page.

Profile values are rendered as readable text rows with alternating
``bg-body-tertiary`` surfaces. The only read-mode action is a borderless pencil
button with an accessible label. Name components and the URL/title pair of
each link share one preview row and open as one inline-edit group.

Supported controls
==================

The :file:`Resources/Private/Partials/InlineProfile/Field.html` partial
resolves validation, element ID and input type before composing three focused
partials:

*   :file:`Field/Preview.html` renders the text preview and pencil trigger,
*   :file:`Field/Control.html` renders either ``f:form.textfield`` or
    ``f:form.textarea``, including the CKEditor hook, and
*   :file:`Field/Actions.html` renders delete, cancel and save.

:file:`Field/Group.html` composes related textfields below one preview. Its
``data-ie-field-ids`` value defines which fields open, cancel and save together;
``data-ie-display-field-ids`` and ``data-ie-display-mode`` control whether the
preview joins values (the name) or uses the first non-empty value (link title
falling back to its URL).

``validation.inputType`` or the explicit ``inputType`` argument can select HTML
input types such as ``tel`` and ``url``. Setting ``textarea`` renders a
textarea instead. The dedicated :file:`Field/Gender.html` partial handles the
select field without an action group. Its ``change`` event saves immediately
through the generic update endpoint. :file:`Settings/Sync.html` contains the
separately persisted checkbox.

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
        - ``f:form.select`` with the
          ``academic-persons-inline-edit__field`` class. Gender additionally
          uses ``data-ie-autosave-on-change``.
    *   - Checkbox
        - The synchronization control uses
          ``academic-persons-inline-edit__sync-checkbox`` and its dedicated
          form.
    *   - Multiline text
        - Field partial with ``textarea: true``. Passing ``richText: true``
          additionally turns the textarea into the TYPO3 CKEditor 5 when the
          field is opened.

Generic field update
====================

The URL is generated by ``f:uri.action`` for ``updateAction()`` with page type
:php:`1733735`. Requests must use ``POST`` and
``Content-Type: application/json``. Only values changed since the last
successful save are sent. An empty string clears a property; omitted
properties remain unchanged.

..  code-block:: json
    :caption: Partial profile update

    {
      "profile": 123,
      "data": {
        "firstName": "Jane",
        "website": ""
      }
    }

Unknown profile properties and gender values not configured in TCA are
rejected. Extbase validation errors are returned in an ``errors`` object keyed
by property path and are mapped back to the corresponding form controls by the
frontend module.

``ProfileGenderOptionsService::getAllowedValues()`` reads only the non-empty TCA
values required for strict validation. ``getOptions()`` additionally resolves
the labels for the Fluid select. Keeping validation independent of localization
also keeps the service usable in isolated unit tests.

Rich-text content fields
========================

Five profile properties are rich-text fields. Four are displayed with the
personal data, while ``miscellaneous`` is the :guilabel:`About me`
description:

*   ``coreCompetences``,
*   ``teachingArea``,
*   ``supervisedDoctoralThesis``,
*   ``supervisedThesis``, and
*   ``miscellaneous``.

The editor is TYPO3's shipped CKEditor 5 from the
``typo3/cms-rte-ckeditor`` package. The frontend module imports its CKEditor
modules through TYPO3's JavaScript import map; it does not load an editor from
a CDN. An editor instance is created lazily when a rich-text field is opened.
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

Each open text or rich-text field has three explicit actions. Gender is the
exception and saves immediately when its select value changes.

*   :guilabel:`Delete` (``data-ie-dismiss``) clears the current browser-side
    draft. The editor stays open and no request is sent.
*   :guilabel:`Cancel` (``data-ie-cancel``) restores the last successfully
    persisted value and closes only that field. No request is sent.
*   :guilabel:`Save` (``data-ie-save``) sends that field through the JSON AJAX
    endpoint. It closes the field only after a successful response or when
    there is no changed value to persist.

The action group uses Bootstrap utility classes to remain content-sized and
aligned to the start of the editor instead of stretching to the CKEditor
height. No additional stylesheet or inline style is required. The bulk
:guilabel:`Edit all` button beside the personal-data heading opens both regular
fields and grouped rows. The bulk :guilabel:`Cancel` button has the separate
``data-ie-cancel-all`` hook; it restores all last successfully persisted values
and closes the editors.

The pencil is rendered through TYPO3's ``core:icon`` ViewHelper. Template
overrides may replace the icon but must retain the button's edit hook,
``data-ie-for`` target and accessible label. The profile value itself must not
be placed back inside the button.

Server-side sanitization
------------------------

Client-side editor configuration is not treated as a security boundary.
``ProfileUpdateValidationService`` accepts an explicit list of editable
properties and passes the five rich-text values through
``ProfileRichTextSanitizer`` before validation and persistence. The sanitizer
uses TYPO3's allow-list based HTML sanitizer and permits only:

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

The extension requires at least TYPO3 13.4.31 or TYPO3 14.3.6. These constraints
include the HTML-sanitizer fixes published with TYPO3-CORE-SA-2026-006. Projects
must still keep TYPO3 security updates current.

Synchronization checkbox
========================

The synchronization checkbox appears as the compact :guilabel:`Private` switch
immediately left of :guilabel:`Edit all` in the personal-section header and is
persisted immediately through ``updateSkipSyncAction()``. Its form is a sibling
of the profile form, not a nested form. The visual label follows the supplied
profile-page design; the underlying data and endpoint semantics remain
``skipSync``. It does not submit or mutate any other field. The endpoint accepts
exactly one boolean property:

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

The upload action propagates non-successful JSON responses out of TYPO3's
Extbase ``USER`` content rendering with ``PropagateResponseException``. A
``JsonResponse`` returned by the action alone would contribute its body to the
surrounding ``PAGE`` object while the outer frontend response retained status
``200``. Propagation therefore preserves the documented ``422`` and ``500``
HTTP status codes for the AJAX client.

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
endpoints return machine-readable errors directly. Image upload validation is
also converted to JSON by the controller's error action.

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
        - Generic field forms and controls. The first form owns the bulk action;
          additional forms preserve valid markup in separate grid sections.
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
          as its runtime ``top`` offset.
    *   - ``data-ie-field-group``, ``data-ie-field-ids`` and
          ``data-ie-display-field-ids``
        - Grouped preview/editor and the controls participating in it.
    *   - ``data-ie-group-edit``, ``data-ie-group-dismiss``,
          ``data-ie-group-cancel`` and ``data-ie-group-save``
        - Open, clear the draft, restore and persist a grouped field row.
    *   - ``data-ie-field-actions``
        - Content-sized Bootstrap group for the three per-field actions.
    *   - ``data-ie-autosave-on-change``
        - Saves the gender select immediately after a changed selection.
    *   - ``data-academic-persons-inline-edit-edit-all-btn``
        - Opens all editable single fields and grouped rows.
    *   - ``data-ie-dismiss``
        - Deletes the current draft value without closing or saving it.
    *   - ``data-ie-cancel``
        - Restores the last persisted value and closes one field without a
          request.
    *   - ``data-ie-save``
        - Persists one field through the generic JSON endpoint.
    *   - ``data-ie-cancel-all``
        - Restores all persisted field values and closes the bulk editor.
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

Tests
=====

Controller unit tests cover method, payload and authentication errors for the
JSON actions. Sanitizer unit tests cover the supported profile properties,
allowed editor markup and rejection of scripts, event attributes, styles,
unknown tags and unsafe link schemes. Validation-service unit tests verify that
sanitization happens before a value is registered for persistence.

Functional plugin tests render both Bootstrap modal states, verify the
decomposed Fluid contracts, AJAX-only controls, direct rich-text previews and
the separate delete, cancel and save actions. They persist malicious rich-text
input through the real update endpoint and assert that only the sanitized
response is stored. The inline image tests verify that a missing file can never
return success and that a real multipart upload returns ``hasImage: true`` and
creates the FAL relation. They also exercise the dedicated image deletion
endpoint through the generated action URL. Form submissions reuse the complete
rendered action URL, including the JSON page type, so the tests exercise the
same routing contract as the browser. Upload tests are assigned to the
``not-core-13`` PHPUnit group because TYPO3 v13's CLI upload permission check
requires a real HTTP upload.
