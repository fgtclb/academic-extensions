/**
 * `<academic-persons-edit-rich-text>` - one rich text field, and the one
 * CKEditor 5 instance on it.
 *
 * ## Why this is not part of the document editor's template
 *
 * `ClassicEditor.create(textarea)` takes the textarea out of the document and
 * puts its own container, toolbar and editable in its place. Everything below
 * that point belongs to CKEditor: it mutates it on every keystroke, its balloon
 * and its selection tracking work against `document`, and its UI writes
 * document level `<style>` elements. A `lit-html` template that contained the
 * textarea would try to patch nodes it does not own on the next re-render of
 * the parent - and a re-render is what happens when the request the visitor
 * just started answers with a validation error.
 *
 * So Lit never renders *into* this element. It creates and removes the element
 * itself, and everything below it - the textarea, the editor, the character
 * counter - is this element's own. That is the whole mechanism, and it is why
 * the element exists rather than a directive: an element is the unit lit-html
 * treats as opaque.
 *
 * ## The lifetime is the point
 *
 * `connectedCallback()` creates the editor and `disconnectedCallback()`
 * destroys it. A `ClassicEditor` that is dropped without being destroyed keeps
 * its window and document listeners until it is collected, and the document
 * editor is removed from the document on every close. The controller destroys
 * the editors of the closing subtree as well - see `finishDocumentClose()` in
 * `profile/documents.ts` - and the two are idempotent by construction:
 * `destroyRichTextEditors()` forgets the editor before it awaits its
 * `destroy()`, so whichever path runs second finds nothing to destroy.
 *
 * A close immediately followed by a reopen creates a *new* element with a *new*
 * textarea, so the create-while-destroying race that a shared textarea would
 * have has no node to happen on. The teardown promise below is kept anyway, for
 * the one case that does share a node: an element that is moved in the document
 * and therefore disconnected and reconnected.
 *
 * ## No shadow root, no decorators
 *
 * Both for the reasons `elements/root.ts` gives, and one more that is specific
 * to this element: CKEditor 5 does not support a classic editor inside a shadow
 * root at all.
 */
import { hooks } from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  ownerEditingContext,
  type EditingContext,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import { profileRichTextElementName } from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import {
  destroyRichTextEditors,
  ensureRichTextEditor,
} from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";

/** The tag name of this element. Public API from the moment it ships. */
export { profileRichTextElementName };

/**
 * Everything the document editor's template knows about the field, in the
 * shape the `<textarea>` needs it. Written as one property rather than as
 * eleven, because it is set in one place from one `DocumentField` and a
 * partially applied set would render a control that is briefly wrong.
 */
export interface ProfileRichTextConfiguration {
  readonly ariaDescribedBy?: string;
  readonly characterLimit?: number;
  readonly disabled?: boolean;
  readonly id?: string;
  readonly invalid?: boolean;
  readonly name?: string;
  readonly readOnly?: boolean;
  readonly required?: boolean;
  readonly rows?: number;
}

/**
 * The element.
 *
 * Public surface: the tag name, the `context` and `configuration` properties,
 * the `value` property, the read only `field` property, and the `input` event
 * the textarea below it bubbles. It observes no attributes.
 */
export class ProfileRichTextElement extends HTMLElement {
  #context: EditingContext | null = null;
  #configuration: ProfileRichTextConfiguration = {};
  #field: HTMLTextAreaElement | null = null;
  #teardown: Promise<void> = Promise.resolve();
  #value = "";

  /**
   * The contract of `Templates/Profile/Index.html`.
   *
   * Assigned by whoever creates the element, and otherwise resolved from the
   * `<academic-persons-edit-profile-editing>` above it on connection. The
   * editor needs it for the interface language and for the failure message it
   * reports when the editor cannot be created.
   */
  get context(): EditingContext | null {
    return this.#context;
  }

  set context(context: EditingContext | null) {
    this.#context = context;
  }

  /** What the control looks like. Applied to the textarea on assignment. */
  get configuration(): ProfileRichTextConfiguration {
    return this.#configuration;
  }

  set configuration(configuration: ProfileRichTextConfiguration) {
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
  get value(): string {
    return this.#field?.value ?? this.#value;
  }

  set value(value: string) {
    this.#value = value;
    if (this.#field !== null && this.#field.value !== value) {
      this.#field.value = value;
    }
  }

  /** The textarea, or `null` for an element that was never connected. */
  get field(): HTMLTextAreaElement | null {
    return this.#field;
  }

  connectedCallback(): void {
    this.#context ??= ownerEditingContext(this);
    const field = this.#field ?? this.#createField();
    this.#applyConfiguration();
    const context = this.#context;
    if (context === null) {
      // Not an error: an element whose owner has not read its contract yet is
      // one a later connection resolves. The textarea is a working plain
      // control until then.
      return;
    }
    // Chained behind the teardown of the previous connection rather than
    // started outright: a disconnect that is still destroying the editor of
    // this very textarea would otherwise be raced by the new creation.
    void this.#teardown.then((): void => {
      if (this.isConnected) {
        void ensureRichTextEditor(context, field);
      }
    });
  }

  disconnectedCallback(): void {
    this.#teardown = destroyRichTextEditors(this);
  }

  #createField(): HTMLTextAreaElement {
    const field = document.createElement("textarea");
    field.className = "form-control";
    field.value = this.#value;
    // The hook the controller collects the value through and the one
    // "ensureRichTextEditor()" recognises the field by.
    field.setAttribute("data-pe-rich-text", "");
    this.#field = field;
    this.append(field);

    return field;
  }

  #applyConfiguration(): void {
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
      configuration.invalid === true ? "true" : "false",
    );
    if (configuration.ariaDescribedBy === undefined) {
      field.removeAttribute("aria-describedby");
    } else {
      field.setAttribute("aria-describedby", configuration.ariaDescribedBy);
    }
    if (configuration.name === undefined) {
      delete field.dataset.peDocumentField;
    } else {
      field.dataset.peDocumentField = configuration.name;
    }
    if (configuration.characterLimit === undefined) {
      delete hooks(field).peCharacterLimit;
    } else {
      hooks(field).peCharacterLimit = String(configuration.characterLimit);
    }
  }
}

/**
 * Defines the element, idempotently.
 *
 * Called by the entry point. A second call is a no-op rather than the
 * `NotSupportedError` a repeated `customElements.define()` raises.
 */
export const registerProfileRichTextElement = (): void => {
  if (customElements.get(profileRichTextElementName) !== undefined) {
    return;
  }
  customElements.define(profileRichTextElementName, ProfileRichTextElement);
};
