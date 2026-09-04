/**
 * `<academic-persons-edit-image-editor>` - the element that owns the profile
 * image editor.
 *
 * ## Why this one renders nothing
 *
 * The editor is an Extbase `<f:form>`. It carries `__referrer[…]` and the
 * `__trustedProperties` HMAC that the property mapper validates the upload
 * against, and nothing in a browser can recompute that signature. The form
 * therefore stays server rendered, and this element is a controller over Fluid's
 * markup rather than a `LitElement` with a template: it takes the partial's
 * output as its light DOM children, binds the four events the Vue directives
 * used to bind, and writes the twenty-odd bindings they used to derive.
 *
 * That is not a preference. A template that re-rendered this subtree would have
 * to reproduce the hidden fields, and a subtree CropperJS fills with its own
 * custom elements is one no template may own.
 *
 * ## What it does not own
 *
 * The state and every request stay in `profile/image.ts`, which the behavioural
 * suite drives directly and pins. This element creates one controller, hands it
 * a callback, and turns each state change into DOM. Reading the two apart is
 * the point: the controller is testable without a registry and the element is
 * testable without a server.
 *
 * ## No decorators, no shadow root
 *
 * Both for the reasons `elements/root.ts` gives: the suite runs the sources
 * under node's type stripping, and the theme's Bootstrap stylesheet has to
 * reach the controls.
 */
import {
  createImageEditing,
  type ImageEditingController,
  type ImageState,
} from "@fgtclb/academic-persons-edit/frontend/profile/image.js";
import {
  profileEditingElementName,
  profileEditingElementPrefix,
  ProfileEditingElement,
} from "@fgtclb/academic-persons-edit/frontend/profile/elements/root.js";
import type { EditingContext } from "@fgtclb/academic-persons-edit/frontend/profile/context.js";

/** The tag name of this element. Public API from the moment it ships. */
export const profileImageEditorElementName = `${profileEditingElementPrefix}image-editor`;

/**
 * The transition the editor opens and closes with.
 *
 * The class names are Vue's - `<Transition name="…">` derives them - and they
 * are kept, because the declarations they select are unchanged and a class an
 * integrator may have overridden is not renamed by a commit that does not have
 * to. What changes is who applies them.
 */
const transitionPrefix = "academic-persons-profile-editing-image-editor";

/** How long after the computed duration a transition is given up on. */
const transitionTimeoutSlack = 50;

const toMilliseconds = (value: string): number => {
  const amount = Number.parseFloat(value);
  if (!Number.isFinite(amount)) {
    return 0;
  }

  return value.trim().endsWith("ms") ? amount : amount * 1000;
};

const transitionDuration = (element: HTMLElement): number =>
  Math.max(
    0,
    ...globalThis
      .getComputedStyle(element)
      .transitionDuration.split(",")
      .map(toMilliseconds),
  );

/**
 * Runs one enter or leave transition on an element and calls back when it is
 * over, whether it ran or not.
 *
 * `<Transition>` used to do this. Three things it did that a bare
 * `transitionend` listener does not, and all three are the reason this is a
 * function rather than one line:
 *
 * - **It ends even when the transition never starts.** `transitionend` does not
 *   fire for an element that is `display: none`, for a property that does not
 *   actually change, or for one that is removed halfway. The editor's close
 *   path hangs off the callback - the focus returns to the open button there
 *   and the collapsed layout is restored there - so a transition that never
 *   ends is a silent, intermittent defect rather than a missing animation.
 * - **It ends at once when there is nothing to animate.** The stylesheet turns
 *   the transition off under `prefers-reduced-motion`, and a visitor who asked
 *   for that must not wait out a timeout.
 * - **It can be cancelled.** Reopening the editor while it closes has to drop
 *   the pending leave, its classes and its callback.
 *
 * @returns the cancellation of the transition it started.
 */
export const runElementTransition = (
  element: HTMLElement,
  kind: "enter" | "leave",
  done: () => void,
): (() => void) => {
  const active = `${transitionPrefix}-${kind}-active`;
  // The stylesheet declares the two ends of the animation as "-enter-from" and
  // "-leave-to"; "-enter-to" and "-leave-from" are Vue's names for the resting
  // state and carry no declarations, so they are not applied.
  const offset = kind === "enter" ? `${transitionPrefix}-enter-from` : `${transitionPrefix}-leave-to`;

  element.classList.add(active);
  if (kind === "enter") {
    element.classList.add(offset);
  }

  const duration = transitionDuration(element);
  const clear = (): void => {
    element.classList.remove(active, offset);
  };
  if (duration === 0) {
    clear();
    done();

    return (): void => undefined;
  }

  const finish = (): void => {
    cancel();
    clear();
    done();
  };
  const onTransitionEnd = (event: Event): void => {
    if (event.target === element) {
      finish();
    }
  };
  // Reads "timeout" and "frame" below it. Neither can be read before it is
  // written: nothing calls this until the browser has been given the chance to
  // run one of the two, and both are handed out by then.
  const cancel = (): void => {
    globalThis.cancelAnimationFrame(frame);
    globalThis.clearTimeout(timeout);
    element.removeEventListener("transitionend", onTransitionEnd);
  };

  element.addEventListener("transitionend", onTransitionEnd);
  const timeout = globalThis.setTimeout(finish, duration + transitionTimeoutSlack);
  // Two frames, as Vue's own "nextFrame()" uses: a single one can be flushed
  // together with the style that was just written, and the browser then never
  // sees the state the animation starts from - so nothing animates and the
  // timeout below is what ends the transition.
  let frame = globalThis.requestAnimationFrame((): void => {
    frame = globalThis.requestAnimationFrame((): void => {
      if (kind === "enter") {
        element.classList.remove(offset);
      } else {
        element.classList.add(offset);
      }
    });
  });

  return (): void => {
    cancel();
    clear();
  };
};

const setHidden = (element: Element | null, hidden: boolean): void => {
  if (element instanceof HTMLElement) {
    element.hidden = hidden;
  }
};

const setDisabled = (
  element: Element | null,
  disabled: boolean,
): void => {
  if (
    element instanceof HTMLButtonElement ||
    element instanceof HTMLFieldSetElement
  ) {
    element.disabled = disabled;
  }
};

const setImageSource = (
  element: Element | null,
  url: string,
  alternative: string,
): void => {
  if (!(element instanceof HTMLImageElement)) {
    return;
  }
  // Assigning "src" resolves it against the document, so an unset preview would
  // read back as the page url rather than as nothing.
  if (url === "") {
    element.removeAttribute("src");
  } else {
    element.src = url;
  }
  element.alt = alternative;
};

const setClass = (
  element: Element | null,
  className: string,
  present: boolean,
): void => {
  element?.classList.toggle(className, present);
};

/**
 * The element.
 *
 * Public surface: the tag name, the writable `context` property, the read only
 * `controller` property and the `render()` method. It observes no attributes
 * and dispatches no events of its own - every status the image editing reports
 * is reported by the controller, which holds the context and writes the live
 * region through `profile/common.ts` directly.
 */
export class ProfileImageEditorElement extends HTMLElement {
  #context: EditingContext | null = null;
  #controller: ImageEditingController | null = null;
  #cancelTransition: (() => void) | null = null;
  #open = false;

  #handleClick = (event: Event): void => {
    const target = event.target instanceof Element ? event.target : null;
    const button = target?.closest<HTMLButtonElement>(
      "[data-pe-open-image-view], [data-pe-close-image-view], [data-pe-delete-image], " +
        "[data-pe-cancel-delete-image], [data-pe-confirm-delete-image]",
    );
    const controller = this.#controller;
    if (button === null || button === undefined || button.disabled || controller === null) {
      return;
    }
    if (button.matches("[data-pe-open-image-view]")) {
      void controller.openImage();
    } else if (button.matches("[data-pe-close-image-view]")) {
      controller.closeImage();
    } else if (button.matches("[data-pe-delete-image]")) {
      controller.requestDeleteImage();
    } else if (button.matches("[data-pe-cancel-delete-image]")) {
      controller.cancelDeleteImage();
    } else {
      void controller.deleteImage();
    }
  };

  #handleChange = (event: Event): void => {
    if (
      event.target instanceof HTMLInputElement &&
      event.target.type === "file"
    ) {
      void this.#controller?.selectImage(event);
    }
  };

  #handleSubmit = (event: Event): void => {
    // The form posts through "fetch()", never through the browser: the response
    // is JSON and the page it was rendered on stays.
    event.preventDefault();
    void this.#controller?.submitImage(event);
  };

  /**
   * The contract of `Templates/Profile/Index.html`.
   *
   * Assigned by whoever creates the element, and otherwise resolved from the
   * `<academic-persons-edit-profile-editing>` above it on connection - the
   * markup of this element is rendered by Fluid, so there is no creating caller
   * to assign it. Either way the element never reads the root's attributes
   * itself: the contract is read once, by the owner, and handed down.
   */
  get context(): EditingContext | null {
    return this.#context;
  }

  set context(context: EditingContext | null) {
    this.#context = context;
  }

  /** The image editing this element drives, or `null` until it is connected. */
  get controller(): ImageEditingController | null {
    return this.#controller;
  }

  connectedCallback(): void {
    if (this.#context === null) {
      const owner = this.closest(profileEditingElementName);
      this.#context =
        owner instanceof ProfileEditingElement ? owner.context : null;
    }
    const context = this.#context;
    if (context === null) {
      // Not an error: an editor whose owner has not read its contract yet is
      // one a later connection resolves.
      return;
    }
    // Delegated on the root and not on this element, because the button that
    // opens the editor belongs to the image card and stands outside it.
    context.root.addEventListener("click", this.#handleClick);
    this.addEventListener("change", this.#handleChange);
    this.addEventListener("submit", this.#handleSubmit);
    if (this.#controller === null) {
      this.#controller = createImageEditing(context, (): void => this.render());
    }
    this.render();
  }

  disconnectedCallback(): void {
    this.#context?.root.removeEventListener("click", this.#handleClick);
    this.removeEventListener("change", this.#handleChange);
    this.removeEventListener("submit", this.#handleSubmit);
  }

  /**
   * Writes everything the Fluid partial used to derive with `v-if`, `v-show`
   * and `v-bind`, plus the two column widths of `Templates/Profile/Index.html`.
   *
   * Called after every change the controller accepts, and once on connection so
   * that the markup agrees with the state it was rendered before.
   */
  render(): void {
    const context = this.#context;
    const state = this.#controller?.image;
    if (context === null || state === undefined) {
      return;
    }
    this.#renderEditor(state);
    this.#renderDeleteActions(state);
    this.#renderPreview(state);
    this.#renderFooter(state);
    this.#renderLayout(context, state);
  }

  /** The editor as a whole: its visibility, its transition and its busy state. */
  #renderEditor(state: ImageState): void {
    const section = this.querySelector<HTMLElement>(
      "[data-pe-image-view-container]",
    );
    if (section === null) {
      return;
    }
    section.setAttribute("aria-busy", state.pending ? "true" : "false");
    section.style.cursor = state.pending ? "wait" : "";
    setDisabled(this.querySelector("[data-pe-image-fieldset]"), state.pending);

    if (state.editing && !this.#open) {
      this.#open = true;
      section.hidden = false;
      this.#startTransition(section, "enter", (): void => undefined);
    } else if (!state.editing && this.#open) {
      this.#open = false;
      if (state.closing) {
        this.#startTransition(section, "leave", (): void =>
          this.#controller?.finishImageClose(),
        );
      }
    }
    if (!state.editing && !state.closing) {
      this.#cancelTransition?.();
      this.#cancelTransition = null;
      section.hidden = true;
    }
  }

  /** Delete, and the question it asks before it deletes. */
  #renderDeleteActions(state: ImageState): void {
    setHidden(
      this.querySelector("[data-pe-image-delete-actions]"),
      !state.hasImage,
    );
    setHidden(
      this.querySelector("[data-pe-delete-image]"),
      state.confirmingDelete,
    );
    for (const selector of [
      "[data-pe-delete-image-confirm-question]",
      "[data-pe-cancel-delete-image]",
      "[data-pe-confirm-delete-image]",
    ]) {
      setHidden(this.querySelector(selector), !state.confirmingDelete);
    }
    for (const selector of [
      "[data-pe-delete-image]",
      "[data-pe-cancel-delete-image]",
      "[data-pe-confirm-delete-image]",
    ]) {
      setDisabled(this.querySelector(selector), state.pending);
    }
  }

  /** The cropper stage, the plain preview, and which of the two is shown. */
  #renderPreview(state: ImageState): void {
    const cropping =
      state.cropperRequested && state.hasSelection && state.previewUrl !== "";
    setHidden(this.querySelector("[data-pe-image-cropper-stage]"), !cropping);
    setImageSource(
      this.querySelector("[data-pe-image-cropper-source]"),
      state.previewUrl,
      state.selectedName,
    );
    const preview = this.querySelector("[data-pe-image-selected-preview]");
    setHidden(preview, cropping || state.previewUrl === "");
    setImageSource(preview, state.previewUrl, state.selectedName);
  }

  /** The error the upload reports, and the two buttons below it. */
  #renderFooter(state: ImageState): void {
    const error = this.querySelector<HTMLElement>("[data-pe-image-error]");
    if (error !== null) {
      error.textContent = state.error;
      error.hidden = state.error === "";
    }
    this.querySelector('input[type="file"]')?.setAttribute(
      "aria-invalid",
      state.error === "" ? "false" : "true",
    );
    setDisabled(this.querySelector("[data-pe-close-image-view]"), state.pending);
    setDisabled(
      this.querySelector("[data-pe-upload-image]"),
      state.pending ||
        !state.hasSelection ||
        state.previewUrl === "" ||
        (state.cropperRequested && !state.cropperReady),
    );
    setHidden(this.querySelector("[data-pe-image-upload-spinner]"), !state.pending);
  }

  /**
   * The two columns of `Templates/Profile/Index.html`: the image moves out of
   * the way while the editor is open and the fields take the full width.
   *
   * They stand outside this element and are written from it, because the state
   * they are derived from is this one - the same reason the module below has
   * always written the previews of the image card.
   */
  #renderLayout(context: EditingContext, state: ImageState): void {
    const collapsed = state.editing || state.closing;
    const preview = context.root.querySelector("[data-pe-image-preview-column]");
    setClass(preview, "col-lg-4", !collapsed);
    setHidden(preview, collapsed);
    const fields = context.root.querySelector(
      ".academic-persons-profile-editing__profile-fields-column",
    );
    setClass(fields, "col-lg-12", collapsed);
    setClass(fields, "col-lg-8", !collapsed);
    context.root
      .querySelector("[data-pe-open-image-view]")
      ?.setAttribute("aria-expanded", state.editing ? "true" : "false");
  }

  #startTransition(
    section: HTMLElement,
    kind: "enter" | "leave",
    done: () => void,
  ): void {
    this.#cancelTransition?.();
    this.#cancelTransition = null;
    // "settled" rather than an unconditional assignment: a transition that has
    // nothing to animate finishes inside the call, and storing the cancellation
    // it hands back afterwards would leave a finished transition looking live.
    let settled = false;
    const cancel = runElementTransition(section, kind, (): void => {
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
 * Called by the entry point, after the root element: the root reads the
 * contract and mounts the application that renders this element's markup, so
 * defining this one first would upgrade a copy that is about to be replaced.
 */
export const registerProfileImageEditorElement = (): void => {
  if (customElements.get(profileImageEditorElementName) !== undefined) {
    return;
  }
  customElements.define(profileImageEditorElementName, ProfileImageEditorElement);
};
