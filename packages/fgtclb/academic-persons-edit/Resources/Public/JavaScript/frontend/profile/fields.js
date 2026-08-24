import {
  getProfileUid,
  isEditableField,
  requestJson,
  showStatus,
} from "./common.js";
import {
  ensureRichTextEditor,
  getPlainText,
  getRichTextEditorValue,
  getRichTextInitialValue,
  isRichTextField,
  renderRichTextPreview,
  setRichTextEditorValue,
} from "./rich-text.js";

const fieldsFormSelector = "[data-ie-fields-form]";
const editButtonSelector =
  "[data-academic-persons-inline-edit-activate-btn]";
const editAllButtonSelector =
  "[data-academic-persons-inline-edit-edit-all-btn]";
const editAllButtonLabelSelector = "[data-ie-edit-all-button-label]";
const buttonAreaSelector = "[data-form-field-button-area]";
const fieldSelector = ".academic-persons-inline-edit__field";
const fieldPreviewSelector = "[data-ie-field-preview]";
const fieldEditorSelector = "[data-ie-field-editor]";
const fieldGroupSelector = "[data-ie-field-group]";
const groupPreviewSelector = "[data-ie-group-preview]";
const groupPreviewContentSelector = "[data-ie-group-preview-content]";
const groupEditorSelector = "[data-ie-group-editor]";
const groupEditButtonSelector = "[data-ie-group-edit]";
const profileNameSelector = "[data-ie-profile-name]";
const autosaveOnChangeSelector = "[data-ie-autosave-on-change]";
const fieldActionsSelector = "[data-ie-field-actions]";

export const getFieldEditElement = (field) =>
  field.closest(fieldGroupSelector)?.querySelector(groupEditorSelector) ??
  field.closest(fieldEditorSelector) ??
  field.closest("[data-ie-editor-container]") ??
  field;

export const getFieldValue = (field) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  const editorValue = getRichTextEditorValue(field);
  return editorValue ?? field.value;
};

export const setFieldValue = (field, value) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    field.checked = Boolean(value);
    return;
  }
  const normalizedValue =
    value === null || value === undefined ? "" : String(value);
  field.value = normalizedValue;
  setRichTextEditorValue(field, normalizedValue);
};

export const getFieldDisplayValue = (field, value) => {
  if (isRichTextField(field)) {
    return getPlainText(String(value ?? ""));
  }
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return value
      ? (field.dataset.ieCheckedLabel ?? "")
      : (field.dataset.ieUncheckedLabel ?? "");
  }
  if (field instanceof HTMLSelectElement) {
    const selectedOption = field.selectedOptions[0];
    return selectedOption?.value
      ? (selectedOption.textContent ?? "").trim()
      : "";
  }
  return String(value ?? "").trim();
};

export const getFieldPropertyName = (field) => {
  const bracketProperty = field.name.match(/\[([^\]]+)]$/)?.[1];
  return bracketProperty ?? field.name;
};

export const getFieldById = (root, fieldId) => {
  if (!fieldId) {
    return null;
  }
  const normalizedFieldId = fieldId.startsWith("inline-profile-")
    ? fieldId
    : `inline-profile-${root.dataset.profileUid ?? ""}-${fieldId}`;
  const field = root.querySelector(`#${CSS.escape(normalizedFieldId)}`);
  return isEditableField(field) ? field : null;
};

export const getActivateButton = (root, field) => {
  if (!field.id) {
    return null;
  }
  const selector = `${editButtonSelector}[data-ie-for="${CSS.escape(field.id)}"]`;
  const button = root.querySelector(selector);
  return button instanceof HTMLButtonElement ? button : null;
};

export const getFieldPreview = (root, field) => {
  if (!field.id) {
    return null;
  }
  const selector = `${fieldPreviewSelector}[data-ie-for="${CSS.escape(field.id)}"]`;
  const preview = root.querySelector(selector);
  return preview instanceof HTMLElement ? preview : null;
};

export const parseFieldIds = (value) =>
  (value ?? "").split(/\s+/).filter((fieldId) => fieldId !== "");

export const getFieldsByIds = (root, value) =>
  parseFieldIds(value)
    .map((fieldId) => getFieldById(root, fieldId))
    .filter((field) => field !== null);

export const getGroupFields = (root, group) =>
  getFieldsByIds(root, group.dataset.ieFieldIds);

export const renderProfileName = (root) => {
  const heading = root.querySelector(profileNameSelector);
  if (!(heading instanceof HTMLElement)) {
    return;
  }
  heading.textContent = getFieldsByIds(
    root,
    heading.dataset.ieProfileNameFieldIds,
  )
    .map((field) => getFieldDisplayValue(field, getFieldValue(field)))
    .filter((fieldValue) => fieldValue !== "")
    .join(" ");
};

export const renderFieldGroupPreview = (root, group) => {
  const content = group.querySelector(groupPreviewContentSelector);
  if (!(content instanceof HTMLElement)) {
    return;
  }
  const values = getFieldsByIds(
    root,
    group.dataset.ieDisplayFieldIds ?? group.dataset.ieFieldIds,
  )
    .map((field) => getFieldDisplayValue(field, getFieldValue(field)))
    .filter((value) => value !== "");
  const value =
    group.dataset.ieDisplayMode === "first"
      ? (values[0] ?? "")
      : values.join(" ");
  if (value !== "") {
    content.classList.remove("text-body-secondary");
    content.textContent = value;
    return;
  }
  content.classList.add("text-body-secondary");
  content.textContent = content.dataset.emptyLabel ?? "";
};

export const toggleEditGroup = (root, group, state = true) => {
  const editor = group.querySelector(groupEditorSelector);
  const preview = group.querySelector(groupPreviewSelector);
  const button = group.querySelector(groupEditButtonSelector);
  const fields = getGroupFields(root, group).filter(
    (field) => !field.disabled && !field.readOnly,
  );
  if (!(editor instanceof HTMLElement) || fields.length === 0) {
    return;
  }
  editor.classList.toggle("d-none", !state);
  preview?.classList.toggle("d-none", state);
  button?.setAttribute("aria-expanded", String(state));
  if (state) {
    fields[0].focus();
  }
};

export const setEditAllButtonState = (root, active) => {
  const button = root.querySelector(editAllButtonSelector);
  if (!(button instanceof HTMLButtonElement)) {
    return;
  }
  button.classList.toggle("active", active);
  button.setAttribute("aria-pressed", String(active));
  const label = button.querySelector(editAllButtonLabelSelector);
  const nextLabel = active
    ? button.dataset.ieCloseAllLabel
    : button.dataset.ieEditAllLabel;
  if (label instanceof HTMLElement && nextLabel) {
    label.textContent = nextLabel;
  }
};

export const clearValidationErrors = (fields) => {
  fields.forEach((field) => {
    field.classList.remove("is-invalid");
    getFieldEditElement(field).classList.remove("is-invalid");
    const feedback = field
      .closest("[data-ie-field-wrapper], [data-ie-group-control], .form-check")
      ?.querySelector(".invalid-feedback");
    if (feedback) {
      feedback.textContent = "";
    }
  });
};

export const getTemplateButton = (template) =>
  template instanceof HTMLTemplateElement
    ? template.content.querySelector("button")
    : null;

export const createActivateButton = (root, field, fieldValue) => {
  const displayValue = getFieldDisplayValue(field, fieldValue);
  const template = root.querySelector(
    displayValue === ""
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
    label.textContent = displayValue === "" ? "+" : displayValue;
  }
  return button;
};

export const renderActivateButton = (root, field, fieldValue) => {
  if (!field.id) {
    return;
  }
  if (isRichTextField(field)) {
    renderRichTextPreview(root, field, fieldValue);
    return;
  }
  const group = field.closest(fieldGroupSelector);
  if (group instanceof HTMLElement) {
    renderFieldGroupPreview(root, group);
    return;
  }
  const preview = getFieldPreview(root, field);
  const content = preview?.querySelector("[data-ie-field-preview-content]");
  if (!(content instanceof HTMLElement)) {
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
    return;
  }
  const displayValue = getFieldDisplayValue(field, fieldValue);
  content.classList.toggle("text-body-secondary", displayValue === "");
  content.textContent = displayValue || preview.dataset.emptyLabel || "";
};

export const toggleEditField = (root, fieldId, state = true) => {
  const field = getFieldById(root, fieldId);
  if (!field || field.disabled || field.readOnly) {
    return;
  }
  const group = field.closest(fieldGroupSelector);
  if (group instanceof HTMLElement) {
    toggleEditGroup(root, group, state);
    return;
  }
  getFieldEditElement(field).classList.toggle("d-none", !state);
  getFieldPreview(root, field)?.classList.toggle("d-none", state);
  getActivateButton(root, field)?.setAttribute("aria-expanded", String(state));
  root
    .querySelectorAll(
      `${fieldActionsSelector}[data-ie-for="${CSS.escape(field.id)}"]`,
    )
    .forEach((actions) => actions.classList.toggle("d-none", !state));

  if (!state) {
    return;
  }
  if (isRichTextField(field)) {
    void ensureRichTextEditor(root, field)
      .then((editor) => editor?.editing.view.focus())
      .catch(() => field.focus());
  } else {
    field.focus();
  }
};

export const closeFields = (root, fields) => {
  const groups = new Set();
  fields.forEach((field) => {
    const group = field.closest(fieldGroupSelector);
    if (group instanceof HTMLElement) {
      groups.add(group);
    } else if (field.id) {
      toggleEditField(root, field.id, false);
    }
  });
  groups.forEach((group) => toggleEditGroup(root, group, false));
};

export const showValidationErrors = (root, fields, errors) => {
  Object.entries(errors).forEach(([propertyPath, messages]) => {
    const propertyName = propertyPath.split(".").pop();
    const field = fields.find(
      (candidate) => getFieldPropertyName(candidate) === propertyName,
    );
    if (!field) {
      return;
    }
    field.classList.add("is-invalid");
    getFieldEditElement(field).classList.add("is-invalid");
    if (field.id) {
      toggleEditField(root, field.id, true);
    }
    const feedback = field
      .closest("[data-ie-field-wrapper], [data-ie-group-control], .form-check")
      ?.querySelector(".invalid-feedback");
    if (feedback) {
      feedback.textContent = Array.isArray(messages)
        ? messages.join(" ")
        : String(messages);
    }
  });
};

export const initializeFieldEditing = (root) => {
  const forms = Array.from(root.querySelectorAll(fieldsFormSelector)).filter(
    (element) => element instanceof HTMLFormElement,
  );
  if (forms.length === 0) {
    return;
  }

  const fields = Array.from(root.querySelectorAll(fieldSelector)).filter(
    isEditableField,
  );
  const persistedValues = new Map(
    fields.map((field) => [field, getFieldValue(field)]),
  );
  renderProfileName(root);
  root.querySelectorAll(fieldGroupSelector).forEach((group) => {
    if (!(group instanceof HTMLElement)) {
      return;
    }
    renderFieldGroupPreview(root, group);
    const hasEditableField = getGroupFields(root, group).some(
      (field) => !field.disabled && !field.readOnly,
    );
    group
      .querySelector(groupEditButtonSelector)
      ?.classList.toggle("d-none", !hasEditableField);
  });
  fields
    .filter((field) => !field.closest(fieldGroupSelector))
    .forEach((field) => renderActivateButton(root, field, getFieldValue(field)));

  const normalizedRichTextBaselines = new WeakSet();
  let editAllActive = false;
  setEditAllButtonState(root, editAllActive);

  const finishEditAllWhenClosed = () => {
    if (!editAllActive) {
      return;
    }
    const hasOpenField = fields.some(
      (field) =>
        !field.disabled &&
        !field.readOnly &&
        !getFieldEditElement(field).classList.contains("d-none"),
    );
    if (!hasOpenField) {
      editAllActive = false;
      setEditAllButtonState(root, false);
    }
  };

  const resetFields = (fieldsToReset) => {
    fieldsToReset.forEach((field) => {
      setFieldValue(field, persistedValues.get(field) ?? "");
    });
    clearValidationErrors(fieldsToReset);
  };

  const saveFields = async (fieldsToSave) => {
    if (root.getAttribute("aria-busy") === "true") {
      return false;
    }
    try {
      await Promise.all(
        fieldsToSave
          .filter(isRichTextField)
          .map((field) => ensureRichTextEditor(root, field)),
      );
    } catch {
      return false;
    }
    fieldsToSave.filter(isRichTextField).forEach((field) => {
      if (!normalizedRichTextBaselines.has(field)) {
        const initialValue = getRichTextInitialValue(field);
        if (initialValue !== undefined) {
          persistedValues.set(field, initialValue);
        }
        normalizedRichTextBaselines.add(field);
      }
    });
    clearValidationErrors(fieldsToSave);
    const changedFields = fieldsToSave.filter(
      (field) =>
        getFieldPropertyName(field) &&
        !field.disabled &&
        !field.readOnly &&
        persistedValues.get(field) !== getFieldValue(field),
    );

    if (changedFields.length === 0) {
      closeFields(root, fieldsToSave);
      finishEditAllWhenClosed();
      showStatus(root, "info", root.dataset.messageUnchanged ?? null);
      return true;
    }

    const invalidField = changedFields.find((field) => !field.checkValidity());
    if (invalidField) {
      invalidField.classList.add("is-invalid");
      getFieldEditElement(invalidField).classList.add("is-invalid");
      if (isRichTextField(invalidField)) {
        toggleEditField(root, invalidField.id, true);
      } else {
        invalidField.reportValidity();
      }
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
      const result = await requestJson(updateUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profile: profileUid, data }),
      });
      changedFields.forEach((field) => {
        const propertyName = getFieldPropertyName(field);
        const value = Object.hasOwn(result.data ?? {}, propertyName)
          ? result.data[propertyName]
          : getFieldValue(field);
        setFieldValue(field, value);
        persistedValues.set(field, value);
        renderActivateButton(root, field, value);
      });
      renderProfileName(root);
      closeFields(root, changedFields);
      finishEditAllWhenClosed();
      showStatus(root, "success");
      return true;
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      if (result?.errors && typeof result.errors === "object") {
        showValidationErrors(root, fields, result.errors);
        showStatus(root, "warning", root.dataset.messageValidation ?? null);
      } else {
        showStatus(root, "danger", result?.message ?? null);
      }
      return false;
    } finally {
      root.removeAttribute("aria-busy");
    }
  };

  root.addEventListener("click", (event) => {
    if (!(event.target instanceof Element)) {
      return;
    }
    const button = event.target.closest("button");
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }
    if (button.matches(groupEditButtonSelector)) {
      event.preventDefault();
      const group = button.closest(fieldGroupSelector);
      if (group instanceof HTMLElement) {
        toggleEditGroup(root, group, true);
      }
      return;
    }
    if (button.matches("[data-ie-group-dismiss]")) {
      event.preventDefault();
      const group = button.closest(fieldGroupSelector);
      if (group instanceof HTMLElement) {
        const groupFields = getGroupFields(root, group).filter(
          (field) => !field.disabled && !field.readOnly,
        );
        groupFields.forEach((field) => setFieldValue(field, ""));
        clearValidationErrors(groupFields);
        toggleEditGroup(root, group, true);
      }
      return;
    }
    if (button.matches("[data-ie-group-cancel]")) {
      event.preventDefault();
      const group = button.closest(fieldGroupSelector);
      if (group instanceof HTMLElement) {
        const groupFields = getGroupFields(root, group);
        resetFields(groupFields);
        renderFieldGroupPreview(root, group);
        toggleEditGroup(root, group, false);
        finishEditAllWhenClosed();
      }
      return;
    }
    if (button.matches("[data-ie-group-save]")) {
      event.preventDefault();
      const group = button.closest(fieldGroupSelector);
      if (group instanceof HTMLElement) {
        void saveFields(getGroupFields(root, group));
      }
      return;
    }
    if (button.matches(editAllButtonSelector)) {
      event.preventDefault();
      editAllActive = !editAllActive;
      if (editAllActive) {
        root.querySelectorAll(fieldGroupSelector).forEach((group) => {
          if (group instanceof HTMLElement) {
            toggleEditGroup(root, group, true);
          }
        });
        root.querySelectorAll(editButtonSelector).forEach((editButton) => {
          if (editButton.dataset.ieFor) {
            toggleEditField(root, editButton.dataset.ieFor, true);
          }
        });
      } else {
        closeFields(root, fields);
      }
      setEditAllButtonState(root, editAllActive);
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
      const field = getFieldById(root, button.dataset.ieFor);
      if (field) {
        setFieldValue(field, "");
        clearValidationErrors([field]);
        toggleEditField(root, field.id, true);
      }
      return;
    }
    if (button.matches("[data-ie-cancel]")) {
      event.preventDefault();
      const field = getFieldById(root, button.dataset.ieFor);
      if (field) {
        setFieldValue(field, persistedValues.get(field) ?? "");
        clearValidationErrors([field]);
        toggleEditField(root, field.id, false);
        finishEditAllWhenClosed();
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

  root.addEventListener("change", (event) => {
    const field = event.target;
    if (!isEditableField(field) || !field.matches(autosaveOnChangeSelector)) {
      return;
    }
    void saveFields([field]);
  });

  forms.forEach((form) => {
    form.addEventListener("submit", (event) => event.preventDefault());
    form.addEventListener("reset", (event) => event.preventDefault());
  });
};
