/* Generated from Resources/Private/TypeScript — do not edit. */
import { html, LitElement, nothing } from "lit";
import { guard } from "lit/directives/guard.js";
import { ifDefined } from "lit/directives/if-defined.js";
import { live } from "lit/directives/live.js";
import { repeat } from "lit/directives/repeat.js";
import {
  ownerEditingContext
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { editingIcon } from "@fgtclb/academic-persons-edit/frontend/profile/elements/icons.js";
import { profileContractContactsElementName } from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
const emptyContractContactEditor = Object.freeze({
  deleteConfirmation: "",
  error: "",
  errors: {},
  fields: [],
  mode: "view",
  open: false,
  pending: false,
  record: null,
  section: "",
  title: "",
  values: {}
});
const editorId = (section) => `profile-editing-contract-contact-editor-${section.identifier}`;
const fieldId = (index, field) => `profile-editing-contract-contact-field-${index}-${field.name}`;
const fieldErrorId = (index, field) => `profile-editing-contract-contact-field-error-${index}-${field.name}`;
const textValue = (value) => value === null || value === void 0 ? "" : String(value);
const classNames = (...names) => names.filter((name) => name !== false).join(" ");
class ProfileContractContactsElement extends LitElement {
  static properties = {
    context: { attribute: false },
    contract: { attribute: false },
    editor: { attribute: false },
    emptyMessage: { attribute: false },
    sections: { attribute: false }
  };
  constructor() {
    super();
    this.context = null;
    this.contract = null;
    this.editor = emptyContractContactEditor;
    this.emptyMessage = "";
    this.sections = [];
  }
  /** Light DOM. See `elements/document-editor.ts` for the six reasons. */
  createRenderRoot() {
    return this;
  }
  connectedCallback() {
    this.context ??= ownerEditingContext(this);
    super.connectedCallback();
  }
  render() {
    return html`${repeat(
      this.sections,
      (section) => section.identifier,
      (section) => this.#renderSection(section)
    )}`;
  }
  /** One section: its heading, its add control, its rows and its editor. */
  #renderSection(section) {
    var _a;
    const identifier = section.identifier;
    return html`<section class="pt-4 mt-4" data-pe-contract-contact-section=${identifier}>
      <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
        <h3 class="h4 mb-0">${section.label}</h3>
        <button
          type="button"
          class="btn rounded-0 btn-sm btn-link p-2"
          ?disabled=${this.editor.pending}
          aria-controls=${editorId(section)}
          aria-expanded=${this.#isOpen("add", identifier, null) ? "true" : "false"}
          data-pe-contract-contact-add
        >
          ${this.#icon("add")}
          <span class="visually-hidden">${((_a = this.context) == null ? void 0 : _a.labels.documentAdd) ?? ""}</span>
        </button>
      </div>
      ${this.#isOpen("add", identifier, null) ? html`<section
            id=${editorId(section)}
            class="border bg-body-tertiary p-3 p-lg-4 mb-3"
            aria-busy=${this.editor.pending ? "true" : "false"}
            style=${this.editor.pending ? "cursor: wait" : ""}
            data-pe-contract-contact-editor
            data-pe-contract-contact-form
          >
            ${this.#renderEditor()}
          </section>` : nothing}
      ${section.items.length > 0 ? html`<div class="border-top">
            ${repeat(
      section.items,
      (item) => item.uid,
      (item, index) => this.#renderItem(section, item, index)
    )}
          </div>` : html`<p
            class="bg-body-tertiary py-2 ps-3 small text-body-secondary"
            role="status"
          >
            ${this.emptyMessage}
          </p>`}
    </section>`;
  }
  /** One contact: its summary columns, its controls and its own editor. */
  #renderItem(section, item, index) {
    const identifier = section.identifier;
    const last = index === section.items.length - 1;
    return html`<article
      class=${classNames(
      "row g-0 align-items-center border-bottom py-2 ps-3",
      index % 2 === 0 && "bg-body-tertiary",
      item.hidden && "opacity-50"
    )}
      data-pe-contract-contact-item=${item.uid}
    >
      ${repeat(
      item.summary,
      (summary) => summary.label,
      (summary) => html`<div class="col-12 col-md py-1 pe-md-3">
          <div class="d-md-none fw-semibold mb-1">${summary.label}</div>
          <span>${summary.value === "" ? "\u2014" : summary.value}</span>
        </div>`
    )}
      <div
        class="col-12 col-md-auto d-flex flex-nowrap gap-1 justify-content-end align-self-center ms-auto pe-2"
        role="group"
        data-pe-contract-contact-actions
      >
        ${this.#renderItemAction("view", section, item)}
        ${this.#renderSortAction("down", "move-down", last)}
        ${this.#renderSortAction("up", "move-up", index === 0)}
        ${this.#renderItemAction("delete", section, item)}
        ${this.#renderItemAction("edit", section, item)}
      </div>
      ${this.editor.open && this.editor.mode !== "add" && this.editor.section === identifier && this.editor.record === item.uid ? html`<section
            id=${editorId(section)}
            class="col-12 border bg-body-tertiary p-3 p-lg-4 mt-3"
            aria-busy=${this.editor.pending ? "true" : "false"}
            style=${this.editor.pending ? "cursor: wait" : ""}
            data-pe-contract-contact-editor
            data-pe-contract-contact-form
          >
            ${this.#renderEditor()}
          </section>` : nothing}
    </article>`;
  }
  /**
   * One of the view, edit and delete controls of a row.
   *
   * The three hooks are written as three boolean attribute bindings rather than
   * as one computed attribute name: lit-html has no spread of its own, and the
   * spellings the controller delegates on are literals in this file this way,
   * which is what makes a rename findable from either side.
   */
  #renderItemAction(mode, section, item) {
    var _a, _b, _c;
    const label = {
      delete: (_a = this.context) == null ? void 0 : _a.labels.documentDelete,
      edit: (_b = this.context) == null ? void 0 : _b.labels.documentEdit,
      view: (_c = this.context) == null ? void 0 : _c.labels.documentView
    }[mode];
    return html`<button
      type="button"
      class="btn rounded-0 btn-sm btn-link text-body p-2"
      title=${label ?? ""}
      aria-label=${label ?? ""}
      ?disabled=${this.editor.pending}
      aria-controls=${editorId(section)}
      aria-expanded=${this.#isOpen(mode, section.identifier, item.uid) ? "true" : "false"}
      ?data-pe-contract-contact-view=${mode === "view"}
      ?data-pe-contract-contact-edit=${mode === "edit"}
      ?data-pe-contract-contact-delete=${mode === "delete"}
    >
      ${this.#icon(mode)}
    </button>`;
  }
  /** One of the two sort controls of a row, disabled at its end of the list. */
  #renderSortAction(direction, icon, atEnd) {
    var _a, _b;
    const label = direction === "up" ? (_a = this.context) == null ? void 0 : _a.labels.sortUp : (_b = this.context) == null ? void 0 : _b.labels.sortDown;
    return html`<button
      type="button"
      class="btn rounded-0 btn-sm btn-link text-body p-2"
      title=${label ?? ""}
      aria-label=${label ?? ""}
      ?disabled=${this.editor.pending || atEnd}
      data-pe-contract-contact-sort=${direction}
    >
      ${this.#icon(icon)}
    </button>`;
  }
  /** The editor itself, identical wherever the list puts it. */
  #renderEditor() {
    var _a;
    const editor = this.editor;
    const labels = (_a = this.context) == null ? void 0 : _a.labels;
    return html`<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <h4 class="h5 mb-0" tabindex="-1" data-pe-contract-contact-heading>
          ${editor.title}
        </h4>
        ${editor.mode === "delete" ? nothing : html`<button
              type="button"
              class="btn rounded-0 btn-outline-secondary btn-sm"
              ?disabled=${editor.pending}
              data-pe-contract-contact-cancel
            >
              ${(labels == null ? void 0 : labels.documentClose) ?? ""}
            </button>`}
      </div>
      ${editor.error === "" ? nothing : html`<div class="alert alert-danger" role="alert">${editor.error}</div>`}
      ${editor.mode === "delete" ? html`<p class="mb-4">${editor.deleteConfirmation}</p>` : nothing}
      ${editor.mode === "view" ? html`<dl class="row mb-0">
            ${repeat(
      editor.fields,
      (field) => field.name,
      (field) => html`
                <dt class="col-sm-4">${field.label}</dt>
                <dd class="col-sm-8">
                  ${field.displayValue === void 0 || field.displayValue === "" ? html`<span>${(labels == null ? void 0 : labels.documentEmpty) ?? ""}</span>` : html`<span>${field.displayValue}</span>`}
                </dd>
              `
    )}
          </dl>` : nothing}
      ${editor.mode === "add" || editor.mode === "edit" ? html`<div class="row g-3" data-pe-contract-contact-fields>
            ${repeat(
      editor.fields,
      (field) => field.name,
      (field, index) => this.#renderField(field, index)
    )}
          </div>` : nothing}
      ${editor.mode === "view" ? nothing : html`<div class="d-flex justify-content-end gap-2 mt-4">
            <button
              type="button"
              class="btn rounded-0 btn-outline-secondary"
              ?disabled=${editor.pending}
              data-pe-contract-contact-cancel
            >
              ${(labels == null ? void 0 : labels.documentClose) ?? ""}
            </button>
            <button
              type="button"
              class=${editor.mode === "delete" ? "btn rounded-0 btn-danger" : "btn rounded-0 btn-primary"}
              ?disabled=${editor.pending}
              data-pe-contract-contact-save
            >
              ${editor.pending ? html`<span
                    class="spinner-border spinner-border-sm me-1"
                    aria-hidden="true"
                  ></span>` : nothing}
              <span
                >${editor.mode === "delete" ? (labels == null ? void 0 : labels.documentDelete) ?? "" : (labels == null ? void 0 : labels.documentSave) ?? ""}</span
              >
            </button>
          </div>`}`;
  }
  /** One editable field: its label, its help, its control and its error. */
  #renderField(field, index) {
    const id = fieldId(index, field);
    const errorId = fieldErrorId(index, field);
    const message = this.editor.errors[field.name];
    return html`<div class="col-12 col-md-6">
      <div class="d-flex align-items-center">
        <label class="form-label" for=${id}>
          <span>${field.label}</span>
          ${field.required ? html`<span class="text-danger ms-1" aria-hidden="true">*</span>` : nothing}
        </label>
        ${this.#renderHelptext(field)}
      </div>
      ${this.#renderControl(field, id, errorId)}
      ${message === void 0 || message === "" ? nothing : html`<div id=${errorId} class="invalid-feedback d-block" role="alert">
            ${message}
          </div>`}
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
      ${this.#icon("help")}
    </button>`;
  }
  /**
   * A select or an input, which is every control the contact forms declare.
   *
   * `v-model` decided between `value` and `checked` by the control it stood on;
   * the same decision is made here, so a checkbox a form adds later keeps
   * working rather than writing the string "on" into the record.
   */
  #renderControl(field, id, errorId) {
    const disabled = field.disabled || this.editor.pending;
    const invalid = this.editor.errors[field.name] === void 0 ? "false" : "true";
    const autocomplete = field.autocomplete === void 0 || field.autocomplete === "" ? void 0 : field.autocomplete;
    if (field.type === "select") {
      const selected = textValue(this.editor.values[field.name]);
      return html`<select
        class="form-select"
        id=${id}
        name=${field.name}
        ?required=${field.required}
        ?disabled=${disabled}
        autocomplete=${ifDefined(autocomplete)}
        aria-describedby=${errorId}
        aria-invalid=${invalid}
        data-pe-contract-contact-field=${field.name}
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
    if (field.type === "checkbox") {
      return html`<input
        class="form-control"
        type="checkbox"
        id=${id}
        name=${field.name}
        ?disabled=${disabled}
        aria-describedby=${errorId}
        aria-invalid=${invalid}
        data-pe-contract-contact-field=${field.name}
        .checked=${live(this.editor.values[field.name] === true)}
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
      autocomplete=${ifDefined(autocomplete)}
      aria-describedby=${errorId}
      aria-invalid=${invalid}
      data-pe-contract-contact-field=${field.name}
      .value=${live(textValue(this.editor.values[field.name]))}
    />`;
  }
  /** Whether the open editor is the one this control would open. */
  #isOpen(mode, section, record) {
    return this.editor.open && this.editor.mode === mode && this.editor.section === section && this.editor.record === record;
  }
  /** Cloned from the `<template data-pe-icon="...">` block of `Index.html`. */
  #icon(name) {
    return guard(
      [this.context, name],
      () => editingIcon(this.context, name)
    );
  }
}
const registerProfileContractContactsElement = () => {
  if (customElements.get(profileContractContactsElementName) !== void 0) {
    return;
  }
  customElements.define(
    profileContractContactsElementName,
    ProfileContractContactsElement
  );
};
export {
  ProfileContractContactsElement,
  emptyContractContactEditor,
  profileContractContactsElementName,
  registerProfileContractContactsElement
};
