/* Generated from Resources/Private/TypeScript — do not edit. */
import { nextTick, reactive } from "@fgtclb/academic-persons-edit/frontend/vue.js";
import {
  getProfileUid,
  hooks,
  initializePopover,
  requestJson,
  showStatus
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  destroyRichTextEditors,
  ensureRichTextEditor,
  getPlainText,
  getRichTextEditorValue,
  isAllowedRichTextLink,
  parseRichTextPreview
} from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";
const sectionSelector = "[data-pe-document-section]";
const itemSelector = "[data-pe-document-item]";
const itemsSelector = "[data-pe-document-items]";
const itemTemplateSelector = "[data-pe-document-item-template]";
const emptyStateSelector = "[data-pe-document-empty-state]";
const listHeaderSelector = "[data-pe-document-list-header]";
const documentViewSelector = "[data-pe-document-view-container]";
const addCollapseTargetSelector = "[data-pe-document-add-collapse-target]";
const itemCollapseTargetSelector = "[data-pe-document-item-collapse-target]";
const controllers = /* @__PURE__ */ new WeakMap();
const initializedRoots = /* @__PURE__ */ new WeakSet();
const dragStates = /* @__PURE__ */ new WeakMap();
let collapseTargetSequence = 0;
const isDocumentMode = (value) => ["add", "view", "edit", "delete"].includes(value);
const getDocumentRows = (section) => {
  const items = section.querySelector(itemsSelector);
  if (items === null) {
    return [];
  }
  return Array.from(items.children).filter(
    (element) => element instanceof HTMLElement && element.matches(itemSelector)
  );
};
const asDocumentField = (value) => {
  if (typeof value !== "object" || value === null) {
    return null;
  }
  const field = value;
  if (typeof field.name !== "string") {
    return null;
  }
  return {
    autocomplete: String(field.autocomplete ?? ""),
    characterLimit: field.characterLimit,
    columnClass: field.columnClass,
    compactCheckbox: field.compactCheckbox,
    disabled: field.disabled === true,
    displayValue: String(field.displayValue ?? ""),
    helptext: String(field.helptext ?? ""),
    label: String(field.label ?? field.name),
    name: field.name,
    options: Array.isArray(field.options) ? field.options : [],
    readOnly: field.readOnly === true,
    required: field.required === true,
    richText: field.richText === true,
    type: String(field.type ?? "text"),
    value: field.value ?? ""
  };
};
const getResponseFields = (value) => Array.isArray(value) ? value.map(asDocumentField).filter((field) => field !== null) : [];
const asRecord = (value) => typeof value === "object" && value !== null ? value : {};
const asContractContactItem = (value) => {
  const item = asRecord(value);
  const uid = Number(item.uid);
  if (!Number.isInteger(uid) || uid <= 0) {
    return null;
  }
  return {
    display: asRecord(item.display),
    hidden: item.hidden === true,
    sorting: Number(item.sorting) || 0,
    summary: Array.isArray(item.summary) ? item.summary.map((entry) => {
      const summary = asRecord(entry);
      return {
        label: String(summary.label ?? ""),
        value: String(summary.value ?? "")
      };
    }) : [],
    uid,
    values: asRecord(item.values)
  };
};
const getResponseContactSections = (value) => Array.isArray(value) ? value.flatMap((entry) => {
  const section = asRecord(entry);
  const identifier = String(section.identifier ?? "");
  if (identifier === "") {
    return [];
  }
  return [{
    identifier,
    items: Array.isArray(section.items) ? section.items.map(asContractContactItem).filter((item) => item !== null) : [],
    label: String(section.label ?? identifier),
    singularLabel: String(section.singularLabel ?? section.label ?? identifier)
  }];
}) : [];
const getSectionHeading = (section) => {
  var _a, _b;
  return ((_b = (_a = section.querySelector("h2")) == null ? void 0 : _a.textContent) == null ? void 0 : _b.trim()) ?? "";
};
const getDocumentSubject = (section, fields) => {
  const titleField = fields.find((field) => field.name === "title");
  const title = [titleField == null ? void 0 : titleField.displayValue, titleField == null ? void 0 : titleField.value].map((value) => String(value ?? "").trim()).find((value) => value !== "");
  return title ?? getSectionHeading(section);
};
const getModeLabel = (root, mode) => {
  const labels = {
    add: root.dataset.labelDocumentAdd,
    view: root.dataset.labelDocumentView,
    edit: root.dataset.labelDocumentEdit,
    delete: root.dataset.labelDocumentDelete
  };
  return labels[mode] ?? "";
};
const getRecordFromButton = (button) => {
  const row = button.closest(itemSelector);
  const record = Number.parseInt((row == null ? void 0 : row.dataset.itemUid) ?? "", 10);
  return Number.isInteger(record) && record > 0 ? record : null;
};
const getDocumentCollapseTarget = (button, section, mode) => {
  var _a;
  return mode === "add" ? section.querySelector(addCollapseTargetSelector) : ((_a = button.closest(itemSelector)) == null ? void 0 : _a.querySelector(itemCollapseTargetSelector)) ?? null;
};
const getDocumentCollapseTargetSelector = (target) => {
  if (target.id === "") {
    collapseTargetSequence += 1;
    target.id = `profile-editing-document-collapse-${collapseTargetSequence}`;
  }
  return `#${CSS.escape(target.id)}`;
};
const requestDocument = (root, url, data) => {
  const profile = getProfileUid(root);
  if (url === void 0 || profile === null) {
    return Promise.reject(new Error("The document endpoint is unavailable."));
  }
  return requestJson(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ profile, data })
  });
};
const appendRichText = (container, value) => {
  const parsedDocument = parseRichTextPreview(value);
  const fragment = document.createDocumentFragment();
  Array.from(parsedDocument.body.childNodes).forEach((node) => {
    fragment.append(document.importNode(node, true));
  });
  container.replaceChildren(fragment);
};
const getRowDisplayValue = (item, name) => {
  const display = item.display ?? {};
  if (name === "dateStart" && !display.dateStart) {
    return display.date ?? "";
  }
  if (name === "date" && !display.date) {
    return display.dateStart ?? "";
  }
  return display[name] ?? "";
};
const renderDocumentTitle = (container, title, link) => {
  const normalizedTitle = title || "\u2014";
  if (typeof link === "string" && isAllowedRichTextLink(link)) {
    const anchor = document.createElement("a");
    anchor.href = link;
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
const updateDocumentRow = (row, item) => {
  var _a, _b;
  row.dataset.itemUid = String(item.uid ?? "");
  row.dataset.itemSorting = String(item.sorting ?? "");
  row.querySelectorAll("[data-pe-document-value]").forEach(
    (element) => {
      const name = hooks(element).peDocumentValue ?? "";
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
      element.textContent = String(value || "\u2014");
    }
  );
  const title = row.querySelector("[data-pe-document-title]");
  if (title !== null) {
    renderDocumentTitle(
      title,
      String(((_a = item.display) == null ? void 0 : _a.title) ?? ""),
      (_b = item.values) == null ? void 0 : _b.link
    );
  }
};
const refreshDocumentRows = (section) => {
  const rows = getDocumentRows(section);
  const sortable = section.dataset.sectionSortable === "1" && rows.length > 1;
  rows.forEach((row, index) => {
    row.dataset.itemPosition = String(index);
    row.classList.toggle("bg-body-tertiary", index % 2 === 0);
    const up = row.querySelector(
      '[data-pe-document-sort="up"]'
    );
    const down = row.querySelector(
      '[data-pe-document-sort="down"]'
    );
    const drag = row.querySelector("[data-pe-document-drag]");
    if (up !== null) {
      up.disabled = index === 0;
      up.setAttribute("aria-disabled", String(up.disabled));
    }
    if (down !== null) {
      down.disabled = index === rows.length - 1;
      down.setAttribute("aria-disabled", String(down.disabled));
    }
    if (drag !== null) {
      drag.disabled = !sortable;
      drag.draggable = sortable;
      drag.setAttribute("aria-disabled", String(drag.disabled));
    }
  });
  const emptyState = section.querySelector(emptyStateSelector);
  emptyState == null ? void 0 : emptyState.classList.toggle("d-none", rows.length > 0);
  const listHeader = section.querySelector(listHeaderSelector);
  listHeader == null ? void 0 : listHeader.classList.toggle("d-md-flex", rows.length > 0);
};
const insertDocumentRow = (section, item) => {
  const template = section.querySelector(itemTemplateSelector);
  const items = section.querySelector(itemsSelector);
  const templateRow = template == null ? void 0 : template.querySelector(itemSelector);
  if (items === null || templateRow === null || templateRow === void 0) {
    return null;
  }
  const row = templateRow.cloneNode(true);
  if (!(row instanceof HTMLElement)) {
    return null;
  }
  updateDocumentRow(row, item);
  items.append(row);
  refreshDocumentRows(section);
  return row;
};
const getDocumentOrder = (section) => Array.from(
  getDocumentRows(section),
  (row) => Number.parseInt(row.dataset.itemUid ?? "", 10)
).filter((uid) => Number.isInteger(uid) && uid > 0);
const applyDocumentOrder = (section, order) => {
  const items = section.querySelector(itemsSelector);
  if (items === null) {
    return;
  }
  const rowsByUid = new Map(
    getDocumentRows(section).map(
      (row) => [row.dataset.itemUid, row]
    )
  );
  order.forEach((uid) => {
    const row = rowsByUid.get(String(uid));
    if (row !== void 0) {
      items.append(row);
    }
  });
  refreshDocumentRows(section);
};
const setSectionPending = (section, pending) => {
  section.setAttribute("aria-busy", String(pending));
  section.style.cursor = pending ? "wait" : "";
  section.querySelectorAll("button").forEach((button) => {
    if (pending) {
      if (hooks(button).peDocumentWasDisabled === void 0) {
        hooks(button).peDocumentWasDisabled = button.disabled ? "1" : "0";
      }
      button.disabled = true;
    } else if (hooks(button).peDocumentWasDisabled !== void 0) {
      button.disabled = hooks(button).peDocumentWasDisabled === "1";
      delete hooks(button).peDocumentWasDisabled;
    }
  });
};
const persistDocumentOrder = async (root, section, order, previousOrder) => {
  var _a;
  setSectionPending(section, true);
  try {
    const response = await requestDocument(root, root.dataset.sortDocumentUrl, {
      section: section.dataset.sectionKey,
      order
    });
    applyDocumentOrder(section, Array.isArray(response.order) ? response.order.map(Number) : order);
    showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
  } catch (error) {
    applyDocumentOrder(section, previousOrder);
    showStatus(root, "danger", ((_a = error.result) == null ? void 0 : _a.message) ?? null);
  } finally {
    setSectionPending(section, false);
    refreshDocumentRows(section);
  }
};
const clearDocumentDropPosition = (state) => {
  state.section.querySelectorAll(`${itemSelector}.is-drop-before, ${itemSelector}.is-drop-after`).forEach((row) => row.classList.remove("is-drop-before", "is-drop-after"));
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
  if (targetRow !== null && targetRow.closest(itemsSelector) === state.items) {
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
  if (state === void 0) {
    return;
  }
  clearDocumentDropPosition(state);
  state.items.classList.remove("is-drag-active");
  state.row.classList.remove("is-dragging");
  dragStates.delete(root);
};
const initializeDocumentDragAndDrop = (root) => {
  root.addEventListener("dragstart", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const handle = target == null ? void 0 : target.closest("[data-pe-document-drag]");
    const row = handle == null ? void 0 : handle.closest(itemSelector);
    const section = row == null ? void 0 : row.closest(sectionSelector);
    const items = row == null ? void 0 : row.closest(itemsSelector);
    if (handle === null || handle === void 0 || handle.disabled || row === null || row === void 0 || section === null || section === void 0 || items === null || items === void 0 || section.dataset.sectionSortable !== "1") {
      return;
    }
    dragStates.set(root, {
      section,
      items,
      row,
      handle,
      order: getDocumentOrder(section),
      dropRow: null,
      dropPosition: null
    });
    items.classList.add("is-drag-active");
    row.classList.add("is-dragging");
    if (event.dataTransfer !== null) {
      event.dataTransfer.effectAllowed = "move";
      event.dataTransfer.setData("text/plain", row.dataset.itemUid ?? "");
      const bounds = row.getBoundingClientRect();
      const offsetX = Math.min(Math.max(event.clientX - bounds.left, 0), bounds.width);
      const offsetY = Math.min(Math.max(event.clientY - bounds.top, 0), bounds.height);
      event.dataTransfer.setDragImage(row, offsetX, offsetY);
    }
  });
  root.addEventListener("dragover", (event) => {
    const state = dragStates.get(root);
    const target = event.target instanceof Element ? event.target : null;
    if (state === void 0 || target === null) {
      return;
    }
    if (target.closest(sectionSelector) !== state.section) {
      clearDocumentDropPosition(state);
      return;
    }
    event.preventDefault();
    updateDocumentDropPosition(state, target, event.clientY);
    if (event.dataTransfer !== null) {
      event.dataTransfer.dropEffect = "move";
    }
  });
  root.addEventListener("drop", (event) => {
    const state = dragStates.get(root);
    const target = event.target instanceof Element ? event.target : null;
    if (state === void 0 || (target == null ? void 0 : target.closest(sectionSelector)) !== state.section) {
      return;
    }
    event.preventDefault();
    updateDocumentDropPosition(state, target, event.clientY);
    if (state.dropRow !== null && state.dropPosition === "before") {
      state.dropRow.before(state.row);
    } else if (state.dropRow !== null && state.dropPosition === "after") {
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
  root.addEventListener("dragend", () => clearDragState(root));
};
const createDocumentEditing = (root) => {
  const documentState = reactive({
    contactEmptyMessage: root.dataset.messageContractContactEmpty ?? "",
    contactSections: [],
    deleteConfirmation: root.dataset.messageDocumentDeleteConfirm ?? "",
    error: "",
    errors: {},
    fields: [],
    kind: "document",
    mode: "view",
    open: false,
    pending: false,
    record: null,
    section: "",
    target: "",
    title: "",
    values: {}
  });
  const contractContactState = reactive({
    deleteConfirmation: root.dataset.messageContractContactDeleteConfirm ?? "",
    error: "",
    errors: {},
    fields: [],
    mode: "view",
    open: false,
    pending: false,
    record: null,
    section: "",
    title: "",
    values: {}
  });
  let activeSection = null;
  let trigger = null;
  let contractContactTrigger = null;
  let rowPendingRemoval = null;
  let sectionPendingRefresh = null;
  const initializeDocumentEditors = async () => {
    var _a;
    await nextTick();
    const view = root.querySelector(documentViewSelector);
    if (view === null) {
      return;
    }
    initializePopover(view);
    await Promise.all(
      Array.from(
        view.querySelectorAll("textarea[data-pe-rich-text]")
      ).map((field) => ensureRichTextEditor(root, field))
    );
    if (documentState.mode === "add" || documentState.mode === "edit") {
      const firstField = view.querySelector("[data-pe-document-field]:not([disabled])");
      if (firstField instanceof HTMLTextAreaElement && firstField.matches("[data-pe-rich-text]")) {
        const editor = await ensureRichTextEditor(root, firstField);
        editor == null ? void 0 : editor.editing.view.focus();
      } else {
        firstField == null ? void 0 : firstField.focus();
      }
      return;
    }
    (_a = view.querySelector("[data-pe-document-heading]")) == null ? void 0 : _a.focus();
  };
  const collapseDocument = () => {
    contractContactState.open = false;
    documentState.open = false;
    trigger == null ? void 0 : trigger.setAttribute("aria-expanded", "false");
  };
  const openDocument = async (modeValue, event) => {
    var _a;
    if (!isDocumentMode(modeValue) || documentState.pending) {
      return;
    }
    const button = event.currentTarget instanceof HTMLButtonElement ? event.currentTarget : event.target instanceof Element ? event.target.closest("button") : null;
    const section = button == null ? void 0 : button.closest(sectionSelector);
    if (button === null || section === null || section === void 0) {
      return;
    }
    if (documentState.open && trigger === button && documentState.mode === modeValue) {
      collapseDocument();
      return;
    }
    const collapseTarget = getDocumentCollapseTarget(button, section, modeValue);
    if (collapseTarget === null) {
      return;
    }
    const record = modeValue === "add" ? null : getRecordFromButton(button);
    if (modeValue !== "add" && record === null) {
      return;
    }
    documentState.pending = true;
    documentState.error = "";
    documentState.errors = {};
    try {
      const response = await requestDocument(root, root.dataset.documentFormUrl, {
        section: section.dataset.sectionKey,
        record: record ?? 0,
        mode: modeValue
      });
      const fields = getResponseFields(response.fields);
      activeSection = section;
      documentState.fields = fields;
      documentState.contactSections = getResponseContactSections(
        response.contactSections
      );
      documentState.kind = section.dataset.sectionKind === "contract" ? "contract" : "document";
      documentState.mode = modeValue;
      documentState.record = typeof response.record === "number" ? response.record : record;
      documentState.section = section.dataset.sectionKey ?? "";
      documentState.title = [
        getModeLabel(root, modeValue),
        getDocumentSubject(section, fields)
      ].filter(Boolean).join(": ");
      documentState.values = Object.fromEntries(
        fields.map((field) => [field.name, field.value])
      );
      trigger == null ? void 0 : trigger.setAttribute("aria-expanded", "false");
      trigger = button;
      documentState.target = getDocumentCollapseTargetSelector(collapseTarget);
      button.setAttribute("aria-controls", collapseTarget.id);
      button.setAttribute("aria-expanded", "true");
      documentState.open = true;
      documentState.pending = false;
      await initializeDocumentEditors();
    } catch (error) {
      showStatus(root, "danger", ((_a = error.result) == null ? void 0 : _a.message) ?? null);
    } finally {
      documentState.pending = false;
    }
  };
  const closeDocument = () => {
    if (documentState.pending) {
      return;
    }
    collapseDocument();
  };
  const finishDocumentClose = (element) => {
    const focusTarget = trigger;
    void destroyRichTextEditors(element);
    rowPendingRemoval == null ? void 0 : rowPendingRemoval.remove();
    if (sectionPendingRefresh !== null) {
      refreshDocumentRows(sectionPendingRefresh);
    }
    rowPendingRemoval = null;
    sectionPendingRefresh = null;
    activeSection = null;
    trigger = null;
    documentState.target = "";
    documentState.fields = [];
    documentState.contactSections = [];
    documentState.values = {};
    contractContactState.fields = [];
    contractContactState.values = {};
    if ((focusTarget == null ? void 0 : focusTarget.isConnected) === true) {
      focusTarget.focus({ preventScroll: true });
    }
  };
  const collectDocumentValues = () => {
    const values = { ...documentState.values };
    const view = root.querySelector(documentViewSelector);
    documentState.fields.forEach((field) => {
      if (!field.richText || view === null) {
        return;
      }
      const control = view.querySelector(
        `[data-pe-document-field="${CSS.escape(field.name)}"]`
      );
      if (control !== null) {
        values[field.name] = getRichTextEditorValue(control) ?? control.value;
      }
    });
    return values;
  };
  const submitDocument = async () => {
    if (documentState.pending || documentState.mode === "view" || activeSection === null) {
      return;
    }
    const form = root.querySelector("[data-pe-document-form]");
    if (documentState.mode !== "delete" && form !== null && !form.reportValidity()) {
      return;
    }
    const endpoint = documentState.mode === "add" ? root.dataset.createDocumentUrl : documentState.mode === "edit" ? root.dataset.updateDocumentUrl : root.dataset.deleteDocumentUrl;
    const data = { section: documentState.section };
    if (documentState.mode !== "add") {
      data.record = documentState.record;
    }
    if (documentState.mode !== "delete") {
      data.fields = collectDocumentValues();
    }
    documentState.pending = true;
    documentState.error = "";
    documentState.errors = {};
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    try {
      const response = await requestDocument(root, endpoint, data);
      const item = response.item;
      if (documentState.mode === "add" && item !== void 0) {
        insertDocumentRow(activeSection, item);
      } else if (documentState.mode === "edit" && item !== void 0) {
        const row = activeSection.querySelector(
          `${itemSelector}[data-item-uid="${CSS.escape(String(documentState.record))}"]`
        );
        if (row !== null) {
          updateDocumentRow(row, item);
        }
        refreshDocumentRows(activeSection);
      } else if (documentState.mode === "delete") {
        rowPendingRemoval = activeSection.querySelector(
          `${itemSelector}[data-item-uid="${CSS.escape(String(documentState.record))}"]`
        );
        sectionPendingRefresh = activeSection;
      }
      const successMessage = documentState.mode === "delete" ? root.dataset.messageDocumentDeleted : root.dataset.messageDocumentSaved;
      documentState.pending = false;
      closeDocument();
      showStatus(root, "success", successMessage ?? null);
    } catch (error) {
      const result = error.result;
      documentState.error = (result == null ? void 0 : result.message) ?? root.dataset.messageErrorMessage ?? "";
      documentState.errors = Object.fromEntries(
        Object.entries((result == null ? void 0 : result.errors) ?? {}).map(([name, messages]) => [
          name,
          Array.isArray(messages) ? messages.map(String).join(" ") : String(messages)
        ])
      );
    } finally {
      documentState.pending = false;
    }
  };
  const sortDocument = async (direction, event) => {
    var _a;
    const button = event.currentTarget instanceof HTMLButtonElement ? event.currentTarget : event.target instanceof Element ? event.target.closest("button") : null;
    const section = button == null ? void 0 : button.closest(sectionSelector);
    const record = button === null ? null : getRecordFromButton(button);
    if (button === null || section === null || section === void 0 || record === null || !["up", "down"].includes(direction)) {
      return;
    }
    setSectionPending(section, true);
    try {
      const response = await requestDocument(root, root.dataset.sortDocumentUrl, {
        section: section.dataset.sectionKey,
        record,
        direction
      });
      applyDocumentOrder(
        section,
        Array.isArray(response.order) ? response.order.map(Number) : []
      );
      showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
    } catch (error) {
      showStatus(root, "danger", ((_a = error.result) == null ? void 0 : _a.message) ?? null);
    } finally {
      setSectionPending(section, false);
      refreshDocumentRows(section);
    }
  };
  const openContractContact = async (modeValue, section, event, record = 0) => {
    var _a;
    if (!isDocumentMode(modeValue) || documentState.record === null || contractContactState.pending) {
      return;
    }
    const normalizedRecord = modeValue === "add" ? null : record;
    if (modeValue !== "add" && (!Number.isInteger(record) || record <= 0)) {
      return;
    }
    const button = event.currentTarget instanceof HTMLElement ? event.currentTarget : event.target instanceof Element ? event.target.closest("button") : null;
    if (contractContactState.open && contractContactState.mode === modeValue && contractContactState.section === section && contractContactState.record === normalizedRecord) {
      closeContractContact();
      return;
    }
    contractContactState.pending = true;
    contractContactState.error = "";
    contractContactState.errors = {};
    try {
      const response = await requestDocument(
        root,
        root.dataset.contractContactFormUrl,
        {
          contract: documentState.record,
          section,
          record: normalizedRecord ?? 0,
          mode: modeValue
        }
      );
      const fields = getResponseFields(response.fields);
      contractContactState.fields = fields;
      contractContactState.mode = modeValue;
      contractContactState.record = typeof response.record === "number" ? response.record : normalizedRecord;
      contractContactState.section = section;
      contractContactState.title = [
        getModeLabel(root, modeValue),
        String(response.title ?? "")
      ].filter(Boolean).join(": ");
      contractContactState.values = Object.fromEntries(
        fields.map((field) => [field.name, field.value])
      );
      contractContactTrigger = button;
      contractContactState.open = true;
      await nextTick();
      const editor = root.querySelector(
        "[data-pe-contract-contact-editor]"
      );
      if (editor !== null) {
        initializePopover(editor);
        editor.scrollIntoView({ behavior: "smooth", block: "nearest" });
        const focusTarget = contractContactState.mode === "add" || contractContactState.mode === "edit" ? editor.querySelector(
          "input:not([disabled]), select:not([disabled])"
        ) : editor.querySelector("[data-pe-contract-contact-heading]");
        focusTarget == null ? void 0 : focusTarget.focus({ preventScroll: true });
      }
    } catch (error) {
      showStatus(root, "danger", ((_a = error.result) == null ? void 0 : _a.message) ?? null);
    } finally {
      contractContactState.pending = false;
    }
  };
  const closeContractContact = () => {
    if (!contractContactState.pending) {
      contractContactState.open = false;
      const focusTarget = contractContactTrigger;
      contractContactTrigger = null;
      void nextTick().then(() => {
        if ((focusTarget == null ? void 0 : focusTarget.isConnected) === true) {
          focusTarget.focus({ preventScroll: true });
        }
      });
    }
  };
  const submitContractContact = async () => {
    if (contractContactState.pending || contractContactState.mode === "view" || documentState.record === null) {
      return;
    }
    const form = root.querySelector("[data-pe-document-form]");
    if (contractContactState.mode !== "delete" && form !== null && !form.reportValidity()) {
      return;
    }
    const endpoint = contractContactState.mode === "add" ? root.dataset.createContractContactUrl : contractContactState.mode === "edit" ? root.dataset.updateContractContactUrl : root.dataset.deleteContractContactUrl;
    const data = {
      contract: documentState.record,
      section: contractContactState.section
    };
    if (contractContactState.mode !== "add") {
      data.record = contractContactState.record;
    }
    if (contractContactState.mode !== "delete") {
      data.fields = { ...contractContactState.values };
    }
    contractContactState.pending = true;
    contractContactState.error = "";
    contractContactState.errors = {};
    showStatus(root, "info", root.dataset.messageSaving ?? null);
    try {
      const response = await requestDocument(root, endpoint, data);
      const section = documentState.contactSections.find(
        (candidate) => candidate.identifier === contractContactState.section
      );
      const item = asContractContactItem(response.item);
      if (section !== void 0) {
        if (contractContactState.mode === "add" && item !== null) {
          section.items.push(item);
        } else if (contractContactState.mode === "edit" && item !== null) {
          const index = section.items.findIndex(
            (candidate) => candidate.uid === contractContactState.record
          );
          if (index >= 0) {
            section.items.splice(index, 1, item);
          }
        } else if (contractContactState.mode === "delete") {
          const index = section.items.findIndex(
            (candidate) => candidate.uid === contractContactState.record
          );
          if (index >= 0) {
            section.items.splice(index, 1);
          }
        }
      }
      contractContactState.pending = false;
      contractContactState.open = false;
      showStatus(
        root,
        "success",
        contractContactState.mode === "delete" ? root.dataset.messageDocumentDeleted ?? null : root.dataset.messageDocumentSaved ?? null
      );
    } catch (error) {
      const result = error.result;
      contractContactState.error = (result == null ? void 0 : result.message) ?? root.dataset.messageErrorMessage ?? "";
      contractContactState.errors = Object.fromEntries(
        Object.entries((result == null ? void 0 : result.errors) ?? {}).map(
          ([name, messages]) => [
            name,
            Array.isArray(messages) ? messages.map(String).join(" ") : String(messages)
          ]
        )
      );
    } finally {
      contractContactState.pending = false;
    }
  };
  const sortContractContact = async (direction, sectionIdentifier, record) => {
    var _a;
    if (contractContactState.pending || documentState.record === null || !["up", "down"].includes(direction)) {
      return;
    }
    const section = documentState.contactSections.find(
      (candidate) => candidate.identifier === sectionIdentifier
    );
    if (section === void 0) {
      return;
    }
    contractContactState.pending = true;
    try {
      const response = await requestDocument(
        root,
        root.dataset.sortContractContactUrl,
        {
          contract: documentState.record,
          section: sectionIdentifier,
          record,
          direction
        }
      );
      const itemsByUid = new Map(
        section.items.map((item) => [item.uid, item])
      );
      const order = Array.isArray(response.order) ? response.order.map(Number) : [];
      const sortedItems = order.flatMap((uid) => {
        const item = itemsByUid.get(uid);
        return item === void 0 ? [] : [item];
      });
      if (sortedItems.length === section.items.length) {
        sortedItems.forEach((item, index) => {
          item.sorting = (index + 1) * 10;
        });
        section.items.splice(0, section.items.length, ...sortedItems);
      }
      showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
    } catch (error) {
      showStatus(root, "danger", ((_a = error.result) == null ? void 0 : _a.message) ?? null);
    } finally {
      contractContactState.pending = false;
    }
  };
  const documentFieldHtml = (fieldValue) => {
    const field = asDocumentField(fieldValue);
    if (field === null || !field.richText) {
      return "";
    }
    return parseRichTextPreview(field.displayValue ?? "").body.innerHTML;
  };
  const controller = {
    contractContact: contractContactState,
    document: documentState,
    openDocument,
    closeDocument,
    finishDocumentClose,
    submitDocument,
    openContractContact,
    closeContractContact,
    submitContractContact,
    sortContractContact,
    sortDocument,
    documentFieldHtml
  };
  controllers.set(root, controller);
  return controller;
};
const initializeDocumentSections = (root) => {
  root.querySelectorAll(sectionSelector).forEach(refreshDocumentRows);
  if (initializedRoots.has(root)) {
    return;
  }
  initializedRoots.add(root);
  initializeDocumentDragAndDrop(root);
  root.addEventListener("click", (event) => {
    const target = event.target instanceof Element ? event.target : null;
    const button = target == null ? void 0 : target.closest(
      "[data-pe-document-add], [data-pe-document-view], [data-pe-document-edit], [data-pe-document-delete], [data-pe-document-sort]"
    );
    if (button === null || button === void 0 || button.disabled) {
      return;
    }
    const controller = controllers.get(root);
    if (controller === void 0) {
      return;
    }
    const direction = hooks(button).peDocumentSort;
    if (direction !== void 0) {
      void controller.sortDocument(direction, event);
      return;
    }
    const mode = button.matches("[data-pe-document-add]") ? "add" : button.matches("[data-pe-document-view]") ? "view" : button.matches("[data-pe-document-edit]") ? "edit" : "delete";
    void controller.openDocument(mode, event);
  });
};
export {
  createDocumentEditing,
  initializeDocumentSections
};
