import {
  toEditingContext,
  type EditingContext,
  type EditingTarget,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";

export const rootSelector = "[data-academic-persons-profile-editing]";

/**
 * The `data-pe-*` hooks the Fluid templates put on an element, spelled as the
 * `dataset` keys they become - `data-pe-field-ids` is `peFieldIds`, never
 * anything else.
 *
 * `HTMLElement.dataset` is a `DOMStringMap`, so *every* key reads as
 * `string | undefined` and a misspelled one type checks silently. Reading and
 * writing the hooks through this map instead makes an unknown key a
 * `typecheckJs` failure, which is what a prefix rename needs to be caught by.
 *
 * It does not cover the other direction: an attribute renamed in a template
 * and nowhere else stays invisible here. That one is the PHP functional
 * suite's, which reads the partials themselves - see
 * `docs/testing/academic-persons-edit-frontend-tests.md`.
 *
 * The root's own contract - the urls, the messages and the labels of
 * `Templates/Profile/Index.html` - is a vocabulary of its own and is read by
 * `readEditingContext()` of `profile/context.ts`, through the same mechanism.
 */
export type ProfileEditingHooks = {
  peCharacterLimit?: string;
  peCheckedLabel?: string;
  peCloseAllLabel?: string;
  peContractContactField?: string;
  peContractContactItem?: string;
  peContractContactSection?: string;
  peContractContactSort?: string;
  peDisplayFieldIds?: string;
  peDocumentField?: string;
  peDisplayMode?: string;
  peDocumentSort?: string;
  peDocumentValue?: string;
  peDocumentWasDisabled?: string;
  peEditAllLabel?: string;
  peFieldIds?: string;
  peFor?: string;
  peProfileNameFieldIds?: string;
  peUncheckedLabel?: string;
};

export const hooks = (element: HTMLElement): ProfileEditingHooks =>
  element.dataset as ProfileEditingHooks;

export type EditableField =
  | HTMLInputElement
  | HTMLSelectElement
  | HTMLTextAreaElement;
export type StatusType = "danger" | "success" | "info" | "warning";
export type JsonResult = Record<string, unknown> & { success: true };

interface BootstrapPopoverConstructor {
  new (element: Element): unknown;
}

interface BootstrapToastStatic {
  getOrCreateInstance(element: Element): { show(): void };
}

interface BootstrapApi {
  Popover?: BootstrapPopoverConstructor;
  Toast?: BootstrapToastStatic;
}

interface FailedRequest extends Error {
  result: unknown;
}

let activeRequestCount = 0;
let previousBodyCursor = "";

// Only the pointer, never "aria-busy" on <body>: the editors set that on the
// region they are actually waiting for, and marking the whole document busy
// makes a screen reader stop reporting everything else on the page as well.
const setRequestBusy = (busy: boolean): void => {
  const body = document.body;
  if (busy) {
    if (activeRequestCount === 0) {
      previousBodyCursor = body.style.cursor;
    }
    activeRequestCount += 1;
    body.style.cursor = "wait";
    return;
  }
  activeRequestCount = Math.max(0, activeRequestCount - 1);
  if (activeRequestCount === 0) {
    body.style.cursor = previousBodyCursor;
  }
};

const getBootstrap = (): BootstrapApi | undefined =>
  (globalThis as unknown as { bootstrap?: BootstrapApi }).bootstrap;

/**
 * Disables a control the way assistive technology also hears it.
 *
 * A `disabled` button is removed from the accessibility tree by some
 * combinations and merely announced as unavailable by others, so both editors
 * write `aria-disabled` beside it rather than one writing it and the other not.
 * The state itself stays in `disabled`: that is what keeps the click.
 */
export const setDisabled = (button: HTMLButtonElement, disabled: boolean): void => {
  button.disabled = disabled;
  button.setAttribute("aria-disabled", String(disabled));
};

export const isEditableField = (element: unknown): element is EditableField =>
  element instanceof HTMLInputElement ||
  element instanceof HTMLSelectElement ||
  element instanceof HTMLTextAreaElement;

export const initializePopover = (scope: ParentNode = document): unknown[] => {
  const Popover = getBootstrap()?.Popover;
  if (Popover === undefined) {
    return [];
  }
  return Array.from(
    scope.querySelectorAll(`[data-bs-toggle='popover']`),
    (trigger): unknown => new Popover(trigger),
  );
};

export const showStatus = (
  editingTarget: EditingTarget,
  type: StatusType,
  message: string | null = null,
): void => {
  const context: EditingContext = toEditingContext(editingTarget);
  const messages = context.messages;
  const statusValues: Record<StatusType, {
    title: string;
    message: string;
    className: string;
  }> = {
    danger: {
      title: messages.errorTitle ?? "",
      message: messages.errorMessage ?? "",
      className: "bg-danger",
    },
    success: {
      title: messages.successTitle ?? "",
      message: messages.successMessage ?? "",
      className: "bg-success",
    },
    info: {
      title: messages.infoTitle ?? "",
      message: messages.infoMessage ?? "",
      className: "bg-info",
    },
    warning: {
      title: messages.warningTitle ?? "",
      message: messages.validation ?? "",
      className: "bg-warning",
    },
  };
  const status = statusValues[type];
  // A failure interrupts (role="alert"), everything else waits for a pause
  // (role="status"). The two regions exist side by side in the markup.
  const statusToast = context.root.querySelector<HTMLElement>(
    `[data-pe-status-toast='${type === "danger" ? "alert" : "status"}']`,
  );
  if (statusToast === null) {
    return;
  }
  statusToast.classList.remove(
    "d-none",
    "bg-info",
    "bg-success",
    "bg-danger",
    "bg-warning",
  );
  statusToast.classList.add(status.className);
  const titleElement = statusToast.querySelector<HTMLElement>(".status-title");
  const messageElement = statusToast.querySelector<HTMLElement>(".status-message");
  if (titleElement !== null) {
    titleElement.textContent = status.title;
  }
  if (messageElement !== null) {
    messageElement.textContent = message ?? status.message;
  }
  getBootstrap()?.Toast?.getOrCreateInstance(statusToast).show();
};

export const requestJson = async (
  url: string,
  options: RequestInit = {},
): Promise<JsonResult> => {
  const { headers = {}, ...requestOptions } = options;
  setRequestBusy(true);
  try {
    const response = await fetch(url, {
      credentials: "same-origin",
      ...requestOptions,
      headers: {
        Accept: "application/json",
        // Required by every writing endpoint. A custom header cannot be set on
        // a cross-origin request without a preflight, which is what keeps a
        // foreign page from posting to the editor with the visitor's session.
        "X-Requested-With": "XMLHttpRequest",
        ...headers,
      },
    });
    const result: unknown = await response.json().catch((): null => null);
    if (
      !response.ok ||
      typeof result !== "object" ||
      result === null ||
      !("success" in result) ||
      result.success !== true
    ) {
      const error = new Error("The request failed.") as FailedRequest;
      error.result = result;
      throw error;
    }
    return result as JsonResult;
  } finally {
    setRequestBusy(false);
  }
};
