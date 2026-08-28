import { getProfileUid, requestJson, showStatus } from "./common.js";
import {
  ensureRichTextEditor,
  getPlainText,
  getRichTextEditorValue,
  isAllowedRichTextLink,
  parseRichTextPreview,
} from "./rich-text.js";

const sectionSelector = "[data-ie-document-section]";
const itemSelector = "[data-ie-document-item]";
const itemsSelector = "[data-ie-document-items]";
const itemTemplateSelector = "template[data-ie-document-item-template]";
const emptyStateSelector = "[data-ie-document-empty-state]";
const modalSelector = "[data-ie-document-modal]";
const initializedRoots = new WeakSet();
const modalStates = new WeakMap();
const dragStates = new WeakMap();

const getDocumentModalParts = (root) => {
  const modal = root.querySelector(modalSelector);
  if (!(modal instanceof HTMLElement)) {
    return null;
  }
  const form = modal.querySelector("[data-ie-document-form]");
  const title = modal.querySelector("[data-ie-document-modal-title]");
  const fields = modal.querySelector("[data-ie-document-fields]");
  const confirmation = modal.querySelector("[data-ie-document-delete-confirmation]");
  const error = modal.querySelector("[data-ie-document-error]");
  const submit = modal.querySelector("[data-ie-document-submit]");
  const submitLabel = modal.querySelector("[data-ie-document-submit-label]");
  const spinner = modal.querySelector("[data-ie-document-spinner]");
  if (
    !(form instanceof HTMLFormElement) ||
    !(title instanceof HTMLElement) ||
    !(fields instanceof HTMLElement) ||
    !(confirmation instanceof HTMLElement) ||
    !(error instanceof HTMLElement) ||
    !(submit instanceof HTMLButtonElement) ||
    !(submitLabel instanceof HTMLElement) ||
    !(spinner instanceof HTMLElement)
  ) {
    return null;
  }
  return { modal, form, title, fields, confirmation, error, submit, submitLabel, spinner };
};

const getSectionHeading = (section) => section.querySelector("h2")?.textContent?.trim() ?? "";

const getDocumentModalSubject = (section, fields) => {
  const titleField = fields.find((field) => field?.name === "title");
  const title = [titleField?.displayValue, titleField?.value]
    .map((value) => String(value ?? "").trim())
    .find((value) => value !== "");
  return title ?? getSectionHeading(section);
};

const getModeLabel = (root, mode) => {
  const labels = {
    add: root.dataset.labelDocumentAdd,
    view: root.dataset.labelDocumentView,
    edit: root.dataset.labelDocumentEdit,
    delete: root.dataset.labelDocumentDelete,
  };
  return labels[mode] ?? "";
};

const setModalError = (parts, message = "") => {
  parts.error.textContent = message;
  parts.error.classList.toggle("d-none", message === "");
};

const setModalPending = (parts, pending) => {
  if (pending) {
    parts.modal.setAttribute("aria-busy", "true");
  } else {
    parts.modal.removeAttribute("aria-busy");
  }
  parts.modal.querySelectorAll("button, input, select, textarea").forEach((control) => {
    if (
      !(control instanceof HTMLButtonElement) &&
      !(control instanceof HTMLInputElement) &&
      !(control instanceof HTMLSelectElement) &&
      !(control instanceof HTMLTextAreaElement)
    ) {
      return;
    }
    if (pending) {
      if (control.dataset.ieDocumentWasDisabled === undefined) {
        control.dataset.ieDocumentWasDisabled = control.disabled ? "1" : "0";
      }
      control.disabled = true;
    } else if (control.dataset.ieDocumentWasDisabled !== undefined) {
      control.disabled = control.dataset.ieDocumentWasDisabled === "1";
      delete control.dataset.ieDocumentWasDisabled;
    }
  });
  parts.spinner.classList.toggle("d-none", !pending);
};

const appendRichText = (container, value) => {
  const parsedDocument = parseRichTextPreview(value);
  const fragment = document.createDocumentFragment();
  Array.from(parsedDocument.body.childNodes).forEach((node) => {
    fragment.append(document.importNode(node, true));
  });
  container.replaceChildren(fragment);
};

const renderViewFields = (root, parts, fields) => {
  const list = document.createElement("dl");
  list.className = "row mb-0";
  fields.forEach((field) => {
    const term = document.createElement("dt");
    term.className = "col-sm-4";
    term.textContent = field.label ?? field.name;
    const description = document.createElement("dd");
    description.className = "col-sm-8";
    const displayValue = String(field.displayValue ?? "");
    if (field.richText && getPlainText(displayValue) !== "") {
      appendRichText(description, displayValue);
    } else {
      description.textContent = displayValue || root.dataset.labelDocumentEmpty || "—";
    }
    list.append(term, description);
  });
  parts.fields.replaceChildren(list);
};

const createFieldControl = (field, fieldId) => {
  let control;
  if (field.type === "select") {
    control = document.createElement("select");
    control.className = "form-select";
    const emptyOption = document.createElement("option");
    emptyOption.value = "";
    emptyOption.textContent = "—";
    control.append(emptyOption);
    (field.options ?? []).forEach((optionData) => {
      const option = document.createElement("option");
      option.value = String(optionData.value);
      option.textContent = optionData.label;
      control.append(option);
    });
    control.value = field.value === null ? "" : String(field.value ?? "");
  } else if (field.type === "textarea") {
    control = document.createElement("textarea");
    control.className = "form-control";
    control.rows = 6;
    control.value = String(field.value ?? "");
    if (field.richText) {
      control.dataset.ieRichText = "";
      const characterLimit = Number.parseInt(String(field.characterLimit ?? ""), 10);
      if (Number.isSafeInteger(characterLimit) && characterLimit > 0) {
        control.dataset.ieCharacterLimit = String(characterLimit);
      }
    }
  } else {
    control = document.createElement("input");
    control.className = field.type === "checkbox" ? "form-check-input" : "form-control";
    control.type = field.type === "checkbox" ? "checkbox" : field.type || "text";
    if (field.type === "checkbox") {
      control.checked = field.value === true;
    } else {
      control.value = field.value === null ? "" : String(field.value ?? "");
    }
  }
  control.id = fieldId;
  control.name = field.name;
  control.required = field.required === true;
  control.readOnly = field.readOnly === true;
  control.disabled = field.disabled === true;
  control.dataset.ieDocumentField = "";
  return control;
};

const renderEditFields = async (root, parts, fields, fieldIdPrefix) => {
  const fragment = document.createDocumentFragment();
  const richTextControls = [];
  fields.forEach((field, index) => {
    const wrapper = document.createElement("div");
    const checkbox = field.type === "checkbox";
    const compactCheckbox = checkbox && field.compactCheckbox === true;
    const fullWidth = checkbox || field.type === "textarea";
    const columnClass = String(field.columnClass ?? "").trim();
    wrapper.className = compactCheckbox
      ? `${columnClass || "col-12"} d-flex`
      : checkbox
        ? `${columnClass || "col-12"} form-check`
        : columnClass || (fullWidth ? "col-12" : "col-12 col-md-6");
    if (compactCheckbox) {
      wrapper.dataset.ieDocumentCompactCheckbox = "";
    }
    const fieldId = `${fieldIdPrefix}-${index}-${field.name}`;
    const control = createFieldControl(field, fieldId);
    const label = document.createElement("label");
    label.htmlFor = fieldId;
    label.className = checkbox ? "form-check-label" : "form-label";
    label.textContent = field.label ?? field.name;
    if (field.required === true) {
      const requiredMarker = document.createElement("span");
      requiredMarker.className = "text-danger ms-1";
      requiredMarker.setAttribute("aria-hidden", "true");
      requiredMarker.textContent = "*";
      label.append(requiredMarker);
    }
    const feedback = document.createElement("div");
    feedback.className = "invalid-feedback";
    feedback.dataset.ieDocumentFieldError = field.name;
    const characterLimit = Number.parseInt(String(field.characterLimit ?? ""), 10);
    const characterCounter = document.createElement("div");
    if (field.richText && Number.isSafeInteger(characterLimit) && characterLimit > 0) {
      characterCounter.id = `${fieldId}-character-counter`;
      characterCounter.className = "form-text text-end";
      characterCounter.setAttribute("aria-live", "polite");
      characterCounter.dataset.ieCharacterCounter = "";
      characterCounter.dataset.ieFor = fieldId;
      characterCounter.textContent = `0 / ${characterLimit}`;
    }
    if (compactCheckbox) {
      const formCheck = document.createElement("div");
      formCheck.className = "form-check mt-auto";
      formCheck.append(control, label, feedback);
      wrapper.append(formCheck);
    } else if (checkbox) {
      wrapper.append(control, label, feedback);
    } else {
      wrapper.append(label, control);
      if (characterCounter.dataset.ieCharacterCounter !== undefined) {
        wrapper.append(characterCounter);
      }
      wrapper.append(feedback);
    }
    fragment.append(wrapper);
    if (field.richText && control instanceof HTMLTextAreaElement) {
      richTextControls.push(control);
    }
  });
  parts.fields.replaceChildren(fragment);
  await Promise.all(richTextControls.map((control) => ensureRichTextEditor(root, control)));
};

const renderDocumentModal = async (root, parts, section, mode, fields) => {
  const label = getModeLabel(root, mode);
  const subject = getDocumentModalSubject(section, fields);
  parts.title.textContent = [label, subject].filter(Boolean).join(": ");
  parts.confirmation.classList.toggle("d-none", mode !== "delete");
  parts.confirmation.textContent = mode === "delete" ? (root.dataset.messageDocumentDeleteConfirm ?? "") : "";
  parts.fields.classList.toggle("d-none", mode === "delete");
  parts.submit.classList.toggle("d-none", mode === "view");
  parts.submit.classList.toggle("btn-danger", mode === "delete");
  parts.submit.classList.toggle("btn-primary", mode !== "delete");
  parts.submit.classList.remove("btn-success");
  parts.submitLabel.textContent = mode === "delete" ? (root.dataset.labelDocumentDelete ?? "") : (root.dataset.labelDocumentSave ?? "");
  if (mode === "view") {
    renderViewFields(root, parts, fields);
  } else if (mode === "delete") {
    parts.fields.replaceChildren();
  } else {
    const idPrefix = `ie-document-${root.dataset.profileUid ?? "profile"}-${section.dataset.sectionKey ?? "section"}`.replace(/[^a-zA-Z0-9_-]/g, "-");
    await renderEditFields(root, parts, fields, idPrefix);
  }
};

const requestDocument = (root, url, data) => {
  const profile = getProfileUid(root);
  if (!url || profile === null) {
    return Promise.reject(new Error("The document endpoint is unavailable."));
  }
  return requestJson(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ profile, data }),
  });
};

const openDocumentModal = async (root, section, mode, record, trigger) => {
  const parts = getDocumentModalParts(root);
  const Modal = globalThis.bootstrap?.Modal;
  if (!parts || typeof Modal?.getOrCreateInstance !== "function") {
    showStatus(root, "danger");
    return;
  }
  setModalError(parts);
  setModalPending(parts, true);
  try {
    const response = await requestDocument(root, root.dataset.documentFormUrl, {
      section: section.dataset.sectionKey,
      record: record ?? 0,
      mode,
    });
    const fields = Array.isArray(response.fields) ? response.fields : [];
    modalStates.set(parts.modal, { root, section, mode, record: response.record ?? null, fields, trigger });
    await renderDocumentModal(root, parts, section, mode, fields);
    Modal.getOrCreateInstance(parts.modal).show();
  } catch (error) {
    showStatus(root, "danger", error.result?.message ?? null);
  } finally {
    setModalPending(parts, false);
  }
};

const getRowDisplayValue = (item, name) => {
  const display = item.display ?? {};
  if (name === "yearStart" && !display.yearStart) {
    return display.year ?? "";
  }
  if (name === "year" && !display.year) {
    return display.yearStart ?? "";
  }
  return display[name] ?? "";
};

const renderDocumentTitle = (container, title, link) => {
  const normalizedTitle = title || "—";
  if (link && isAllowedRichTextLink(String(link))) {
    const anchor = document.createElement("a");
    anchor.href = String(link);
    anchor.target = "_blank";
    anchor.rel = "noopener noreferrer";
    anchor.textContent = normalizedTitle;
    container.replaceChildren(anchor);
    return;
  }
  const span = document.createElement("span");
  span.textContent = normalizedTitle;
  container.replaceChildren(span);
};

export const updateDocumentRow = (section, row, item) => {
  if (!(section instanceof HTMLElement) || !(row instanceof HTMLElement)) {
    return;
  }
  row.dataset.itemUid = String(item.uid ?? "");
  row.dataset.itemSorting = String(item.sorting ?? "");
  row.querySelectorAll("[data-ie-document-value]").forEach((element) => {
    if (!(element instanceof HTMLElement)) {
      return;
    }
    const name = element.dataset.ieDocumentValue;
    const value = getRowDisplayValue(item, name);
    if (name === "bodytext") {
      const normalizedValue = String(value ?? "");
      element.classList.toggle("d-none", getPlainText(normalizedValue) === "");
      if (normalizedValue === "") {
        element.replaceChildren();
      } else {
        appendRichText(element, normalizedValue);
      }
      return;
    }
    element.textContent = String(value || "—");
  });
  const title = row.querySelector("[data-ie-document-title]");
  if (title instanceof HTMLElement) {
    renderDocumentTitle(title, String(item.display?.title ?? ""), item.values?.link ?? "");
  }
};

export const refreshDocumentRows = (section) => {
  if (!(section instanceof HTMLElement)) {
    return;
  }
  const rows = Array.from(section.querySelectorAll(itemSelector)).filter((row) => row instanceof HTMLElement);
  const sortable = section.dataset.sectionSortable === "1" && rows.length > 1;
  rows.forEach((row, index) => {
    row.dataset.itemPosition = String(index);
    row.classList.toggle("bg-body-tertiary", index % 2 === 0);
    const up = row.querySelector('[data-ie-document-sort="up"]');
    const down = row.querySelector('[data-ie-document-sort="down"]');
    const drag = row.querySelector("[data-ie-document-drag]");
    if (up instanceof HTMLButtonElement) {
      up.disabled = index === 0;
      up.setAttribute("aria-disabled", String(up.disabled));
    }
    if (down instanceof HTMLButtonElement) {
      down.disabled = index === rows.length - 1;
      down.setAttribute("aria-disabled", String(down.disabled));
    }
    if (drag instanceof HTMLButtonElement) {
      drag.disabled = !sortable;
      drag.draggable = sortable;
      drag.setAttribute("aria-disabled", String(drag.disabled));
    }
  });
  const emptyState = section.querySelector(emptyStateSelector);
  if (emptyState instanceof HTMLElement) {
    emptyState.classList.toggle("d-none", rows.length > 0);
  }
};

const insertDocumentRow = (section, item) => {
  const template = section.querySelector(itemTemplateSelector);
  const items = section.querySelector(itemsSelector);
  const templateRow = template instanceof HTMLTemplateElement ? template.content.querySelector(itemSelector) : null;
  if (!(items instanceof HTMLElement) || !(templateRow instanceof HTMLElement)) {
    return null;
  }
  const row = templateRow.cloneNode(true);
  if (!(row instanceof HTMLElement)) {
    return null;
  }
  updateDocumentRow(section, row, item);
  items.append(row);
  refreshDocumentRows(section);
  return row;
};

const applyValidationErrors = (parts, errors) => {
  parts.form.querySelectorAll(".is-invalid").forEach((field) => {
    field.classList.remove("is-invalid");
  });
  if (!errors || typeof errors !== "object") {
    return;
  }
  Object.entries(errors).forEach(([name, messages]) => {
    const field = parts.form.elements.namedItem(name);
    const feedback = parts.form.querySelector(`[data-ie-document-field-error="${CSS.escape(name)}"]`);
    if (
      field instanceof HTMLInputElement ||
      field instanceof HTMLSelectElement ||
      field instanceof HTMLTextAreaElement
    ) {
      field.classList.add("is-invalid");
    }
    if (feedback instanceof HTMLElement) {
      feedback.textContent = Array.isArray(messages) ? messages.join(" ") : "";
    }
  });
};

const collectDocumentFields = (parts, fields) => {
  const values = {};
  fields.forEach((field) => {
    if (field.readOnly || field.disabled) {
      return;
    }
    const control = parts.form.elements.namedItem(field.name);
    if (control instanceof HTMLInputElement && field.type === "checkbox") {
      values[field.name] = control.checked;
    } else if (
      control instanceof HTMLInputElement ||
      control instanceof HTMLSelectElement ||
      control instanceof HTMLTextAreaElement
    ) {
      values[field.name] = control instanceof HTMLTextAreaElement && field.richText
        ? (getRichTextEditorValue(control) ?? control.value)
        : control.value;
    }
  });
  return values;
};

const submitDocumentModal = async (parts) => {
  const state = modalStates.get(parts.modal);
  if (!state) {
    return;
  }
  const { root, section, mode, record, fields } = state;
  if (mode === "view") {
    return;
  }
  if (mode !== "delete" && !parts.form.reportValidity()) {
    return;
  }
  const endpoint = mode === "add"
    ? root.dataset.createDocumentUrl
    : mode === "edit"
      ? root.dataset.updateDocumentUrl
      : root.dataset.deleteDocumentUrl;
  const data = { section: section.dataset.sectionKey };
  if (mode !== "add") {
    data.record = record;
  }
  if (mode !== "delete") {
    data.fields = collectDocumentFields(parts, fields);
  }
  setModalError(parts);
  setModalPending(parts, true);
  showStatus(root, "info", root.dataset.messageSaving ?? null);
  try {
    const response = await requestDocument(root, endpoint, data);
    if (mode === "add") {
      insertDocumentRow(section, response.item);
    } else if (mode === "edit") {
      const row = section.querySelector(`${itemSelector}[data-item-uid="${CSS.escape(String(record))}"]`);
      if (row instanceof HTMLElement) {
        updateDocumentRow(section, row, response.item);
      }
      refreshDocumentRows(section);
    } else {
      const row = section.querySelector(`${itemSelector}[data-item-uid="${CSS.escape(String(record))}"]`);
      row?.remove();
      refreshDocumentRows(section);
    }
    setModalPending(parts, false);
    globalThis.bootstrap.Modal.getOrCreateInstance(parts.modal).hide();
    showStatus(
      root,
      "success",
      mode === "delete" ? (root.dataset.messageDocumentDeleted ?? null) : (root.dataset.messageDocumentSaved ?? null),
    );
  } catch (error) {
    applyValidationErrors(parts, error.result?.errors);
    setModalError(parts, error.result?.message ?? root.dataset.messageErrorMessage ?? "");
  } finally {
    setModalPending(parts, false);
  }
};

const setSectionPending = (section, pending) => {
  if (pending) {
    section.setAttribute("aria-busy", "true");
  } else {
    section.removeAttribute("aria-busy");
  }
  section.querySelectorAll("button").forEach((button) => {
    if (!(button instanceof HTMLButtonElement)) {
      return;
    }
    if (pending) {
      if (button.dataset.ieDocumentWasDisabled === undefined) {
        button.dataset.ieDocumentWasDisabled = button.disabled ? "1" : "0";
      }
      button.disabled = true;
    } else if (button.dataset.ieDocumentWasDisabled !== undefined) {
      button.disabled = button.dataset.ieDocumentWasDisabled === "1";
      delete button.dataset.ieDocumentWasDisabled;
    }
  });
};

export const getDocumentOrder = (section) => Array.from(section.querySelectorAll(itemSelector), (row) => Number.parseInt(row.dataset.itemUid ?? "", 10)).filter((uid) => Number.isInteger(uid) && uid > 0);

export const applyDocumentOrder = (section, order) => {
  const items = section.querySelector(itemsSelector);
  if (!(items instanceof HTMLElement) || !Array.isArray(order)) {
    return;
  }
  const rowsByUid = new Map(Array.from(section.querySelectorAll(itemSelector)).map((row) => [row.dataset.itemUid, row]));
  order.forEach((uid) => {
    const row = rowsByUid.get(String(uid));
    if (row instanceof HTMLElement) {
      items.append(row);
    }
  });
  refreshDocumentRows(section);
};

const sortDocument = async (root, section, row, direction) => {
  const record = Number.parseInt(row.dataset.itemUid ?? "", 10);
  if (!Number.isInteger(record) || record <= 0 || !["up", "down"].includes(direction)) {
    return;
  }
  setSectionPending(section, true);
  try {
    const response = await requestDocument(root, root.dataset.sortDocumentUrl, {
      section: section.dataset.sectionKey,
      record,
      direction,
    });
    applyDocumentOrder(section, response.order);
    showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
  } catch (error) {
    showStatus(root, "danger", error.result?.message ?? null);
  } finally {
    setSectionPending(section, false);
    refreshDocumentRows(section);
  }
};

const persistDocumentOrder = async (root, section, order, previousOrder) => {
  setSectionPending(section, true);
  try {
    const response = await requestDocument(root, root.dataset.sortDocumentUrl, {
      section: section.dataset.sectionKey,
      order,
    });
    applyDocumentOrder(section, response.order);
    showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
  } catch (error) {
    applyDocumentOrder(section, previousOrder);
    showStatus(root, "danger", error.result?.message ?? null);
  } finally {
    setSectionPending(section, false);
    refreshDocumentRows(section);
  }
};

const clearDocumentDropPosition = (state) => {
  state.section
    .querySelectorAll(`${itemSelector}.is-drop-before, ${itemSelector}.is-drop-after`)
    .forEach((row) => {
      row.classList.remove("is-drop-before", "is-drop-after");
    });
  state.items.classList.remove("is-drop-at-end");
  state.dropRow = null;
  state.dropPosition = null;
};

const getDocumentDropPosition = (state, targetRow, clientY) => {
  const bounds = targetRow.getBoundingClientRect();
  if (Number.isFinite(clientY) && bounds.height > 0) {
    return clientY < bounds.top + bounds.height / 2 ? "before" : "after";
  }
  const rows = Array.from(state.items.querySelectorAll(itemSelector));
  return rows.indexOf(state.row) < rows.indexOf(targetRow) ? "after" : "before";
};

const updateDocumentDropPosition = (state, target, clientY) => {
  clearDocumentDropPosition(state);
  const targetRow = target.closest(itemSelector);
  if (targetRow === state.row) {
    return;
  }
  if (
    targetRow instanceof HTMLElement &&
    targetRow.closest(itemsSelector) === state.items
  ) {
    const position = getDocumentDropPosition(state, targetRow, clientY);
    targetRow.classList.add(position === "before" ? "is-drop-before" : "is-drop-after");
    state.dropRow = targetRow;
    state.dropPosition = position;
    return;
  }
  if (target.closest(itemsSelector) === state.items) {
    state.items.classList.add("is-drop-at-end");
    state.dropPosition = "end";
  }
};

const clearDragState = (root) => {
  const state = dragStates.get(root);
  if (!state) {
    return;
  }
  clearDocumentDropPosition(state);
  state.items.classList.remove("is-drag-active");
  state.row.classList.remove("is-dragging");
  state.handle.removeAttribute("aria-grabbed");
  dragStates.delete(root);
};

const initializeDocumentDragAndDrop = (root) => {
  root.addEventListener("dragstart", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const handle = target?.closest("[data-ie-document-drag]");
    const row = handle?.closest(itemSelector);
    const section = row?.closest(sectionSelector);
    const items = row?.closest(itemsSelector);
    if (!(handle instanceof HTMLButtonElement)) {
      return;
    }
    if (
      handle.disabled ||
      !(row instanceof HTMLElement) ||
      !(section instanceof HTMLElement) ||
      !(items instanceof HTMLElement) ||
      section.dataset.sectionSortable !== "1"
    ) {
      event.preventDefault();
      return;
    }
    dragStates.set(root, {
      section,
      items,
      row,
      handle,
      order: getDocumentOrder(section),
      dropRow: null,
      dropPosition: null,
    });
    items.classList.add("is-drag-active");
    row.classList.add("is-dragging");
    handle.setAttribute("aria-grabbed", "true");
    if (event.dataTransfer) {
      event.dataTransfer.effectAllowed = "move";
      event.dataTransfer.setData("text/plain", row.dataset.itemUid ?? "");
      const bounds = row.getBoundingClientRect();
      const offsetX = Number.isFinite(event.clientX)
        ? Math.min(Math.max(event.clientX - bounds.left, 0), bounds.width)
        : 0;
      const offsetY = Number.isFinite(event.clientY)
        ? Math.min(Math.max(event.clientY - bounds.top, 0), bounds.height)
        : 0;
      event.dataTransfer.setDragImage?.(row, offsetX, offsetY);
    }
  });
  root.addEventListener("dragover", (event) => {
    const state = dragStates.get(root);
    const target = event.target instanceof Element ? event.target : null;
    if (!state || !target) {
      return;
    }
    if (target.closest(sectionSelector) !== state.section) {
      clearDocumentDropPosition(state);
      return;
    }
    event.preventDefault();
    updateDocumentDropPosition(state, target, event.clientY);
    if (event.dataTransfer) {
      event.dataTransfer.dropEffect = "move";
    }
  });
  root.addEventListener("drop", (event) => {
    const state = dragStates.get(root);
    const target = event.target instanceof Element ? event.target : null;
    if (!state || target?.closest(sectionSelector) !== state.section) {
      return;
    }
    event.preventDefault();
    updateDocumentDropPosition(state, target, event.clientY);
    if (state.dropRow instanceof HTMLElement && state.dropPosition === "before") {
      state.dropRow.before(state.row);
    } else if (state.dropRow instanceof HTMLElement && state.dropPosition === "after") {
      state.dropRow.after(state.row);
    } else if (state.dropPosition === "end") {
      state.items.append(state.row);
    }
    const previousOrder = state.order;
    const order = getDocumentOrder(state.section);
    const changed = order.length === previousOrder.length && order.some((uid, index) => uid !== previousOrder[index]);
    const section = state.section;
    clearDragState(root);
    refreshDocumentRows(section);
    if (changed) {
      void persistDocumentOrder(root, section, order, previousOrder);
    }
  });
  root.addEventListener("dragend", () => {
    clearDragState(root);
  });
};

export const initializeDocumentSections = (root) => {
  if (!(root instanceof HTMLElement)) {
    return;
  }
  root.querySelectorAll(sectionSelector).forEach(refreshDocumentRows);
  if (initializedRoots.has(root)) {
    return;
  }
  initializedRoots.add(root);
  const parts = getDocumentModalParts(root);
  parts?.form.addEventListener("submit", (event) => {
    event.preventDefault();
    void submitDocumentModal(parts);
  });
  parts?.modal.addEventListener("hidden.bs.modal", () => {
    const trigger = modalStates.get(parts.modal)?.trigger;
    if (trigger instanceof HTMLElement) {
      trigger.focus();
    }
  });
  parts?.modal.addEventListener("hide.bs.modal", (event) => {
    if (parts.modal.getAttribute("aria-busy") === "true") {
      event.preventDefault();
    }
  });
  initializeDocumentDragAndDrop(root);
  root.addEventListener("click", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const button = target?.closest(
      "[data-ie-document-add], [data-ie-document-view], [data-ie-document-edit], " +
        "[data-ie-document-delete], [data-ie-document-sort]",
    );
    if (!(button instanceof HTMLButtonElement) || button.disabled) {
      return;
    }
    const section = button.closest(sectionSelector);
    if (!(section instanceof HTMLElement)) {
      return;
    }
    if (button.matches("[data-ie-document-add]")) {
      void openDocumentModal(root, section, "add", null, button);
      return;
    }
    const row = button.closest(itemSelector);
    const record = Number.parseInt(row?.dataset.itemUid ?? "", 10);
    if (!(row instanceof HTMLElement) || !Number.isInteger(record) || record <= 0) {
      return;
    }
    if (button.matches("[data-ie-document-sort]")) {
      void sortDocument(root, section, row, button.dataset.ieDocumentSort);
      return;
    }
    const mode = button.matches("[data-ie-document-view]")
      ? "view"
      : button.matches("[data-ie-document-edit]")
        ? "edit"
        : "delete";
    void openDocumentModal(root, section, mode, record, button);
  });
};
