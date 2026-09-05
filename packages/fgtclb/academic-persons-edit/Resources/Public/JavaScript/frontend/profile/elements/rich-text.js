/* Generated from Resources/Private/TypeScript — do not edit. */
import { hooks } from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  ownerEditingContext
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { profileRichTextElementName } from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import {
  destroyRichTextEditors,
  ensureRichTextEditor
} from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";
class ProfileRichTextElement extends HTMLElement {
  #context = null;
  #configuration = {};
  #field = null;
  #teardown = Promise.resolve();
  #value = "";
  /**
   * The contract of `Templates/Profile/Index.html`.
   *
   * Assigned by whoever creates the element, and otherwise resolved from the
   * `<academic-persons-edit-profile-editing>` above it on connection. The
   * editor needs it for the interface language and for the failure message it
   * reports when the editor cannot be created.
   */
  get context() {
    return this.#context;
  }
  set context(context) {
    this.#context = context;
  }
  /** What the control looks like. Applied to the textarea on assignment. */
  get configuration() {
    return this.#configuration;
  }
  set configuration(configuration) {
    this.#configuration = configuration;
    this.#applyConfiguration();
  }
  /**
   * The markup of the field.
   *
   * Only ever written *into* the textarea, and only while no editor is live:
   * once CKEditor owns the field it is the source of the value, and assigning
   * over it would discard what the visitor typed. The controller reads the
   * value back through `getRichTextEditorValue()` for exactly that reason.
   */
  get value() {
    var _a;
    return ((_a = this.#field) == null ? void 0 : _a.value) ?? this.#value;
  }
  set value(value) {
    this.#value = value;
    if (this.#field !== null && this.#field.value !== value) {
      this.#field.value = value;
    }
  }
  /** The textarea, or `null` for an element that was never connected. */
  get field() {
    return this.#field;
  }
  connectedCallback() {
    this.#context ??= ownerEditingContext(this);
    const field = this.#field ?? this.#createField();
    this.#applyConfiguration();
    const context = this.#context;
    if (context === null) {
      return;
    }
    void this.#teardown.then(() => {
      if (this.isConnected) {
        void ensureRichTextEditor(context, field);
      }
    });
  }
  disconnectedCallback() {
    this.#teardown = destroyRichTextEditors(this);
  }
  #createField() {
    const field = document.createElement("textarea");
    field.className = "form-control";
    field.value = this.#value;
    field.setAttribute("data-pe-rich-text", "");
    this.#field = field;
    this.append(field);
    return field;
  }
  #applyConfiguration() {
    const field = this.#field;
    if (field === null) {
      return;
    }
    const configuration = this.#configuration;
    field.rows = configuration.rows ?? 6;
    field.name = configuration.name ?? "";
    field.id = configuration.id ?? "";
    field.required = configuration.required === true;
    field.readOnly = configuration.readOnly === true;
    field.disabled = configuration.disabled === true;
    field.setAttribute(
      "aria-invalid",
      configuration.invalid === true ? "true" : "false"
    );
    if (configuration.ariaDescribedBy === void 0) {
      field.removeAttribute("aria-describedby");
    } else {
      field.setAttribute("aria-describedby", configuration.ariaDescribedBy);
    }
    if (configuration.name === void 0) {
      delete field.dataset.peDocumentField;
    } else {
      field.dataset.peDocumentField = configuration.name;
    }
    if (configuration.characterLimit === void 0) {
      delete hooks(field).peCharacterLimit;
    } else {
      hooks(field).peCharacterLimit = String(configuration.characterLimit);
    }
  }
}
const registerProfileRichTextElement = () => {
  if (customElements.get(profileRichTextElementName) !== void 0) {
    return;
  }
  customElements.define(profileRichTextElementName, ProfileRichTextElement);
};
export {
  ProfileRichTextElement,
  profileRichTextElementName,
  registerProfileRichTextElement
};
