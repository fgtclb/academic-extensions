/* Generated from Resources/Private/TypeScript — do not edit. */
const rootSelector = "[data-academic-persons-profile-editing]";
const hooks = (element) => element.dataset;
let activeRequestCount = 0;
let previousBodyCursor = "";
const setRequestBusy = (busy) => {
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
const getBootstrap = () => globalThis.bootstrap;
const isEditableField = (element) => element instanceof HTMLInputElement || element instanceof HTMLSelectElement || element instanceof HTMLTextAreaElement;
const getProfileUid = (root) => {
  const profileUid = Number.parseInt(root.dataset.profileUid ?? "", 10);
  return Number.isInteger(profileUid) && profileUid > 0 ? profileUid : null;
};
const initializePopover = (scope = document) => {
  var _a;
  const Popover = (_a = getBootstrap()) == null ? void 0 : _a.Popover;
  if (Popover === void 0) {
    return [];
  }
  return Array.from(
    scope.querySelectorAll(`[data-bs-toggle='popover']`),
    (trigger) => new Popover(trigger)
  );
};
const showStatus = (root, type, message = null) => {
  var _a, _b;
  const statusValues = {
    danger: {
      title: root.dataset.messageErrorTitle ?? "",
      message: root.dataset.messageErrorMessage ?? "",
      className: "bg-danger"
    },
    success: {
      title: root.dataset.messageSuccessTitle ?? "",
      message: root.dataset.messageSuccessMessage ?? "",
      className: "bg-success"
    },
    info: {
      title: root.dataset.messageInfoTitle ?? "",
      message: root.dataset.messageInfoMessage ?? "",
      className: "bg-info"
    },
    warning: {
      title: root.dataset.messageWarningTitle ?? "",
      message: root.dataset.messageValidation ?? "",
      className: "bg-warning"
    }
  };
  const status = statusValues[type];
  const statusToast = root.querySelector(
    `[data-pe-status-toast='${type === "danger" ? "alert" : "status"}']`
  );
  if (statusToast === null) {
    return;
  }
  statusToast.classList.remove(
    "d-none",
    "bg-info",
    "bg-success",
    "bg-danger",
    "bg-warning"
  );
  statusToast.classList.add(status.className);
  const titleElement = statusToast.querySelector(".status-title");
  const messageElement = statusToast.querySelector(".status-message");
  if (titleElement !== null) {
    titleElement.textContent = status.title;
  }
  if (messageElement !== null) {
    messageElement.textContent = message ?? status.message;
  }
  (_b = (_a = getBootstrap()) == null ? void 0 : _a.Toast) == null ? void 0 : _b.getOrCreateInstance(statusToast).show();
};
const requestJson = async (url, options = {}) => {
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
        ...headers
      }
    });
    const result = await response.json().catch(() => null);
    if (!response.ok || typeof result !== "object" || result === null || !("success" in result) || result.success !== true) {
      const error = new Error("The request failed.");
      error.result = result;
      throw error;
    }
    return result;
  } finally {
    setRequestBusy(false);
  }
};
export {
  getProfileUid,
  hooks,
  initializePopover,
  isEditableField,
  requestJson,
  rootSelector,
  showStatus
};
