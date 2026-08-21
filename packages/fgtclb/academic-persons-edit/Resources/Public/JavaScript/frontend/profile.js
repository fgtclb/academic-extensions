const rootSelector = "[data-academic-persons-inline-edit]";
const fieldsFormSelector = "[data-ie-fields-form]";
const editButtonSelector =
  "[data-academic-persons-inline-edit-activate-btn]";
const editAllButtonSelector =
  "[data-academic-persons-inline-edit-edit-all-btn]";
const footerButtonAreaSelector = "[data-ie-footer-button-area]";
const buttonAreaSelector = "[data-form-field-button-area]";
const fieldSelector = ".academic-persons-inline-edit__field";
const imageFormSelector = ".academic-persons-inline-edit__image-form";
const imageModalSelector = "[data-ie-image-modal]";
const syncFormSelector = "[data-ie-sync-form]";
const syncCheckboxSelector = ".academic-persons-inline-edit__sync-checkbox";

const isEditableField = (element) =>
  element instanceof HTMLInputElement ||
  element instanceof HTMLSelectElement ||
  element instanceof HTMLTextAreaElement;

const getFieldValue = (field) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  return field.value;
};

const setFieldValue = (field, value) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    field.checked = Boolean(value);
    return;
  }
  field.value = value === null || value === undefined ? "" : String(value);
};

const getFieldPropertyName = (field) => {
  const bracketProperty = field.name.match(/\[([^\]]+)]$/)?.[1];
  return bracketProperty ?? field.name;
};

const getProfileUid = (root) => {
  const profileUid = Number.parseInt(root.dataset.profileUid ?? "", 10);
  return Number.isInteger(profileUid) && profileUid > 0 ? profileUid : null;
};

const getFieldById = (root, fieldId) => {
  if (!fieldId) {
    return null;
  }
  const field = root.querySelector(`#${CSS.escape(fieldId)}`);
  return isEditableField(field) ? field : null;
};

const getActivateButton = (root, field) => {
  if (!field.id) {
    return null;
  }
  const selector = `${editButtonSelector}[data-ie-for="${CSS.escape(field.id)}"]`;
  const button = root.querySelector(selector);
  return button instanceof HTMLButtonElement ? button : null;
};

const setFooterVisible = (root, visible) => {
  root
    .querySelector(footerButtonAreaSelector)
    ?.classList.toggle("d-none", !visible);
};

const showStatus = (root, type, message = null) => {
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

const clearValidationErrors = (fields) => {
  fields.forEach((field) => {
    field.classList.remove("is-invalid");
    const feedback = field
      .closest(".mb-3, .form-check")
      ?.querySelector(".invalid-feedback");
    if (feedback) {
      feedback.textContent = "";
    }
  });
};

const showValidationErrors = (fields, errors) => {
  Object.entries(errors).forEach(([propertyPath, messages]) => {
    const propertyName = propertyPath.split(".").pop();
    const field = fields.find(
      (candidate) => getFieldPropertyName(candidate) === propertyName,
    );
    if (!field) {
      return;
    }

    field.classList.add("is-invalid");
    const feedback = field
      .closest(".mb-3, .form-check")
      ?.querySelector(".invalid-feedback");
    if (feedback) {
      feedback.textContent = Array.isArray(messages)
        ? messages.join(" ")
        : String(messages);
    }
  });
};

const getTemplateButton = (template) => {
  if (template instanceof HTMLTemplateElement) {
    return template.content.querySelector("button");
  }
  return null;
};

const createActivateButton = (root, field, fieldValue) => {
  const normalizedValue =
    fieldValue === null || fieldValue === undefined ? "" : String(fieldValue);
  const template = root.querySelector(
    normalizedValue === ""
      ? "[data-ie-new-button-template]"
      : "[data-ie-edit-button-template]",
  );
  const templateButton = getTemplateButton(template);
  if (!(templateButton instanceof HTMLButtonElement)) {
    return null;
  }

  const button = templateButton.cloneNode(true);
  if (!(button instanceof HTMLButtonElement)) {
    return null;
  }

  button.dataset.ieFor = field.id;
  const label = button.querySelector("[data-ie-button-label]");
  if (label) {
    label.textContent = normalizedValue === "" ? "+" : normalizedValue;
  }
  return button;
};

const renderActivateButton = (root, field, fieldValue) => {
  if (!field.id || field.disabled || field.readOnly) {
    return;
  }

  const currentButton = getActivateButton(root, field);
  const replacementButton = createActivateButton(root, field, fieldValue);
  if (!replacementButton) {
    return;
  }

  if (currentButton) {
    replacementButton.classList.toggle(
      "d-none",
      currentButton.classList.contains("d-none"),
    );
    currentButton.replaceWith(replacementButton);
    return;
  }

  field
    .closest(".mb-3, .form-check")
    ?.querySelector(buttonAreaSelector)
    ?.append(replacementButton);
};

const toggleEditField = (root, fieldId, state = true) => {
  const field = getFieldById(root, fieldId);
  if (!field || field.disabled || field.readOnly) {
    return;
  }

  field.classList.toggle("d-none", !state);
  getActivateButton(root, field)?.classList.toggle("d-none", state);
  root
    .querySelectorAll(`[data-ie-dismiss][data-ie-for="${CSS.escape(fieldId)}"]`)
    .forEach((button) => button.classList.toggle("d-none", !state));
  root
    .querySelectorAll(`[data-ie-save][data-ie-for="${CSS.escape(fieldId)}"]`)
    .forEach((button) => button.classList.toggle("d-none", !state));

  if (state) {
    field.focus();
  }
};

const closeFields = (root, fields) => {
  fields.forEach((field) => {
    if (!(field instanceof HTMLSelectElement) && field.id) {
      toggleEditField(root, field.id, false);
    }
  });
};

const closeAllFields = (root, fields) => {
  closeFields(root, fields);
  setFooterVisible(root, false);
};

const requestJson = async (url, options) => {
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

const initializeFieldEditing = (root) => {
  const forms = Array.from(root.querySelectorAll(fieldsFormSelector)).filter(
    (element) => element instanceof HTMLFormElement,
  );
  const primaryForm = forms[0];
  if (!(primaryForm instanceof HTMLFormElement)) {
    return;
  }

  const fields = Array.from(root.querySelectorAll(fieldSelector)).filter(
    isEditableField,
  );
  const persistedValues = new Map(
    fields.map((field) => [field, getFieldValue(field)]),
  );
  let bulkEditing = false;

  const finishBulkEditingWhenClosed = () => {
    const hasOpenTextField = fields.some(
      (field) =>
        !(field instanceof HTMLSelectElement) &&
        !field.classList.contains("d-none"),
    );
    if (!hasOpenTextField) {
      bulkEditing = false;
      setFooterVisible(root, false);
    }
  };

  const resetFields = (fieldsToReset) => {
    fieldsToReset.forEach((field) => {
      setFieldValue(field, persistedValues.get(field) ?? "");
    });
    clearValidationErrors(fieldsToReset);
  };

  const saveFields = async (fieldsToSave, closeEverything = false) => {
    if (root.getAttribute("aria-busy") === "true") {
      return false;
    }

    clearValidationErrors(fieldsToSave);
    const changedFields = fieldsToSave.filter(
      (field) =>
        getFieldPropertyName(field) &&
        !field.disabled &&
        !field.readOnly &&
        persistedValues.get(field) !== getFieldValue(field),
    );

    if (changedFields.length === 0) {
      if (closeEverything) {
        closeAllFields(root, fields);
        bulkEditing = false;
      } else {
        closeFields(root, fieldsToSave);
        finishBulkEditingWhenClosed();
      }
      showStatus(root, "info", root.dataset.messageUnchanged ?? null);
      return true;
    }

    const invalidField = changedFields.find((field) => !field.checkValidity());
    if (invalidField) {
      invalidField.reportValidity();
      invalidField.classList.add("is-invalid");
      showStatus(root, "warning", root.dataset.messageValidation ?? null);
      return false;
    }

    const profileUid = getProfileUid(root);
    const updateUrl = root.dataset.updateUrl;
    if (profileUid === null || !updateUrl) {
      showStatus(root, "danger");
      return false;
    }

    const data = Object.fromEntries(
      changedFields.map((field) => [
        getFieldPropertyName(field),
        getFieldValue(field),
      ]),
    );
    root.setAttribute("aria-busy", "true");
    showStatus(root, "info", root.dataset.messageSaving ?? null);

    try {
      await requestJson(updateUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profile: profileUid, data }),
      });

      changedFields.forEach((field) => {
        const value = getFieldValue(field);
        persistedValues.set(field, value);
        renderActivateButton(root, field, value);
      });

      if (closeEverything) {
        closeAllFields(root, fields);
        bulkEditing = false;
      } else {
        closeFields(root, changedFields);
        finishBulkEditingWhenClosed();
      }
      showStatus(root, "success");
      return true;
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      if (result?.errors && typeof result.errors === "object") {
        showValidationErrors(fields, result.errors);
        showStatus(root, "warning", root.dataset.messageValidation ?? null);
      } else {
        showStatus(root, "danger", result?.message ?? null);
      }
      return false;
    } finally {
      root.removeAttribute("aria-busy");
    }
  };

  root.addEventListener("change", (event) => {
    if (
      event.target instanceof HTMLSelectElement &&
      event.target.matches(fieldSelector)
    ) {
      void saveFields([event.target]);
    }
  });

  root.addEventListener("click", (event) => {
    if (!(event.target instanceof Element)) {
      return;
    }
    const button = event.target.closest("button");
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }

    if (button.matches(editAllButtonSelector)) {
      event.preventDefault();
      root.querySelectorAll(editButtonSelector).forEach((editButton) => {
        if (editButton.dataset.ieFor) {
          toggleEditField(root, editButton.dataset.ieFor, true);
        }
      });
      bulkEditing = true;
      setFooterVisible(root, true);
      return;
    }

    if (button.matches(editButtonSelector)) {
      event.preventDefault();
      if (button.dataset.ieFor) {
        toggleEditField(root, button.dataset.ieFor, true);
      }
      return;
    }

    if (button.matches("[data-ie-dismiss]")) {
      event.preventDefault();
      const fieldId = button.dataset.ieFor;
      if (fieldId) {
        const field = getFieldById(root, fieldId);
        if (field) {
          resetFields([field]);
          toggleEditField(root, fieldId, false);
          finishBulkEditingWhenClosed();
        }
      } else {
        resetFields(fields);
        closeAllFields(root, fields);
        bulkEditing = false;
      }
      return;
    }

    if (button.matches("[data-ie-save]")) {
      event.preventDefault();
      const field = getFieldById(root, button.dataset.ieFor);
      if (field) {
        void saveFields([field]);
      }
    }
  });

  forms.forEach((form) => {
    form.addEventListener("submit", (event) => {
      event.preventDefault();
      const savesAllFields = form === primaryForm;
      const fieldsToSave = savesAllFields
        ? fields
        : fields.filter((field) => form.contains(field));
      void saveFields(fieldsToSave, savesAllFields);
    });
    form.addEventListener("reset", (event) => event.preventDefault());
  });

  // Keep the footer state coherent when a field is saved individually while
  // the bulk editor is open.
  root.addEventListener("input", () => {
    if (bulkEditing) {
      setFooterVisible(root, true);
    }
  });
};

const initializeSkipSync = (root) => {
  const form = root.querySelector(syncFormSelector);
  const checkbox = form?.querySelector(syncCheckboxSelector);
  if (!(form instanceof HTMLFormElement) || !(checkbox instanceof HTMLInputElement)) {
    return;
  }

  let persistedValue = checkbox.checked;
  form.addEventListener("submit", (event) => event.preventDefault());
  checkbox.addEventListener("change", async () => {
    const profileUid = getProfileUid(root);
    const updateUrl = root.dataset.skipSyncUrl;
    if (profileUid === null || !updateUrl) {
      checkbox.checked = persistedValue;
      showStatus(root, "danger");
      return;
    }

    const requestedValue = checkbox.checked;
    form.setAttribute("aria-busy", "true");
    checkbox.disabled = true;
    showStatus(root, "info", root.dataset.messageSaving ?? null);

    try {
      const result = await requestJson(updateUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          profile: profileUid,
          data: { skipSync: requestedValue },
        }),
      });
      persistedValue = Boolean(result.skipSync);
      checkbox.checked = persistedValue;
      checkbox.classList.remove("is-invalid");
      showStatus(root, "success");
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      checkbox.checked = persistedValue;
      checkbox.classList.add("is-invalid");
      showStatus(root, "danger", result?.message ?? null);
    } finally {
      checkbox.disabled = false;
      form.removeAttribute("aria-busy");
    }
  });
};

const getImagePreviews = (root) =>
  Array.from(
    root.querySelectorAll(
      "[data-ie-image-preview], [data-ie-image-modal-preview]",
    ),
  );

const setImagePreviewUrl = (preview, url, alt = "", title = "") => {
  const image = preview.querySelector("img");
  if (!(image instanceof HTMLImageElement)) {
    return;
  }

  preview
    .querySelectorAll("source")
    .forEach((source) => source.setAttribute("srcset", url));
  image.removeAttribute("srcset");
  image.src = url;
  image.alt = alt;
  image.title = title;
};

const updateImagePreviewsFromFile = (root, file) => {
  getImagePreviews(root).forEach((preview) => {
    const objectUrl = URL.createObjectURL(file);
    setImagePreviewUrl(preview, objectUrl, file.name, file.name);
    const image = preview.querySelector("img");
    image?.addEventListener("load", () => URL.revokeObjectURL(objectUrl), {
      once: true,
    });
  });
};

const setImageState = (root, hasImage) => {
  root.dataset.hasImage = hasImage ? "1" : "0";
  root
    .querySelector("[data-ie-delete-image]")
    ?.classList.toggle("d-none", !hasImage);

  const submitButton = root.querySelector(
    `${imageFormSelector} button[type="submit"]`,
  );
  if (submitButton instanceof HTMLButtonElement) {
    const label = hasImage
      ? submitButton.dataset.replaceLabel
      : submitButton.dataset.addLabel;
    if (label) {
      const labelElement = submitButton.querySelector("[data-ie-action-label]");
      if (labelElement) {
        labelElement.textContent = label;
      }
    }
  }

  const modalHint = root.querySelector("[data-ie-image-modal-hint]");
  if (modalHint instanceof HTMLElement) {
    const label = hasImage
      ? modalHint.dataset.replaceLabel
      : modalHint.dataset.addLabel;
    if (label) {
      modalHint.textContent = label;
    }
  }

  root
    .querySelector("[data-ie-image-delete-hint]")
    ?.classList.toggle("d-none", !hasImage);
};

const initializeImageEditing = (root) => {
  const form = root.querySelector(imageFormSelector);
  const modal = root.querySelector(imageModalSelector);
  const openButton = root.querySelector("[data-ie-open-image-modal]");
  const fileInput = form?.querySelector('input[type="file"]');
  const uploadButton = form?.querySelector("[data-ie-upload-image]");
  const deleteButton = root.querySelector("[data-ie-delete-image]");
  const Modal = globalThis.bootstrap?.Modal;
  if (
    !(form instanceof HTMLFormElement) ||
    !(modal instanceof HTMLElement) ||
    !(openButton instanceof HTMLButtonElement) ||
    !(fileInput instanceof HTMLInputElement) ||
    !(uploadButton instanceof HTMLButtonElement) ||
    !Modal
  ) {
    return;
  }

  const modalInstance = Modal.getOrCreateInstance(modal);
  let requestPending = false;

  const clearImageError = () => {
    fileInput.classList.remove("is-invalid");
    const feedback = form.querySelector("[data-ie-image-error]");
    if (feedback) {
      feedback.textContent = "";
    }
  };

  const showImageError = (message) => {
    fileInput.classList.add("is-invalid");
    const feedback = form.querySelector("[data-ie-image-error]");
    if (feedback) {
      feedback.textContent = message;
    }
  };

  const updateActionAvailability = () => {
    const hasSelectedFile = fileInput.files?.length === 1;
    fileInput.disabled = requestPending;
    uploadButton.disabled = requestPending || !hasSelectedFile;
    if (deleteButton instanceof HTMLButtonElement) {
      deleteButton.disabled =
        requestPending || root.dataset.hasImage !== "1";
    }
    modal.querySelectorAll("[data-ie-close-image-modal]").forEach((button) => {
      if (button instanceof HTMLButtonElement) {
        button.disabled = requestPending;
      }
    });
  };

  const setRequestPending = (pending, activeButton = null) => {
    requestPending = pending;
    modal.toggleAttribute("aria-busy", pending);
    modal.querySelectorAll("[data-ie-action-spinner]").forEach((spinner) => {
      spinner.classList.toggle(
        "d-none",
        !pending || spinner.closest("button") !== activeButton,
      );
    });
    updateActionAvailability();
  };

  modal.addEventListener("show.bs.modal", () => {
    clearImageError();
    updateActionAvailability();
  });
  modal.addEventListener("hide.bs.modal", (event) => {
    if (requestPending) {
      event.preventDefault();
    }
  });
  modal.addEventListener("hidden.bs.modal", () => {
    form.reset();
    clearImageError();
    updateActionAvailability();
    openButton.focus();
  });

  fileInput.addEventListener("change", () => {
    clearImageError();
    updateActionAvailability();
  });

  setImageState(root, root.dataset.hasImage === "1");
  updateActionAvailability();

  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (requestPending) {
      return;
    }
    if (!form.reportValidity()) {
      showStatus(root, "warning", root.dataset.messageValidation ?? null);
      return;
    }

    const file = fileInput?.files?.[0];
    if (!(file instanceof File)) {
      showStatus(root, "warning", root.dataset.messageValidation ?? null);
      return;
    }

    clearImageError();
    setRequestPending(true, uploadButton);
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    let uploadSucceeded = false;

    try {
      await requestJson(form.action, {
        method: "POST",
        body: new FormData(form),
      });
      updateImagePreviewsFromFile(root, file);
      setImageState(root, true);
      uploadSucceeded = true;
      showStatus(root, "success", root.dataset.messageImageUploaded ?? null);
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      const message = result?.message ?? root.dataset.messageErrorMessage ?? "";
      showImageError(message);
      showStatus(root, "danger", message || null);
    } finally {
      setRequestPending(false);
      if (uploadSucceeded) {
        modalInstance.hide();
      }
    }
  });

  deleteButton?.addEventListener("click", async () => {
    if (requestPending || root.dataset.hasImage !== "1") {
      return;
    }

    const profileUid = getProfileUid(root);
    const deleteUrl = root.dataset.deleteImageUrl;
    if (profileUid === null || !deleteUrl) {
      showStatus(root, "danger");
      return;
    }

    setRequestPending(
      true,
      deleteButton instanceof HTMLButtonElement ? deleteButton : null,
    );
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    let deletionSucceeded = false;

    try {
      await requestJson(deleteUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profile: profileUid, data: {} }),
      });

      const placeholderUrl = root.dataset.placeholderImageUrl;
      if (placeholderUrl) {
        getImagePreviews(root).forEach((preview) => {
          setImagePreviewUrl(
            preview,
            placeholderUrl,
            root.dataset.placeholderImageAlt ?? "",
          );
        });
      }
      setImageState(root, false);
      deletionSucceeded = true;
      showStatus(root, "success", root.dataset.messageImageDeleted ?? null);
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      showStatus(root, "danger", result?.message ?? null);
    } finally {
      setRequestPending(false);
      if (deletionSucceeded) {
        modalInstance.hide();
      }
    }
  });
};

document.querySelectorAll(rootSelector).forEach((root) => {
  if (!(root instanceof HTMLElement)) {
    return;
  }
  initializeFieldEditing(root);
  initializeSkipSync(root);
  initializeImageEditing(root);
});
