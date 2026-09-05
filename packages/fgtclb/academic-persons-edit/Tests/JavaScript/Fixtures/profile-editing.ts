/**
 * The markup the profile editing modules are driven against.
 *
 * ## Where it comes from
 *
 * Every block below is the rendered shape of a Fluid partial of this extension,
 * named at the block. Nothing here is invented: an attribute that the templates
 * do not emit does not belong in a fixture, because then a test would pass on
 * markup no visitor ever receives. Two reductions are deliberate and are the
 * only ones:
 *
 * - `<f:translate>` is replaced by the English text it resolves to, and
 *   `<core:icon>` by nothing at all. Neither is read by the JavaScript.
 * - What a `LitElement` renders is not transcribed: the mount points are, and
 *   the element renders into them here exactly as it does in a browser. The
 *   blocks that predate ACE-509 additionally dropped the Vue directives of the
 *   partials they were transcribed from, and there are none left to drop.
 *
 * What is kept verbatim is everything a module queries: the `data-pe-*` hooks,
 * the element ids, the class names it toggles, the `data-*` configuration of
 * the root, and the structure the `closest()` calls walk. A template change
 * that drops one of those turns a test red here, which is the point - the PHP
 * functional tests assert the markup, and these assert what the JavaScript does
 * with it.
 *
 * See `docs/testing/academic-persons-edit-frontend-tests.md`.
 */

/**
 * The endpoints of `Templates/Profile/Index.html:12-49`, absolute as
 * `f:uri.action(absolute: true)` renders them. The origin is the one
 * `Build/tests/dom.mjs` gives the window, so `credentials: "same-origin"`
 * means the same thing here as on a real page.
 */
export const endpoints = {
  update: "https://example.test/profile/update",
  skipSync: "https://example.test/profile/skip-sync",
  deleteImage: "https://example.test/profile/delete-image",
  documentForm: "https://example.test/profile/document-form",
  createDocument: "https://example.test/profile/create-document",
  updateDocument: "https://example.test/profile/update-document",
  deleteDocument: "https://example.test/profile/delete-document",
  sortDocument: "https://example.test/profile/sort-document",
  contractContactForm: "https://example.test/profile/contract-contact-form",
  createContractContact: "https://example.test/profile/create-contract-contact",
  updateContractContact: "https://example.test/profile/update-contract-contact",
  deleteContractContact: "https://example.test/profile/delete-contract-contact",
  sortContractContact: "https://example.test/profile/sort-contract-contact",
  uploadImage: "https://example.test/profile/upload-image",
} as const;

/** The placeholder image `Index.html:50-52` resolves through `f:uri.resource`. */
export const placeholderImageUrl =
  "/typo3conf/ext/academic_persons_edit/Resources/Public/Images/profile-placeholder.png";

/**
 * The translated strings the root carries as `data-message-*` and
 * `data-label-*` (`Index.html:89-115`). The values are the English labels of
 * `Resources/Private/Language/locallang.xlf`, shortened where the exact
 * wording is irrelevant - a test asserts that *this* message is shown, never
 * what it says.
 */
export const messages = {
  saving: "Saving …",
  successTitle: "Saved",
  successMessage: "The change was saved.",
  errorTitle: "Error",
  errorMessage: "The change could not be saved.",
  infoTitle: "Information",
  infoMessage: "Working …",
  warningTitle: "Please check your input",
  validation: "Please check the highlighted fields.",
  editorError: "The editor could not be started.",
  unchanged: "Nothing was changed.",
  imageUploaded: "The image was uploaded.",
  imageUploadMissing: "The upload returned no image.",
  imageDeleted: "The image was deleted.",
  documentSaved: "The entry was saved.",
  documentDeleted: "The entry was deleted.",
  documentSorted: "The order was saved.",
  documentDeleteConfirm: "Delete this entry?",
  contractContactDeleteConfirm: "Delete this contact?",
  contractContactEmpty: "No contacts yet.",
  placeholderAlt: "No profile image",
  empty: "Not specified",
} as const;

/** The mode labels of `Index.html:109-114`, read by `getModeLabel()`. */
export const labels = {
  add: "Add",
  view: "View",
  edit: "Edit",
  delete: "Delete",
  save: "Save",
  close: "Cancel",
  sortUp: "Move up",
  sortDown: "Move down",
} as const;

export interface RootOptions {
  /** The markup below the root, in the order `Index.html:116-172` renders it. */
  content?: string;
  /**
   * The markup inside the image editor target, which is the first child of the
   * root and is where `Index.html` renders the image editor.
   */
  target?: string;
  hasImage?: boolean;
  imageRenderType?: string;
  imageCropperRatio?: string;
  profileUid?: number;
}

/**
 * `Templates/Profile/Index.html:66-115` - the plugin root and the whole
 * `data-*` contract between Fluid and the JavaScript.
 */
export const profileEditingRoot = ({
  content = "",
  target = "",
  hasImage = true,
  imageRenderType = "",
  imageCropperRatio = "",
  profileUid = 1,
}: RootOptions = {}): string => `
<div class="academic-persons-profile-editing container-fluid px-0"
  data-academic-persons-profile-editing
  data-update-url="${endpoints.update}"
  data-skip-sync-url="${endpoints.skipSync}"
  data-delete-image-url="${endpoints.deleteImage}"
  data-document-form-url="${endpoints.documentForm}"
  data-create-document-url="${endpoints.createDocument}"
  data-update-document-url="${endpoints.updateDocument}"
  data-delete-document-url="${endpoints.deleteDocument}"
  data-sort-document-url="${endpoints.sortDocument}"
  data-contract-contact-form-url="${endpoints.contractContactForm}"
  data-create-contract-contact-url="${endpoints.createContractContact}"
  data-update-contract-contact-url="${endpoints.updateContractContact}"
  data-delete-contract-contact-url="${endpoints.deleteContractContact}"
  data-sort-contract-contact-url="${endpoints.sortContractContact}"
  data-placeholder-image-url="${placeholderImageUrl}"
  data-placeholder-image-alt="${messages.placeholderAlt}"
  data-has-image="${hasImage ? "1" : "0"}"
  data-image-render-type="${imageRenderType}"
  data-image-cropper-ratio="${imageCropperRatio}"
  data-profile-uid="${profileUid}"
  data-editor-language="en"
  data-message-saving="${messages.saving}"
  data-message-success-message="${messages.successMessage}"
  data-message-error-message="${messages.errorMessage}"
  data-message-error-title="${messages.errorTitle}"
  data-message-success-title="${messages.successTitle}"
  data-message-info-title="${messages.infoTitle}"
  data-message-info-message="${messages.infoMessage}"
  data-message-warning-title="${messages.warningTitle}"
  data-message-validation="${messages.validation}"
  data-message-editor-error="${messages.editorError}"
  data-message-unchanged="${messages.unchanged}"
  data-message-image-uploaded="${messages.imageUploaded}"
  data-message-image-upload-missing="${messages.imageUploadMissing}"
  data-message-image-deleted="${messages.imageDeleted}"
  data-message-document-saved="${messages.documentSaved}"
  data-message-document-deleted="${messages.documentDeleted}"
  data-message-document-sorted="${messages.documentSorted}"
  data-message-document-delete-confirm="${messages.documentDeleteConfirm}"
  data-message-contract-contact-delete-confirm="${messages.contractContactDeleteConfirm}"
  data-message-contract-contact-empty="${messages.contractContactEmpty}"
  data-label-document-add="${labels.add}"
  data-label-document-view="${labels.view}"
  data-label-document-edit="${labels.edit}"
  data-label-document-delete="${labels.delete}"
  data-label-document-save="${labels.save}"
  data-label-document-close="${labels.close}"
  data-label-document-empty="${messages.empty}"
  data-label-sort-up="${labels.sortUp}"
  data-label-sort-down="${labels.sortDown}">
  <div id="profile-editing-${profileUid}-image-editor-target" data-pe-image-editor-target>${target}</div>
  ${content}
  ${iconTemplates()}
  ${statusToast()}
</div>`;

/**
 * `Templates/Profile/Index.html:66-73` - the custom element the template wraps
 * the plugin root in.
 *
 * The wrapper is what a browser upgrades and what starts the editor. The
 * controllers below it neither know nor ask about it, which is why every other
 * fixture here stops at the root and the controller tests drive that: an
 * element around markup the module never queries would be arrangement without
 * a subject.
 */
export const profileEditingElement = (options: RootOptions = {}): string => `
<academic-persons-edit-profile-editing>${profileEditingRoot(options)}</academic-persons-edit-profile-editing>`;

/**
 * `Templates/Profile/Index.html:184-206` - one `<template>` per icon a browser
 * rendered editor draws.
 *
 * `<core:icon>` fills each of them on the server, because the icon registry is
 * the only thing that knows the identifiers and the site's overrides. The
 * content is reduced to a marker element here for the same reason a
 * `<f:translate>` is reduced to its English text: no module reads the icon, it
 * clones it - and a clone of a marker proves the cloning as well as a clone of
 * an SVG would.
 */
export const iconTemplates = (
  names: string[] = ["help", "add", "view", "edit", "delete", "move-up", "move-down"],
): string =>
  names
    .map(
      (name): string =>
        `<template data-pe-icon="${name}"><span data-test-icon="${name}"></span></template>`,
    )
    .join("");

/**
 * `Partials/Profile/StatusToast.html:185-218` - the two live regions
 * `showStatus()` picks between, the assertive one for a failure and the polite
 * one for everything else.
 */
export const statusToast = (): string => `
<div class="toast-container position-fixed bottom-0 end-0 p-3">
  <div data-pe-status-toast="status" class="toast" role="status" aria-live="polite" aria-atomic="true">
    <div class="toast-header"><strong class="me-auto status-title"></strong></div>
    <div class="toast-body status-message text-white"></div>
  </div>
  <div data-pe-status-toast="alert" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="toast-header"><strong class="me-auto status-title"></strong></div>
    <div class="toast-body status-message text-white"></div>
  </div>
</div>`;

/**
 * `Partials/Profile/Header.html:5-97` - the profile name, the synchronisation
 * switch and the "edit all" toggle.
 */
export const profileHeader = ({
  profileUid = 1,
  nameFieldIds = "firstName lastName",
  name = "Ada Lovelace",
  skipSync = false,
}: {
  profileUid?: number;
  nameFieldIds?: string;
  name?: string;
  skipSync?: boolean;
} = {}): string => `
<header data-pe-profile-header>
  <h1 id="profile-editing-${profileUid}-name-heading" class="h2 fw-bolder mb-0"
    data-pe-profile-name data-pe-profile-name-field-ids="${nameFieldIds}">${name}</h1>
  <form class="academic-persons-profile-editing__sync-form flex-shrink-0" data-pe-sync-form>
    <div class="form-check form-switch">
      <input class="form-check-input academic-persons-profile-editing__sync-checkbox"
        type="checkbox" name="skipSync" id="profile-editing-${profileUid}-skipSync" value="1"
        aria-describedby="profile-editing-${profileUid}-skipSync-error" aria-invalid="false"${skipSync ? " checked" : ""} />
      <label class="form-check-label" for="profile-editing-${profileUid}-skipSync">Do not synchronise</label>
      <div id="profile-editing-${profileUid}-skipSync-error" class="invalid-feedback" role="alert"></div>
    </div>
  </form>
  <button class="btn rounded-0 btn-outline-secondary btn-sm" type="button"
    data-academic-persons-profile-editing-edit-all-btn
    data-pe-edit-all-label="Edit all"
    data-pe-close-all-label="Close all"
    aria-pressed="false">
    <span data-pe-edit-all-button-label>Edit all</span>
  </button>
</header>`;

interface FieldOptions {
  identifier: string;
  profileUid?: number;
  value?: string;
  /** `field.propertyName`, which is what the endpoint payload is keyed by. */
  propertyName?: string;
  required?: boolean;
  disabled?: boolean;
  readOnly?: boolean;
}

/**
 * `Partials/Profile/Field/Editable.html:8-108` with
 * `Partials/Profile/Field/Preview.html`, `Control.html` and `Actions.html`
 * rendered into it: one text field with its preview, its editor and the three
 * per-field buttons.
 */
export const textField = ({
  identifier,
  profileUid = 1,
  value = "",
  propertyName,
  required = false,
  disabled = false,
  readOnly = false,
}: FieldOptions): string => {
  const elementId = `profile-editing-${profileUid}-${identifier}`;
  const name = propertyName ?? identifier;

  return `
<div class="col-12">
  <div class="rounded-0 p-3 p-lg-4" data-pe-field-wrapper>
    <div class="d-flex align-items-start gap-3" data-form-field-button-area data-pe-field-preview
      data-pe-for="${elementId}" data-empty-label="${messages.empty}">
      <div class="flex-grow-1 overflow-hidden">
        <div class="fw-semibold">${identifier}</div>
        <div class="text-break" data-pe-field-preview-content>${value}</div>
      </div>
      ${
        disabled || readOnly
          ? ""
          : `<button class="btn rounded-0 btn-sm border-0 p-1 text-body flex-shrink-0"
        data-academic-persons-profile-editing-activate-btn data-pe-for="${elementId}" type="button"
        aria-controls="${elementId}-editor" aria-expanded="false" title="Edit" aria-label="Edit"></button>`
      }
    </div>
    <div id="${elementId}-editor" class="d-none mt-3" data-pe-field-editor data-pe-for="${elementId}">
      <div class="d-flex align-items-center">
        <label class="form-label fw-semibold" for="${elementId}">${identifier}</label>
      </div>
      <div class="d-flex flex-column flex-xl-row gap-2 align-items-start">
        <input class="flex-grow-1 w-100 form-control form-control-sm academic-persons-profile-editing__field"
          type="text" name="${name}" id="${elementId}" value="${value}"
          aria-describedby="${elementId}-error" aria-invalid="false"${required ? " required" : ""}${disabled ? " disabled" : ""}${readOnly ? " readonly" : ""} />
        ${
          disabled || readOnly
            ? ""
            : fieldActions(elementId)
        }
      </div>
      <div id="${elementId}-error" class="invalid-feedback" role="alert"></div>
    </div>
  </div>
</div>`;
};

/**
 * `Partials/Profile/Field/Editable.html:23-61` in its rich text branch, with
 * `Preview.html:184-207` and `Control.html:7-32`: the textarea CKEditor is
 * created on, its character counter and its sanitised preview.
 */
export const richTextField = ({
  identifier,
  profileUid = 1,
  value = "",
  propertyName,
  characterLimit,
}: {
  identifier: string;
  profileUid?: number;
  value?: string;
  propertyName?: string;
  characterLimit?: number;
}): string => {
  const elementId = `profile-editing-${profileUid}-${identifier}`;

  return `
<div class="col-12">
  <div class="rounded-0 p-3 p-lg-4" data-pe-field-wrapper>
    <div class="d-flex align-items-start gap-3" data-form-field-button-area data-pe-field-preview
      data-pe-for="${elementId}" data-empty-label="${messages.empty}">
      <div class="flex-grow-1 overflow-hidden">
        <div class="fw-semibold">${identifier}</div>
        <div class="text-break" data-pe-field-preview-content data-pe-rich-text-preview
          data-pe-for="${elementId}" data-empty-label="${messages.empty}">
          <div data-pe-rich-text-preview-content>${value}</div>
        </div>
      </div>
      <button class="btn rounded-0 btn-sm border-0 p-1 text-body flex-shrink-0"
        data-academic-persons-profile-editing-activate-btn data-pe-for="${elementId}" type="button"
        aria-controls="${elementId}-editor" aria-expanded="false" title="Edit" aria-label="Edit"></button>
    </div>
    <div id="${elementId}-editor" class="d-none mt-3" data-pe-field-editor data-pe-for="${elementId}">
      <div class="d-flex align-items-center gap-2 mb-2" data-pe-rich-text-heading>
        <label class="form-label fw-semibold mb-0" for="${elementId}">${identifier}</label>
        ${fieldActions(elementId)}
      </div>
      <div class="flex-grow-1 w-100" data-pe-editor-container>
        <textarea name="${propertyName ?? identifier}" id="${elementId}" rows="5"
          class="form-control form-control-sm academic-persons-profile-editing__field"
          aria-describedby="${elementId}-error" aria-invalid="false"
          data-pe-rich-text="true"${characterLimit === undefined ? "" : ` data-pe-character-limit="${characterLimit}"`}>${value}</textarea>
        ${
          characterLimit === undefined
            ? ""
            : `<div id="${elementId}-character-counter" class="form-text text-end" aria-live="polite"
          data-pe-character-counter data-pe-for="${elementId}">0 / ${characterLimit}</div>`
        }
      </div>
      <div id="${elementId}-error" class="invalid-feedback" role="alert"></div>
    </div>
  </div>
</div>`;
};

/** `Partials/Profile/Field/Actions.html:114-162` - clear, undo and save. */
export const fieldActions = (elementId: string): string => `
<div class="btn-group btn-group-sm d-none flex-shrink-0" data-pe-field-actions data-pe-for="${elementId}"
  role="group" aria-label="Actions">
  <button class="btn rounded-0 btn-outline-danger" data-pe-dismiss data-pe-for="${elementId}" type="button" title="Clear" aria-label="Clear"></button>
  <button class="btn rounded-0 btn-outline-secondary" data-pe-cancel data-pe-for="${elementId}" type="button" title="Undo" aria-label="Undo"></button>
  <button class="btn rounded-0 btn-success" data-pe-save data-pe-for="${elementId}" type="button" title="Save" aria-label="Save"></button>
</div>`;

/**
 * `Partials/Profile/Field/Checkbox.html:73-140` with
 * `Partials/Profile/Field/AutosaveUndo.html:147-161` - the visibility switch,
 * which saves on change and has no save button of its own.
 */
export const checkboxField = ({
  identifier,
  profileUid = 1,
  checked = false,
  propertyName,
}: {
  identifier: string;
  profileUid?: number;
  checked?: boolean;
  propertyName?: string;
}): string => {
  const elementId = `profile-editing-${profileUid}-${identifier}`;

  return `
<div class="col-12">
  <div class="rounded-1 p-3 p-lg-4" data-pe-field-wrapper>
    <div class="d-flex align-items-start gap-3" data-form-field-button-area data-pe-field-preview
      data-pe-for="${elementId}" data-empty-label="${messages.empty}">
      <div class="flex-grow-1 overflow-hidden">
        <div class="fw-semibold">${identifier}</div>
        <div class="text-break" data-pe-field-preview-content></div>
      </div>
      <button class="btn rounded-0 btn-sm border-0 p-1 text-body flex-shrink-0"
        data-academic-persons-profile-editing-activate-btn data-pe-for="${elementId}" type="button"
        aria-controls="${elementId}-editor" aria-expanded="false" title="Edit" aria-label="Edit"></button>
    </div>
    <div id="${elementId}-editor" class="d-none mt-3" data-pe-field-editor data-pe-for="${elementId}">
      <div class="form-check form-switch">
        <div class="d-flex flex-row">
          <input class="form-check-input academic-persons-profile-editing__field" type="checkbox"
            name="${propertyName ?? identifier}" id="${elementId}" value="1"${checked ? " checked" : ""}
            aria-describedby="${elementId}-error" aria-invalid="false"
            data-pe-autosave-on-change="true"
            data-pe-checked-label="Public"
            data-pe-unchecked-label="Private" />
          <label class="form-check-label ms-2" for="${elementId}">${identifier}</label>
          <button class="btn btn-sm rounded-0 btn-outline-secondary ms-auto" data-pe-autosave-undo
            data-pe-cancel data-pe-for="${elementId}" type="button" title="Undo" aria-label="Undo"></button>
        </div>
        <div id="${elementId}-error" class="invalid-feedback" role="alert"></div>
      </div>
    </div>
  </div>
</div>`;
};

/**
 * `Partials/Profile/Field/Group.html:9-140` - several fields behind one
 * preview, with the group's own edit, clear, undo and save buttons.
 */
export const fieldGroup = ({
  identifier,
  profileUid = 1,
  fields,
  displayFieldIds,
  displayMode = "join",
}: {
  identifier: string;
  profileUid?: number;
  fields: { identifier: string; value?: string; propertyName?: string }[];
  displayFieldIds?: string;
  displayMode?: "join" | "first";
}): string => {
  const groupId = `profile-editing-${profileUid}-${identifier}-group`;
  const fieldIds = fields
    .map((field): string => `profile-editing-${profileUid}-${field.identifier}`)
    .join(" ");
  const controls = fields
    .map((field): string => {
      const elementId = `profile-editing-${profileUid}-${field.identifier}`;

      return `
        <div class="col-12" data-pe-group-control>
          <label class="form-label" for="${elementId}">${field.identifier}</label>
          <input class="form-control form-control-sm academic-persons-profile-editing__field" type="text"
            name="${field.propertyName ?? field.identifier}" id="${elementId}" value="${field.value ?? ""}"
            aria-describedby="${elementId}-error" aria-invalid="false" />
          <div id="${elementId}-error" class="invalid-feedback" role="alert"></div>
        </div>`;
    })
    .join("");

  return `
<div class="col-12" data-pe-field-group data-pe-field-ids="${fieldIds}"
  data-pe-display-field-ids="${displayFieldIds ?? fieldIds}" data-pe-display-mode="${displayMode}">
  <div class="rounded-1 p-3 p-lg-4">
    <div class="d-flex align-items-start gap-3" data-pe-group-preview>
      <div class="flex-grow-1 overflow-hidden">
        <div class="fw-semibold">${identifier}</div>
        <div class="text-break" data-pe-group-preview-content data-empty-label="${messages.empty}"></div>
      </div>
      <button class="btn rounded-0 btn-sm border-0 p-1 text-body flex-shrink-0" data-pe-group-edit type="button"
        aria-controls="${groupId}-editor" aria-expanded="false" title="Edit" aria-label="Edit"></button>
    </div>
    <div id="${groupId}-editor" class="d-none mt-3" data-pe-group-editor>
      <div class="row g-3">${controls}</div>
      <div class="d-flex justify-content-end mt-3" data-pe-group-actions>
        <div class="btn-group btn-group-sm flex-shrink-0" role="group" aria-label="Actions">
          <button class="btn rounded-0 btn-outline-danger" data-pe-group-dismiss type="button" title="Clear" aria-label="Clear"></button>
          <button class="btn rounded-0 btn-outline-secondary" data-pe-group-cancel type="button" title="Undo" aria-label="Undo"></button>
          <button class="btn rounded-0 btn-success" data-pe-group-save type="button" title="Save" aria-label="Save"></button>
        </div>
      </div>
    </div>
  </div>
</div>`;
};

/** `Partials/Profile/Profile/Personal.html:15-28` - the form the fields sit in. */
export const fieldsForm = (fields: string): string => `
<form class="academic-persons-profile-editing__form" data-pe-fields-form>
  <fieldset class="border-0 p-0 m-0">
    <div class="row g-3">${fields}</div>
  </fieldset>
</form>`;

interface DocumentRowOptions {
  uid: number;
  sorting?: number;
  position?: number;
  title?: string;
  link?: string;
  dateStart?: string;
  bodytext?: string;
  actions?: string[];
  sortable?: boolean;
}

/**
 * `Partials/Profile/Documents/ProfileInformationRow.html:187-254` with
 * `Actions.html:85-181` - one list row with its value cells, its action group
 * and the collapse target the editor is teleported into.
 */
export const documentRow = ({
  uid,
  sorting = 0,
  position = 0,
  title = "",
  link = "",
  dateStart = "",
  bodytext = "",
  actions = ["view", "down", "up", "delete", "edit"],
  sortable = true,
}: DocumentRowOptions): string => `
<article class="row g-0 align-items-center border-bottom py-2" data-pe-document-item
  data-item-uid="${uid}" data-item-sorting="${sorting}" data-item-position="${position}">
  <div class="col-12 col-md-3 py-1 pe-md-3">
    <div data-pe-document-value="dateStart">${dateStart}</div>
  </div>
  <div class="col-12 col-md py-1 pe-md-3">
    <div data-pe-document-title>${link === "" ? `<span>${title}</span>` : `<a href="${link}" target="_blank" rel="noopener noreferrer">${title}</a>`}</div>
  </div>
  <div class="col-12 py-1 pe-md-3">
    <div class="${bodytext === "" ? "d-none" : ""}" data-pe-document-value="bodytext">${bodytext}</div>
  </div>
  ${documentActions(actions, sortable)}
  <div class="col-12 pe-3" data-pe-document-item-collapse-target></div>
</article>`;

/** `Partials/Profile/Documents/Actions.html:85-181`. */
export const documentActions = (actions: string[], sortable: boolean): string => {
  const buttons: Record<string, string> = {
    view: `<button type="button" class="btn rounded-0 btn-sm btn-link text-body p-2" title="View" aria-label="View" aria-expanded="false" data-pe-document-view></button>`,
    down: `<button type="button" class="btn rounded-0 btn-sm btn-link text-body p-2" title="Move down" aria-label="Move down" data-pe-document-sort="down"></button>`,
    up: `<button type="button" class="btn rounded-0 btn-sm btn-link text-body p-2" title="Move up" aria-label="Move up" data-pe-document-sort="up"></button>`,
    delete: `<button type="button" class="btn rounded-0 btn-sm btn-link text-body p-2" title="Delete" aria-label="Delete" aria-expanded="false" data-pe-document-delete></button>`,
    edit: `<button type="button" class="btn rounded-0 btn-sm btn-link text-body p-2" title="Edit" aria-label="Edit" aria-expanded="false" data-pe-document-edit></button>`,
  };

  return `
  <div class="col-12 col-md-auto d-flex flex-nowrap gap-1 justify-content-end ms-auto" role="group"
    aria-label="Actions" data-pe-document-actions>
    ${sortable ? `<button type="button" class="btn rounded-0 btn-sm btn-link text-body p-2 d-none d-md-inline-flex" title="Sort" aria-label="Sort" draggable="true" data-pe-document-drag></button>` : ""}
    ${actions.map((action): string => buttons[action] ?? "").join("")}
  </div>`;
};

/**
 * `Partials/Profile/Documents/Sections.html:14-77` with
 * `ProfileInformation.html:5-19` and `Header.html:45-85`: one section, its list,
 * its row template, its list header and its empty state.
 */
export const documentSection = ({
  identifier,
  profileUid = 1,
  kind = "profileInformation",
  sortable = true,
  rows = "",
  canCreate = true,
  actions = ["view", "down", "up", "delete", "edit"],
}: {
  identifier: string;
  profileUid?: number;
  kind?: string;
  sortable?: boolean;
  rows?: string;
  canCreate?: boolean;
  actions?: string[];
}): string => `
<section class="mt-5" aria-labelledby="profile-editing-${profileUid}-document-section-${identifier}-heading"
  data-pe-document-section
  data-section-key="${identifier}"
  data-section-readonly="0"
  data-section-sortable="${sortable ? "1" : "0"}"
  data-section-kind="${kind}">
  <div class="d-flex align-items-center justify-content-between gap-3 mb-3" data-pe-document-section-header>
    <h2 id="profile-editing-${profileUid}-document-section-${identifier}-heading" class="display-6 fw-normal mb-0">${identifier}</h2>
    ${canCreate ? `<button type="button" class="btn rounded-0 btn-sm btn-link p-2" aria-expanded="false" data-pe-document-add></button>` : ""}
  </div>
  <div data-pe-document-add-collapse-target></div>
  <div class="row g-0 align-items-end border-bottom pb-2 fw-semibold text-start d-none" data-pe-document-list-header></div>
  <div data-pe-document-items>${rows}</div>
  <div class="d-none" aria-hidden="true" data-pe-document-item-template>
    ${documentRow({ uid: 0, actions, sortable })}
  </div>
  <div class="bg-body-tertiary py-2 ps-3 small text-body-secondary" role="status" data-pe-document-empty-state>No entries yet.</div>
</section>`;

/**
 * What `<academic-persons-edit-document-editor>` renders for `mode: "add"` or
 * `"edit"`: the view container the controller queries for its rich text fields
 * and for the field to focus.
 *
 * It is a fixture rather than the element itself because the files that use it
 * drive the controller without a registry. The controls carry the ids and the
 * `data-pe-document-field` hooks the element builds from the response's
 * `fields`, so the same response drives the markup here and the markup the
 * element renders in `document-editor-element.test.ts`.
 */
export const documentEditorView = ({
  fields,
  heading = "Add: Publications",
}: {
  fields: {
    name: string;
    type?: "text" | "textarea" | "checkbox";
    richText?: boolean;
    disabled?: boolean;
    value?: string;
  }[];
  heading?: string;
}): string => {
  const controls = fields
    .map((field, index): string => {
      const id = `profile-editing-document-field-${index}-${field.name}`;
      if (field.type === "textarea") {
        return `<textarea class="form-control" rows="6" id="${id}" name="${field.name}"
          data-pe-document-field="${field.name}"${field.richText === true ? ' data-pe-rich-text=""' : ""}${field.disabled === true ? " disabled" : ""}>${field.value ?? ""}</textarea>`;
      }
      if (field.type === "checkbox") {
        return `<input class="form-check-input" type="checkbox" id="${id}" name="${field.name}"
          data-pe-document-field="${field.name}"${field.disabled === true ? " disabled" : ""} />`;
      }

      return `<input class="form-control" type="text" id="${id}" name="${field.name}" value="${field.value ?? ""}"
        data-pe-document-field="${field.name}"${field.disabled === true ? " disabled" : ""} />`;
    })
    .join("\n");

  return `
<section class="academic-persons-profile-editing__document-collapse border bg-body p-3 my-3" data-pe-document-view-container>
  <form data-pe-document-form>
    <h2 class="display-6 fw-normal mb-0" tabindex="-1" data-pe-document-heading>${heading}</h2>
    <div class="row g-3" data-pe-document-fields>
      ${controls}
    </div>
  </form>
</section>`;
};

/**
 * `Partials/Profile/Image/Card.html:15-102` - the preview the upload and the
 * deletion write into, and the button focus returns to when the editor closes.
 */
export const imageCard = ({
  profileUid = 1,
  src = "/fileadmin/_processed_/profile.jpg",
  alt = "Ada Lovelace",
  title = "Ada Lovelace",
}: {
  profileUid?: number;
  src?: string;
  alt?: string;
  title?: string;
} = {}): string => `
<div class="col-12 col-lg-4 academic-persons-profile-editing__image-preview-column" data-pe-image-preview-column>
  <div class="sticky-top" data-pe-sticky-image>
    <section aria-labelledby="profile-editing-${profileUid}-image-heading">
      <div data-pe-image-preview>
        <figure class="mb-0">
          <picture class="d-block">
            <source srcset="${src}.webp" type="image/webp" media="(min-width: 992px)" />
            <img src="${src}" alt="${alt}" class="img-fluid w-100 object-fit-cover" title="${title}" loading="lazy" />
          </picture>
        </figure>
      </div>
      <button class="btn rounded-0 btn-outline-secondary btn-sm w-100" type="button" data-pe-open-image-view
        aria-expanded="false" aria-controls="profile-editing-${profileUid}-image-view"
        title="Edit image" aria-label="Edit image"></button>
    </section>
  </div>
</div>`;

/**
 * `Templates/Profile/Index.html:146-154` - the column beside the image card,
 * which widens to the full row while the editor is open.
 */
export const profileFieldsColumn = (content = ""): string => `
<div class="col-12 col-lg-8 academic-persons-profile-editing__profile-fields-column">${content}</div>`;

/**
 * `Partials/Profile/Image/Editor.html` - the upload form, its preview, the
 * cropper stage and the delete actions, in the state a page is delivered in:
 * closed, idle, without an error.
 *
 * The `<f:form>` is transcribed with its hidden fields. They are not decoration:
 * the property mapper validates the upload against the `__trustedProperties`
 * signature, nothing in a browser can recompute it, and it is the reason the
 * editor stays server rendered. A test asserts that they leave with the request.
 */
export const imageEditorView = ({
  profileUid = 1,
  action = endpoints.uploadImage,
}: { profileUid?: number; action?: string } = {}): string => `
<section id="profile-editing-${profileUid}-image-view"
  class="academic-persons-profile-editing__image-editor border p-3 p-lg-4 mb-5" aria-busy="false" hidden="hidden"
  data-pe-image-view-container>
  <div class="academic-persons-profile-editing__image-editor-content">
    <form action="${action}" method="post" enctype="multipart/form-data"
      class="academic-persons-profile-editing__image-form">
      <div>
        <input type="hidden" name="tx_academicpersonsedit_profile[__referrer][@extension]" value="AcademicPersonsEdit" />
        <input type="hidden" name="tx_academicpersonsedit_profile[__referrer][@controller]" value="Profile" />
        <input type="hidden" name="tx_academicpersonsedit_profile[__referrer][@action]" value="edit" />
        <input type="hidden" name="tx_academicpersonsedit_profile[__referrer][arguments]" value="YTowOnt9ecf6f1" />
        <input type="hidden" name="tx_academicpersonsedit_profile[__trustedProperties]"
          value="a:1:{s:7:&quot;profile&quot;;a:1:{s:5:&quot;image&quot;;i:1;}}5f3c2a" />
      </div>
      <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <h2 class="display-6 fw-normal mb-0" tabindex="-1" data-pe-image-editor-heading>Profile image</h2>
        <div class="d-flex align-items-center gap-2" data-pe-image-delete-actions>
          <button class="btn rounded-0 btn-danger" type="button" data-pe-delete-image title="Delete the image">Delete</button>
          <span class="text-danger" hidden="hidden" data-pe-delete-image-confirm-question>Delete this image?</span>
          <button class="btn rounded-0 btn-outline-secondary" type="button" hidden="hidden"
            data-pe-cancel-delete-image>Cancel</button>
          <button class="btn rounded-0 btn-danger" type="button" hidden="hidden" data-pe-confirm-delete-image
            title="Delete the image">Delete</button>
        </div>
      </div>
      <fieldset data-pe-image-fieldset>
        <div class="mb-3" data-pe-image-view-preview>
          <div class="academic-persons-profile-editing__image-cropper" hidden="hidden" data-pe-image-cropper-stage>
            <img alt="" data-pe-image-cropper-source />
          </div>
          <img class="img-fluid" alt="" hidden="hidden" data-pe-image-selected-preview />
        </div>
        <label class="form-label" for="profile-editing-${profileUid}-image">Image</label>
        <div>
          <input class="form-control form-control-sm" type="file" name="tx_academicpersonsedit_profile[profile][image]"
            id="profile-editing-${profileUid}-image" accept="image/jpeg,image/png"
            aria-describedby="profile-editing-${profileUid}-image-error" aria-invalid="false" required />
        </div>
      </fieldset>
      <div id="profile-editing-${profileUid}-image-error" class="alert alert-danger mt-3 mb-0" role="alert"
        hidden="hidden" data-pe-image-error></div>
      <div class="d-flex justify-content-end gap-2 mt-4">
        <button class="btn rounded-0 btn-outline-secondary" type="button" data-pe-close-image-view>Cancel</button>
        <button class="btn rounded-0 btn-primary" type="submit" disabled data-pe-upload-image>
          <span class="spinner-border spinner-border-sm me-1" aria-hidden="true" hidden="hidden"
            data-pe-image-upload-spinner></span>Save</button>
      </div>
    </form>
  </div>
</section>`;

/**
 * The same partial with the element that drives it, which is how
 * `Templates/Profile/Index.html` renders it: inside the image editor target,
 * so pass it as `target` rather than as `content`.
 */
export const imageEditor = (
  options: { profileUid?: number; action?: string } = {},
): string => `
<academic-persons-edit-image-editor>${imageEditorView(options)}</academic-persons-edit-image-editor>`;

/**
 * The one query helper every test file needs: a `querySelector` that fails with
 * the selector in the message rather than handing back a `null` that turns into
 * an assertion about `undefined` three lines later.
 */
export const select = <T extends Element>(
  scope: ParentNode,
  selector: string,
  type: new () => T,
): T => {
  const element = scope.querySelector(selector);
  if (!(element instanceof type)) {
    throw new Error(`The test markup has no "${selector}".`);
  }

  return element;
};

/** The same for a list, so a test can index into it without a cast. */
export const selectAll = <T extends Element>(
  scope: ParentNode,
  selector: string,
  type: new () => T,
): T[] =>
  Array.from(scope.querySelectorAll(selector)).map((element): T => {
    if (!(element instanceof type)) {
      throw new Error(`The test markup has a "${selector}" of the wrong kind.`);
    }

    return element;
  });
