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
 * regions and, from the next commits on, the child elements all need one owner
 * that outlives a single function call; module level `WeakMap`s keyed by the
 * root element were the workaround for not having one.
 *
 * ## It renders nothing
 *
 * Fluid renders the whole editor and the element wraps that markup as light DOM
 * children. Nothing here is a `LitElement` and nothing here has a shadow root:
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
 * of the following commits declare `static properties` instead of `@property`.
 *
 * ## What Vue still does here
 *
 * The document editor is still rendered by Vue while ACE-509 replaces the
 * editors one at a time, so this element still creates the application and
 * mounts it on the root. The image editor has left it and owns itself, in
 * `elements/image-editor.ts`. The mount is also why the four initialisers
 * below run from `onMounted()` and not from `connectedCallback()`:
 * `mount()` assigns the container's `innerHTML` as the template and then clears
 * the container (`vue.esm-browser.prod.js`: `i.template=r.innerHTML` followed by
 * `r.textContent=""`), so every element reference taken before the mount is
 * detached from the document. When the last Vue rendered editor is gone, the
 * mount goes with it and the initialisers move into `connectedCallback()`.
 */
import {
  createApp,
  onMounted,
  type App,
} from "@fgtclb/academic-persons-edit/frontend/vue.js";
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
 * Whether a tag name belongs to this extension's element family.
 *
 * Handed to Vue as `compilerOptions.isCustomElement` for as long as Vue still
 * compiles a part of the editor: its runtime compiler resolves an unknown tag
 * as a component and warns "Failed to resolve component" for every one it does
 * not know, and the elements of the following commits are rendered inside the
 * mounted root. The predicate is exported rather than inlined so that what the
 * shim covers can be asserted without a Vue application.
 */
export const isProfileEditingElementTag = (tag: string): boolean =>
  tag.startsWith(profileEditingElementPrefix);

const createProfileEditingApp = (context: EditingContext): App => {
  const application = createApp({
    setup(): Record<string, unknown> {
      const documentController = createDocumentEditing(context);
      const syncController = createSkipSync(context);

      onMounted((): void => {
        initializeStickyImageOffset(context.root);
        initializeFieldEditing(context);
        initializeDocumentSections(context);
        initializePopover(context.root);
      });

      return {
        ...documentController,
        ...syncController,
      };
    },
  });
  application.config.compilerOptions.isCustomElement = isProfileEditingElementTag;

  return application;
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
    createProfileEditingApp(this.#context).mount(root);
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
