/**
 * `<academic-persons-edit-contract-contacts>` - the addresses, phone numbers
 * and mail addresses of one contract, and the inline editor that adds, shows,
 * changes and deletes one of them.
 *
 * It replaces two Fluid partials at once:
 * `Partials/Profile/Documents/ContractContacts.html` (154 lines, 44 Vue
 * directives) and `Partials/Profile/Documents/ContractContactEditor.html` (161
 * lines, 62 directives). Both are deleted, and for the same reason as the
 * document editor's own partial: none of it can be server rendered. The
 * sections, their items, the summary columns of a row, and the fields, labels
 * and options of the editor all come from responses - the contract's own
 * `documentForm` answer for the list, `contractContactForm` for the editor - so
 * a template that is a function of that data is the honest shape.
 *
 * The two partials become one element because they were never two things: the
 * editor was rendered twice by the list, once for an "add" below the section
 * heading and once for a "view", "edit" or "delete" inside the row it belongs
 * to, and it read the same state in both places. A second element would have to
 * be handed that state twice and would buy no boundary.
 *
 * ## What it does not own
 *
 * The requests. `profile/documents.ts` keeps `openContractContact()`,
 * `closeContractContact()`, `submitContractContact()` and
 * `sortContractContact()`, keeps the state they write, and keeps the endpoints
 * - this element renders that state and reports the presses. The alternative,
 * an element that calls the five contact endpoints itself, was rejected twice
 * over: the contract's record and the open document editor are the controller's
 * to know, and the behavioural suite that pins those four methods drives them
 * without a custom element registry, which is what makes them testable in the
 * first place.
 *
 * The presses are reported as *native clicks* on buttons carrying
 * `data-pe-contract-contact-*`, delegated on the plugin root by the controller,
 * rather than as custom events of this element. That is not a shortcut: Lit
 * creates this element inside the document editor's template, so the controller
 * never holds it and could not attach a listener to it, and
 * `openContractContact()` takes the event to find the button focus has to
 * return to. It is the same mechanism the document list already uses.
 *
 * ## Light DOM, no decorators
 *
 * `createRenderRoot()` returns `this`, and the reactive properties are declared
 * with `declare` and assigned in the constructor. Both for the reasons
 * `elements/document-editor.ts` states in full; the short form is that the
 * theme's Bootstrap stylesheet and Bootstrap's own popover JavaScript have to
 * reach these controls, and that a class field would shadow the accessor Lit
 * installs on the prototype.
 */
import { html, LitElement, nothing, type TemplateResult } from "lit";
import { guard } from "lit/directives/guard.js";
import { ifDefined } from "lit/directives/if-defined.js";
import { live } from "lit/directives/live.js";
import { repeat } from "lit/directives/repeat.js";
import {
  ownerEditingContext,
  type EditingContext,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { editingIcon } from "@fgtclb/academic-persons-edit/frontend/profile/elements/icons.js";
import { profileContractContactsElementName } from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import type {
  ContractContactItem,
  ContractContactSection,
  DocumentField,
  DocumentMode,
  DocumentValue,
} from "@fgtclb/academic-persons-edit/frontend/profile/documents.js";

/** The tag name of this element. Public API from the moment it ships. */
export { profileContractContactsElementName };

/**
 * The open contact editor, as this element needs it.
 *
 * One property rather than eleven: it is a snapshot of the controller's own
 * `ContractContactState` and is assigned in one place, and a partially applied
 * set would render an editor that is briefly wrong - the fields of one contact
 * under the heading of another.
 *
 * `readonly` throughout, which is the whole mechanism. Lit re-renders on the
 * assignment of a property, never on a write inside the object it holds, so the
 * controller hands over a fresh object every time and this element can neither
 * write into the one it was given nor be surprised by a write from elsewhere.
 */
export interface ProfileContractContactEditorState {
  readonly deleteConfirmation: string;
  readonly error: string;
  readonly errors: Record<string, string>;
  readonly fields: DocumentField[];
  readonly mode: DocumentMode;
  readonly open: boolean;
  readonly pending: boolean;
  readonly record: number | null;
  readonly section: string;
  readonly title: string;
  readonly values: Record<string, DocumentValue>;
}

/** No editor open, which is what a contract in view mode starts in. */
export const emptyContractContactEditor: ProfileContractContactEditorState =
  Object.freeze({
    deleteConfirmation: "",
    error: "",
    errors: {},
    fields: [],
    mode: "view" as DocumentMode,
    open: false,
    pending: false,
    record: null,
    section: "",
    title: "",
    values: {},
  });

/**
 * The id of the editor of one section.
 *
 * One per section and not one per item, because only one editor is open at a
 * time: the "add" of the section and the "view", "edit" and "delete" of every
 * row of it all point their `aria-controls` at the same id, exactly as the
 * partial did.
 */
const editorId = (section: ContractContactSection): string =>
  `profile-editing-contract-contact-editor-${section.identifier}`;

const fieldId = (index: number, field: DocumentField): string =>
  `profile-editing-contract-contact-field-${index}-${field.name}`;

const fieldErrorId = (index: number, field: DocumentField): string =>
  `profile-editing-contract-contact-field-error-${index}-${field.name}`;

const textValue = (value: DocumentValue | undefined): string =>
  value === null || value === undefined ? "" : String(value);

const classNames = (...names: (string | false)[]): string =>
  names.filter((name): name is string => name !== false).join(" ");

/**
 * The element.
 *
 * Public surface: the tag name, the five reactive properties below, and the
 * `data-pe-contract-contact-*` hooks it renders, which are what the controller
 * delegates on. It observes no attributes, dispatches no events of its own and
 * calls no endpoint - it is created by the document editor's template and is
 * never spelled in markup.
 */
export class ProfileContractContactsElement extends LitElement {
  static override properties = {
    context: { attribute: false },
    contract: { attribute: false },
    editor: { attribute: false },
    emptyMessage: { attribute: false },
    sections: { attribute: false },
  };

  declare context: EditingContext | null;
  declare contract: number | null;
  declare editor: ProfileContractContactEditorState;
  declare emptyMessage: string;
  declare sections: ContractContactSection[];

  constructor() {
    super();
    this.context = null;
    this.contract = null;
    this.editor = emptyContractContactEditor;
    this.emptyMessage = "";
    this.sections = [];
  }

  /** Light DOM. See `elements/document-editor.ts` for the six reasons. */
  override createRenderRoot(): HTMLElement {
    return this;
  }

  override connectedCallback(): void {
    // Assigned by the document editor's template, which creates this element.
    // The fallback covers nothing today and costs one call; it is what keeps
    // the element usable without its creator.
    this.context ??= ownerEditingContext(this);
    super.connectedCallback();
  }

  override render(): TemplateResult {
    return html`${repeat(
      this.sections,
      (section): string => section.identifier,
      (section): TemplateResult => this.#renderSection(section),
    )}`;
  }

  /** One section: its heading, its add control, its rows and its editor. */
  #renderSection(section: ContractContactSection): TemplateResult {
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
          <span class="visually-hidden">${this.context?.labels.documentAdd ?? ""}</span>
        </button>
      </div>
      ${this.#isOpen("add", identifier, null)
        ? html`<section
            id=${editorId(section)}
            class="border bg-body-tertiary p-3 p-lg-4 mb-3"
            aria-busy=${this.editor.pending ? "true" : "false"}
            style=${this.editor.pending ? "cursor: wait" : ""}
            data-pe-contract-contact-editor
            data-pe-contract-contact-form
          >
            ${this.#renderEditor()}
          </section>`
        : nothing}
      ${section.items.length > 0
        ? html`<div class="border-top">
            ${repeat(
              section.items,
              (item): number => item.uid,
              (item, index): TemplateResult => this.#renderItem(section, item, index),
            )}
          </div>`
        : html`<p
            class="bg-body-tertiary py-2 ps-3 small text-body-secondary"
            role="status"
          >
            ${this.emptyMessage}
          </p>`}
    </section>`;
  }

  /** One contact: its summary columns, its controls and its own editor. */
  #renderItem(
    section: ContractContactSection,
    item: ContractContactItem,
    index: number,
  ): TemplateResult {
    const identifier = section.identifier;
    const last = index === section.items.length - 1;

    return html`<article
      class=${classNames(
        "row g-0 align-items-center border-bottom py-2 ps-3",
        index % 2 === 0 && "bg-body-tertiary",
        item.hidden && "opacity-50",
      )}
      data-pe-contract-contact-item=${item.uid}
    >
      ${repeat(
        item.summary,
        (summary): string => summary.label,
        (summary): TemplateResult => html`<div class="col-12 col-md py-1 pe-md-3">
          <div class="d-md-none fw-semibold mb-1">${summary.label}</div>
          <span>${summary.value === "" ? "—" : summary.value}</span>
        </div>`,
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
      ${this.editor.open &&
      this.editor.mode !== "add" &&
      this.editor.section === identifier &&
      this.editor.record === item.uid
        ? html`<section
            id=${editorId(section)}
            class="col-12 border bg-body-tertiary p-3 p-lg-4 mt-3"
            aria-busy=${this.editor.pending ? "true" : "false"}
            style=${this.editor.pending ? "cursor: wait" : ""}
            data-pe-contract-contact-editor
            data-pe-contract-contact-form
          >
            ${this.#renderEditor()}
          </section>`
        : nothing}
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
  #renderItemAction(
    mode: "view" | "edit" | "delete",
    section: ContractContactSection,
    item: ContractContactItem,
  ): TemplateResult {
    const label = {
      delete: this.context?.labels.documentDelete,
      edit: this.context?.labels.documentEdit,
      view: this.context?.labels.documentView,
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
  #renderSortAction(
    direction: "up" | "down",
    icon: string,
    atEnd: boolean,
  ): TemplateResult {
    const label =
      direction === "up" ? this.context?.labels.sortUp : this.context?.labels.sortDown;

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
  #renderEditor(): TemplateResult {
    const editor = this.editor;
    const labels = this.context?.labels;

    return html`<div class="d-flex align-items-center justify-content-between gap-3 mb-4">
        <h4 class="h5 mb-0" tabindex="-1" data-pe-contract-contact-heading>
          ${editor.title}
        </h4>
        ${editor.mode === "delete"
          ? nothing
          : html`<button
              type="button"
              class="btn rounded-0 btn-outline-secondary btn-sm"
              ?disabled=${editor.pending}
              data-pe-contract-contact-cancel
            >
              ${labels?.documentClose ?? ""}
            </button>`}
      </div>
      ${editor.error === ""
        ? nothing
        : html`<div class="alert alert-danger" role="alert">${editor.error}</div>`}
      ${editor.mode === "delete"
        ? html`<p class="mb-4">${editor.deleteConfirmation}</p>`
        : nothing}
      ${editor.mode === "view"
        ? html`<dl class="row mb-0">
            ${repeat(
              editor.fields,
              (field): string => field.name,
              (field): TemplateResult => html`
                <dt class="col-sm-4">${field.label}</dt>
                <dd class="col-sm-8">
                  ${field.displayValue === undefined || field.displayValue === ""
                    ? html`<span>${labels?.documentEmpty ?? ""}</span>`
                    : html`<span>${field.displayValue}</span>`}
                </dd>
              `,
            )}
          </dl>`
        : nothing}
      ${editor.mode === "add" || editor.mode === "edit"
        ? html`<div class="row g-3" data-pe-contract-contact-fields>
            ${repeat(
              editor.fields,
              (field): string => field.name,
              (field, index): TemplateResult => this.#renderField(field, index),
            )}
          </div>`
        : nothing}
      ${editor.mode === "view"
        ? nothing
        : html`<div class="d-flex justify-content-end gap-2 mt-4">
            <button
              type="button"
              class="btn rounded-0 btn-outline-secondary"
              ?disabled=${editor.pending}
              data-pe-contract-contact-cancel
            >
              ${labels?.documentClose ?? ""}
            </button>
            <button
              type="button"
              class=${editor.mode === "delete"
                ? "btn rounded-0 btn-danger"
                : "btn rounded-0 btn-primary"}
              ?disabled=${editor.pending}
              data-pe-contract-contact-save
            >
              ${editor.pending
                ? html`<span
                    class="spinner-border spinner-border-sm me-1"
                    aria-hidden="true"
                  ></span>`
                : nothing}
              <span
                >${editor.mode === "delete"
                  ? (labels?.documentDelete ?? "")
                  : (labels?.documentSave ?? "")}</span
              >
            </button>
          </div>`}`;
  }

  /** One editable field: its label, its help, its control and its error. */
  #renderField(field: DocumentField, index: number): TemplateResult {
    const id = fieldId(index, field);
    const errorId = fieldErrorId(index, field);
    const message = this.editor.errors[field.name];

    return html`<div class="col-12 col-md-6">
      <div class="d-flex align-items-center">
        <label class="form-label" for=${id}>
          <span>${field.label}</span>
          ${field.required
            ? html`<span class="text-danger ms-1" aria-hidden="true">*</span>`
            : nothing}
        </label>
        ${this.#renderHelptext(field)}
      </div>
      ${this.#renderControl(field, id, errorId)}
      ${message === undefined || message === ""
        ? nothing
        : html`<div id=${errorId} class="invalid-feedback d-block" role="alert">
            ${message}
          </div>`}
    </div>`;
  }

  #renderHelptext(field: DocumentField): TemplateResult | typeof nothing {
    if (field.helptext === undefined || field.helptext === "") {
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
  #renderControl(field: DocumentField, id: string, errorId: string): TemplateResult {
    const disabled = field.disabled || this.editor.pending;
    const invalid = this.editor.errors[field.name] === undefined ? "false" : "true";
    const autocomplete =
      field.autocomplete === undefined || field.autocomplete === ""
        ? undefined
        : field.autocomplete;
    if (field.type === "select") {
      // The selectedness is written on the options and not as "value" on the
      // "<select>": lit-html commits an element's own parts before the child
      // part that renders its children, so a "value" set on the select would be
      // applied while it still has no option to match.
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
          (option): string => textValue(option.value),
          (option): TemplateResult =>
            html`<option
              value=${textValue(option.value)}
              .selected=${live(textValue(option.value) === selected)}
            >
              ${option.label}
            </option>`,
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
  #isOpen(mode: DocumentMode, section: string, record: number | null): boolean {
    return (
      this.editor.open &&
      this.editor.mode === mode &&
      this.editor.section === section &&
      this.editor.record === record
    );
  }

  /** Cloned from the `<template data-pe-icon="...">` block of `Index.html`. */
  #icon(name: string): unknown {
    return guard(
      [this.context, name],
      (): Node | typeof nothing => editingIcon(this.context, name),
    );
  }
}

/**
 * Defines the element, idempotently.
 *
 * Called by the entry point and by `profile/documents.ts`, which creates the
 * document editor that renders this one. A second call is a no-op rather than
 * the `NotSupportedError` a repeated `customElements.define()` raises.
 */
export const registerProfileContractContactsElement = (): void => {
  if (customElements.get(profileContractContactsElementName) !== undefined) {
    return;
  }
  customElements.define(
    profileContractContactsElementName,
    ProfileContractContactsElement,
  );
};
