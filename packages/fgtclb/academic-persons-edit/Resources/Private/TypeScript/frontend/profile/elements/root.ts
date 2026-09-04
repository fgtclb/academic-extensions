/**
 * `<academic-persons-edit-profile-editing>` - the element that owns one profile
 * editor.
 *
 * ## Why an element rather than a start-up scan
 *
 * The entry point used to look for `[data-academic-persons-profile-editing]`
 * once, at load, and remember what it had already started in a module level
 * `WeakSet`. That is the bookkeeping a custom element does for free and does
 * better: the registry upgrades every matching element, whenever it appears,
 * and calls `connectedCallback()` exactly once per element per insertion. An
 * editor rendered into the page later - by a plugin that loads its content over
 * ajax, or by a second occurrence of the plugin - starts by itself.
 *
 * It also gives the editor an object to *be*. `EditingContext`, the status
 * regions and the child elements all need one owner that outlives a single
 * function call; module level `WeakMap`s keyed by the root element were the
 * workaround for not having one.
 *
 * ## It renders nothing
 *
 * Fluid renders the editor's frame and the element wraps that markup as light
 * DOM children; the two `LitElement`s below it render into the light DOM as
 * well. Nothing here is a `LitElement` and nothing anywhere has a shadow root:
 * the theme's Bootstrap stylesheet has to reach the controls, Bootstrap's own
 * popover and toast JavaScript positions against `document`, and CKEditor 5
 * does not run inside a shadow root. See
 * `docs/architecture/profile-editing-contract.md`.
 *
 * ## No decorators
 *
 * The behavioural suite runs the TypeScript sources directly under node's type
 * stripping, which erases annotations but does not transform, and
 * `Build/tsconfig.tests.json` sets `erasableSyntaxOnly` to make that a type
 * error rather than a runtime one. A `@customElement` decorator would therefore
 * not run. Registration is a plain `customElements.define()`, and the elements
 * below declare `static properties` instead of `@property`.
 *
 * ## It starts the editor on connection
 *
 * `connectedCallback()` builds the controllers and runs the four initialisers,
 * in that order and in one pass. Until ACE-509 removed the runtime they ran
 * from Vue's `onMounted()` instead, and that was not a preference: `mount()`
 * assigned the container's `innerHTML` as the template and then cleared the
 * container, so every element reference taken before the mount pointed at a
 * detached node. Nothing rewrites the markup any more - Fluid renders it, the
 * elements below drive it - so the references a controller takes on connection
 * are the ones the visitor sees.
 */
import {
  initializePopover,
  rootSelector,
  showStatus,
  type StatusType,
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  readEditingContext,
  type EditingContext,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
import {
  createDocumentEditing,
  initializeDocumentSections,
} from "@fgtclb/academic-persons-edit/frontend/profile/documents.js";
import {
  profileEditingElementName,
  profileEditingElementPrefix,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/names.js";
import { initializeFieldEditing } from "@fgtclb/academic-persons-edit/frontend/profile/fields.js";
import { initializeStickyImageOffset } from "@fgtclb/academic-persons-edit/frontend/profile/sticky-image.js";
import { createSkipSync } from "@fgtclb/academic-persons-edit/frontend/profile/sync.js";

/**
 * The prefix and the tag name, re-exported from `elements/names.ts` where they
 * are declared. They were introduced here and are read from here by everything
 * that already knows this module, so the export stays; what moved is the
 * declaration, because three modules now need a name and importing an element
 * to spell one is what builds an import cycle.
 */
export { profileEditingElementName, profileEditingElementPrefix };

/** The event a descendant dispatches to have the editor report a status. */
export const profileEditingStatusEvent = "pe:status";

/** The payload of `pe:status`. */
export interface ProfileEditingStatusDetail {
  readonly type: StatusType;
  readonly message?: string | null;
}

const statusTypes: readonly string[] = ["danger", "info", "success", "warning"];

const isStatusType = (value: unknown): value is StatusType =>
  typeof value === "string" && statusTypes.includes(value);

/**
 * Builds the editor of one root: the controllers first, the initialisers after.
 *
 * That is the order Vue's `setup()`/`onMounted()` pair produced, and it is kept
 * rather than defended. Every listener the two controllers register is
 * delegated on the root and every initialiser writes DOM below it, so the six
 * calls are independent of each other as they stand today - but that is a
 * property of the current implementations and not something either side
 * promises, and reordering them buys nothing.
 */
const startProfileEditing = (context: EditingContext): void => {
  createDocumentEditing(context);
  createSkipSync(context);
  initializeStickyImageOffset(context.root);
  initializeFieldEditing(context);
  initializeDocumentSections(context);
  initializePopover(context.root);
};

/**
 * The element itself.
 *
 * Public surface: the tag name, the read only `context` property, the
 * `showStatus()` method and the `pe:status` event it listens for. It observes
 * no attributes and dispatches no events of its own.
 */
export class ProfileEditingElement extends HTMLElement {
  #context: EditingContext | null = null;

  #handleStatus = (event: Event): void => {
    const detail: unknown = (event as CustomEvent<unknown>).detail;
    const status =
      typeof detail === "object" && detail !== null
        ? (detail as Partial<ProfileEditingStatusDetail>)
        : null;
    if (status === null || !isStatusType(status.type)) {
      return;
    }
    this.showStatus(status.type, status.message ?? null);
  };

  /**
   * The contract of `Templates/Profile/Index.html`, read once when the element
   * first connected, and `null` for an element that carries no editor root.
   */
  get context(): EditingContext | null {
    return this.#context;
  }

  connectedCallback(): void {
    // Re-added on every connection, because "disconnectedCallback()" takes it
    // off again: an element that is moved in the document keeps its editor but
    // has to keep its listener too.
    this.addEventListener(profileEditingStatusEvent, this.#handleStatus);
    if (this.#context !== null) {
      return;
    }
    const root = this.querySelector<HTMLElement>(rootSelector);
    if (root === null) {
      // Not an error and not remembered: the element is empty until Fluid's
      // markup is below it, and a later connection reads it then.
      return;
    }
    this.#context = readEditingContext(root);
    startProfileEditing(this.#context);
  }

  disconnectedCallback(): void {
    this.removeEventListener(profileEditingStatusEvent, this.#handleStatus);
  }

  /**
   * Writes one of the two live regions of `Partials/Profile/StatusToast.html`:
   * the assertive one for a failure, the polite one for everything else.
   *
   * The rendering itself stays in `profile/common.ts`, which the controllers
   * call directly and which the behavioural suite pins. What the element adds
   * is the address: a descendant reports a status by dispatching `pe:status`
   * and needs to know neither the root nor the module.
   */
  showStatus(type: StatusType, message: string | null = null): void {
    if (this.#context === null) {
      return;
    }
    showStatus(this.#context, type, message);
  }
}

/**
 * Defines the element, idempotently.
 *
 * Called by the entry point. A second call is a no-op rather than the
 * `NotSupportedError` a repeated `customElements.define()` raises, because the
 * entry point may be evaluated more than once in a page that loads the module
 * under two cache keys.
 */
export const registerProfileEditingElement = (): void => {
  if (customElements.get(profileEditingElementName) !== undefined) {
    return;
  }
  customElements.define(profileEditingElementName, ProfileEditingElement);
};
