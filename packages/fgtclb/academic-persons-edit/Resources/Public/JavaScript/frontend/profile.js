const formSelector = "[data-academic-persons-inline-edit]";
const editButtonSelector = "[data-academic-persons-inline-edit-activate-btn]";
const editAllButtonSelector =
  "[data-academic-persons-inline-edit-edit-all-btn]";
const footerButtonAreaSelector = "[data-ie-footer-button-area]";
const buttonAreaSelector = "[data-form-field-button-area]";
const fieldSelector = ".academic-persons-inline-edit__field";
const templateInlineEditButton = document.getElementById(
  "templateInlineEditBtn",
);
const templateInlineEditNew = document.getElementById("templateInlineEditNew");
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

const getFieldById = (form, fieldId) => {
  if (!fieldId) {
    return null;
  }
  const field = form.querySelector(`#${CSS.escape(fieldId)}`);
  return isEditableField(field) ? field : null;
};
const getActivateButton = (field) => {
  if (!field.form || !field.id) {
    return null;
  }
  const selector = `${editButtonSelector}[data-ie-for="${CSS.escape(field.id)}"]`;
  const button = field.form.querySelector(selector);
  return button instanceof HTMLButtonElement ? button : null;
};
const setFooterVisible = (form, visible) => {
  const footer =
    form.querySelector(footerButtonAreaSelector) ??
    document.querySelector(footerButtonAreaSelector);
  footer?.classList.toggle("d-none", !visible);
};
const showStatus = (form, type) => {
  const statusValues = {
    danger: {
      title: form.dataset.messageErrorTitle ?? "",
      message: form.dataset.messageErrorMessage ?? "",
      className: "bg-danger",
    },
    error: {
      title: form.dataset.messageErrorTitle ?? "",
      message: form.dataset.messageErrorMessage ?? "",
      className: "bg-danger",
    },
    success: {
      title: form.dataset.messageSuccessTitle ?? "",
      message: form.dataset.messageSuccessMessage ?? "",
      className: "bg-success",
    },
    info: {
      title: form.dataset.messageInfoTitle ?? "",
      message: form.dataset.messageInfoMessage ?? "",
      className: "bg-info",
    },
    warning: {
      title: form.dataset.messageWarningTitle ?? "",
      message: form.dataset.messageWarningMessage ?? "",
      className: "bg-warning",
    },
  };
  const status = statusValues[type] ?? statusValues.error;
  const statusToast =
    form.querySelector("[data-ie-status-toast]") ??
    document.querySelector("[data-ie-status-toast]");
  if (!(statusToast instanceof HTMLElement)) {
    return;
  }
  const titleElement = statusToast.querySelector(".status-title");
  const messageElement = statusToast.querySelector(".status-message");
  statusToast.classList.remove(
    "d-none",
    "bg-info",
    "bg-success",
    "bg-danger",
    "bg-warning",
  );
  statusToast.classList.add(status.className);
  if (titleElement) {
    titleElement.textContent = status.title;
  }
  if (messageElement) {
    messageElement.textContent = status.message;
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
    const field = fields.find((candidate) => candidate.name === propertyName);
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
  if (template instanceof HTMLButtonElement) {
    return template;
  }
  if (template instanceof HTMLElement) {
    return template.querySelector("button");
  }
  return null;
};
const createActivateButton = (template, field, fieldValue) => {
  const templateButton = getTemplateButton(template);
  if (!(templateButton instanceof HTMLButtonElement)) {
    return null;
  }
  const button = templateButton.cloneNode(true);
  if (!(button instanceof HTMLButtonElement)) {
    return null;
  }
  button.removeAttribute("id");
  button.type = "button";
  button.setAttribute("data-academic-persons-inline-edit-activate-btn", "");
  button.dataset.ieFor = field.id;
  if (fieldValue !== "") {
    const label = button.querySelector("[data-ie-button-label]");
    if (label) {
      label.textContent = String(fieldValue);
    } else {
      button.textContent = String(fieldValue);
    }
  }
  return button;
};
const renderActivateButton = (fieldValue, field) => {
  if (!field.form || !field.id) {
    return;
  }
  const normalizedValue =
    fieldValue === null || fieldValue === undefined ? "" : String(fieldValue);
  const currentButton = getActivateButton(field);
  const template =
    normalizedValue === "" ? templateInlineEditNew : templateInlineEditButton;
  const replacementButton = createActivateButton(
    template,
    field,
    normalizedValue,
  );
  if (replacementButton) {
    if (currentButton) {
      replacementButton.classList.toggle(
        "d-none",
        currentButton.classList.contains("d-none"),
      );
      currentButton.replaceWith(replacementButton);
      return;
    }

    const buttonArea = field
      .closest(".mb-3, .form-check")
      ?.querySelector(buttonAreaSelector);
    buttonArea?.append(replacementButton);
    return;
  }

  //fallback
  if (currentButton) {
    currentButton.textContent =
      normalizedValue === ""
        ? (currentButton.dataset.emptyLabel ?? "+")
        : normalizedValue;
  }
};
const toggleEditField = (form, fieldId, state = true) => {
  const inputElement = getFieldById(form, fieldId);
  if (!inputElement) {
    return;
  }
  const activateButton = getActivateButton(inputElement);
  inputElement.classList.toggle("d-none", !state);
  activateButton?.classList.toggle("d-none", state);
  form
    .querySelectorAll(`[data-ie-dismiss][data-ie-for="${CSS.escape(fieldId)}"]`)
    .forEach((button) => {
      button.classList.toggle("d-none", !state);
    });
  form
    .querySelectorAll(`[data-ie-save][data-ie-for="${CSS.escape(fieldId)}"]`)
    .forEach((button) => {
      button.classList.toggle("d-none", !state);
    });
};
const closeAllFields = (form, fields) => {
  fields.forEach((field) => {
    if (field instanceof HTMLSelectElement) {
      return;
    }
    if (field.id) {
      toggleEditField(form, field.id, false);
    }
  });
  setFooterVisible(form, false);
};
const updateView = (initialValues, fields) => {
  fields.forEach((field) => {
    if (!field.name) {
      return;
    }
    const value = initialValues.get(field.name) ?? "";
    setFieldValue(field, value);
    if (!(field instanceof HTMLSelectElement)) {
      renderActivateButton(value, field);
    }
  });
};
document.querySelectorAll(formSelector).forEach((form) => {
  if (!(form instanceof HTMLFormElement)) {
    return;
  }
  const fields = Array.from(form.querySelectorAll(fieldSelector)).filter(
    isEditableField,
  );
  const initialValues = new Map(
    fields.map((field) => [field.name, getFieldValue(field)]),
  );
  form.addEventListener("change", (event) => {
    event.preventDefault();
    if (!(event.target instanceof Element)) {
      return;
    }
    const select = event.target.closest("select");
    if (!(select instanceof HTMLSelectElement)) {
      return;
    }
    form.requestSubmit();
  });
  form.addEventListener("click", (event) => {
    if (!(event.target instanceof Element)) {
      return;
    }
    const button = event.target.closest("button");
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }
    if (button.matches(editAllButtonSelector)) {
      event.preventDefault();
      form.querySelectorAll(editButtonSelector).forEach((editButton) => {
        const fieldId = editButton.dataset.ieFor;

        if (fieldId) {
          toggleEditField(form, fieldId, true);
        }
      });
      setFooterVisible(form, true);
      return;
    }
    if (button.matches(editButtonSelector)) {
      event.preventDefault();
      const fieldId = button.dataset.ieFor;
      if (fieldId) {
        toggleEditField(form, fieldId, true);
      }
      return;
    }

    if (button.matches("[data-ie-dismiss]")) {
      event.preventDefault();
      const fieldId = button.dataset.ieFor;
      if (fieldId) {
        const field = getFieldById(form, fieldId);
        if (field) {
          setFieldValue(field, initialValues.get(field.name) ?? "");
          clearValidationErrors([field]);
          toggleEditField(form, fieldId, false);
        }
      } else {
        fields.forEach((field) => {
          setFieldValue(field, initialValues.get(field.name) ?? "");
        });
        clearValidationErrors(fields);
        closeAllFields(form, fields);
      }
      return;
    }

    if (button.matches("[data-ie-save]")) {
      event.preventDefault();
      form.requestSubmit();
    }
  });
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    clearValidationErrors(fields);
    const data = {};
    fields.forEach((field) => {
      if (!field.name) {
        return;
      }
      const value = getFieldValue(field);
      if (initialValues.get(field.name) !== value) {
        data[field.name] = value;
      }
    });
    if (Object.keys(data).length === 0) {
      showStatus(form, "info");
      return;
    }
    const profileUid = Number.parseInt(form.dataset.profileUid ?? "", 10);
    const updateUrl = form.dataset.updateUrl;
    if (!Number.isInteger(profileUid) || profileUid <= 0 || !updateUrl) {
      showStatus(form, "danger");
      return;
    }
    form.setAttribute("aria-busy", "true");
    showStatus(form, "info");
    try {
      const response = await fetch(updateUrl, {
        method: "POST",
        credentials: "same-origin",
        headers: {
          Accept: "application/json",
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          profile: profileUid,
          data,
        }),
      });
      const result = await response.json().catch(() => null);
      if (!response.ok || result?.success !== true) {
        if (result?.errors !== null && typeof result?.errors === "object") {
          showValidationErrors(fields, result.errors);
        }
        showStatus(form, "danger");
        return;
      }
      Object.entries(data).forEach(([propertyName, value]) => {
        initialValues.set(propertyName, value);
      });
      updateView(initialValues, fields);
      closeAllFields(form, fields);
      showStatus(form, "success");
    } catch (error) {
      console.error("Inline edit request failed:", error);
      showStatus(form, "danger");
    } finally {
      form.removeAttribute("aria-busy");
    }
  });
});
