/**
 * `<academic-persons-edit-document-editor>` - the collapse panel that views,
 * adds, edits or deletes one document or contract.
 *
 * ## It controls markup, it does not render any
 *
 * The panel is `Partials/Profile/Documents/Editor.html`, rendered once per
 * page as `<template data-pe-proto="document-panel">`, and its field rows and
 * controls are the prototypes of `Partials/Profile/Prototypes.html`. This
 * element clones them and fills their slots. Every tag, class and label of the
 * document editor is therefore in a Fluid file an integrator can override -
 * which is the whole difference to rendering the panel from JavaScript, and
 * the reason `documentForm` still answers field descriptors and no HTML.
 *
 * What an override cannot change is the order things are inserted in and which
 * slot carries which value. Both are here, both are typed
 * (`PrototypeSlots`/`PrototypeLists`), and a slot the partial stops emitting
 * is a failure of the functional prototype inventory test.
 *
 * ## Why `LitElement`
 *
 * This is the element that uses the lifecycle rather than tolerating it. A
 * keystroke assigns `values`, a refusal assigns `errors`, a request assigns
 * `pending` - and none of those may rebuild the panel, because rebuilding it
 * replaces every control the visitor is typing in and every live CKEditor.
 * `changedProperties` is what separates the structural change (`fields`,
 * `mode`, `kind`, `record`) that rebuilds from the value change that patches,
 * and the batching is what turns the five assignments of one `openDocument()`
 * into one DOM pass.
 *
 * It renders nothing: `createRenderRoot()` of `elements/base.ts` returns a
 * detached node, so lit-html can never reach the children this element writes.
 *
 * ## No decorators
 *
 * `static properties` and a plain `customElements.define()`. The behavioural
 * suite runs these sources under node's type stripping, which erases
 * annotations but does not transform, and `Build/tsconfig.tests.json` sets
 * `erasableSyntaxOnly` so a decorator is a type error rather than a runtime
 * one. The reactive properties are declared with `declare` and given their
 * value in the constructor: `target` is ES2022, so `useDefineForClassFields`
 * is on and a class *field* would define an own property that shadows the
 * accessor Lit installs on the prototype - the element would update once and
 * never again.
 */
import type { PropertyValues } from "lit";
import { hooks } from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  ownerEditingContext,
  type EditingContext,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { ProfileEditingElement } from "@fgtclb/academic-persons-edit/frontend/profile/elements/base.js";
import {
  applyFieldErrors,
  cloneDisplayRow,
  cloneField,
  fieldControlId,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/field-clone.js";
import {
  profileContractContactsElementName,
  profileDocumentEditorElementName,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import {
  emptyContractContactEditor,
  type ProfileContractContactEditorState,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/contract-contacts.js";
import { createElementTransition } from "@fgtclb/academic-persons-edit/frontend/profile/elements/transition.js";
import { fillPrototype } from "@fgtclb/academic-persons-edit/frontend/profile/prototypes.js";
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

/** The id prefix of every control this editor renders. */
export const documentFieldIdPrefix = "profile-editing-document-field";

/**
 * The class name prefix of the collapse transition. The declarations it
 * selects are in `Resources/Private/Scss/frontend/profile-editing.scss`.
 */
const runDocumentTransition = createElementTransition(
  "academic-persons-profile-editing-document-collapse",
);

/** The properties whose change means the panel has to be built again. */
const structuralProperties: readonly (keyof ProfileDocumentEditorElement)[] = [
  "context",
  "deleteConfirmation",
  "fields",
  "heading",
  "kind",
  "mode",
  "record",
];

/**
 * The element.
 *
 * Public surface: the tag name, the reactive properties below, and the four
 * `pe:document-*` events. It observes no attributes - it is created and driven
 * by `profile/documents.ts` and never spelled in markup.
 */
export class ProfileDocumentEditorElement extends ProfileEditingElement {
  static override properties = {
    contactEditor: { attribute: false },
    contactEmptyMessage: { attribute: false },
    contactSections: { attribute: false },
    context: { attribute: false },
    deleteConfirmation: { attribute: false },
    error: { attribute: false },
    errors: { attribute: false },
    fields: { attribute: false },
    heading: { attribute: false },
    kind: { attribute: false },
    mode: { attribute: false },
    open: { attribute: false },
    pending: { attribute: false },
    record: { attribute: false },
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
  #built = false;
  #contacts: Element | null = null;

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
    this.addEventListener("click", this.#onClick);
    this.addEventListener("submit", this.#onSubmit);
    this.addEventListener("input", this.#onValueChanged);
    this.addEventListener("change", this.#onValueChanged);
  }

  /**
   * Resolves once the contract contacts below have rendered as well.
   *
   * `updateComplete` is per element, and the contacts are an element of their
   * own: this one assigns their properties while it builds, which schedules
   * *their* update for a later microtask. A caller that awaits this element
   * and then queries for the contact editor - which is what
   * `openContractContact()` does before it focuses the first control - would
   * otherwise look at the document before the list is in it.
   */
  override async getUpdateComplete(): Promise<boolean> {
    const completed = await super.getUpdateComplete();
    // Read through the tag name and a structural type rather than through the
    // class: importing the element here would make the module graph a cycle
    // for nothing, and "updateComplete" is the whole contract that is needed.
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

  override updated(
    changed: PropertyValues<ProfileDocumentEditorElement>,
  ): void {
    if (this.#needsBuild(changed)) {
      this.#build();
    } else {
      this.#patch(changed);
    }
    this.#syncContacts();
    this.#syncTransition(changed);
  }

  #needsBuild(
    changed: PropertyValues<ProfileDocumentEditorElement>,
  ): boolean {
    if (!this.#built) {
      return true;
    }

    return structuralProperties.some((name): boolean => changed.has(name));
  }

  /** Clones the panel prototype and puts it below this element. */
  #build(): void {
    const source = this.context?.root;
    if (source === undefined) {
      return;
    }
    const editing = this.mode === "add" || this.mode === "edit";
    const showContacts = this.mode === "view" && this.kind === "contract";
    const panel = fillPrototype(source, "document-panel", {
      busy: this.pending ? "true" : "false",
      deleteConfirmation: this.deleteConfirmation,
      error: this.error,
      errorHidden: this.error === "" ? true : undefined,
      heading: this.heading,
      isDelete: this.mode === "delete",
      isSave: this.mode !== "delete",
      kind: this.kind,
      pending: this.pending ? true : undefined,
      showActions: this.mode !== "view",
      showClose: this.mode !== "delete",
      showContacts,
      showDisplay: this.mode === "view",
      showFields: editing,
      spinnerHidden: this.pending ? undefined : true,
    });
    panel.list(
      "displayRows",
      this.mode === "view"
        ? this.fields.map((field): Node =>
            cloneDisplayRow(
              source,
              field,
              this.context?.labels.documentEmpty ?? "",
            ),
          )
        : [],
    );
    panel.list(
      "fields",
      editing
        ? this.fields.map((field, index): Node =>
            cloneField({
              error: this.errors[field.name],
              field,
              hook: "documentField",
              idPrefix: documentFieldIdPrefix,
              index,
              pending: this.pending,
              source,
              value: this.values[field.name],
            }),
          )
        : [],
    );
    this.#contacts = showContacts
      ? document.createElement(profileContractContactsElementName)
      : null;
    panel.list("contacts", this.#contacts === null ? [] : [this.#contacts]);
    // "replaceChildren()" and not an incremental patch: the platform
    // disconnects everything it removes, so every rich text element of the
    // previous panel is torn down by its own "disconnectedCallback()" and
    // teardown needs no scope of its own. Scoping it by hand is the defect
    // this replaces.
    this.replaceChildren(panel.fragment);
    this.#built = true;
  }

  /** Writes what changed onto the panel that is already there. */
  #patch(changed: PropertyValues<ProfileDocumentEditorElement>): void {
    const section = this.querySelector<HTMLElement>(
      "[data-pe-document-view-container]",
    );
    if (section === null) {
      return;
    }
    if (changed.has("pending")) {
      section.setAttribute("aria-busy", this.pending ? "true" : "false");
      this.querySelectorAll<HTMLButtonElement>(
        "[data-pe-document-cancel], [data-pe-document-save]",
      ).forEach((button): void => {
        button.disabled = this.pending;
      });
      this.querySelectorAll<HTMLElement>(
        "[data-pe-document-save] .spinner-border",
      ).forEach((spinner): void => {
        spinner.hidden = !this.pending;
      });
      this.fields.forEach((field, index): void => {
        const control = section.querySelector(
          `#${CSS.escape(fieldControlId(documentFieldIdPrefix, index, field))}`,
        );
        if (
          control instanceof HTMLInputElement ||
          control instanceof HTMLSelectElement ||
          control instanceof HTMLTextAreaElement
        ) {
          control.disabled = field.disabled || this.pending;
        }
      });
    }
    if (changed.has("error")) {
      const alert = section.querySelector<HTMLElement>(".alert[role='alert']");
      if (alert !== null) {
        alert.textContent = this.error;
        alert.hidden = this.error === "";
      }
    }
    if (changed.has("errors")) {
      applyFieldErrors(section, this.fields, documentFieldIdPrefix, this.errors);
    }
  }

  /** Forwards the five properties the contact list is driven by. */
  #syncContacts(): void {
    const contacts = this.#contacts as
      | (Element & {
          context?: EditingContext | null;
          contract?: number | null;
          editor?: ProfileContractContactEditorState;
          emptyMessage?: string;
          sections?: ContractContactSection[];
        })
      | null;
    if (contacts === null) {
      return;
    }
    contacts.context = this.context;
    contacts.contract = this.record;
    contacts.sections = this.contactSections;
    contacts.emptyMessage = this.contactEmptyMessage;
    contacts.editor = this.contactEditor;
  }

  #syncTransition(
    changed: PropertyValues<ProfileDocumentEditorElement>,
  ): void {
    if (this.open && !this.#transitioned) {
      this.#transitioned = true;
      this.#startTransition("enter", (): void => undefined);

      return;
    }
    if (!this.open && this.#transitioned && changed.has("open")) {
      this.#transitioned = false;
      this.#startTransition("leave", (): void => this.#reportClosed());
    }
  }

  #onClick = (event: Event): void => {
    const target = event.target instanceof Element ? event.target : null;
    const button = target?.closest<HTMLButtonElement>(
      "[data-pe-document-cancel]",
    );
    if (button === null || button === undefined || button.disabled) {
      return;
    }
    this.dispatchEvent(
      new CustomEvent(documentEditorCloseEvent, { bubbles: true }),
    );
  };

  #onSubmit = (event: Event): void => {
    // Never through the browser: the endpoints answer JSON and the page stays.
    event.preventDefault();
    if (this.pending) {
      return;
    }
    this.dispatchEvent(
      new CustomEvent(documentEditorSubmitEvent, { bubbles: true }),
    );
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
    const name = hooks(control).peDocumentField;
    if (name === undefined) {
      return;
    }
    const value =
      control instanceof HTMLInputElement && control.type === "checkbox"
        ? control.checked
        : control.value;
    this.dispatchEvent(
      new CustomEvent<ProfileDocumentEditorInputDetail>(
        documentEditorInputEvent,
        { bubbles: true, detail: { name, value } },
      ),
    );
  };

  #reportClosed(): void {
    // A frame later, never in this turn. "updated()" runs inside Lit's own
    // update cycle and the owner removes this element when the close is
    // reported - tearing the tree out from inside the update that produced it
    // is how a reactive element ends up patching detached nodes.
    globalThis.requestAnimationFrame((): void => {
      this.dispatchEvent(
        new CustomEvent(documentEditorClosedEvent, { bubbles: true }),
      );
    });
  }

  #startTransition(kind: "enter" | "leave", done: () => void): void {
    const section = this.querySelector<HTMLElement>(
      "[data-pe-document-view-container]",
    );
    if (section === null) {
      done();

      return;
    }
    this.#cancelTransition?.();
    this.#cancelTransition = null;
    // "settled" rather than an unconditional assignment: a transition that has
    // nothing to animate finishes inside the call, and storing the
    // cancellation it hands back afterwards would leave a finished transition
    // looking live.
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
  customElements.define(
    profileDocumentEditorElementName,
    ProfileDocumentEditorElement,
  );
};
