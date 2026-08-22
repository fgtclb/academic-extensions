import { Bold, Italic } from "@ckeditor/ckeditor5-basic-styles";
import { ClassicEditor } from "@ckeditor/ckeditor5-editor-classic";
import { Essentials } from "@ckeditor/ckeditor5-essentials";
import { Link } from "@ckeditor/ckeditor5-link";
import { List } from "@ckeditor/ckeditor5-list";
import { Paragraph } from "@ckeditor/ckeditor5-paragraph";

const rootSelector = "[data-academic-persons-inline-edit]";
const fieldsFormSelector = "[data-ie-fields-form]";
const editButtonSelector = "[data-academic-persons-inline-edit-activate-btn]";
const editAllButtonSelector =
  "[data-academic-persons-inline-edit-edit-all-btn]";
const footerButtonAreaSelector = "[data-ie-footer-button-area]";
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
const stickyImageSelector = "[data-ie-sticky-image]";
const pageHeaderSelector = "#page-header";
const imageFormSelector = ".academic-persons-inline-edit__image-form";
const imageModalSelector = "[data-ie-image-modal]";
const syncFormSelector = "[data-ie-sync-form]";
const syncCheckboxSelector = ".academic-persons-inline-edit__sync-checkbox";
const richTextFieldSelector = "[data-ie-rich-text]";
const autosaveOnChangeSelector = "[data-ie-autosave-on-change]";
const richTextPreviewSelector = "[data-ie-rich-text-preview]";
const richTextPreviewContentSelector = "[data-ie-rich-text-preview-content]";
const fieldActionsSelector = "[data-ie-field-actions]";
const richTextEditors = new WeakMap();
const richTextEditorPromises = new WeakMap();
const richTextInitialValues = new WeakMap();
const allowedRichTextPreviewTags = new Set([
  "a",
  "br",
  "em",
  "li",
  "ol",
  "p",
  "strong",
  "ul",
]);
const blockedRichTextPreviewTags = new Set([
  "iframe",
  "math",
  "object",
  "script",
  "style",
  "svg",
  "template",
]);
const allowedRichTextLinkSchemes = new Set(["http", "https", "mailto", "tel"]);
const richTextEditorConfig = {
  licenseKey: "GPL",
  plugins: [Essentials, Paragraph, Bold, Italic, List, Link],
  toolbar: {
    items: [
      "undo",
      "redo",
      "|",
      "bold",
      "italic",
      "|",
      "bulletedList",
      "numberedList",
      "|",
      "link",
    ],
    shouldNotGroupWhenFull: false,
  },
  link: {
    allowedProtocols: ["http", "https", "mailto", "tel"],
    defaultProtocol: "https://",
  },
};

const isEditableField = (element) =>
  element instanceof HTMLInputElement ||
  element instanceof HTMLSelectElement ||
  element instanceof HTMLTextAreaElement;

const isRichTextField = (field) => field.matches(richTextFieldSelector);

const getFieldEditElement = (field) =>
  field.closest(fieldGroupSelector)?.querySelector(groupEditorSelector) ??
  field.closest(fieldEditorSelector) ??
  field.closest("[data-ie-editor-container]") ??
  field;

const getRichTextPreview = (root, field) => {
  if (!isRichTextField(field) || !field.id) {
    return null;
  }
  return root.querySelector(
    `${richTextPreviewSelector}[data-ie-for="${CSS.escape(field.id)}"]`,
  );
};

const getFieldValue = (field) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    return field.checked;
  }
  const editor = richTextEditors.get(field);
  if (editor) {
    field.value = editor.getData();
  }
  return field.value;
};

const setFieldValue = (field, value) => {
  if (field instanceof HTMLInputElement && field.type === "checkbox") {
    field.checked = Boolean(value);
    return;
  }
  const normalizedValue =
    value === null || value === undefined ? "" : String(value);
  field.value = normalizedValue;
  const editor = richTextEditors.get(field);
  if (editor && editor.getData() !== normalizedValue) {
    editor.setData(normalizedValue);
  }
};

const getPlainText = (value) => {
  const parsedDocument = new DOMParser().parseFromString(value, "text/html");
  return (parsedDocument.body.textContent ?? "")
    .replaceAll("\u00a0", " ")
    .replace(/\s+/g, " ")
    .trim();
};

const isAllowedRichTextLink = (value) => {
  const normalizedValue = value.trim();
  if (normalizedValue === "" || normalizedValue.startsWith("//")) {
    return false;
  }
  const scheme = normalizedValue.match(/^([a-z][a-z\d+.-]*):/i)?.[1];
  return (
    scheme === undefined || allowedRichTextLinkSchemes.has(scheme.toLowerCase())
  );
};

const parseRichTextPreview = (value) => {
  const parsedDocument = new DOMParser().parseFromString(value, "text/html");
  Array.from(parsedDocument.body.querySelectorAll("*")).forEach((element) => {
    const tagName = element.tagName.toLowerCase();
    if (blockedRichTextPreviewTags.has(tagName)) {
      element.remove();
      return;
    }
    if (!allowedRichTextPreviewTags.has(tagName)) {
      element.replaceWith(...Array.from(element.childNodes));
      return;
    }
    Array.from(element.attributes).forEach((attribute) => {
      const keepsHref =
        tagName === "a" &&
        attribute.name === "href" &&
        isAllowedRichTextLink(attribute.value);
      if (!keepsHref) {
        element.removeAttribute(attribute.name);
      }
    });
  });
  return parsedDocument;
};

const renderRichTextPreview = (root, field, value) => {
  const preview = getRichTextPreview(root, field);
  const content = preview?.querySelector(richTextPreviewContentSelector);
  if (!(preview instanceof HTMLElement) || !(content instanceof HTMLElement)) {
    return;
  }
  const normalizedValue =
    value === null || value === undefined ? "" : String(value);
  if (getPlainText(normalizedValue) === "") {
    const emptyLabel = document.createElement("span");
    emptyLabel.className = "text-body-secondary";
    emptyLabel.textContent = preview.dataset.emptyLabel ?? "";
    content.replaceChildren(emptyLabel);
    return;
  }
  const parsedDocument = parseRichTextPreview(normalizedValue);
  const fragment = document.createDocumentFragment();
  Array.from(parsedDocument.body.childNodes).forEach((node) => {
    fragment.append(document.importNode(node, true));
  });
  content.replaceChildren(fragment);
};

const getFieldDisplayValue = (field, value) => {
  if (isRichTextField(field)) {
    return getPlainText(String(value ?? ""));
  }
  if (field instanceof HTMLSelectElement) {
    const selectedOption = field.selectedOptions[0];
    return selectedOption?.value
      ? (selectedOption.textContent ?? "").trim()
      : "";
  }
  return String(value ?? "").trim();
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

const getFieldPreview = (root, field) => {
  if (!field.id) {
    return null;
  }
  const selector = `${fieldPreviewSelector}[data-ie-for="${CSS.escape(field.id)}"]`;
  const preview = root.querySelector(selector);
  return preview instanceof HTMLElement ? preview : null;
};

const parseFieldIds = (value) =>
  (value ?? "").split(/\s+/).filter((fieldId) => fieldId !== "");

const getFieldsByIds = (root, value) =>
  parseFieldIds(value)
    .map((fieldId) => getFieldById(root, fieldId))
    .filter((field) => field !== null);

const getGroupFields = (root, group) =>
  getFieldsByIds(root, group.dataset.ieFieldIds);

const renderProfileName = (root) => {
  const heading = root.querySelector(profileNameSelector);
  if (!(heading instanceof HTMLElement)) {
    return;
  }
  const value = getFieldsByIds(root, heading.dataset.ieProfileNameFieldIds)
    .map((field) => getFieldDisplayValue(field, getFieldValue(field)))
    .filter((fieldValue) => fieldValue !== "")
    .join(" ");
  heading.textContent = value;
};

const renderFieldGroupPreview = (root, group) => {
  const content = group.querySelector(groupPreviewContentSelector);
  if (!(content instanceof HTMLElement)) {
    return;
  }
  const displayFields = getFieldsByIds(
    root,
    group.dataset.ieDisplayFieldIds ?? group.dataset.ieFieldIds,
  );
  const values = displayFields
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

const toggleEditGroup = (root, group, state = true) => {
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

const ensureRichTextEditor = (root, field) => {
  if (!isRichTextField(field) || field.disabled || field.readOnly) {
    return Promise.resolve(null);
  }
  const editor = richTextEditors.get(field);
  if (editor) {
    return Promise.resolve(editor);
  }
  const pendingEditor = richTextEditorPromises.get(field);
  if (pendingEditor) {
    return pendingEditor;
  }
  const editorPromise = ClassicEditor.create(field, richTextEditorConfig)
    .then((createdEditor) => {
      richTextEditors.set(field, createdEditor);
      richTextInitialValues.set(field, createdEditor.getData());
      createdEditor.model.document.on("change:data", () => {
        field.value = createdEditor.getData();
        field.dispatchEvent(new Event("input", { bubbles: true }));
      });
      return createdEditor;
    })
    .catch((error) => {
      showStatus(root, "danger", root.dataset.messageEditorError ?? null);
      throw error;
    })
    .finally(() => richTextEditorPromises.delete(field));
  richTextEditorPromises.set(field, editorPromise);
  return editorPromise;
};

const clearValidationErrors = (fields) => {
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

const showValidationErrors = (root, fields, errors) => {
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

const getTemplateButton = (template) => {
  if (template instanceof HTMLTemplateElement) {
    return template.content.querySelector("button");
  }
  return null;
};

const createActivateButton = (root, field, fieldValue) => {
  const normalizedValue =
    fieldValue === null || fieldValue === undefined ? "" : String(fieldValue);
  const displayValue = getFieldDisplayValue(field, normalizedValue);
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

const renderActivateButton = (root, field, fieldValue) => {
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

const toggleEditField = (root, fieldId, state = true) => {
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
  const previewElement = getFieldPreview(root, field);
  previewElement?.classList.toggle("d-none", state);
  getActivateButton(root, field)?.setAttribute("aria-expanded", String(state));
  root
    .querySelectorAll(
      `${fieldActionsSelector}[data-ie-for="${CSS.escape(fieldId)}"]`,
    )
    .forEach((actions) => actions.classList.toggle("d-none", !state));

  if (state) {
    if (isRichTextField(field)) {
      void ensureRichTextEditor(root, field)
        .then((editor) => editor?.editing.view.focus())
        .catch(() => field.focus());
    } else {
      field.focus();
    }
  }
};

const closeFields = (root, fields) => {
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

const initializeStickyImageOffset = (root) => {
  const stickyImage = root.querySelector(stickyImageSelector);
  const pageHeader = document.querySelector(pageHeaderSelector);
  if (!(stickyImage instanceof HTMLElement)) {
    return;
  }
  if (!(pageHeader instanceof HTMLElement)) {
    stickyImage.style.removeProperty("top");
    return;
  }

  const updateOffset = () => {
    const headerOuterHeight = Math.max(
      0,
      Math.ceil(pageHeader.getBoundingClientRect().height),
    );
    stickyImage.style.setProperty(
      "top",
      `${headerOuterHeight + 10}px`,
      "important",
    );
  };

  updateOffset();
  const HeaderResizeObserver = globalThis.ResizeObserver;
  if (typeof HeaderResizeObserver === "function") {
    const resizeObserver = new HeaderResizeObserver(updateOffset);
    resizeObserver.observe(pageHeader, { box: "border-box" });
    globalThis.addEventListener("pagehide", () => resizeObserver.disconnect(), {
      once: true,
    });
    return;
  }

  globalThis.addEventListener("resize", updateOffset);
  globalThis.addEventListener(
    "pagehide",
    () => globalThis.removeEventListener("resize", updateOffset),
    { once: true },
  );
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
    .forEach((field) =>
      renderActivateButton(root, field, getFieldValue(field)),
    );
  const normalizedRichTextBaselines = new WeakSet();
  let bulkEditing = false;

  const finishBulkEditingWhenClosed = () => {
    const hasOpenTextField = fields.some(
      (field) =>
        !(field instanceof HTMLSelectElement) &&
        !getFieldEditElement(field).classList.contains("d-none"),
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
        const initialValue = richTextInitialValues.get(field);
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
          setFieldValue(field, "");
          clearValidationErrors([field]);
          toggleEditField(root, fieldId, true);
        }
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
        finishBulkEditingWhenClosed();
      }
      return;
    }

    if (button.matches("[data-ie-cancel-all]")) {
      event.preventDefault();
      resetFields(fields);
      closeAllFields(root, fields);
      bulkEditing = false;
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
  if (
    !(form instanceof HTMLFormElement) ||
    !(checkbox instanceof HTMLInputElement)
  ) {
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

const getImagePreview = (root, selector) => {
  const preview = root.querySelector(selector);
  return preview instanceof HTMLElement ? preview : null;
};

const setImagePreviewUrl = (preview, url, alt = "", title = "") => {
  const image = preview.querySelector("img");
  if (!(image instanceof HTMLImageElement)) {
    return;
  }

  preview
    .querySelectorAll("source")
    .forEach((source) => source.removeAttribute("srcset"));
  image.removeAttribute("srcset");
  image.src = url;
  image.alt = alt;
  image.title = title;
};

const setImageState = (root, hasImage) => {
  root.dataset.hasImage = hasImage ? "1" : "0";
  root
    .querySelector("[data-ie-delete-image]")
    ?.classList.toggle("d-none", !hasImage);

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
  const pagePreview = getImagePreview(root, "[data-ie-image-preview]");
  const modalPreview = getImagePreview(root, "[data-ie-image-modal-preview]");
  const Modal = globalThis.bootstrap?.Modal;
  if (
    !(form instanceof HTMLFormElement) ||
    !(modal instanceof HTMLElement) ||
    !(openButton instanceof HTMLButtonElement) ||
    !(fileInput instanceof HTMLInputElement) ||
    !(uploadButton instanceof HTMLButtonElement) ||
    !(pagePreview instanceof HTMLElement) ||
    !(modalPreview instanceof HTMLElement) ||
    !Modal
  ) {
    return;
  }

  const modalInstance = Modal.getOrCreateInstance(modal);
  let requestPending = false;
  let selectedPreviewUrl = null;
  let persistedPreviewUrl = null;

  const releaseSelectedPreviewUrl = () => {
    if (selectedPreviewUrl) {
      URL.revokeObjectURL(selectedPreviewUrl);
      selectedPreviewUrl = null;
    }
  };

  const releasePersistedPreviewUrl = () => {
    if (persistedPreviewUrl) {
      URL.revokeObjectURL(persistedPreviewUrl);
      persistedPreviewUrl = null;
    }
  };

  const copyPagePreviewToModal = () => {
    const image = pagePreview.querySelector("img");
    if (image instanceof HTMLImageElement) {
      setImagePreviewUrl(modalPreview, image.src, image.alt, image.title);
    }
  };

  const previewSelectedFile = (file) => {
    releaseSelectedPreviewUrl();
    selectedPreviewUrl = URL.createObjectURL(file);
    setImagePreviewUrl(modalPreview, selectedPreviewUrl, file.name, file.name);
  };

  const commitSelectedPreview = (file) => {
    if (!selectedPreviewUrl) {
      return;
    }
    releasePersistedPreviewUrl();
    getImagePreviews(root).forEach((preview) => {
      setImagePreviewUrl(preview, selectedPreviewUrl, file.name, file.name);
    });
    persistedPreviewUrl = selectedPreviewUrl;
    selectedPreviewUrl = null;
  };

  const clearImageError = () => {
    fileInput.classList.remove("is-invalid");
    const feedback = form.querySelector("[data-ie-image-error]");
    if (feedback) {
      feedback.textContent = "";
      feedback.classList.add("d-none");
    }
  };

  const showImageError = (message) => {
    fileInput.classList.add("is-invalid");
    const feedback = form.querySelector("[data-ie-image-error]");
    if (feedback) {
      feedback.textContent = message;
      feedback.classList.remove("d-none");
    }
  };

  const updateActionAvailability = () => {
    const hasSelectedFile = fileInput.files?.length === 1;
    fileInput.disabled = requestPending;
    uploadButton.disabled = requestPending || !hasSelectedFile;
    if (deleteButton instanceof HTMLButtonElement) {
      deleteButton.disabled = requestPending || root.dataset.hasImage !== "1";
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
    copyPagePreviewToModal();
    updateActionAvailability();
  });
  modal.addEventListener("hide.bs.modal", (event) => {
    if (requestPending) {
      event.preventDefault();
    }
  });
  modal.addEventListener("hidden.bs.modal", () => {
    form.reset();
    releaseSelectedPreviewUrl();
    copyPagePreviewToModal();
    clearImageError();
    updateActionAvailability();
    openButton.focus();
  });

  fileInput.addEventListener("change", () => {
    clearImageError();
    const file = fileInput.files?.[0];
    if (file instanceof File) {
      previewSelectedFile(file);
    } else {
      releaseSelectedPreviewUrl();
      copyPagePreviewToModal();
    }
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
      showImageError(root.dataset.messageValidation ?? "");
      return;
    }

    const file = fileInput?.files?.[0];
    if (!(file instanceof File)) {
      showImageError(root.dataset.messageValidation ?? "");
      return;
    }

    // Build the multipart body before `setRequestPending()` disables the file
    // input. Disabled form controls are deliberately omitted by FormData.
    const formData = new FormData(form);
    clearImageError();
    setRequestPending(true, uploadButton);
    let uploadSucceeded = false;

    try {
      const result = await requestJson(form.action, {
        method: "POST",
        body: formData,
      });
      if (result.hasImage !== true) {
        const error = new Error("The upload returned no profile image.");
        error.result = {
          message: root.dataset.messageImageUploadMissing ?? "",
        };
        throw error;
      }
      commitSelectedPreview(file);
      setImageState(root, true);
      uploadSucceeded = true;
      showStatus(root, "success", root.dataset.messageImageUploaded ?? null);
    } catch (error) {
      const result = error instanceof Error ? error.result : null;
      const message =
        result?.error === "image_upload_missing"
          ? (root.dataset.messageImageUploadMissing ?? "")
          : (result?.message ?? root.dataset.messageErrorMessage ?? "");
      showImageError(message);
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
        releaseSelectedPreviewUrl();
        releasePersistedPreviewUrl();
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
      showImageError(result?.message ?? root.dataset.messageErrorMessage ?? "");
    } finally {
      setRequestPending(false);
      if (deletionSucceeded) {
        modalInstance.hide();
      }
    }
  });

  globalThis.addEventListener("pagehide", () => {
    releaseSelectedPreviewUrl();
    releasePersistedPreviewUrl();
  });
};

document.querySelectorAll(rootSelector).forEach((root) => {
  if (!(root instanceof HTMLElement)) {
    return;
  }
  initializeStickyImageOffset(root);
  initializeFieldEditing(root);
  initializeSkipSync(root);
  initializeImageEditing(root);
});
