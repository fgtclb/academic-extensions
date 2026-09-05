import {
  getProfileUid,
  isEditableField,
  requestJson,
  showStatus,
  type EditableField,
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  ensureRichTextEditor,
  getPlainText,
  getRichTextEditorValue,
  getRichTextInitialValue,
  isRichTextField,
  renderRichTextPreview,
  setRichTextEditorValue,
} from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";

type FieldValue = string | boolean;
type ValidationErrors = Record<string, unknown>;

interface RequestError extends Error {
  result?: {
    data?: Record<string, unknown>;
    errors?: ValidationErrors;
    message?: string;
  };
}

const fieldsFormSelector = "[data-ie-fields-form]";
const editButtonSelector = "[data-academic-persons-inline-edit-activate-btn]";
const editAllButtonSelector = "[data-academic-persons-inline-edit-edit-all-btn]";
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

const isFieldReadOnly = (field: EditableField): boolean =>
  field instanceof HTMLSelectElement ? false : field.readOnly;

export const getFieldEditElement = (field: EditableField): HTMLElement =>
  field
    .closest<HTMLElement>(fieldGroupSelector)
    ?.querySelector<HTMLElement>(groupEditorSelector) ??
  field.closest<HTMLElement>(fieldEditorSelector) ??
  field.closest<HTMLElement>("[data-ie-editor-container]") ??
  field;

export const getFieldValue = (field: EditableField): FieldValue => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  const editorValue = isRichTextField(field)
    ? getRichTextEditorValue(field)
    : null;
  return editorValue ?? field.value;
};

export const setFieldValue = (
  field: EditableField,
  value: unknown,
): void => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    field.checked = Boolean(value);
    return;
  }
  const normalizedValue =
    value === null || value === undefined ? "" : String(value);
  field.value = normalizedValue;
  if (isRichTextField(field)) {
    setRichTextEditorValue(field, normalizedValue);
  }
};

export const getFieldDisplayValue = (
  field: EditableField,
  value: unknown,
): string => {
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

export const getFieldPropertyName = (field: EditableField): string => {
  const bracketProperty = field.name.match(/\[([^\]]+)]$/)?.[1];
  return bracketProperty ?? field.name;
};

export const getFieldById = (
  root: HTMLElement,
  fieldId: string | undefined,
): EditableField | null => {
  if (fieldId === undefined || fieldId === "") {
    return null;
  }
  const normalizedFieldId = fieldId.startsWith("inline-profile-")
    ? fieldId
    : `inline-profile-${root.dataset.profileUid ?? ""}-${fieldId}`;
  const field = root.querySelector(`#${CSS.escape(normalizedFieldId)}`);
  return isEditableField(field) ? field : null;
};

export const getActivateButton = (
  root: HTMLElement,
  field: EditableField,
): HTMLButtonElement | null => {
  if (field.id === "") {
    return null;
  }
  return root.querySelector<HTMLButtonElement>(
    `${editButtonSelector}[data-ie-for="${CSS.escape(field.id)}"]`,
  );
};

export const getFieldPreview = (
  root: HTMLElement,
  field: EditableField,
): HTMLElement | null => {
  if (field.id === "") {
    return null;
  }
  return root.querySelector<HTMLElement>(
    `${fieldPreviewSelector}[data-ie-for="${CSS.escape(field.id)}"]`,
  );
};

export const parseFieldIds = (value: string | undefined): string[] =>
  (value ?? "").split(/\s+/).filter((fieldId): boolean => fieldId !== "");

export const getFieldsByIds = (
  root: HTMLElement,
  value: string | undefined,
): EditableField[] =>
  parseFieldIds(value)
    .map((fieldId): EditableField | null => getFieldById(root, fieldId))
    .filter((field): field is EditableField => field !== null);

export const getGroupFields = (
  root: HTMLElement,
  group: HTMLElement,
): EditableField[] => getFieldsByIds(root, group.dataset.ieFieldIds);

export const renderProfileName = (root: HTMLElement): void => {
  const heading = root.querySelector<HTMLElement>(profileNameSelector);
  if (heading === null) {
    return;
  }
  heading.textContent = getFieldsByIds(
    root,
    heading.dataset.ieProfileNameFieldIds,
  )
    .map((field): string => getFieldDisplayValue(field, getFieldValue(field)))
    .filter((fieldValue): boolean => fieldValue !== "")
    .join(" ");
};

export const renderFieldGroupPreview = (
  root: HTMLElement,
  group: HTMLElement,
): void => {
  const content = group.querySelector<HTMLElement>(groupPreviewContentSelector);
  if (content === null) {
    return;
  }
  const values = getFieldsByIds(
    root,
    group.dataset.ieDisplayFieldIds ?? group.dataset.ieFieldIds,
  )
    .map((field): string => getFieldDisplayValue(field, getFieldValue(field)))
    .filter((value): boolean => value !== "");
  const value =
    group.dataset.ieDisplayMode === "first"
      ? (values[0] ?? "")
      : values.join(" ");
  content.classList.toggle("text-body-secondary", value === "");
  content.textContent = value || content.dataset.emptyLabel || "";
};

export const toggleEditGroup = (
  root: HTMLElement,
  group: HTMLElement,
  state = true,
): void => {
  const editor = group.querySelector<HTMLElement>(groupEditorSelector);
  const preview = group.querySelector<HTMLElement>(groupPreviewSelector);
  const button = group.querySelector<HTMLButtonElement>(groupEditButtonSelector);
  const fields = getGroupFields(root, group).filter(
    (field): boolean => !field.disabled && !isFieldReadOnly(field),
  );
  if (editor === null || fields.length === 0) {
    return;
  }
  editor.classList.toggle("d-none", !state);
  preview?.classList.toggle("d-none", state);
  button?.setAttribute("aria-expanded", String(state));
  if (!state) {
    button?.focus();
    return;
  }
  fields[0]?.focus();
};

export const setEditAllButtonState = (
  root: HTMLElement,
  active: boolean,
): void => {
  const button = root.querySelector<HTMLButtonElement>(editAllButtonSelector);
  if (button === null) {
    return;
  }
  button.classList.toggle("active", active);
  button.setAttribute("aria-pressed", String(active));
  const label = button.querySelector<HTMLElement>(editAllButtonLabelSelector);
  const nextLabel = active
    ? button.dataset.ieCloseAllLabel
    : button.dataset.ieEditAllLabel;
  if (label !== null && nextLabel !== undefined) {
    label.textContent = nextLabel;
  }
};

export const clearValidationErrors = (fields: EditableField[]): void => {
  fields.forEach((field): void => {
    field.setAttribute("aria-invalid", "false");
    field.classList.remove("is-invalid");
    getFieldEditElement(field).classList.remove("is-invalid");
    const feedback = field
      .closest<HTMLElement>(
        "[data-ie-field-wrapper], [data-ie-group-control], .form-check",
      )
      ?.querySelector<HTMLElement>(".invalid-feedback");
    if (feedback !== null && feedback !== undefined) {
      feedback.textContent = "";
    }
  });
};

export const getTemplateButton = (
  template: Element | null,
): HTMLButtonElement | null =>
  template instanceof HTMLTemplateElement
    ? template.content.querySelector<HTMLButtonElement>("button")
    : null;

export const createActivateButton = (
  root: HTMLElement,
  field: EditableField,
  fieldValue: unknown,
): HTMLButtonElement | null => {
  const displayValue = getFieldDisplayValue(field, fieldValue);
  const template = root.querySelector(
    displayValue === ""
      ? "[data-ie-new-button-template]"
      : "[data-ie-edit-button-template]",
  );
  const templateButton = getTemplateButton(template);
  if (templateButton === null) {
    return null;
  }
  const button = templateButton.cloneNode(true);
  if (!(button instanceof HTMLButtonElement)) {
    return null;
  }
  button.dataset.ieFor = field.id;
  button.setAttribute("aria-controls", `${field.id}-editor`);
  button.setAttribute("aria-expanded", "false");
  const label = button.querySelector<HTMLElement>("[data-ie-button-label]");
  if (label !== null) {
    label.textContent = displayValue === "" ? "+" : displayValue;
  }
  return button;
};

export const renderActivateButton = (
  root: HTMLElement,
  field: EditableField,
  fieldValue: unknown,
): void => {
  if (field.id === "") {
    return;
  }
  if (isRichTextField(field)) {
    renderRichTextPreview(root, field, fieldValue);
    return;
  }
  const group = field.closest<HTMLElement>(fieldGroupSelector);
  if (group !== null) {
    renderFieldGroupPreview(root, group);
    return;
  }
  const preview = getFieldPreview(root, field);
  const content = preview?.querySelector<HTMLElement>("[data-ie-field-preview-content]");
  if (content === null || content === undefined) {
    const currentButton = getActivateButton(root, field);
    const replacementButton = createActivateButton(root, field, fieldValue);
    if (replacementButton === null) {
      return;
    }
    if (currentButton !== null) {
      replacementButton.classList.toggle(
        "d-none",
        currentButton.classList.contains("d-none"),
      );
      currentButton.replaceWith(replacementButton);
      return;
    }
    field
      .closest<HTMLElement>(".mb-3, .form-check")
      ?.querySelector<HTMLElement>(buttonAreaSelector)
      ?.append(replacementButton);
    return;
  }
  const displayValue = getFieldDisplayValue(field, fieldValue);
  content.classList.toggle("text-body-secondary", displayValue === "");
  content.textContent = displayValue || preview?.dataset.emptyLabel || "";
};

export const toggleEditField = (
  root: HTMLElement,
  fieldId: string,
  state = true,
): void => {
  const field = getFieldById(root, fieldId);
  if (field === null || field.disabled || isFieldReadOnly(field)) {
    return;
  }
  const group = field.closest<HTMLElement>(fieldGroupSelector);
  if (group !== null) {
    toggleEditGroup(root, group, state);
    return;
  }
  getFieldEditElement(field).classList.toggle("d-none", !state);
  getFieldPreview(root, field)?.classList.toggle("d-none", state);
  getActivateButton(root, field)?.setAttribute("aria-expanded", String(state));
  root
    .querySelectorAll<HTMLElement>(
      `${fieldActionsSelector}[data-ie-for="${CSS.escape(field.id)}"]`,
    )
    .forEach((actions): void => {
      actions.classList.toggle("d-none", !state);
    });
  if (!state) {
    getActivateButton(root, field)?.focus();
    return;
  }
  if (isRichTextField(field)) {
    void ensureRichTextEditor(root, field)
      .then((editor): void => editor?.editing.view.focus())
      .catch((): void => field.focus());
  } else {
    field.focus();
  }
};

export const closeFields = (
  root: HTMLElement,
  fields: EditableField[],
): void => {
  const groups = new Set<HTMLElement>();
  fields.forEach((field): void => {
    const group = field.closest<HTMLElement>(fieldGroupSelector);
    if (group !== null) {
      groups.add(group);
    } else if (field.id !== "") {
      toggleEditField(root, field.id, false);
    }
  });
  groups.forEach((group): void => toggleEditGroup(root, group, false));
};

export const showValidationErrors = (
  root: HTMLElement,
  fields: EditableField[],
  errors: ValidationErrors,
): void => {
  const invalidFields: EditableField[] = [];
  Object.entries(errors).forEach(([propertyPath, messages]): void => {
    const propertyName = propertyPath.split(".").pop();
    const field = fields.find(
      (candidate): boolean => getFieldPropertyName(candidate) === propertyName,
    );
    if (field === undefined) {
      return;
    }
    field.classList.add("is-invalid");
    field.setAttribute("aria-invalid", "true");
    invalidFields.push(field);
    getFieldEditElement(field).classList.add("is-invalid");
    if (field.id !== "") {
      toggleEditField(root, field.id, true);
    }
    const feedback = field
      .closest<HTMLElement>(
        "[data-ie-field-wrapper], [data-ie-group-control], .form-check",
      )
      ?.querySelector<HTMLElement>(".invalid-feedback");
    if (feedback !== null && feedback !== undefined) {
      feedback.textContent = Array.isArray(messages)
        ? messages.map(String).join(" ")
        : String(messages);
    }
  });
  const firstInvalidField = invalidFields[0];
  if (firstInvalidField !== undefined) {
    if (isRichTextField(firstInvalidField)) {
      void ensureRichTextEditor(root, firstInvalidField)
        .then((editor): void => editor?.editing.view.focus())
        .catch((): void => firstInvalidField.focus());
    } else {
      firstInvalidField.focus();
    }
  }
};

export const initializeFieldEditing = (root: HTMLElement): void => {
  const forms = Array.from(
    root.querySelectorAll<HTMLFormElement>(fieldsFormSelector),
  );
  if (forms.length === 0) {
    return;
  }
  const fields = Array.from(root.querySelectorAll(fieldSelector)).filter(
    isEditableField,
  );
  const persistedValues = new Map<EditableField, FieldValue>(
    fields.map((field): [EditableField, FieldValue] => [field, getFieldValue(field)]),
  );
  renderProfileName(root);
  root.querySelectorAll<HTMLElement>(fieldGroupSelector).forEach((group): void => {
    renderFieldGroupPreview(root, group);
    const hasEditableField = getGroupFields(root, group).some(
      (field): boolean => !field.disabled && !isFieldReadOnly(field),
    );
    group
      .querySelector(groupEditButtonSelector)
      ?.classList.toggle("d-none", !hasEditableField);
  });
  fields
    .filter((field): boolean => field.closest(fieldGroupSelector) === null)
    .forEach((field): void => renderActivateButton(root, field, getFieldValue(field)));

  const normalizedRichTextBaselines = new WeakSet<HTMLTextAreaElement>();
  let editAllActive = false;
  setEditAllButtonState(root, editAllActive);

  const finishEditAllWhenClosed = (): void => {
    if (!editAllActive) {
      return;
    }
    const hasOpenField = fields.some(
      (field): boolean =>
        !field.disabled &&
        !isFieldReadOnly(field) &&
        !getFieldEditElement(field).classList.contains("d-none"),
    );
    if (!hasOpenField) {
      editAllActive = false;
      setEditAllButtonState(root, false);
    }
  };

  const resetFields = (fieldsToReset: EditableField[]): void => {
    fieldsToReset.forEach((field): void => {
      setFieldValue(field, persistedValues.get(field) ?? "");
    });
    clearValidationErrors(fieldsToReset);
  };

  const saveFields = async (fieldsToSave: EditableField[]): Promise<boolean> => {
    if (root.getAttribute("aria-busy") === "true") {
      return false;
    }
    const richTextFields = fieldsToSave.filter(isRichTextField);
    try {
      await Promise.all(
        richTextFields.map((field) => ensureRichTextEditor(root, field)),
      );
    } catch {
      return false;
    }
    richTextFields.forEach((field): void => {
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
      (field): boolean =>
        getFieldPropertyName(field) !== "" &&
        !field.disabled &&
        !isFieldReadOnly(field) &&
        persistedValues.get(field) !== getFieldValue(field),
    );
    if (changedFields.length === 0) {
      closeFields(root, fieldsToSave);
      finishEditAllWhenClosed();
      showStatus(root, "info", root.dataset.messageUnchanged ?? null);
      return true;
    }
    const invalidField = changedFields.find(
      (field): boolean => !field.checkValidity(),
    );
    if (invalidField !== undefined) {
      invalidField.classList.add("is-invalid");
      invalidField.setAttribute("aria-invalid", "true");
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
    if (profileUid === null || updateUrl === undefined) {
      showStatus(root, "danger");
      return false;
    }
    const data = Object.fromEntries(
      changedFields.map((field): [string, FieldValue] => [
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
      const responseData =
        typeof result.data === "object" && result.data !== null
          ? (result.data as Record<string, unknown>)
          : {};
      changedFields.forEach((field): void => {
        const propertyName = getFieldPropertyName(field);
        const value = Object.hasOwn(responseData, propertyName)
          ? responseData[propertyName]
          : getFieldValue(field);
        setFieldValue(field, value);
        persistedValues.set(field, getFieldValue(field));
        renderActivateButton(root, field, value);
      });
      renderProfileName(root);
      closeFields(root, changedFields);
      finishEditAllWhenClosed();
      showStatus(root, "success");
      return true;
    } catch (error) {
      const result = (error as RequestError).result;
      if (result?.errors !== undefined) {
        showValidationErrors(root, fields, result.errors);
        showStatus(root, "warning", root.dataset.messageValidation ?? null);
      } else {
        showStatus(root, "danger", result?.message ?? null);
      }
      return false;
    } finally {
      root.setAttribute("aria-busy", "false");
    }
  };

  root.addEventListener("click", (event): void => {
    const target = event.target;
    if (!(target instanceof Element)) {
      return;
    }
    const button = target.closest("button");
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }
    if (button.matches(groupEditButtonSelector)) {
      event.preventDefault();
      const group = button.closest<HTMLElement>(fieldGroupSelector);
      if (group !== null) {
        toggleEditGroup(root, group, true);
      }
      return;
    }
    if (button.matches("[data-ie-group-dismiss]")) {
      event.preventDefault();
      const group = button.closest<HTMLElement>(fieldGroupSelector);
      if (group !== null) {
        const groupFields = getGroupFields(root, group).filter(
          (field): boolean => !field.disabled && !isFieldReadOnly(field),
        );
        groupFields.forEach((field): void => setFieldValue(field, ""));
        clearValidationErrors(groupFields);
        toggleEditGroup(root, group, true);
      }
      return;
    }
    if (button.matches("[data-ie-group-cancel]")) {
      event.preventDefault();
      const group = button.closest<HTMLElement>(fieldGroupSelector);
      if (group !== null) {
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
      const group = button.closest<HTMLElement>(fieldGroupSelector);
      if (group !== null) {
        void saveFields(getGroupFields(root, group));
      }
      return;
    }
    if (button.matches(editAllButtonSelector)) {
      event.preventDefault();
      editAllActive = !editAllActive;
      if (editAllActive) {
        root.querySelectorAll<HTMLElement>(fieldGroupSelector).forEach((group): void => {
          toggleEditGroup(root, group, true);
        });
        root.querySelectorAll<HTMLElement>(editButtonSelector).forEach((editButton): void => {
          if (editButton.dataset.ieFor !== undefined) {
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
      if (button.dataset.ieFor !== undefined) {
        toggleEditField(root, button.dataset.ieFor, true);
      }
      return;
    }
    if (button.matches("[data-ie-dismiss]")) {
      event.preventDefault();
      const field = getFieldById(root, button.dataset.ieFor);
      if (field !== null) {
        setFieldValue(field, "");
        clearValidationErrors([field]);
        toggleEditField(root, field.id, true);
      }
      return;
    }
    if (button.matches("[data-ie-cancel]")) {
      event.preventDefault();
      const field = getFieldById(root, button.dataset.ieFor);
      if (field !== null) {
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
      if (field !== null) {
        void saveFields([field]);
      }
    }
  });
  root.addEventListener("change", (event): void => {
    const field = event.target;
    if (!isEditableField(field) || !field.matches(autosaveOnChangeSelector)) {
      return;
    }
    void saveFields([field]);
  });
  forms.forEach((form): void => {
    form.addEventListener("submit", (event): void => event.preventDefault());
    form.addEventListener("reset", (event): void => event.preventDefault());
  });
};
