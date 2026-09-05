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
 * and nowhere else stays invisible here. That one waits for the behavioural
 * harness of ACE-509 - see
 * `docs/testing/academic-persons-edit-frontend-tests.md`.
 */
export type ProfileEditingHooks = {
  peCharacterLimit?: string;
  peCheckedLabel?: string;
  peCloseAllLabel?: string;
  peDisplayFieldIds?: string;
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

export const isEditableField = (element: unknown): element is EditableField =>
  element instanceof HTMLInputElement ||
  element instanceof HTMLSelectElement ||
  element instanceof HTMLTextAreaElement;

export const getProfileUid = (root: HTMLElement): number | null => {
  const profileUid = Number.parseInt(root.dataset.profileUid ?? "", 10);
  return Number.isInteger(profileUid) && profileUid > 0 ? profileUid : null;
};

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
  root: HTMLElement,
  type: StatusType,
  message: string | null = null,
): void => {
  const statusValues: Record<StatusType, {
    title: string;
    message: string;
    className: string;
  }> = {
    danger: {
      title: root.dataset.messageErrorTitle ?? "",
      message: root.dataset.messageErrorMessage ?? "",
      className: "bg-danger",
    },
    success: {
      title: root.dataset.messageSuccessTitle ?? "",
      message: root.dataset.messageSuccessMessage ?? "",
      className: "bg-success",
    },
    info: {
      title: root.dataset.messageInfoTitle ?? "",
      message: root.dataset.messageInfoMessage ?? "",
      className: "bg-info",
    },
    warning: {
      title: root.dataset.messageWarningTitle ?? "",
      message: root.dataset.messageValidation ?? "",
      className: "bg-warning",
    },
  };
  const status = statusValues[type];
  // A failure interrupts (role="alert"), everything else waits for a pause
  // (role="status"). The two regions exist side by side in the markup.
  const statusToast = root.querySelector<HTMLElement>(
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
