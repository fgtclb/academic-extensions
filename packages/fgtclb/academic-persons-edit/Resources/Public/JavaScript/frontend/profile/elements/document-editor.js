/* Generated from Resources/Private/TypeScript — do not edit. */
import { hooks } from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  ownerEditingContext
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { ProfileEditingElement } from "@fgtclb/academic-persons-edit/frontend/profile/elements/base.js";
import {
  applyFieldErrors,
  cloneDisplayRow,
  cloneField,
  fieldControlId
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/field-clone.js";
import {
  profileContractContactsElementName,
  profileDocumentEditorElementName
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import {
  emptyContractContactEditor
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/contract-contacts.js";
import { createElementTransition } from "@fgtclb/academic-persons-edit/frontend/profile/elements/transition.js";
import { fillPrototype } from "@fgtclb/academic-persons-edit/frontend/profile/prototypes.js";
const documentEditorCloseEvent = "pe:document-close";
const documentEditorSubmitEvent = "pe:document-submit";
const documentEditorInputEvent = "pe:document-input";
const documentEditorClosedEvent = "pe:document-closed";
const documentFieldIdPrefix = "profile-editing-document-field";
const runDocumentTransition = createElementTransition(
  "academic-persons-profile-editing-document-collapse"
);
const structuralProperties = [
  "context",
  "deleteConfirmation",
  "fields",
  "heading",
  "kind",
  "mode",
  "record"
];
class ProfileDocumentEditorElement extends ProfileEditingElement {
  static properties = {
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
    values: { attribute: false }
  };
  #cancelTransition = null;
  #transitioned = false;
  #built = false;
  #contacts = null;
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
  async getUpdateComplete() {
    const completed = await super.getUpdateComplete();
    const contacts = this.querySelector(profileContractContactsElementName);
    await (contacts == null ? void 0 : contacts.updateComplete);
    return completed;
  }
  connectedCallback() {
    this.context ??= ownerEditingContext(this);
    super.connectedCallback();
  }
  updated(changed) {
    if (this.#needsBuild(changed)) {
      this.#build();
    } else {
      this.#patch(changed);
    }
    this.#syncContacts();
    this.#syncTransition(changed);
  }
  #needsBuild(changed) {
    if (!this.#built) {
      return true;
    }
    return structuralProperties.some((name) => changed.has(name));
  }
  /** Clones the panel prototype and puts it below this element. */
  #build() {
    var _a;
    const source = (_a = this.context) == null ? void 0 : _a.root;
    if (source === void 0) {
      return;
    }
    const editing = this.mode === "add" || this.mode === "edit";
    const showContacts = this.mode === "view" && this.kind === "contract";
    const panel = fillPrototype(source, "document-panel", {
      busy: this.pending ? "true" : "false",
      deleteConfirmation: this.deleteConfirmation,
      error: this.error,
      errorHidden: this.error === "" ? true : void 0,
      heading: this.heading,
      isDelete: this.mode === "delete",
      isSave: this.mode !== "delete",
      kind: this.kind,
      pending: this.pending ? true : void 0,
      showActions: this.mode !== "view",
      showClose: this.mode !== "delete",
      showContacts,
      showDisplay: this.mode === "view",
      showFields: editing,
      spinnerHidden: this.pending ? void 0 : true
    });
    panel.list(
      "displayRows",
      this.mode === "view" ? this.fields.map(
        (field) => {
          var _a2;
          return cloneDisplayRow(
            source,
            field,
            ((_a2 = this.context) == null ? void 0 : _a2.labels.documentEmpty) ?? ""
          );
        }
      ) : []
    );
    panel.list(
      "fields",
      editing ? this.fields.map(
        (field, index) => cloneField({
          error: this.errors[field.name],
          field,
          hook: "documentField",
          idPrefix: documentFieldIdPrefix,
          index,
          pending: this.pending,
          source,
          value: this.values[field.name]
        })
      ) : []
    );
    this.#contacts = showContacts ? document.createElement(profileContractContactsElementName) : null;
    panel.list("contacts", this.#contacts === null ? [] : [this.#contacts]);
    this.replaceChildren(panel.fragment);
    this.#built = true;
  }
  /** Writes what changed onto the panel that is already there. */
  #patch(changed) {
    const section = this.querySelector(
      "[data-pe-document-view-container]"
    );
    if (section === null) {
      return;
    }
    if (changed.has("pending")) {
      section.setAttribute("aria-busy", this.pending ? "true" : "false");
      this.querySelectorAll(
        "[data-pe-document-cancel], [data-pe-document-save]"
      ).forEach((button) => {
        button.disabled = this.pending;
      });
      this.querySelectorAll(
        "[data-pe-document-save] .spinner-border"
      ).forEach((spinner) => {
        spinner.hidden = !this.pending;
      });
      this.fields.forEach((field, index) => {
        const control = section.querySelector(
          `#${CSS.escape(fieldControlId(documentFieldIdPrefix, index, field))}`
        );
        if (control instanceof HTMLInputElement || control instanceof HTMLSelectElement || control instanceof HTMLTextAreaElement) {
          control.disabled = field.disabled || this.pending;
        }
      });
    }
    if (changed.has("error")) {
      const alert = section.querySelector(".alert[role='alert']");
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
  #syncContacts() {
    const contacts = this.#contacts;
    if (contacts === null) {
      return;
    }
    contacts.context = this.context;
    contacts.contract = this.record;
    contacts.sections = this.contactSections;
    contacts.emptyMessage = this.contactEmptyMessage;
    contacts.editor = this.contactEditor;
  }
  #syncTransition(changed) {
    if (this.open && !this.#transitioned) {
      this.#transitioned = true;
      this.#startTransition("enter", () => void 0);
      return;
    }
    if (!this.open && this.#transitioned && changed.has("open")) {
      this.#transitioned = false;
      this.#startTransition("leave", () => this.#reportClosed());
    }
  }
  #onClick = (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const button = target == null ? void 0 : target.closest(
      "[data-pe-document-cancel]"
    );
    if (button === null || button === void 0 || button.disabled) {
      return;
    }
    this.dispatchEvent(
      new CustomEvent(documentEditorCloseEvent, { bubbles: true })
    );
  };
  #onSubmit = (event) => {
    event.preventDefault();
    if (this.pending) {
      return;
    }
    this.dispatchEvent(
      new CustomEvent(documentEditorSubmitEvent, { bubbles: true })
    );
  };
  #onValueChanged = (event) => {
    const control = event.target;
    if (!(control instanceof HTMLInputElement) && !(control instanceof HTMLSelectElement) && !(control instanceof HTMLTextAreaElement)) {
      return;
    }
    const name = hooks(control).peDocumentField;
    if (name === void 0) {
      return;
    }
    const value = control instanceof HTMLInputElement && control.type === "checkbox" ? control.checked : control.value;
    this.dispatchEvent(
      new CustomEvent(
        documentEditorInputEvent,
        { bubbles: true, detail: { name, value } }
      )
    );
  };
  #reportClosed() {
    globalThis.requestAnimationFrame(() => {
      this.dispatchEvent(
        new CustomEvent(documentEditorClosedEvent, { bubbles: true })
      );
    });
  }
  #startTransition(kind, done) {
    var _a;
    const section = this.querySelector(
      "[data-pe-document-view-container]"
    );
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
  customElements.define(
    profileDocumentEditorElementName,
    ProfileDocumentEditorElement
  );
};
export {
  ProfileDocumentEditorElement,
  documentEditorCloseEvent,
  documentEditorClosedEvent,
  documentEditorInputEvent,
  documentEditorSubmitEvent,
  documentFieldIdPrefix,
  profileDocumentEditorElementName,
  registerProfileDocumentEditorElement
};
