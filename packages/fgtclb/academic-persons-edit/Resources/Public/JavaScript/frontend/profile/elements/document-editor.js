/* Generated from Resources/Private/TypeScript — do not edit. */
import { html, LitElement, nothing } from "lit";
import { guard } from "lit/directives/guard.js";
import { ifDefined } from "lit/directives/if-defined.js";
import { live } from "lit/directives/live.js";
import { repeat } from "lit/directives/repeat.js";
import {
  ownerEditingContext
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { profileDocumentEditorElementName } from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import { createElementTransition } from "@fgtclb/academic-persons-edit/frontend/profile/elements/transition.js";
import { parseRichTextPreview } from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";
const documentEditorCloseEvent = "pe:document-close";
const documentEditorSubmitEvent = "pe:document-submit";
const documentEditorInputEvent = "pe:document-input";
const documentEditorClosedEvent = "pe:document-closed";
const runDocumentTransition = createElementTransition(
  "academic-persons-profile-editing-document-collapse"
);
const fieldId = (index, field) => `profile-editing-document-field-${index}-${field.name}`;
const fieldErrorId = (index, field) => `profile-editing-document-field-error-${index}-${field.name}`;
const columnClass = (field) => {
  const column = field.columnClass !== void 0 && field.columnClass !== "" ? field.columnClass : field.type === "textarea" || field.type === "checkbox" ? "col-12" : "col-12 col-md-6";
  return field.compactCheckbox === true ? `${column} d-flex align-items-end` : column;
};
const textValue = (value) => value === null || value === void 0 ? "" : String(value);
const richTextFragment = (value) => {
  const parsed = parseRichTextPreview(value);
  const fragment = document.createDocumentFragment();
  Array.from(parsed.body.childNodes).forEach((node) => {
    fragment.append(document.importNode(node, true));
  });
  return fragment;
};
class ProfileDocumentEditorElement extends LitElement {
  static properties = {
    contactEmptyMessage: { attribute: false },
    contactSections: { attribute: false },
    context: { attribute: false },
    deleteConfirmation: { attribute: false },
    error: { attribute: false },
    errors: { attribute: false },
    fields: { attribute: false },
    kind: { attribute: false },
    mode: { attribute: false },
    open: { attribute: false },
    pending: { attribute: false },
    record: { attribute: false },
    heading: { attribute: false },
    values: { attribute: false }
  };
  #cancelTransition = null;
  #transitioned = false;
  constructor() {
    super();
    this.contactEmptyMessage = "";
    this.contactSections = [];
    this.context = null;
    this.deleteConfirmation = "";
    this.error = "";
    this.errors = {};
    this.fields = [];
    this.heading = "";
    this.kind = "document";
    this.mode = "view";
    this.open = false;
    this.pending = false;
    this.record = null;
    this.values = {};
  }
  /** Light DOM. See the six reasons at the top of this file. */
  createRenderRoot() {
    return this;
  }
  connectedCallback() {
    this.context ??= ownerEditingContext(this);
    super.connectedCallback();
  }
  render() {
    var _a;
    return html`
      <section
        class="academic-persons-profile-editing__document-collapse border bg-body p-3 p-lg-4 my-3"
        aria-busy=${this.pending ? "true" : "false"}
        style=${this.pending ? "cursor: wait" : ""}
        data-pe-document-kind=${this.kind}
        data-pe-document-view-container
      >
        <div class="academic-persons-profile-editing__document-collapse-content">
          <form data-pe-document-form @submit=${this.#onSubmit}>
            <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
              <h2 class="display-6 fw-normal mb-0" tabindex="-1" data-pe-document-heading>
                ${this.heading}
              </h2>
              ${this.mode === "delete" ? nothing : html`<button
                    type="button"
                    class="btn rounded-0 btn-outline-secondary btn-sm"
                    ?disabled=${this.pending}
                    @click=${this.#onClose}
                  >
                    ${((_a = this.context) == null ? void 0 : _a.labels.documentClose) ?? ""}
                  </button>`}
            </div>
            ${this.error === "" ? nothing : html`<div class="alert alert-danger" role="alert">${this.error}</div>`}
            ${this.mode === "delete" ? html`<p class="mb-4">${this.deleteConfirmation}</p>` : nothing}
            ${this.mode === "view" ? this.#renderDisplayValues() : nothing}
            ${this.mode === "view" && this.kind === "contract" ? html`<div class="mt-5">${this.#renderContactSections()}</div>` : nothing}
            ${this.mode === "add" || this.mode === "edit" ? html`<div class="row g-3" data-pe-document-fields>
                  ${repeat(
      this.fields,
      (field) => field.name,
      (field, index) => this.#renderField(field, index)
    )}
                </div>` : nothing}
            ${this.mode === "view" ? nothing : this.#renderActions()}
          </form>
        </div>
      </section>
    `;
  }
  firstUpdated() {
    if (this.open) {
      this.#transitioned = true;
      this.#startTransition("enter", () => void 0);
    }
  }
  updated(changed) {
    if (!changed.has("open")) {
      return;
    }
    if (this.open && !this.#transitioned) {
      this.#transitioned = true;
      this.#startTransition("enter", () => void 0);
      return;
    }
    if (!this.open && this.#transitioned) {
      this.#transitioned = false;
      this.#startTransition("leave", () => this.#reportClosed());
    }
  }
  /** The `<dl>` of the view mode: one term and one description per field. */
  #renderDisplayValues() {
    return html`<dl class="row mb-0">
      ${repeat(
      this.fields,
      (field) => field.name,
      (field) => {
        var _a;
        return html`
          <dt class="col-sm-4">${field.label}</dt>
          <dd class="col-sm-8">
            ${field.richText && field.displayValue !== void 0 && field.displayValue !== "" ? html`<div>
                  ${guard(
          [field.displayValue],
          () => richTextFragment(field.displayValue ?? "")
        )}
                </div>` : field.displayValue !== void 0 && field.displayValue !== "" ? html`<span>${field.displayValue}</span>` : html`<span>${((_a = this.context) == null ? void 0 : _a.labels.documentEmpty) ?? ""}</span>`}
          </dd>
        `;
      }
    )}
    </dl>`;
  }
  /**
   * The contacts of a contract.
   *
   * The element is created here and defined by the next commit of ACE-509,
   * which moves `Partials/Profile/Documents/ContractContacts.html` and its
   * editor into `elements/contract-contacts.ts`. Until it is defined the tag is
   * an inert unknown element, so a contract's contact list is not rendered
   * between the two commits - the one seam of this port, and it is here rather
   * than spread over both because the properties the list needs are exactly the
   * ones this element already holds.
   */
  #renderContactSections() {
    return html`<academic-persons-edit-contract-contacts
      .context=${this.context}
      .contract=${this.record}
      .sections=${this.contactSections}
      .emptyMessage=${this.contactEmptyMessage}
    ></academic-persons-edit-contract-contacts>`;
  }
  /** One editable field: its label, its help, its control and its error. */
  #renderField(field, index) {
    const id = fieldId(index, field);
    const errorId = fieldErrorId(index, field);
    const message = this.errors[field.name];
    return html`<div class=${columnClass(field)}>
      <div class=${field.type === "checkbox" ? "form-check" : ""}>
        ${field.type === "checkbox" ? nothing : html`<div class="d-flex align-items-center">
              <label class="form-label" for=${id}>
                <span>${field.label}</span>
                ${field.required ? html`<span class="text-danger ms-1" aria-hidden="true">*</span>` : nothing}
              </label>
              ${this.#renderHelptext(field)}
            </div>`}
        ${this.#renderControl(field, id, errorId)}
        ${field.type === "checkbox" ? html`<label class="form-check-label ms-2" for=${id}>${field.label}</label>
              ${this.#renderHelptext(field)}` : nothing}
        ${field.richText && field.characterLimit !== void 0 && field.characterLimit > 0 ? html`<div
              class="form-text text-end"
              aria-live="polite"
              data-pe-character-counter
              data-pe-for=${id}
            >
              0 / <span>${field.characterLimit}</span>
            </div>` : nothing}
        ${message === void 0 || message === "" ? nothing : html`<div id=${errorId} class="invalid-feedback d-block" role="alert">
              ${message}
            </div>`}
      </div>
    </div>`;
  }
  #renderHelptext(field) {
    if (field.helptext === void 0 || field.helptext === "") {
      return nothing;
    }
    return html`<button
      type="button"
      class="btn rounded-0 btn-link link-info p-0 ms-2 mb-1"
      data-bs-toggle="popover"
      data-pe-helptext
      data-bs-trigger="focus"
      data-bs-placement="right"
      data-bs-custom-class="custom-popover"
      data-bs-title=${field.label}
      data-bs-content=${field.helptext}
      aria-label=${`${field.label}: ${field.helptext}`}
    >
      ${guard([this.context], () => this.#icon("help"))}
    </button>`;
  }
  #renderControl(field, id, errorId) {
    const disabled = field.disabled || this.pending;
    const invalid = this.errors[field.name] === void 0 ? "false" : "true";
    if (field.type === "select") {
      const selected = textValue(this.values[field.name]);
      return html`<select
        class="form-select"
        id=${id}
        name=${field.name}
        ?required=${field.required}
        ?disabled=${disabled}
        autocomplete=${ifDefined(
        field.autocomplete === void 0 || field.autocomplete === "" ? void 0 : field.autocomplete
      )}
        aria-describedby=${errorId}
        aria-invalid=${invalid}
        data-pe-document-field=${field.name}
        @change=${this.#onValueChanged}
      >
        <option value="" .selected=${live(selected === "")}>—</option>
        ${repeat(
        field.options ?? [],
        (option) => textValue(option.value),
        (option) => html`<option
              value=${textValue(option.value)}
              .selected=${live(textValue(option.value) === selected)}
            >
              ${option.label}
            </option>`
      )}
      </select>`;
    }
    if (field.type === "textarea" && field.richText) {
      return html`<academic-persons-edit-rich-text
        .context=${this.context}
        .configuration=${{
        ariaDescribedBy: errorId,
        characterLimit: field.characterLimit,
        disabled,
        id,
        invalid: invalid === "true",
        name: field.name,
        readOnly: field.readOnly,
        required: field.required
      }}
        .value=${textValue(this.values[field.name])}
        @input=${this.#onValueChanged}
      ></academic-persons-edit-rich-text>`;
    }
    if (field.type === "textarea") {
      return html`<textarea
        class="form-control"
        rows="6"
        id=${id}
        name=${field.name}
        ?required=${field.required}
        ?readonly=${field.readOnly}
        ?disabled=${disabled}
        autocomplete=${ifDefined(
        field.autocomplete === void 0 || field.autocomplete === "" ? void 0 : field.autocomplete
      )}
        aria-describedby=${errorId}
        aria-invalid=${invalid}
        data-pe-document-field=${field.name}
        .value=${live(textValue(this.values[field.name]))}
        @input=${this.#onValueChanged}
      ></textarea>`;
    }
    if (field.type === "checkbox") {
      return html`<input
        class="form-check-input"
        type="checkbox"
        id=${id}
        name=${field.name}
        ?disabled=${disabled}
        aria-describedby=${errorId}
        aria-invalid=${invalid}
        data-pe-document-field=${field.name}
        .checked=${live(this.values[field.name] === true)}
        @change=${this.#onValueChanged}
      />`;
    }
    return html`<input
      class="form-control"
      type=${field.type === "" ? "text" : field.type}
      id=${id}
      name=${field.name}
      ?required=${field.required}
      ?readonly=${field.readOnly}
      ?disabled=${disabled}
      autocomplete=${ifDefined(
      field.autocomplete === void 0 || field.autocomplete === "" ? void 0 : field.autocomplete
    )}
      aria-describedby=${errorId}
      aria-invalid=${invalid}
      data-pe-document-field=${field.name}
      .value=${live(textValue(this.values[field.name]))}
      @input=${this.#onValueChanged}
    />`;
  }
  /** Cancel and save, or cancel and delete. */
  #renderActions() {
    var _a;
    const labels = (_a = this.context) == null ? void 0 : _a.labels;
    return html`<div class="d-flex justify-content-end gap-2 mt-4">
      <button
        type="button"
        class="btn rounded-0 btn-outline-secondary"
        ?disabled=${this.pending}
        @click=${this.#onClose}
      >
        ${(labels == null ? void 0 : labels.documentClose) ?? ""}
      </button>
      <button
        type="submit"
        class=${this.mode === "delete" ? "btn rounded-0 btn-danger" : "btn rounded-0 btn-primary"}
        ?disabled=${this.pending}
      >
        ${this.pending ? html`<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>` : nothing}
        <span
          >${this.mode === "delete" ? (labels == null ? void 0 : labels.documentDelete) ?? "" : (labels == null ? void 0 : labels.documentSave) ?? ""}</span
        >
      </button>
    </div>`;
  }
  /**
   * One icon, cloned from the `<template data-pe-icon="...">` block of
   * `Templates/Profile/Index.html`.
   *
   * The icons stay Fluid's. `<core:icon>` resolves an identifier through the
   * icon registry, which knows about the icon set the extension registers and
   * about what a site overrode - none of which a browser can ask. Copying the
   * markup into TypeScript would fork the icon set at the first change.
   */
  #icon(name) {
    var _a;
    const template = (_a = this.context) == null ? void 0 : _a.root.querySelector(
      `template[data-pe-icon="${CSS.escape(name)}"]`
    );
    return template === null || template === void 0 ? nothing : document.importNode(template.content, true);
  }
  #onClose = () => {
    this.dispatchEvent(new CustomEvent(documentEditorCloseEvent, { bubbles: true }));
  };
  #onSubmit = (event) => {
    event.preventDefault();
    this.dispatchEvent(new CustomEvent(documentEditorSubmitEvent, { bubbles: true }));
  };
  #onValueChanged = (event) => {
    const control = event.target;
    if (!(control instanceof HTMLInputElement) && !(control instanceof HTMLSelectElement) && !(control instanceof HTMLTextAreaElement)) {
      return;
    }
    const name = control.dataset.peDocumentField;
    if (name === void 0) {
      return;
    }
    const value = control instanceof HTMLInputElement && control.type === "checkbox" ? control.checked : control.value;
    this.dispatchEvent(
      new CustomEvent(documentEditorInputEvent, {
        bubbles: true,
        detail: { name, value }
      })
    );
  };
  #reportClosed() {
    globalThis.requestAnimationFrame(() => {
      this.dispatchEvent(new CustomEvent(documentEditorClosedEvent, { bubbles: true }));
    });
  }
  #startTransition(kind, done) {
    var _a;
    const section = this.querySelector("[data-pe-document-view-container]");
    if (section === null) {
      done();
      return;
    }
    (_a = this.#cancelTransition) == null ? void 0 : _a.call(this);
    this.#cancelTransition = null;
    let settled = false;
    const cancel = runDocumentTransition(section, kind, () => {
      settled = true;
      this.#cancelTransition = null;
      done();
    });
    if (!settled) {
      this.#cancelTransition = cancel;
    }
  }
}
const registerProfileDocumentEditorElement = () => {
  if (customElements.get(profileDocumentEditorElementName) !== void 0) {
    return;
  }
  customElements.define(profileDocumentEditorElementName, ProfileDocumentEditorElement);
};
export {
  ProfileDocumentEditorElement,
  documentEditorCloseEvent,
  documentEditorClosedEvent,
  documentEditorInputEvent,
  documentEditorSubmitEvent,
  profileDocumentEditorElementName,
  registerProfileDocumentEditorElement
};
