import {
  hooks,
  isEditableField,
  requestJson,
  showStatus,
  type EditableField,
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  toEditingContext,
  type EditingContext,
  type EditingTarget,
} from "@fgtclb/academic-persons-edit/frontend/profile/context.js";
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

const fieldsFormSelector = "[data-pe-fields-form]";
const editButtonSelector = "[data-academic-persons-profile-editing-activate-btn]";
const editAllButtonSelector = "[data-academic-persons-profile-editing-edit-all-btn]";
const editAllButtonLabelSelector = "[data-pe-edit-all-button-label]";
const buttonAreaSelector = "[data-form-field-button-area]";
const fieldSelector = ".academic-persons-profile-editing__field";
const fieldPreviewSelector = "[data-pe-field-preview]";
const fieldEditorSelector = "[data-pe-field-editor]";
const fieldGroupSelector = "[data-pe-field-group]";
const groupPreviewSelector = "[data-pe-group-preview]";
const groupPreviewContentSelector = "[data-pe-group-preview-content]";
const groupEditorSelector = "[data-pe-group-editor]";
const groupEditButtonSelector = "[data-pe-group-edit]";
const profileNameSelector = "[data-pe-profile-name]";
const autosaveOnChangeSelector = "[data-pe-autosave-on-change]";
const fieldActionsSelector = "[data-pe-field-actions]";

const isFieldReadOnly = (field: EditableField): boolean =>
  field instanceof HTMLSelectElement ? false : field.readOnly;

const getFieldEditElement = (field: EditableField): HTMLElement =>
  field
    .closest<HTMLElement>(fieldGroupSelector)
    ?.querySelector<HTMLElement>(groupEditorSelector) ??
  field.closest<HTMLElement>(fieldEditorSelector) ??
  field.closest<HTMLElement>("[data-pe-editor-container]") ??
  field;

const getFieldValue = (field: EditableField): FieldValue => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  const editorValue = isRichTextField(field)
    ? getRichTextEditorValue(field)
    : null;
  return editorValue ?? field.value;
};

const setFieldValue = (
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

const getFieldDisplayValue = (
  field: EditableField,
  value: unknown,
): string => {
  if (isRichTextField(field)) {
    return getPlainText(String(value ?? ""));
  }
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return value
      ? (hooks(field).peCheckedLabel ?? "")
      : (hooks(field).peUncheckedLabel ?? "");
  }
  if (field instanceof HTMLSelectElement) {
    const selectedOption = field.selectedOptions[0];
    return selectedOption?.value
      ? (selectedOption.textContent ?? "").trim()
      : "";
  }
  return String(value ?? "").trim();
};

const getFieldPropertyName = (field: EditableField): string => {
  const bracketProperty = field.name.match(/\[([^\]]+)]$/)?.[1];
  return bracketProperty ?? field.name;
};

const getFieldById = (
  context: EditingContext,
  fieldId: string | undefined,
): EditableField | null => {
  if (fieldId === undefined || fieldId === "") {
    return null;
  }
  const normalizedFieldId = fieldId.startsWith("profile-editing-")
    ? fieldId
    : `profile-editing-${context.profileUidValue}-${fieldId}`;
  const field = context.root.querySelector(`#${CSS.escape(normalizedFieldId)}`);
  return isEditableField(field) ? field : null;
};

const getActivateButton = (
  context: EditingContext,
  field: EditableField,
): HTMLButtonElement | null => {
  if (field.id === "") {
    return null;
  }
  return context.root.querySelector<HTMLButtonElement>(
    `${editButtonSelector}[data-pe-for="${CSS.escape(field.id)}"]`,
  );
};

const getFieldPreview = (
  context: EditingContext,
  field: EditableField,
): HTMLElement | null => {
  if (field.id === "") {
    return null;
  }
  return context.root.querySelector<HTMLElement>(
    `${fieldPreviewSelector}[data-pe-for="${CSS.escape(field.id)}"]`,
  );
};

const parseFieldIds = (value: string | undefined): string[] =>
  (value ?? "").split(/\s+/).filter((fieldId): boolean => fieldId !== "");

const getFieldsByIds = (
  context: EditingContext,
  value: string | undefined,
): EditableField[] =>
  parseFieldIds(value)
    .map((fieldId): EditableField | null => getFieldById(context, fieldId))
    .filter((field): field is EditableField => field !== null);

const getGroupFields = (
  context: EditingContext,
  group: HTMLElement,
): EditableField[] => getFieldsByIds(context, hooks(group).peFieldIds);

const renderProfileName = (context: EditingContext): void => {
  const heading = context.root.querySelector<HTMLElement>(profileNameSelector);
  if (heading === null) {
    return;
  }
  heading.textContent = getFieldsByIds(
    context,
    hooks(heading).peProfileNameFieldIds,
  )
    .map((field): string => getFieldDisplayValue(field, getFieldValue(field)))
    .filter((fieldValue): boolean => fieldValue !== "")
    .join(" ");
};

const renderFieldGroupPreview = (
  context: EditingContext,
  group: HTMLElement,
): void => {
  const content = group.querySelector<HTMLElement>(groupPreviewContentSelector);
  if (content === null) {
    return;
  }
  const values = getFieldsByIds(
    context,
    hooks(group).peDisplayFieldIds ?? hooks(group).peFieldIds,
  )
    .map((field): string => getFieldDisplayValue(field, getFieldValue(field)))
    .filter((value): boolean => value !== "");
  const value =
    hooks(group).peDisplayMode === "first"
      ? (values[0] ?? "")
      : values.join(" ");
  content.classList.toggle("text-body-secondary", value === "");
  content.textContent = value || content.dataset.emptyLabel || "";
};

const toggleEditGroup = (
  context: EditingContext,
  group: HTMLElement,
  state = true,
): void => {
  const editor = group.querySelector<HTMLElement>(groupEditorSelector);
  const preview = group.querySelector<HTMLElement>(groupPreviewSelector);
  const button = group.querySelector<HTMLButtonElement>(groupEditButtonSelector);
  const fields = getGroupFields(context, group).filter(
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

const setEditAllButtonState = (
  context: EditingContext,
  active: boolean,
): void => {
  const button = context.root.querySelector<HTMLButtonElement>(
    editAllButtonSelector,
  );
  if (button === null) {
    return;
  }
  button.classList.toggle("active", active);
  button.setAttribute("aria-pressed", String(active));
  const label = button.querySelector<HTMLElement>(editAllButtonLabelSelector);
  const nextLabel = active
    ? hooks(button).peCloseAllLabel
    : hooks(button).peEditAllLabel;
  if (label !== null && nextLabel !== undefined) {
    label.textContent = nextLabel;
  }
};

const clearValidationErrors = (fields: EditableField[]): void => {
  fields.forEach((field): void => {
    field.setAttribute("aria-invalid", "false");
    field.classList.remove("is-invalid");
    getFieldEditElement(field).classList.remove("is-invalid");
    const feedback = field
      .closest<HTMLElement>(
        "[data-pe-field-wrapper], [data-pe-group-control], .form-check",
      )
      ?.querySelector<HTMLElement>(".invalid-feedback");
    if (feedback !== null && feedback !== undefined) {
      feedback.textContent = "";
    }
  });
};

const getTemplateButton = (
  template: Element | null,
): HTMLButtonElement | null =>
  template instanceof HTMLTemplateElement
    ? template.content.querySelector<HTMLButtonElement>("button")
    : null;

const createActivateButton = (
  context: EditingContext,
  field: EditableField,
  fieldValue: unknown,
): HTMLButtonElement | null => {
  const displayValue = getFieldDisplayValue(field, fieldValue);
  const template = context.root.querySelector(
    displayValue === ""
      ? "[data-pe-new-button-template]"
      : "[data-pe-edit-button-template]",
  );
  const templateButton = getTemplateButton(template);
  if (templateButton === null) {
    return null;
  }
  const button = templateButton.cloneNode(true);
  if (!(button instanceof HTMLButtonElement)) {
    return null;
  }
  hooks(button).peFor = field.id;
  button.setAttribute("aria-controls", `${field.id}-editor`);
  button.setAttribute("aria-expanded", "false");
  const label = button.querySelector<HTMLElement>("[data-pe-button-label]");
  if (label !== null) {
    label.textContent = displayValue === "" ? "+" : displayValue;
  }
  return button;
};

const renderActivateButton = (
  context: EditingContext,
  field: EditableField,
  fieldValue: unknown,
): void => {
  if (field.id === "") {
    return;
  }
  if (isRichTextField(field)) {
    renderRichTextPreview(context.root, field, fieldValue);
    return;
  }
  const group = field.closest<HTMLElement>(fieldGroupSelector);
  if (group !== null) {
    renderFieldGroupPreview(context, group);
    return;
  }
  const preview = getFieldPreview(context, field);
  const content = preview?.querySelector<HTMLElement>("[data-pe-field-preview-content]");
  if (content === null || content === undefined) {
    const currentButton = getActivateButton(context, field);
    const replacementButton = createActivateButton(context, field, fieldValue);
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

const toggleEditField = (
  context: EditingContext,
  fieldId: string,
  state = true,
): void => {
  const field = getFieldById(context, fieldId);
  if (field === null || field.disabled || isFieldReadOnly(field)) {
    return;
  }
  const group = field.closest<HTMLElement>(fieldGroupSelector);
  if (group !== null) {
    toggleEditGroup(context, group, state);
    return;
  }
  getFieldEditElement(field).classList.toggle("d-none", !state);
  getFieldPreview(context, field)?.classList.toggle("d-none", state);
  getActivateButton(context, field)?.setAttribute("aria-expanded", String(state));
  context.root
    .querySelectorAll<HTMLElement>(
      `${fieldActionsSelector}[data-pe-for="${CSS.escape(field.id)}"]`,
    )
    .forEach((actions): void => {
      actions.classList.toggle("d-none", !state);
    });
  if (!state) {
    getActivateButton(context, field)?.focus();
    return;
  }
  if (isRichTextField(field)) {
    void ensureRichTextEditor(context, field)
      .then((editor): void => editor?.editing.view.focus())
      .catch((): void => field.focus());
  } else {
    field.focus();
  }
};

const closeFields = (
  context: EditingContext,
  fields: EditableField[],
): void => {
  const groups = new Set<HTMLElement>();
  fields.forEach((field): void => {
    const group = field.closest<HTMLElement>(fieldGroupSelector);
    if (group !== null) {
      groups.add(group);
    } else if (field.id !== "") {
      toggleEditField(context, field.id, false);
    }
  });
  groups.forEach((group): void => toggleEditGroup(context, group, false));
};

const showValidationErrors = (
  context: EditingContext,
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
      toggleEditField(context, field.id, true);
    }
    const feedback = field
      .closest<HTMLElement>(
        "[data-pe-field-wrapper], [data-pe-group-control], .form-check",
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
      void ensureRichTextEditor(context, firstInvalidField)
        .then((editor): void => editor?.editing.view.focus())
        .catch((): void => firstInvalidField.focus());
    } else {
      firstInvalidField.focus();
    }
  }
};

export const initializeFieldEditing = (editingTarget: EditingTarget): void => {
  const context = toEditingContext(editingTarget);
  const root = context.root;
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
  renderProfileName(context);
  root.querySelectorAll<HTMLElement>(fieldGroupSelector).forEach((group): void => {
    renderFieldGroupPreview(context, group);
    const hasEditableField = getGroupFields(context, group).some(
      (field): boolean => !field.disabled && !isFieldReadOnly(field),
    );
    group
      .querySelector(groupEditButtonSelector)
      ?.classList.toggle("d-none", !hasEditableField);
  });
  fields
    .filter((field): boolean => field.closest(fieldGroupSelector) === null)
    .forEach((field): void =>
      renderActivateButton(context, field, getFieldValue(field)),
    );

  const normalizedRichTextBaselines = new WeakSet<HTMLTextAreaElement>();
  let editAllActive = false;
  setEditAllButtonState(context, editAllActive);

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
      setEditAllButtonState(context, false);
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
        richTextFields.map((field) => ensureRichTextEditor(context, field)),
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
      closeFields(context, fieldsToSave);
      finishEditAllWhenClosed();
      showStatus(context, "info", context.messages.unchanged ?? null);
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
        toggleEditField(context, invalidField.id, true);
      } else {
        invalidField.reportValidity();
      }
      showStatus(context, "warning", context.messages.validation ?? null);
      return false;
    }
    const profileUid = context.profileUid;
    const updateUrl = context.urls.update;
    if (profileUid === null || updateUrl === undefined) {
      showStatus(context, "danger");
      return false;
    }
    const data = Object.fromEntries(
      changedFields.map((field): [string, FieldValue] => [
        getFieldPropertyName(field),
        getFieldValue(field),
      ]),
    );
    root.setAttribute("aria-busy", "true");
    showStatus(context, "info", context.messages.saving ?? null);
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
        renderActivateButton(context, field, value);
      });
      renderProfileName(context);
      closeFields(context, changedFields);
      finishEditAllWhenClosed();
      showStatus(context, "success");
      return true;
    } catch (error) {
      const result = (error as RequestError).result;
      if (result?.errors !== undefined) {
        showValidationErrors(context, fields, result.errors);
        showStatus(context, "warning", context.messages.validation ?? null);
      } else {
        showStatus(context, "danger", result?.message ?? null);
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
        toggleEditGroup(context, group, true);
      }
      return;
    }
    if (button.matches("[data-pe-group-dismiss]")) {
      event.preventDefault();
      const group = button.closest<HTMLElement>(fieldGroupSelector);
      if (group !== null) {
        const groupFields = getGroupFields(context, group).filter(
          (field): boolean => !field.disabled && !isFieldReadOnly(field),
        );
        groupFields.forEach((field): void => setFieldValue(field, ""));
        clearValidationErrors(groupFields);
        toggleEditGroup(context, group, true);
      }
      return;
    }
    if (button.matches("[data-pe-group-cancel]")) {
      event.preventDefault();
      const group = button.closest<HTMLElement>(fieldGroupSelector);
      if (group !== null) {
        const groupFields = getGroupFields(context, group);
        resetFields(groupFields);
        renderFieldGroupPreview(context, group);
        toggleEditGroup(context, group, false);
        finishEditAllWhenClosed();
      }
      return;
    }
    if (button.matches("[data-pe-group-save]")) {
      event.preventDefault();
      const group = button.closest<HTMLElement>(fieldGroupSelector);
      if (group !== null) {
        void saveFields(getGroupFields(context, group));
      }
      return;
    }
    if (button.matches(editAllButtonSelector)) {
      event.preventDefault();
      editAllActive = !editAllActive;
      if (editAllActive) {
        root.querySelectorAll<HTMLElement>(fieldGroupSelector).forEach((group): void => {
          toggleEditGroup(context, group, true);
        });
        root.querySelectorAll<HTMLElement>(editButtonSelector).forEach((editButton): void => {
          const fieldId = hooks(editButton).peFor;
          if (fieldId !== undefined) {
            toggleEditField(context, fieldId, true);
          }
        });
      } else {
        closeFields(context, fields);
      }
      setEditAllButtonState(context, editAllActive);
      return;
    }
    if (button.matches(editButtonSelector)) {
      event.preventDefault();
      const fieldId = hooks(button).peFor;
      if (fieldId !== undefined) {
        toggleEditField(context, fieldId, true);
      }
      return;
    }
    if (button.matches("[data-pe-dismiss]")) {
      event.preventDefault();
      const field = getFieldById(context, hooks(button).peFor);
      if (field !== null) {
        setFieldValue(field, "");
        clearValidationErrors([field]);
        toggleEditField(context, field.id, true);
      }
      return;
    }
    if (button.matches("[data-pe-cancel]")) {
      event.preventDefault();
      const field = getFieldById(context, hooks(button).peFor);
      if (field !== null) {
        setFieldValue(field, persistedValues.get(field) ?? "");
        clearValidationErrors([field]);
        toggleEditField(context, field.id, false);
        finishEditAllWhenClosed();
      }
      return;
    }
    if (button.matches("[data-pe-save]")) {
      event.preventDefault();
      const field = getFieldById(context, hooks(button).peFor);
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
    // A checkbox saves on change, without an explicit save button, so a failed
    // request has to put the control back where it was: otherwise it shows a
    // state the database does not have, and nothing on screen says so. This is
    // what sync.ts does for the synchronisation switch.
    const previousValue = persistedValues.get(field);
    void saveFields([field]).then((saved): void => {
      if (!saved && previousValue !== undefined) {
        setFieldValue(field, previousValue);
      }
    });
  });
  forms.forEach((form): void => {
    form.addEventListener("submit", (event): void => event.preventDefault());
    form.addEventListener("reset", (event): void => event.preventDefault());
  });
};
