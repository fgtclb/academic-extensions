..  index:: Inline editing, AJAX, CKEditor, JSON, Profile image, Rich text
..  _inline-profile-editing:

======================
Inline profile editing
======================

The :guilabel:`Inline profile editing` content element first renders all
profiles assigned to the authenticated frontend user. Its :guilabel:`Edit`
action opens the selected profile in the inline editor; :guilabel:`View` opens
the public ``academic_persons`` Detail plugin on the page configured through
``plugin.tx_academicpersons.detailPid``. This is the same target setting used
by the Academic Persons list views. The shipped
:file:`Resources/Private/Templates/InlineProfile/Index.html` editor template
contains three independently persisted areas:

*   profile fields using the generic JSON update endpoint,
*   the synchronization checkbox using its own JSON endpoint, and
*   the expanding profile-image editor using dedicated upload and delete
    endpoints.

The Vue 3 Composition API entry is maintained as TypeScript in
:file:`Resources/Private/TypeScript/frontend/profile.ts`; the frontend build
generates :file:`Resources/Public/JavaScript/frontend/profile.js`. It mounts
one application on every ``data-academic-persons-inline-edit`` component.
Typed feature modules below :file:`Resources/Private/TypeScript/frontend/profile/`
separately own common requests/status output, field editing, documents, rich
text, synchronization, image editing and sticky positioning. All changes are
saved through AJAX without reloading the page. Editable fields are discovered
across the complete component root, even when the responsive page layout
places them in separate ``data-ie-fields-form`` elements. Reactive inline
views, the toast and compatibility-template elements live in the same
component scope.

Assigned profile overview
=========================

``InlineProfileController::listAction()`` is the default action. It uses the
authenticated frontend-user relation and passes ``{profileListItems}`` to
:file:`Resources/Private/Templates/InlineProfile/List.html`. Every item contains
the Profile object and the title of its ``sys_language_uid`` from the current
site configuration. The profile column contains a square thumbnail or the
shipped placeholder followed by the complete name. :guilabel:`Edit` passes the
explicit ``profileUid`` to ``indexAction()``; the action resolves that UID again
through the authenticated user's assigned profiles before rendering anything.
An unassigned or manipulated UID receives the ``403`` access denied response of
the site, the same one an unauthenticated visitor gets — raised as a propagated
response rather than returned by the action, because on TYPO3 v13 the status
code of an Extbase plugin response never reaches the frontend response.

The :guilabel:`View` URI uses extension ``academicpersons``, controller
``Profile``, action ``detail`` and plugin ``Detail``. Its target page comes
from ``plugin.tx_academicpersons.detailPid`` and must contain the
``academicpersons_detail`` content element. The InlineProfile TypoScript copies
the Academic Persons constant into ``plugin.tx_academicpersonsedit.settings``;
there is no separate editor-specific detail PID. Importing
:file:`EXT:academic_persons/Configuration/Routes/Detail.yaml` is optional and
turns the query-string link into a speaking URI.

View data
=========

The controller assigns the following variables to the Fluid template:

..  list-table::
    :header-rows: 1

    *   - Variable
        - Description
    *   - ``{profile}``
        - Explicitly selected profile after its assignment to the authenticated
          frontend user has been verified.
    *   - ``{profileListItems}``
        - Assigned Profiles and their readable site-language labels. This
          variable belongs to the default list action and list template.
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
    *   - ``Image/Card.html`` and ``Image/View.html``
        - :guilabel:`Profile image` heading above the sticky page preview,
          animated full-width editor, file selection, crop preview and image
          actions.
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
        - Structured document rows and their shared Vue-driven inline collapse.
    *   - ``Field.html``, ``Field/Group.html`` and shared ``Field/*``
        - Preview, control, grouped fields and per-field actions.
    *   - ``Header.html`` and ``StatusToast.html``
        - Complete profile-name heading with synchronization/edit-all controls,
          and scoped status output. The personal form renders its own
          :guilabel:`Personal data` heading.
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
All Bootstrap button controls in the shipped inline view and the retained
legacy modal surfaces carry ``rounded-0`` so their corners remain square.

Above the grid, the complete profile name and the synchronization/edit-all
controls share one responsive header row. The controls wrap below the name on
narrow viewports. On ``lg`` and larger viewports the first content row uses a
``4 / 8`` column split.
The profile image block has ``sticky-top`` so the image and its edit action
stay visible while the profile data scrolls. Below ``lg`` both columns stack in
document order. The about section follows the complete first row and therefore
never overlaps the sticky column.

At runtime ``initializeStickyImageOffset()`` reads the visible outer height of
``#page-header.navbar-fixed-top`` through ``getBoundingClientRect().height``,
adds a 10-pixel visual gap and assigns the result to the ``top`` property of
``data-ie-sticky-image``. A
``ResizeObserver`` watches the header's ``border-box`` and keeps the offset
synchronized whenever the navbar changes height, including height or padding
changes caused by a scroll-dependent header state. Environments without it use
the window ``resize`` event as a fallback. If the matching fixed page header
is absent, Bootstrap's regular ``sticky-top`` value remains in control.

The two columns live in their own ``align-items-stretch`` row. The image column
inherits the stretched cross-axis size, giving the sticky image a containing
block as tall as the adjacent profile data. The full-width about section keeps
its own ``col-12`` in a separate sibling row below it.

The complete profile name is the page's ``h1`` above both columns. Both Fluid and
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

A translated profile initially uses TYPO3's ``parent`` localization state for
the image and follows changes to the default-language image. The first upload
or deletion in that frontend language changes the image field to ``custom``;
the translated profile then owns its FAL reference and can select, replace or
delete its image independently. The image controls therefore remain available
while editing a translated profile; the endpoint still verifies that the
requested profile is assigned to the authenticated frontend user.

The shared
:file:`Resources/Private/Partials/InlineProfile/Field/Editable.html` partial
composes three focused, reusable partials:

*   :file:`Field/Preview.html` renders the text preview and pencil trigger,
*   :file:`Field/Control.html` renders either ``f:form.textfield`` or
    ``f:form.textarea``, including the CKEditor hook, and
*   :file:`Field/Actions.html` renders delete, cancel and save.

:file:`Field/Group.html` composes related textfields below one preview. Its
``data-ie-field-ids`` value defines which fields open, cancel and save together;
``data-ie-display-field-ids`` and ``data-ie-display-mode`` control whether the
preview joins values (the name) or uses the first non-empty value (link title
falling back to its URL).

Helptext buttons are edit controls: field and group previews do not render
them. They appear after the corresponding inline editor is opened. Document
helptexts follow the same rule and are present in add/edit forms, but not in a
document inline view opened in view mode.

``validation.inputType`` supplies the concrete HTML input type. Checkbox
controls save immediately on change. Select controls use the same clear, undo
and save actions as text fields. The synchronization switch in
:file:`Header.html` is persisted through its own endpoint.

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

Profile fields may configure a positive ``characterLimit`` next to
``renderType: ckeditor``. Fluid then adds the limit metadata and an accessible
live counter to that field. The shared rich-text module counts normalized
visible text, keeps the last accepted value when an addition would exceed the
limit and still allows older over-limit content to be shortened. The Extbase
validator checks the submitted sanitized value again before the partial update
is persisted. The shipped ``miscellaneous`` field uses a limit of 1000.

Outside edit mode, each rich-text field renders its formatted content directly
and provides a borderless pencil action on the right. Empty values show a
localized placeholder. The preview is initially rendered through TYPO3's
HTML formatting pipeline and is replaced after a successful save with the
sanitized markup returned by the server. The frontend applies the same strict
tag, attribute and URI-scheme allowlist without assigning markup through
``innerHTML``.

Each open text or rich-text field has three explicit actions. For
``renderType: ckeditor`` only, the action group sits in the label row with
Bootstrap's ``ms-auto`` utility, leaving the editor itself full width. Other
field types retain the action group beside their control. Selects and checkboxes
save immediately when their value changes.

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
:guilabel:`Edit all` toggle beside the page heading opens both regular
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

Editable structured profile sections
=====================================

The inline profile view renders the structured records directly below the
:guilabel:`About me` field. ``ProfileDocumentSectionProvider`` supplies one
ordered view model instead of duplicating relation mapping in Fluid. It reads
all sections, including ``contracts``, from the shared settings graph. That
graph is built from the active packages'
:file:`Configuration/AcademicPersons/Settings.yaml` files, so configured order
is also presentation order. The same graph supplies the public profile,
frontend validation and backend TCA metadata.

For every document section the provider reuses the configured ``identifier``,
``fieldName``, record ``type``, LLL ``label``, ``readonly`` state,
``rowFields``, ``actions`` and the section-local validations. The heading is
translated directly from that label.
A newly configured type consequently does not require another section registry
in ``academic_persons_edit``. The generic localized empty state is used until
an identifier-specific message is added.

``contracts`` contains ``FGTCLB\AcademicPersons\Domain\Model\Contract``
objects. Every other collection contains
``FGTCLB\AcademicPersons\Domain\Model\ProfileInformation`` objects. Contract
rows show the configured values, which are start date and position in the
shipped settings. Profile-information rows render only the configured values in
their declared order. The aliases ``from``, ``to`` and ``description`` map to
``yearStart``, ``yearEnd`` and ``bodytext``. All sections remain visible when
empty and display a localized empty state.

When a section validation marks ``from``, ``to`` or ``year`` with the ``date``
flag, the corresponding add/edit control is an HTML date picker. The complete
selected date is passed as :php:`\DateTime` and persisted in a nullable native
:sql:`DATE` column. The legacy property names ``year``, ``yearStart`` and
``yearEnd`` remain for compatibility, but no time or time zone is stored. The
three date controls and the year-only checkbox each use ``col-12 col-md-3`` and
share one responsive row on medium and larger viewports. Their HTML and
server-side required states come from the same validation set: only a field
with the additional ``required`` flag must be filled. In the shipped settings
this applies to ``year`` but not to ``from`` or ``to``.

The same inline view contains a :guilabel:`Show year only` switch. It is stored
on the profile-information record and changes the compact row, read view and public
profile to render ``Y`` instead of the complete date. The underlying native
:sql:`DATE` values are not modified.

Why Vue creates controls in the inline view
--------------------------------------------

Fluid renders one reusable collapse shell with Vue directives for fields,
buttons, errors and pending state. The reactive document controller teleports
this shell below the selected section heading for add actions and into the
selected record row for view, edit and delete actions. Exactly one document
collapse is open at a time, while the complete profile view remains visible.
When an action is opened, the
permission-checked endpoint returns JSON containing the selected record,
localized labels, values, select options and normalized validation metadata.
Vue turns that runtime schema into controls through ``v-for`` and ``v-model``
and manages the CKEditor lifecycle. This avoids pre-rendering one hidden form
per section and record while keeping static structure in Fluid and mapping
validation errors directly back to the returned field names.

Activating the same add or view trigger a second time closes its collapse with
the same cleanup as :guilabel:`Cancel`. A hidden ordinary DOM container supplies
the Fluid-rendered row prototype used after creation. It deliberately is not an
HTML ``template`` element: Vue consumes such template content while mounting,
which would leave no row to clone after a successful create response.

``contract`` is retained as a separate reactive document kind. It uses the
generic collapse renderer for its configured fields and appends three
contract-specific contact sections in the read view: physical addresses,
email addresses and phone numbers. Their field schemas and validation flags
come from ``contracts.contactSections.<section>.fields`` in the shared settings
graph. The Contract form itself follows the order and metadata in
``contracts.fields``.
The shared contact-editor partial is rendered below the contact-section heading
for add actions and directly inside the selected contact row for view, edit and
delete actions. The two placements use explicit Vue conditions and do not nest
another teleport inside the document editor.

Every writable section heading has an :guilabel:`Add` action. Record controls
are rendered in the exact order of the configured ``actions`` list. The first
row's move-up action and the final row's move-down action are disabled. A list
with both directions also has a drag handle; dropping a row submits the complete
UID order and the server accepts it only when it contains every current section
record exactly once. After a successful mutation JavaScript updates the row
collection, alternating background, sort controls and empty placeholder without
reloading the page. The drag handle is hidden below Bootstrap's ``md``
breakpoint; the explicit up/down controls remain available on mobile.

The add, view, edit and delete workflows share one inline Vue collapse. Its field
schema and current values are loaded through ``documentFormAction()``. Contract
fields include the current organisational-unit, function-type and location
options. Profile-information fields use the section's validation metadata. In
every mode the view heading uses the non-empty ``title`` field of the current
record. New records, contracts and records without a title fall back to the
translated section heading; the mode label remains as its prefix.
A field carrying the ``html`` flag is rendered as a full-width CKEditor 5
control; ordinary textareas are full width as well. Rich-text values are
sanitized before persistence and parsed into the row or read-only view through
the frontend sanitizer before Vue receives the HTML.
When that field's ``editor.limit`` is a positive integer, the textarea carries
the normalized limit and the view renders an accessible live character
counter below CKEditor. The count uses normalized visible text rather than the
stored HTML. CKEditor rejects additions past the limit while still allowing an
older over-limit value to be shortened. The JSON endpoint and the Extbase
form-data validator independently reject over-limit submissions, so client-side
code is not the security boundary. Character limits do not alter backend TCA or
the database schema; the shared required, readonly and field-type metadata does.
The document pending state is released before CKEditor is initialized, because
CKEditor deliberately skips disabled controls.
Every JSON request increments one shared busy counter. While at least one
request is active, the document body exposes ``aria-busy="true"`` and the wait
cursor; the final request restores the previous cursor and sets
``aria-busy="false"`` in a ``finally`` path. Document, contact and image editor
containers mirror that state locally. This keeps failures and concurrent
requests from leaving a stale loading state.

Opening a different document replaces only the in-memory editor schema and
values after the new form has loaded. It never calls the submit action: document
forms have no blur, change, teardown or focus-loss save hook. Only their
explicit save/delete submit control persists a mutation. Focus moves into the
first writable control (or the read/delete heading), returns to the action that
opened a closed editor and remains on native buttons throughout keyboard
sorting. Drag sorting is an optional pointer enhancement; the up/down actions
remain the complete keyboard path.

All generated controls keep an explicit label. Validation errors have stable
IDs referenced through ``aria-describedby`` and update ``aria-invalid``;
dynamic editor headings and expanded controls expose their relationships via
``aria-controls`` and ``aria-expanded``. The inline editors are not modal
dialogs and deliberately do not trap focus.
When the view enters delete mode, its submit control removes ``btn-primary``
and ``btn-success`` and uses ``btn-danger``. All other writable modes restore
``btn-primary``.

``createDocumentAction()``, ``updateDocumentAction()``,
``deleteDocumentAction()`` and ``sortDocumentAction()`` complete the document
JSON API.
Up, down and drag-and-drop intentionally share the sort endpoint. All endpoints
resolve the profile from the authenticated frontend user and then resolve a
record only inside the requested section. A UID from another profile, model kind
or profile-information type is therefore rejected. They additionally enforce
``readonly`` and the configured action list. The shipped contracts section is
writable and exposes the complete configured Contract field set.

The dedicated ``contractContactForm``, ``createContractContact``,
``updateContractContact``, ``deleteContractContact`` and
``sortContractContact`` actions operate below a Contract resolved through the
authenticated Profile. A contact UID from another Contract is rejected. New
contacts are appended with a normalized sorting value; the up/down controls
persist their order independently in each contact section. The first and last
controls are disabled at their respective boundaries. Read-only Contract
configuration blocks every contact mutation at the endpoint as well.
Contract and contact helptexts use the same edit-only Bootstrap popovers as
other inline fields. Physical-address countries are selected from localized
TYPO3 ``CountryProvider`` options and persisted as ISO alpha-2 codes; submitted
values outside that list are rejected by the endpoint.

The InlineProfile plugin still registers only ``InlineProfileController``;
the legacy contract, profile-information and contact controllers remain source
references and are not exposed through its normal or non-cacheable action
maps.

Section order is centralized and every section emits ``data-section-key`` and
``data-section-position`` together with the configured
``data-section-field-name`` and ``data-section-record-type``. Records
additionally emit ``data-item-uid``, ``data-item-sorting`` and
``data-item-position``. ``data-section-sortable`` exposes whether both sorting
directions are available. The explicit up/down controls and drag handle persist
the same record order.

The presentation uses Bootstrap rows with one shared desktop column heading,
compact flat records, separating borders and alternating
``bg-body-tertiary`` backgrounds within each document section. The date columns
remain narrow while title and position columns consume the available width.
On small viewports every record repeats its field labels instead of rendering
the desktop heading. An empty section keeps its heading and add action,
followed by one unobtrusive translated status line. During drag sorting the
browser uses the complete record row as the drag image.
Extension-specific state classes outline both the source row and active list,
while a prominent line above or below the hovered row marks the exact insertion
edge. Dropping into free list space shows the same line at the end of the list.

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
remain in the source tree temporarily so their previous behavior can be
consulted without reconstructing it. They are not delivered through a site
set, an aggregate static template or selectable page TSconfig. This reference
code is not an implementation source for InlineProfile.

The InlineProfile TypoScript, AJAX page type, site set and page TSconfig live in
their own ``Configuration/*/InlineProfile`` components. InlineProfile is the
only component enabled through either its component configuration or the
aggregate.

The InlineProfile functional test setup reflects the same boundary. It uses a
dedicated ``academicpersonsedit_inlineprofile`` fixture and the neutral
``AbstractFrontendProfilePluginTestCase`` base. It does not create a
``ProfileEditing`` record and change its CType afterwards.

Synchronization checkbox
========================

The synchronization checkbox appears as the compact
:guilabel:`Private` switch
immediately left of the :guilabel:`Edit all`/:guilabel:`Close all` toggle in
the page header and is persisted immediately through
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

Expanding profile image editor
==============================

Clicking the compact edit button below the current profile image or its
placeholder keeps the profile view active. Vue's ``Teleport`` renders
:file:`Partials/InlineProfile/Image/Editor.html` into a dedicated full-width
target above the profile header. The header, profile fields and structured
sections remain in the same profile flow below the cropper; no
separate view, modal or overlay is involved. While the editor is open, the
complete image-preview column is hidden and the profile-fields column animates
from ``col-lg-8`` to ``col-lg-12``. The editor itself uses a bordered, padded
surface. Closing it scrolls the restored image-preview column into view and
focuses its edit action without causing a second browser scroll. The scroll is
started only after Vue has completed the leave transition and two animation
frames have applied the final page layout. A ``1.5rem`` scroll margin keeps the
restored preview clear of the viewport edge.

Vue's ``Transition`` animates the teleported editor with a short vertical move
and fade. Its explicit CSS grid row also expands and collapses the editor height,
padding, margin and border instead of removing the complete block in one layout
step. Because the target already has its final width when it is inserted, the
cropper can initialize immediately with the complete available width. The open
scroll leaves ``2rem`` above the editor. Closing the editor removes the
teleported content and restores focus to the image edit action. The return
scroll starts together with the collapse and uses the preview's calculated
final position. Native scroll anchoring is disabled only during this phase, so
it cannot introduce a competing correction. Environments requesting reduced
motion skip the transition.

The full-width ``btn-sm`` edit action sits directly below the preview. Its
visible label and camera icon are complemented by localized ``title`` and
``aria-label`` attributes.

The image editor deliberately has no state-dependent :guilabel:`Add` or
:guilabel:`Replace` action. Selecting a file immediately replaces only the
inline crop preview with a local object URL. The page preview and persisted profile
remain unchanged until :guilabel:`Save` succeeds. A successful upload replaces
both previews, collapses the editor and shows the saved image directly. During
the leave transition, the preview remains hidden and the profile fields retain
their full width. The regular ``4 / 8`` grid is restored only after the editor
has finished collapsing, preventing competing layout changes while the page
returns to the image preview.
:guilabel:`Cancel` discards the selected preview and restores the persisted
image. :guilabel:`Delete` is shown only while an image is persisted.

If ``special.image.renderType`` is ``cropper`` and
``special.image.settings.ratio`` contains a positive ratio such as ``1x1`` or
``16:9``, the editor instantiates the locally packaged CropperJS 2.2 module. The
selection remains constrained to that ratio and only the generated cropped
file is added to the multipart request. No CDN or other runtime request is
used. The ratio remains fixed while the selection can be dragged to choose the
visible image area. The cropper is instantiated only after a new local file is
selected. A persisted profile image and the placeholder remain normal previews
and cannot themselves become crop input. The original upload behavior remains
active for every other render type.

The save button remains disabled until a new local file is selected. This also
prevents an already persisted image from being fetched, cropped and uploaded
again. While an upload or deletion is pending, all image controls are disabled
and the active action displays a Bootstrap spinner. This prevents duplicate
requests and leaving the inline view during a running operation.

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
After persistence, the ordered non-empty values ``title``, ``firstName``,
``middleName`` and ``lastName`` are joined with spaces. That composed profile
name is written to ``alternative`` and ``title`` in both the FAL file metadata
and the profile's file-reference overlay. The JSON response returns the same
values so the in-page preview immediately uses the persisted metadata.

The inline view does not render ``f:form.validationResults``. Upload validation
failures are returned as JSON and displayed in an alert inside the active view.
The controller additionally compares the submitted image with the
persisted FAL reference. It returns ``image_upload_missing`` with status
``422`` instead of reporting success when no new file arrived.

The cropper preserves JPEG, PNG and WebP as output formats and falls back to
PNG for unsupported source types. Every upload remains a separate FAL file.
When a profile already has exactly one independent image relation, replacing
the image keeps the ``sys_file_reference`` uid but assigns the newly uploaded
file to it through DataHandler and persists ``image=custom`` in the profile's
``l10n_state``. The old physical file is removed only after no active reference
uses it anymore. Localized or duplicate legacy references are rebuilt as one
independent relation, so changing one language cannot overwrite another
language's custom image.

The upgrade wizard
``academicPersonsEdit_repairLocalizedProfileImages`` repairs legacy shared,
localized or duplicate image relations and inconsistent relation counters. It
copies a shared physical file for the translated profile, rebuilds every
relation through DataHandler and removes files only when they are unreferenced.

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
grid and composition. The ``Profile``, ``Documents``, ``Image`` and ``Field``
directories group the corresponding UI responsibilities; the status toast and
client-side button templates remain shared at the root. Keep the following
contracts when reusing the shipped JavaScript:

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
        - Sticky image container receiving the measured
          ``#page-header.navbar-fixed-top`` height plus a 10-pixel visual gap
          as its runtime ``top`` offset.
    *   - ``data-ie-document-sections`` and ``data-ie-document-section``
        - Editable structured-section list and stable boundary for its AJAX
          controls.
    *   - ``data-section-key`` and ``data-section-position``
        - Stable section identity and current zero-based presentation position.
    *   - ``data-section-field-name`` and ``data-section-record-type``
        - Field and relation type taken from the shared settings graph for
          section-specific persistence.
    *   - ``data-ie-document-items`` and ``data-ie-document-item``
        - Mutable item collection and record boundaries inside a section.
    *   - ``data-ie-document-item-template``
        - Hidden Fluid-rendered row prototype retained in the mounted DOM and
          cloned after a successful create response.
    *   - ``data-ie-document-add-collapse-target`` and
          ``data-ie-document-item-collapse-target``
        - Teleport destinations below a section heading and inside an individual
          record row. The document controller assigns a unique ID when a target
          is first opened.
    *   - ``data-item-uid``, ``data-item-sorting`` and ``data-item-position``
        - Persisted record identity, domain sorting value and current zero-based
          presentation position.
    *   - ``data-ie-document-empty-state``
        - Localized placeholder rendered when a structured collection is empty.
    *   - ``data-ie-document-add``, ``data-ie-document-view``,
          ``data-ie-document-edit`` and ``data-ie-document-delete``
        - Section creation and in-place row actions.
    *   - ``data-ie-document-sort``
        - Up/down row action persisted through the shared sort endpoint.
    *   - ``data-ie-document-view-container`` and ``data-ie-document-form``
        - Scoped reactive collapse and form used for add, view, edit and delete.
    *   - ``data-ie-field-group``, ``data-ie-field-ids`` and
          ``data-ie-display-field-ids``
        - Grouped preview/editor and the controls participating in it.
    *   - ``data-ie-group-edit``, ``data-ie-group-dismiss``,
          ``data-ie-group-cancel`` and ``data-ie-group-save``
        - Open, clear the draft, restore and persist a grouped field row.
    *   - ``data-ie-field-actions``
        - Content-sized Bootstrap group for the three per-field actions.
    *   - ``data-ie-autosave-on-change``
        - Saves configured checkbox controls immediately after a change.
    *   - ``data-ie-autosave-undo`` and ``data-ie-cancel``
        - Marks the undo action beside an editable checkbox. It restores the
          last successfully persisted value and closes the editor without
          sending another request. Select fields use the regular clear, undo
          and save action group.
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
          ``data-ie-image-view-container``
        - AJAX-only multipart upload form and its teleported Vue editor.
    *   - ``data-ie-image-editor-target``
        - Profile-specific full-width destination for the Vue ``Teleport``.
    *   - ``data-ie-image-preview`` and
          ``data-ie-image-view-preview``
        - Image locations updated after upload or deletion.
    *   - ``data-image-render-type`` and ``data-image-cropper-ratio``
        - Render type and ratio consumed by the local CropperJS host in the
          image editor.
    *   - ``data-ie-status-toast``
        - Scoped status feedback for the component.

Every editable field needs one ``invalid-feedback`` element in its closest
``data-ie-field-wrapper``, ``data-ie-group-control`` or ``.form-check`` wrapper.
Inline collapse targets, views, toast and compatibility-template elements must
remain inside the
component root. All DOM lookups are scoped to that root, so multiple components
remain independent.

Frontend build
==============

The package has no separate development toolchain below
:file:`Resources/Public/`. TypeScript sources and committed JavaScript output
use the repository-wide frontend suites from the repository root:

..  code-block:: bash

    Build/Scripts/runTests.sh -s buildJs
    Build/Scripts/runTests.sh -s checkJsBuildClean
    Build/Scripts/runTests.sh -s lintTypescript -n
    Build/Scripts/runTests.sh -s typecheckJs

Tests
=====

Controller unit tests cover method, payload and authentication errors for the
JSON actions. Sanitizer unit tests cover the supported profile properties,
allowed editor markup and rejection of scripts, event attributes, styles,
unknown tags and unsafe link schemes. Validation-service unit tests verify that
sanitization happens before a value is registered for persistence.

Functional plugin tests render the Vue inline-collapse targets and image view,
verify the
decomposed Fluid contracts, AJAX-only controls, direct rich-text previews and
the separate delete, cancel and save actions. The section-provider unit test
verifies that order, identifiers, field names, relation types and labels come
from the shared settings graph while row fields, action capabilities,
presentation modes and typed records are preserved. Functional fixtures cover
contracts and every configured profile-information relation; the rendered page
test derives the expected
order and metadata from the same settings service, then checks placement below
:guilabel:`About me`, alternating records, empty states, writable add controls
and exactly the configured row actions. AJAX lifecycle tests cover schema
loading, CKEditor inline fields, creation, partial updates, arrow and drag
sorting, cross-section rejection, deletion and read-only endpoint denial.
Contract tests additionally cover every configured field plus address,
email-address and phone-number creation, display, update, independent sorting,
deletion and cross-Contract rejection. They also guard the InlineProfile
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
