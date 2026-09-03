/* Generated from Resources/Private/TypeScript — do not edit. */
import {
  getProfileUid,
  isEditableField,
  requestJson,
  showStatus
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  ensureRichTextEditor,
  getPlainText,
  getRichTextEditorValue,
  getRichTextInitialValue,
  isRichTextField,
  renderRichTextPreview,
  setRichTextEditorValue
} from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";
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
const isFieldReadOnly = (field) => field instanceof HTMLSelectElement ? false : field.readOnly;
const getFieldEditElement = (field) => {
  var _a;
  return ((_a = field.closest(fieldGroupSelector)) == null ? void 0 : _a.querySelector(groupEditorSelector)) ?? field.closest(fieldEditorSelector) ?? field.closest("[data-ie-editor-container]") ?? field;
};
const getFieldValue = (field) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  const editorValue = isRichTextField(field) ? getRichTextEditorValue(field) : null;
  return editorValue ?? field.value;
};
const setFieldValue = (field, value) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    field.checked = Boolean(value);
    return;
  }
  const normalizedValue = value === null || value === void 0 ? "" : String(value);
  field.value = normalizedValue;
  if (isRichTextField(field)) {
    setRichTextEditorValue(field, normalizedValue);
  }
};
const getFieldDisplayValue = (field, value) => {
  if (isRichTextField(field)) {
    return getPlainText(String(value ?? ""));
  }
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return value ? field.dataset.ieCheckedLabel ?? "" : field.dataset.ieUncheckedLabel ?? "";
  }
  if (field instanceof HTMLSelectElement) {
    const selectedOption = field.selectedOptions[0];
    return (selectedOption == null ? void 0 : selectedOption.value) ? (selectedOption.textContent ?? "").trim() : "";
  }
  return String(value ?? "").trim();
};
const getFieldPropertyName = (field) => {
  var _a;
  const bracketProperty = (_a = field.name.match(/\[([^\]]+)]$/)) == null ? void 0 : _a[1];
  return bracketProperty ?? field.name;
};
const getFieldById = (root, fieldId) => {
  if (fieldId === void 0 || fieldId === "") {
    return null;
  }
  const normalizedFieldId = fieldId.startsWith("inline-profile-") ? fieldId : `inline-profile-${root.dataset.profileUid ?? ""}-${fieldId}`;
  const field = root.querySelector(`#${CSS.escape(normalizedFieldId)}`);
  return isEditableField(field) ? field : null;
};
const getActivateButton = (root, field) => {
  if (field.id === "") {
    return null;
  }
  return root.querySelector(
    `${editButtonSelector}[data-ie-for="${CSS.escape(field.id)}"]`
  );
};
const getFieldPreview = (root, field) => {
  if (field.id === "") {
    return null;
  }
  return root.querySelector(
    `${fieldPreviewSelector}[data-ie-for="${CSS.escape(field.id)}"]`
  );
};
const parseFieldIds = (value) => (value ?? "").split(/\s+/).filter((fieldId) => fieldId !== "");
const getFieldsByIds = (root, value) => parseFieldIds(value).map((fieldId) => getFieldById(root, fieldId)).filter((field) => field !== null);
const getGroupFields = (root, group) => getFieldsByIds(root, group.dataset.ieFieldIds);
const renderProfileName = (root) => {
  const heading = root.querySelector(profileNameSelector);
  if (heading === null) {
    return;
  }
  heading.textContent = getFieldsByIds(
    root,
    heading.dataset.ieProfileNameFieldIds
  ).map((field) => getFieldDisplayValue(field, getFieldValue(field))).filter((fieldValue) => fieldValue !== "").join(" ");
};
const renderFieldGroupPreview = (root, group) => {
  const content = group.querySelector(groupPreviewContentSelector);
  if (content === null) {
    return;
  }
  const values = getFieldsByIds(
    root,
    group.dataset.ieDisplayFieldIds ?? group.dataset.ieFieldIds
  ).map((field) => getFieldDisplayValue(field, getFieldValue(field))).filter((value2) => value2 !== "");
  const value = group.dataset.ieDisplayMode === "first" ? values[0] ?? "" : values.join(" ");
  content.classList.toggle("text-body-secondary", value === "");
  content.textContent = value || content.dataset.emptyLabel || "";
};
const toggleEditGroup = (root, group, state = true) => {
  var _a;
  const editor = group.querySelector(groupEditorSelector);
  const preview = group.querySelector(groupPreviewSelector);
  const button = group.querySelector(groupEditButtonSelector);
  const fields = getGroupFields(root, group).filter(
    (field) => !field.disabled && !isFieldReadOnly(field)
  );
  if (editor === null || fields.length === 0) {
    return;
  }
  editor.classList.toggle("d-none", !state);
  preview == null ? void 0 : preview.classList.toggle("d-none", state);
  button == null ? void 0 : button.setAttribute("aria-expanded", String(state));
  if (!state) {
    button == null ? void 0 : button.focus();
    return;
  }
  (_a = fields[0]) == null ? void 0 : _a.focus();
};
const setEditAllButtonState = (root, active) => {
  const button = root.querySelector(editAllButtonSelector);
  if (button === null) {
    return;
  }
  button.classList.toggle("active", active);
  button.setAttribute("aria-pressed", String(active));
  const label = button.querySelector(editAllButtonLabelSelector);
  const nextLabel = active ? button.dataset.ieCloseAllLabel : button.dataset.ieEditAllLabel;
  if (label !== null && nextLabel !== void 0) {
    label.textContent = nextLabel;
  }
};
const clearValidationErrors = (fields) => {
  fields.forEach((field) => {
    var _a;
    field.setAttribute("aria-invalid", "false");
    field.classList.remove("is-invalid");
    getFieldEditElement(field).classList.remove("is-invalid");
    const feedback = (_a = field.closest(
      "[data-ie-field-wrapper], [data-ie-group-control], .form-check"
    )) == null ? void 0 : _a.querySelector(".invalid-feedback");
    if (feedback !== null && feedback !== void 0) {
      feedback.textContent = "";
    }
  });
};
const getTemplateButton = (template) => template instanceof HTMLTemplateElement ? template.content.querySelector("button") : null;
const createActivateButton = (root, field, fieldValue) => {
  const displayValue = getFieldDisplayValue(field, fieldValue);
  const template = root.querySelector(
    displayValue === "" ? "[data-ie-new-button-template]" : "[data-ie-edit-button-template]"
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
  const label = button.querySelector("[data-ie-button-label]");
  if (label !== null) {
    label.textContent = displayValue === "" ? "+" : displayValue;
  }
  return button;
};
const renderActivateButton = (root, field, fieldValue) => {
  var _a, _b;
  if (field.id === "") {
    return;
  }
  if (isRichTextField(field)) {
    renderRichTextPreview(root, field, fieldValue);
    return;
  }
  const group = field.closest(fieldGroupSelector);
  if (group !== null) {
    renderFieldGroupPreview(root, group);
    return;
  }
  const preview = getFieldPreview(root, field);
  const content = preview == null ? void 0 : preview.querySelector("[data-ie-field-preview-content]");
  if (content === null || content === void 0) {
    const currentButton = getActivateButton(root, field);
    const replacementButton = createActivateButton(root, field, fieldValue);
    if (replacementButton === null) {
      return;
    }
    if (currentButton !== null) {
      replacementButton.classList.toggle(
        "d-none",
        currentButton.classList.contains("d-none")
      );
      currentButton.replaceWith(replacementButton);
      return;
    }
    (_b = (_a = field.closest(".mb-3, .form-check")) == null ? void 0 : _a.querySelector(buttonAreaSelector)) == null ? void 0 : _b.append(replacementButton);
    return;
  }
  const displayValue = getFieldDisplayValue(field, fieldValue);
  content.classList.toggle("text-body-secondary", displayValue === "");
  content.textContent = displayValue || (preview == null ? void 0 : preview.dataset.emptyLabel) || "";
};
const toggleEditField = (root, fieldId, state = true) => {
  var _a, _b, _c;
  const field = getFieldById(root, fieldId);
  if (field === null || field.disabled || isFieldReadOnly(field)) {
    return;
  }
  const group = field.closest(fieldGroupSelector);
  if (group !== null) {
    toggleEditGroup(root, group, state);
    return;
  }
  getFieldEditElement(field).classList.toggle("d-none", !state);
  (_a = getFieldPreview(root, field)) == null ? void 0 : _a.classList.toggle("d-none", state);
  (_b = getActivateButton(root, field)) == null ? void 0 : _b.setAttribute("aria-expanded", String(state));
  root.querySelectorAll(
    `${fieldActionsSelector}[data-ie-for="${CSS.escape(field.id)}"]`
  ).forEach((actions) => {
    actions.classList.toggle("d-none", !state);
  });
  if (!state) {
    (_c = getActivateButton(root, field)) == null ? void 0 : _c.focus();
    return;
  }
  if (isRichTextField(field)) {
    void ensureRichTextEditor(root, field).then((editor) => editor == null ? void 0 : editor.editing.view.focus()).catch(() => field.focus());
  } else {
    field.focus();
  }
};
const closeFields = (root, fields) => {
  const groups = /* @__PURE__ */ new Set();
  fields.forEach((field) => {
    const group = field.closest(fieldGroupSelector);
    if (group !== null) {
      groups.add(group);
    } else if (field.id !== "") {
      toggleEditField(root, field.id, false);
    }
  });
  groups.forEach((group) => toggleEditGroup(root, group, false));
};
const showValidationErrors = (root, fields, errors) => {
  const invalidFields = [];
  Object.entries(errors).forEach(([propertyPath, messages]) => {
    var _a;
    const propertyName = propertyPath.split(".").pop();
    const field = fields.find(
      (candidate) => getFieldPropertyName(candidate) === propertyName
    );
    if (field === void 0) {
      return;
    }
    field.classList.add("is-invalid");
    field.setAttribute("aria-invalid", "true");
    invalidFields.push(field);
    getFieldEditElement(field).classList.add("is-invalid");
    if (field.id !== "") {
      toggleEditField(root, field.id, true);
    }
    const feedback = (_a = field.closest(
      "[data-ie-field-wrapper], [data-ie-group-control], .form-check"
    )) == null ? void 0 : _a.querySelector(".invalid-feedback");
    if (feedback !== null && feedback !== void 0) {
      feedback.textContent = Array.isArray(messages) ? messages.map(String).join(" ") : String(messages);
    }
  });
  const firstInvalidField = invalidFields[0];
  if (firstInvalidField !== void 0) {
    if (isRichTextField(firstInvalidField)) {
      void ensureRichTextEditor(root, firstInvalidField).then((editor) => editor == null ? void 0 : editor.editing.view.focus()).catch(() => firstInvalidField.focus());
    } else {
      firstInvalidField.focus();
    }
  }
};
const initializeFieldEditing = (root) => {
  const forms = Array.from(
    root.querySelectorAll(fieldsFormSelector)
  );
  if (forms.length === 0) {
    return;
  }
  const fields = Array.from(root.querySelectorAll(fieldSelector)).filter(
    isEditableField
  );
  const persistedValues = new Map(
    fields.map((field) => [field, getFieldValue(field)])
  );
  renderProfileName(root);
  root.querySelectorAll(fieldGroupSelector).forEach((group) => {
    var _a;
    renderFieldGroupPreview(root, group);
    const hasEditableField = getGroupFields(root, group).some(
      (field) => !field.disabled && !isFieldReadOnly(field)
    );
    (_a = group.querySelector(groupEditButtonSelector)) == null ? void 0 : _a.classList.toggle("d-none", !hasEditableField);
  });
  fields.filter((field) => field.closest(fieldGroupSelector) === null).forEach((field) => renderActivateButton(root, field, getFieldValue(field)));
  const normalizedRichTextBaselines = /* @__PURE__ */ new WeakSet();
  let editAllActive = false;
  setEditAllButtonState(root, editAllActive);
  const finishEditAllWhenClosed = () => {
    if (!editAllActive) {
      return;
    }
    const hasOpenField = fields.some(
      (field) => !field.disabled && !isFieldReadOnly(field) && !getFieldEditElement(field).classList.contains("d-none")
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
    const richTextFields = fieldsToSave.filter(isRichTextField);
    try {
      await Promise.all(
        richTextFields.map((field) => ensureRichTextEditor(root, field))
      );
    } catch {
      return false;
    }
    richTextFields.forEach((field) => {
      if (!normalizedRichTextBaselines.has(field)) {
        const initialValue = getRichTextInitialValue(field);
        if (initialValue !== void 0) {
          persistedValues.set(field, initialValue);
        }
        normalizedRichTextBaselines.add(field);
      }
    });
    clearValidationErrors(fieldsToSave);
    const changedFields = fieldsToSave.filter(
      (field) => getFieldPropertyName(field) !== "" && !field.disabled && !isFieldReadOnly(field) && persistedValues.get(field) !== getFieldValue(field)
    );
    if (changedFields.length === 0) {
      closeFields(root, fieldsToSave);
      finishEditAllWhenClosed();
      showStatus(root, "info", root.dataset.messageUnchanged ?? null);
      return true;
    }
    const invalidField = changedFields.find(
      (field) => !field.checkValidity()
    );
    if (invalidField !== void 0) {
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
    if (profileUid === null || updateUrl === void 0) {
      showStatus(root, "danger");
      return false;
    }
    const data = Object.fromEntries(
      changedFields.map((field) => [
        getFieldPropertyName(field),
        getFieldValue(field)
      ])
    );
    root.setAttribute("aria-busy", "true");
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    try {
      const result = await requestJson(updateUrl, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ profile: profileUid, data })
      });
      const responseData = typeof result.data === "object" && result.data !== null ? result.data : {};
      changedFields.forEach((field) => {
        const propertyName = getFieldPropertyName(field);
        const value = Object.hasOwn(responseData, propertyName) ? responseData[propertyName] : getFieldValue(field);
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
      const result = error.result;
      if ((result == null ? void 0 : result.errors) !== void 0) {
        showValidationErrors(root, fields, result.errors);
        showStatus(root, "warning", root.dataset.messageValidation ?? null);
      } else {
        showStatus(root, "danger", (result == null ? void 0 : result.message) ?? null);
      }
      return false;
    } finally {
      root.setAttribute("aria-busy", "false");
    }
  };
  root.addEventListener("click", (event) => {
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
      const group = button.closest(fieldGroupSelector);
      if (group !== null) {
        toggleEditGroup(root, group, true);
      }
      return;
    }
    if (button.matches("[data-ie-group-dismiss]")) {
      event.preventDefault();
      const group = button.closest(fieldGroupSelector);
      if (group !== null) {
        const groupFields = getGroupFields(root, group).filter(
          (field) => !field.disabled && !isFieldReadOnly(field)
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
      const group = button.closest(fieldGroupSelector);
      if (group !== null) {
        void saveFields(getGroupFields(root, group));
      }
      return;
    }
    if (button.matches(editAllButtonSelector)) {
      event.preventDefault();
      editAllActive = !editAllActive;
      if (editAllActive) {
        root.querySelectorAll(fieldGroupSelector).forEach((group) => {
          toggleEditGroup(root, group, true);
        });
        root.querySelectorAll(editButtonSelector).forEach((editButton) => {
          if (editButton.dataset.ieFor !== void 0) {
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
      if (button.dataset.ieFor !== void 0) {
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
export {
  clearValidationErrors,
  closeFields,
  createActivateButton,
  getActivateButton,
  getFieldById,
  getFieldDisplayValue,
  getFieldEditElement,
  getFieldPreview,
  getFieldPropertyName,
  getFieldValue,
  getFieldsByIds,
  getGroupFields,
  getTemplateButton,
  initializeFieldEditing,
  parseFieldIds,
  renderActivateButton,
  renderFieldGroupPreview,
  renderProfileName,
  setEditAllButtonState,
  setFieldValue,
  showValidationErrors,
  toggleEditField,
  toggleEditGroup
};
