export const rootSelector = "[data-academic-persons-inline-edit]";

export const isEditableField = (element) =>
  element instanceof HTMLInputElement ||
  element instanceof HTMLSelectElement ||
  element instanceof HTMLTextAreaElement;

export const getProfileUid = (root) => {
  const profileUid = Number.parseInt(root.dataset.profileUid ?? "", 10);
  return Number.isInteger(profileUid) && profileUid > 0 ? profileUid : null;
};
export const initializePopover = () => {
  const popoverTriggerList = document.querySelectorAll(
    `[data-bs-toggle='popover']`,
  );
  return [...popoverTriggerList].map(
    (popoverTriggerEl) => new bootstrap.Popover(popoverTriggerEl),
  );
};
export const showStatus = (root, type, message = null) => {
  const statusValues = {
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
  const status = statusValues[type] ?? statusValues.danger;
  const statusToast = root.querySelector("[data-ie-status-toast]");
  if (!(statusToast instanceof HTMLElement)) {
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

  const titleElement = statusToast.querySelector(".status-title");
  const messageElement = statusToast.querySelector(".status-message");
  if (titleElement) {
    titleElement.textContent = status.title;
  }
  if (messageElement) {
    messageElement.textContent = message ?? status.message;
  }

  if (globalThis.bootstrap?.Toast) {
    globalThis.bootstrap.Toast.getOrCreateInstance(statusToast).show();
  }
};

export const requestJson = async (url, options) => {
  const { headers = {}, ...requestOptions } = options;
  const response = await fetch(url, {
    credentials: "same-origin",
    ...requestOptions,
    headers: {
      Accept: "application/json",
      ...headers,
    },
  });
  const result = await response.json().catch(() => null);

  if (!response.ok || result?.success !== true) {
    const error = new Error("The request failed.");
    error.result = result;
    throw error;
  }

  return result;
};
