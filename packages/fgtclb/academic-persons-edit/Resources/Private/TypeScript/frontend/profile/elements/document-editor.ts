/**
 * `<academic-persons-edit-document-editor>` - the collapse panel that views,
 * adds, edits or deletes one document or contract.
 *
 * This is the first `LitElement` of this extension, and it is one because its
 * markup cannot be server rendered: the fields, their labels, their options and
 * their display values all come from the `documentForm` response, and the
 * partial that used to render them was a Vue template with 114 directives in
 * 266 lines. Everything the editor shows is derived from data, so a template
 * that is a function of that data is the honest shape.
 *
 * ## Light DOM, never a shadow root
 *
 * `createRenderRoot()` returns `this`. Six independent reasons, any one of
 * which decides it:
 *
 * 1. The markup is Bootstrap 5 (`btn`, `row`, `col-*`, `form-control`,
 *    `form-check`, `alert`, `spinner-border`, `visually-hidden`), delivered by
 *    the site's theme. A shadow root cuts all of it off and there is no way to
 *    re-import an unknown site's stylesheet into it.
 * 2. CKEditor 5 does not support a classic editor inside a shadow root: its UI
 *    injects document level `<style>` elements and its balloon positioning and
 *    selection tracking work against `document`.
 * 3. Bootstrap's own JavaScript is document scoped - `bootstrap.Popover`
 *    positions the help popovers against `document.body`.
 * 4. The class names are the integrator contract. Existing site CSS targets
 *    `.academic-persons-profile-editing__*`, and scoping those away would be a
 *    second, silent breaking change on top of the partial that is removed.
 * 5. The SCSS pipeline emits one stylesheet per extension. A shadow root would
 *    force the same declarations to be duplicated into a `css` literal here.
 * 6. jsdom implements neither `adoptedStyleSheets` nor
 *    `CSSStyleSheet.replaceSync`, so the behavioural suite could not render the
 *    element outside a browser at all.
 *
 * Consequence, and it is stated in the changelog: this element provides no
 * style encapsulation, and `Partials/Profile/Documents/Editor.html` is no
 * longer a Fluid override point.
 *
 * ## No decorators
 *
 * `static properties` and a plain `customElements.define()`, never
 * `@customElement` / `@property`. The behavioural suite runs these sources
 * under node's type stripping, which erases annotations but does not transform,
 * and `Build/tsconfig.tests.json` sets `erasableSyntaxOnly` so that a decorator
 * is a type error rather than a runtime one.
 *
 * The reactive properties are therefore declared with `declare` and given their
 * value in the constructor. That is not style: `target` is ES2022, so
 * `useDefineForClassFields` is on, and a class *field* would define an own
 * property on the instance that shadows the accessor Lit installs on the
 * prototype - the element would render once and never again.
 *
 * ## Where CKEditor lives
 *
 * Not here. A rich text field is rendered as an
 * `<academic-persons-edit-rich-text>` element with no children in this
 * template, so lit-html creates and removes the element and never patches
 * inside it; the textarea and the editor on it belong to that element alone.
 * `repeat()` keys the field list by name, so a re-render cannot hand one
 * field's element to another field.
 */
import { html, LitElement, nothing, type PropertyValues, type TemplateResult } from "lit";
import { guard } from "lit/directives/guard.js";
import { ifDefined } from "lit/directives/if-defined.js";
import { live } from "lit/directives/live.js";
import { repeat } from "lit/directives/repeat.js";
import {
  ownerEditingContext,
  type EditingContext,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { editingIcon } from "@fgtclb/academic-persons-edit/frontend/profile/elements/icons.js";
import {
  profileContractContactsElementName,
  profileDocumentEditorElementName,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import {
  emptyContractContactEditor,
  type ProfileContractContactEditorState,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/contract-contacts.js";
import { createElementTransition } from "@fgtclb/academic-persons-edit/frontend/profile/elements/transition.js";
import type { ProfileRichTextConfiguration } from "@fgtclb/academic-persons-edit/frontend/profile/elements/rich-text.js";
import { parseRichTextPreview } from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";
import type {
  ContractContactSection,
  DocumentField,
  DocumentMode,
  DocumentValue,
} from "@fgtclb/academic-persons-edit/frontend/profile/documents.js";

/** The tag name of this element. Public API from the moment it ships. */
export { profileDocumentEditorElementName };

/** The editor asks its owner to close it: the cancel button was pressed. */
export const documentEditorCloseEvent = "pe:document-close";

/** The editor asks its owner to save: the form was submitted. */
export const documentEditorSubmitEvent = "pe:document-submit";

/** A control changed. The payload is `ProfileDocumentEditorInputDetail`. */
export const documentEditorInputEvent = "pe:document-input";

/** The leave transition is over and the element may be removed. */
export const documentEditorClosedEvent = "pe:document-closed";

/** The payload of `pe:document-input`. */
export interface ProfileDocumentEditorInputDetail {
  readonly name: string;
  readonly value: DocumentValue;
}

/**
 * The transition prefix, which is `<Transition name="...">` of the partial this
 * element replaces. The declarations it selects are in
 * `Resources/Private/Scss/frontend/profile-editing.scss` and are unchanged.
 */
const runDocumentTransition = createElementTransition(
  "academic-persons-profile-editing-document-collapse",
);

const fieldId = (index: number, field: DocumentField): string =>
  `profile-editing-document-field-${index}-${field.name}`;

const fieldErrorId = (index: number, field: DocumentField): string =>
  `profile-editing-document-field-error-${index}-${field.name}`;

const columnClass = (field: DocumentField): string => {
  const column =
    field.columnClass !== undefined && field.columnClass !== ""
      ? field.columnClass
      : field.type === "textarea" || field.type === "checkbox"
        ? "col-12"
        : "col-12 col-md-6";

  return field.compactCheckbox === true ? `${column} d-flex align-items-end` : column;
};

const textValue = (value: DocumentValue | undefined): string =>
  value === null || value === undefined ? "" : String(value);

/**
 * The sanitised markup of a rich text display value, as nodes rather than as a
 * string.
 *
 * `v-html` handed the allow-list output back to Vue as a string, which parsed
 * it a second time. Here the parsed nodes are imported into the page directly:
 * lit-html renders a `Node` value as itself, so nothing is re-parsed and the
 * `unsafeHTML` directive - which would re-open exactly the hole
 * `parseRichTextPreview()` closes - is not needed.
 */
const richTextFragment = (value: string): DocumentFragment => {
  const parsed = parseRichTextPreview(value);
  const fragment = document.createDocumentFragment();
  Array.from(parsed.body.childNodes).forEach((node): void => {
    fragment.append(document.importNode(node, true));
  });

  return fragment;
};

/**
 * The element.
 *
 * Public surface: the tag name, the reactive properties below, and the four
 * `pe:document-*` events. It observes no attributes - it is created and driven
 * by `profile/documents.ts` and never spelled in markup.
 */
export class ProfileDocumentEditorElement extends LitElement {
  static override properties = {
    contactEditor: { attribute: false },
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
    values: { attribute: false },
  };

  declare contactEditor: ProfileContractContactEditorState;
  declare contactEmptyMessage: string;
  declare contactSections: ContractContactSection[];
  declare context: EditingContext | null;
  declare deleteConfirmation: string;
  declare error: string;
  declare errors: Record<string, string>;
  declare fields: DocumentField[];
  declare heading: string;
  declare kind: "document" | "contract";
  declare mode: DocumentMode;
  declare open: boolean;
  declare pending: boolean;
  declare record: number | null;
  declare values: Record<string, DocumentValue>;

  #cancelTransition: (() => void) | null = null;
  #transitioned = false;

  constructor() {
    super();
    this.contactEditor = emptyContractContactEditor;
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
  override createRenderRoot(): HTMLElement {
    return this;
  }

  /**
   * Resolves once the contract contacts below have rendered as well.
   *
   * `updateComplete` is per element, and the contacts are a `LitElement` of
   * their own: this element assigns their properties while it renders, which
   * schedules *their* update for a later microtask. A caller that awaits this
   * element and then queries for the contact editor - which is what
   * `openContractContact()` does before it focuses the first control - would
   * otherwise look at the document before the list is in it. Lit documents the
   * override for exactly this, and it composes: the child exists by the time
   * `super.getUpdateComplete()` resolves, because it was created during the
   * render that promise reports.
   */
  override async getUpdateComplete(): Promise<boolean> {
    const completed = await super.getUpdateComplete();
    // Read through the tag name and a structural type rather than through the
    // class: importing the element here would make the module graph a cycle for
    // nothing, and "updateComplete" is the whole contract that is needed.
    const contacts = this.querySelector(profileContractContactsElementName) as
      | (Element & { updateComplete?: Promise<unknown> })
      | null;
    await contacts?.updateComplete;

    return completed;
  }

  override connectedCallback(): void {
    // Assigned by "profile/documents.ts", which creates this element. The
    // fallback covers nothing today and costs one call; it is what keeps the
    // element usable without its creator.
    this.context ??= ownerEditingContext(this);
    super.connectedCallback();
  }

  override render(): TemplateResult {
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
              ${this.mode === "delete"
                ? nothing
                : html`<button
                    type="button"
                    class="btn rounded-0 btn-outline-secondary btn-sm"
                    ?disabled=${this.pending}
                    @click=${this.#onClose}
                  >
                    ${this.context?.labels.documentClose ?? ""}
                  </button>`}
            </div>
            ${this.error === ""
              ? nothing
              : html`<div class="alert alert-danger" role="alert">${this.error}</div>`}
            ${this.mode === "delete"
              ? html`<p class="mb-4">${this.deleteConfirmation}</p>`
              : nothing}
            ${this.mode === "view" ? this.#renderDisplayValues() : nothing}
            ${this.mode === "view" && this.kind === "contract"
              ? html`<div class="mt-5">${this.#renderContactSections()}</div>`
              : nothing}
            ${this.mode === "add" || this.mode === "edit"
              ? html`<div class="row g-3" data-pe-document-fields>
                  ${repeat(
                    this.fields,
                    (field): string => field.name,
                    (field, index): TemplateResult => this.#renderField(field, index),
                  )}
                </div>`
              : nothing}
            ${this.mode === "view" ? nothing : this.#renderActions()}
          </form>
        </div>
      </section>
    `;
  }

  override firstUpdated(): void {
    if (this.open) {
      this.#transitioned = true;
      this.#startTransition("enter", (): void => undefined);
    }
  }

  override updated(changed: PropertyValues<this>): void {
    if (!changed.has("open")) {
      return;
    }
    if (this.open && !this.#transitioned) {
      this.#transitioned = true;
      this.#startTransition("enter", (): void => undefined);
      return;
    }
    if (!this.open && this.#transitioned) {
      this.#transitioned = false;
      this.#startTransition("leave", (): void => this.#reportClosed());
    }
  }

  /** The `<dl>` of the view mode: one term and one description per field. */
  #renderDisplayValues(): TemplateResult {
    return html`<dl class="row mb-0">
      ${repeat(
        this.fields,
        (field): string => field.name,
        (field): TemplateResult => html`
          <dt class="col-sm-4">${field.label}</dt>
          <dd class="col-sm-8">
            ${field.richText && field.displayValue !== undefined && field.displayValue !== ""
              ? html`<div>
                  ${guard([field.displayValue], (): DocumentFragment =>
                    richTextFragment(field.displayValue ?? ""),
                  )}
                </div>`
              : field.displayValue !== undefined && field.displayValue !== ""
                ? html`<span>${field.displayValue}</span>`
                : html`<span>${this.context?.labels.documentEmpty ?? ""}</span>`}
          </dd>
        `,
      )}
    </dl>`;
  }

  /**
   * The contacts of a contract, rendered by
   * `<academic-persons-edit-contract-contacts>`.
   *
   * A second element rather than a block of this template, because the contacts
   * are a list of their own with an editor of their own: they are answered by
   * the contract's `documentForm` response, they are written against five
   * endpoints this editor never calls, and they are shown in exactly one of the
   * four modes this element renders. Everything it needs is a property this
   * element already holds, so the whole coupling is the five bindings below.
   */
  #renderContactSections(): TemplateResult {
    return html`<academic-persons-edit-contract-contacts
      .context=${this.context}
      .contract=${this.record}
      .sections=${this.contactSections}
      .emptyMessage=${this.contactEmptyMessage}
      .editor=${this.contactEditor}
    ></academic-persons-edit-contract-contacts>`;
  }

  /** One editable field: its label, its help, its control and its error. */
  #renderField(field: DocumentField, index: number): TemplateResult {
    const id = fieldId(index, field);
    const errorId = fieldErrorId(index, field);
    const message = this.errors[field.name];

    return html`<div class=${columnClass(field)}>
      <div class=${field.type === "checkbox" ? "form-check" : ""}>
        ${field.type === "checkbox"
          ? nothing
          : html`<div class="d-flex align-items-center">
              <label class="form-label" for=${id}>
                <span>${field.label}</span>
                ${field.required
                  ? html`<span class="text-danger ms-1" aria-hidden="true">*</span>`
                  : nothing}
              </label>
              ${this.#renderHelptext(field)}
            </div>`}
        ${this.#renderControl(field, id, errorId)}
        ${field.type === "checkbox"
          ? html`<label class="form-check-label ms-2" for=${id}>${field.label}</label>
              ${this.#renderHelptext(field)}`
          : nothing}
        ${field.richText && field.characterLimit !== undefined && field.characterLimit > 0
          ? html`<div
              class="form-text text-end"
              aria-live="polite"
              data-pe-character-counter
              data-pe-for=${id}
            >
              0 / <span>${field.characterLimit}</span>
            </div>`
          : nothing}
        ${message === undefined || message === ""
          ? nothing
          : html`<div id=${errorId} class="invalid-feedback d-block" role="alert">
              ${message}
            </div>`}
      </div>
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
      ${guard([this.context], (): Node | typeof nothing => editingIcon(this.context, "help"))}
    </button>`;
  }

  #renderControl(
    field: DocumentField,
    id: string,
    errorId: string,
  ): TemplateResult {
    const disabled = field.disabled || this.pending;
    const invalid = this.errors[field.name] === undefined ? "false" : "true";
    if (field.type === "select") {
      // The selectedness is written on the options and not as "value" on the
      // "<select>": lit-html commits an element's own parts before the child
      // part that renders its children, so a "value" set on the select would
      // be applied while it still has no option to match.
      const selected = textValue(this.values[field.name]);

      return html`<select
        class="form-select"
        id=${id}
        name=${field.name}
        ?required=${field.required}
        ?disabled=${disabled}
        autocomplete=${ifDefined(
          field.autocomplete === undefined || field.autocomplete === ""
            ? undefined
            : field.autocomplete,
        )}
        aria-describedby=${errorId}
        aria-invalid=${invalid}
        data-pe-document-field=${field.name}
        @change=${this.#onValueChanged}
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
    if (field.type === "textarea" && field.richText) {
      // The one control lit-html must not own. See "elements/rich-text.ts".
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
          required: field.required,
        } satisfies ProfileRichTextConfiguration}
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
          field.autocomplete === undefined || field.autocomplete === ""
            ? undefined
            : field.autocomplete,
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
        field.autocomplete === undefined || field.autocomplete === ""
          ? undefined
          : field.autocomplete,
      )}
      aria-describedby=${errorId}
      aria-invalid=${invalid}
      data-pe-document-field=${field.name}
      .value=${live(textValue(this.values[field.name]))}
      @input=${this.#onValueChanged}
    />`;
  }

  /** Cancel and save, or cancel and delete. */
  #renderActions(): TemplateResult {
    const labels = this.context?.labels;

    return html`<div class="d-flex justify-content-end gap-2 mt-4">
      <button
        type="button"
        class="btn rounded-0 btn-outline-secondary"
        ?disabled=${this.pending}
        @click=${this.#onClose}
      >
        ${labels?.documentClose ?? ""}
      </button>
      <button
        type="submit"
        class=${this.mode === "delete" ? "btn rounded-0 btn-danger" : "btn rounded-0 btn-primary"}
        ?disabled=${this.pending}
      >
        ${this.pending
          ? html`<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>`
          : nothing}
        <span
          >${this.mode === "delete"
            ? (labels?.documentDelete ?? "")
            : (labels?.documentSave ?? "")}</span
        >
      </button>
    </div>`;
  }

  #onClose = (): void => {
    this.dispatchEvent(new CustomEvent(documentEditorCloseEvent, { bubbles: true }));
  };

  #onSubmit = (event: Event): void => {
    // Never through the browser: the endpoints answer JSON and the page stays.
    event.preventDefault();
    this.dispatchEvent(new CustomEvent(documentEditorSubmitEvent, { bubbles: true }));
  };

  #onValueChanged = (event: Event): void => {
    const control = event.target;
    if (
      !(control instanceof HTMLInputElement) &&
      !(control instanceof HTMLSelectElement) &&
      !(control instanceof HTMLTextAreaElement)
    ) {
      return;
    }
    const name = control.dataset.peDocumentField;
    if (name === undefined) {
      return;
    }
    const value =
      control instanceof HTMLInputElement && control.type === "checkbox"
        ? control.checked
        : control.value;
    this.dispatchEvent(
      new CustomEvent<ProfileDocumentEditorInputDetail>(documentEditorInputEvent, {
        bubbles: true,
        detail: { name, value },
      }),
    );
  };

  #reportClosed(): void {
    // A frame later, never in this turn. "updated()" runs inside Lit's own
    // update cycle and the owner removes this element when the close is
    // reported - tearing the tree out from inside the update that produced it
    // is how a reactive element ends up patching detached nodes. Vue's
    // "after-leave" was a frame away for the same reason.
    globalThis.requestAnimationFrame((): void => {
      this.dispatchEvent(new CustomEvent(documentEditorClosedEvent, { bubbles: true }));
    });
  }

  #startTransition(kind: "enter" | "leave", done: () => void): void {
    const section = this.querySelector<HTMLElement>("[data-pe-document-view-container]");
    if (section === null) {
      done();
      return;
    }
    this.#cancelTransition?.();
    this.#cancelTransition = null;
    // "settled" rather than an unconditional assignment: a transition that has
    // nothing to animate finishes inside the call, and storing the cancellation
    // it hands back afterwards would leave a finished transition looking live.
    let settled = false;
    const cancel = runDocumentTransition(section, kind, (): void => {
      settled = true;
      this.#cancelTransition = null;
      done();
    });
    if (!settled) {
      this.#cancelTransition = cancel;
    }
  }
}

/**
 * Defines the element, idempotently.
 *
 * Called by the entry point. A second call is a no-op rather than the
 * `NotSupportedError` a repeated `customElements.define()` raises.
 */
export const registerProfileDocumentEditorElement = (): void => {
  if (customElements.get(profileDocumentEditorElementName) !== undefined) {
    return;
  }
  customElements.define(profileDocumentEditorElementName, ProfileDocumentEditorElement);
};
