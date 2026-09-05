import { nextTick, reactive } from "@fgtclb/academic-persons-edit/frontend/vue.js";
import {
  getProfileUid,
  initializePopover,
  requestJson,
  showStatus,
} from "@fgtclb/academic-persons-edit/frontend/profile/common.js";
import {
  ensureRichTextEditor,
  getPlainText,
  getRichTextEditorValue,
  isAllowedRichTextLink,
  parseRichTextPreview,
} from "@fgtclb/academic-persons-edit/frontend/profile/rich-text.js";

type DocumentMode = "add" | "view" | "edit" | "delete";
type DocumentValue = string | boolean | number | null;

interface DocumentOption {
  label: string;
  value: DocumentValue;
}

interface DocumentField {
  autocomplete?: string;
  characterLimit?: number;
  columnClass?: string;
  compactCheckbox?: boolean;
  disabled: boolean;
  displayValue?: string;
  helptext?: string;
  label: string;
  name: string;
  options?: DocumentOption[];
  readOnly: boolean;
  required: boolean;
  richText: boolean;
  type: string;
  value: DocumentValue;
}

interface DocumentItem {
  display?: Record<string, unknown>;
  sorting?: number;
  uid?: number;
  values?: Record<string, unknown>;
}

interface ContractContactSummary {
  label: string;
  value: string;
}

interface ContractContactItem extends DocumentItem {
  hidden: boolean;
  summary: ContractContactSummary[];
  uid: number;
}

interface ContractContactSection {
  identifier: string;
  items: ContractContactItem[];
  label: string;
  singularLabel: string;
}

interface ContractContactState {
  deleteConfirmation: string;
  error: string;
  errors: Record<string, string>;
  fields: DocumentField[];
  mode: DocumentMode;
  open: boolean;
  pending: boolean;
  record: number | null;
  section: string;
  title: string;
  values: Record<string, DocumentValue>;
}

interface DocumentState {
  contactEmptyMessage: string;
  contactSections: ContractContactSection[];
  deleteConfirmation: string;
  error: string;
  errors: Record<string, string>;
  fields: DocumentField[];
  kind: "document" | "contract";
  mode: DocumentMode;
  open: boolean;
  pending: boolean;
  record: number | null;
  section: string;
  title: string;
  target: string;
  values: Record<string, DocumentValue>;
}

interface RequestError extends Error {
  result?: {
    errors?: Record<string, unknown>;
    message?: string;
  };
}

export interface DocumentEditingController {
  contractContact: ContractContactState;
  document: DocumentState;
  openDocument(mode: string, event: Event): Promise<void>;
  closeDocument(): void;
  finishDocumentClose(): void;
  submitDocument(): Promise<void>;
  openContractContact(
    mode: string,
    section: string,
    event: Event,
    record?: number,
  ): Promise<void>;
  closeContractContact(): void;
  submitContractContact(): Promise<void>;
  sortContractContact(direction: string, section: string, record: number): Promise<void>;
  sortDocument(direction: string, event: Event): Promise<void>;
  documentFieldHtml(field: unknown): string;
}

interface DragState {
  dropPosition: "before" | "after" | "end" | null;
  dropRow: HTMLElement | null;
  handle: HTMLButtonElement;
  items: HTMLElement;
  order: number[];
  row: HTMLElement;
  section: HTMLElement;
}

const sectionSelector = "[data-ie-document-section]";
const itemSelector = "[data-ie-document-item]";
const itemsSelector = "[data-ie-document-items]";
const itemTemplateSelector = "[data-ie-document-item-template]";
const emptyStateSelector = "[data-ie-document-empty-state]";
const listHeaderSelector = "[data-ie-document-list-header]";
const documentViewSelector = "[data-ie-document-view-container]";
const addCollapseTargetSelector = "[data-ie-document-add-collapse-target]";
const itemCollapseTargetSelector = "[data-ie-document-item-collapse-target]";
const controllers = new WeakMap<HTMLElement, DocumentEditingController>();
const initializedRoots = new WeakSet<HTMLElement>();
const dragStates = new WeakMap<HTMLElement, DragState>();
let collapseTargetSequence = 0;

const isDocumentMode = (value: string): value is DocumentMode =>
  ["add", "view", "edit", "delete"].includes(value);

const getDocumentRows = (section: HTMLElement): HTMLElement[] => {
  const items = section.querySelector<HTMLElement>(itemsSelector);
  if (items === null) {
    return [];
  }
  return Array.from(items.children).filter(
    (element): element is HTMLElement =>
      element instanceof HTMLElement && element.matches(itemSelector),
  );
};

const asDocumentField = (value: unknown): DocumentField | null => {
  if (typeof value !== "object" || value === null) {
    return null;
  }
  const field = value as Partial<DocumentField>;
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
    value: field.value ?? "",
  };
};

const getResponseFields = (value: unknown): DocumentField[] =>
  Array.isArray(value)
    ? value
        .map(asDocumentField)
        .filter((field): field is DocumentField => field !== null)
    : [];

const asRecord = (value: unknown): Record<string, unknown> =>
  typeof value === "object" && value !== null
    ? (value as Record<string, unknown>)
    : {};

const asContractContactItem = (value: unknown): ContractContactItem | null => {
  const item = asRecord(value);
  const uid = Number(item.uid);
  if (!Number.isInteger(uid) || uid <= 0) {
    return null;
  }
  return {
    display: asRecord(item.display),
    hidden: item.hidden === true,
    sorting: Number(item.sorting) || 0,
    summary: Array.isArray(item.summary)
      ? item.summary.map((entry): ContractContactSummary => {
          const summary = asRecord(entry);
          return {
            label: String(summary.label ?? ""),
            value: String(summary.value ?? ""),
          };
        })
      : [],
    uid,
    values: asRecord(item.values),
  };
};

const getResponseContactSections = (value: unknown): ContractContactSection[] =>
  Array.isArray(value)
    ? value.flatMap((entry): ContractContactSection[] => {
        const section = asRecord(entry);
        const identifier = String(section.identifier ?? "");
        if (identifier === "") {
          return [];
        }
        return [{
          identifier,
          items: Array.isArray(section.items)
            ? section.items
                .map(asContractContactItem)
                .filter((item): item is ContractContactItem => item !== null)
            : [],
          label: String(section.label ?? identifier),
          singularLabel: String(section.singularLabel ?? section.label ?? identifier),
        }];
      })
    : [];

const getSectionHeading = (section: HTMLElement): string =>
  section.querySelector("h2")?.textContent?.trim() ?? "";

const getDocumentSubject = (
  section: HTMLElement,
  fields: DocumentField[],
): string => {
  const titleField = fields.find((field): boolean => field.name === "title");
  const title = [titleField?.displayValue, titleField?.value]
    .map((value): string => String(value ?? "").trim())
    .find((value): boolean => value !== "");
  return title ?? getSectionHeading(section);
};

const getModeLabel = (root: HTMLElement, mode: DocumentMode): string => {
  const labels: Record<DocumentMode, string | undefined> = {
    add: root.dataset.labelDocumentAdd,
    view: root.dataset.labelDocumentView,
    edit: root.dataset.labelDocumentEdit,
    delete: root.dataset.labelDocumentDelete,
  };
  return labels[mode] ?? "";
};

const getRecordFromButton = (button: HTMLButtonElement): number | null => {
  const row = button.closest<HTMLElement>(itemSelector);
  const record = Number.parseInt(row?.dataset.itemUid ?? "", 10);
  return Number.isInteger(record) && record > 0 ? record : null;
};

const getDocumentCollapseTarget = (
  button: HTMLButtonElement,
  section: HTMLElement,
  mode: DocumentMode,
): HTMLElement | null =>
  mode === "add"
    ? section.querySelector<HTMLElement>(addCollapseTargetSelector)
    : button
        .closest<HTMLElement>(itemSelector)
        ?.querySelector<HTMLElement>(itemCollapseTargetSelector) ?? null;

const getDocumentCollapseTargetSelector = (target: HTMLElement): string => {
  if (target.id === "") {
    collapseTargetSequence += 1;
    target.id = `academic-persons-inline-document-collapse-${collapseTargetSequence}`;
  }
  return `#${CSS.escape(target.id)}`;
};

const requestDocument = (
  root: HTMLElement,
  url: string | undefined,
  data: Record<string, unknown>,
): Promise<Record<string, unknown>> => {
  const profile = getProfileUid(root);
  if (url === undefined || profile === null) {
    return Promise.reject(new Error("The document endpoint is unavailable."));
  }
  return requestJson(url, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ profile, data }),
  });
};

const appendRichText = (container: HTMLElement, value: string): void => {
  const parsedDocument = parseRichTextPreview(value);
  const fragment = document.createDocumentFragment();
  Array.from(parsedDocument.body.childNodes).forEach((node): void => {
    fragment.append(document.importNode(node, true));
  });
  container.replaceChildren(fragment);
};

const getRowDisplayValue = (item: DocumentItem, name: string): unknown => {
  const display = item.display ?? {};
  if (name === "yearStart" && !display.yearStart) {
    return display.year ?? "";
  }
  if (name === "year" && !display.year) {
    return display.yearStart ?? "";
  }
  return display[name] ?? "";
};

const renderDocumentTitle = (
  container: HTMLElement,
  title: string,
  link: unknown,
): void => {
  const normalizedTitle = title || "—";
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

export const updateDocumentRow = (
  section: HTMLElement,
  row: HTMLElement,
  item: DocumentItem,
): void => {
  row.dataset.itemUid = String(item.uid ?? "");
  row.dataset.itemSorting = String(item.sorting ?? "");
  row.querySelectorAll<HTMLElement>("[data-ie-document-value]").forEach(
    (element): void => {
      const name = element.dataset.ieDocumentValue ?? "";
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
    },
  );
  const title = row.querySelector<HTMLElement>("[data-ie-document-title]");
  if (title !== null) {
    renderDocumentTitle(
      title,
      String(item.display?.title ?? ""),
      item.values?.link,
    );
  }
  void section;
};

export const refreshDocumentRows = (section: HTMLElement): void => {
  const rows = getDocumentRows(section);
  const sortable = section.dataset.sectionSortable === "1" && rows.length > 1;
  rows.forEach((row, index): void => {
    row.dataset.itemPosition = String(index);
    row.classList.toggle("bg-body-tertiary", index % 2 === 0);
    const up = row.querySelector<HTMLButtonElement>(
      '[data-ie-document-sort="up"]',
    );
    const down = row.querySelector<HTMLButtonElement>(
      '[data-ie-document-sort="down"]',
    );
    const drag = row.querySelector<HTMLButtonElement>("[data-ie-document-drag]");
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
  const emptyState = section.querySelector<HTMLElement>(emptyStateSelector);
  emptyState?.classList.toggle("d-none", rows.length > 0);
  const listHeader = section.querySelector<HTMLElement>(listHeaderSelector);
  listHeader?.classList.toggle("d-md-flex", rows.length > 0);
};

const insertDocumentRow = (
  section: HTMLElement,
  item: DocumentItem,
): HTMLElement | null => {
  const template = section.querySelector<HTMLElement>(itemTemplateSelector);
  const items = section.querySelector<HTMLElement>(itemsSelector);
  const templateRow = template?.querySelector<HTMLElement>(itemSelector);
  if (items === null || templateRow === null || templateRow === undefined) {
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

export const getDocumentOrder = (section: HTMLElement): number[] =>
  Array.from(getDocumentRows(section), (row): number =>
    Number.parseInt(row.dataset.itemUid ?? "", 10),
  ).filter((uid): boolean => Number.isInteger(uid) && uid > 0);

export const applyDocumentOrder = (
  section: HTMLElement,
  order: number[],
): void => {
  const items = section.querySelector<HTMLElement>(itemsSelector);
  if (items === null) {
    return;
  }
  const rowsByUid = new Map(
    getDocumentRows(section).map(
      (row): [string | undefined, HTMLElement] => [row.dataset.itemUid, row],
    ),
  );
  order.forEach((uid): void => {
    const row = rowsByUid.get(String(uid));
    if (row !== undefined) {
      items.append(row);
    }
  });
  refreshDocumentRows(section);
};

const setSectionPending = (section: HTMLElement, pending: boolean): void => {
  section.setAttribute("aria-busy", String(pending));
  section.style.cursor = pending ? "wait" : "";
  section.querySelectorAll<HTMLButtonElement>("button").forEach((button): void => {
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

const persistDocumentOrder = async (
  root: HTMLElement,
  section: HTMLElement,
  order: number[],
  previousOrder: number[],
): Promise<void> => {
  setSectionPending(section, true);
  try {
    const response = await requestDocument(root, root.dataset.sortDocumentUrl, {
      section: section.dataset.sectionKey,
      order,
    });
    applyDocumentOrder(section, Array.isArray(response.order) ? response.order.map(Number) : order);
    showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
  } catch (error) {
    applyDocumentOrder(section, previousOrder);
    showStatus(root, "danger", (error as RequestError).result?.message ?? null);
  } finally {
    setSectionPending(section, false);
    refreshDocumentRows(section);
  }
};

const clearDocumentDropPosition = (state: DragState): void => {
  state.section
    .querySelectorAll(`${itemSelector}.is-drop-before, ${itemSelector}.is-drop-after`)
    .forEach((row): void => row.classList.remove("is-drop-before", "is-drop-after"));
  state.items.classList.remove("is-drop-at-end");
  state.dropRow = null;
  state.dropPosition = null;
};

const getDocumentDropPosition = (
  state: DragState,
  targetRow: HTMLElement,
  clientY: number,
): "before" | "after" => {
  const bounds = targetRow.getBoundingClientRect();
  if (Number.isFinite(clientY) && bounds.height > 0) {
    return clientY < bounds.top + bounds.height / 2 ? "before" : "after";
  }
  const rows = Array.from(state.items.querySelectorAll(itemSelector));
  return rows.indexOf(state.row) < rows.indexOf(targetRow) ? "after" : "before";
};

const updateDocumentDropPosition = (
  state: DragState,
  target: Element,
  clientY: number,
): void => {
  clearDocumentDropPosition(state);
  const targetRow = target.closest<HTMLElement>(itemSelector);
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

const clearDragState = (root: HTMLElement): void => {
  const state = dragStates.get(root);
  if (state === undefined) {
    return;
  }
  clearDocumentDropPosition(state);
  state.items.classList.remove("is-drag-active");
  state.row.classList.remove("is-dragging");
  state.handle.removeAttribute("aria-grabbed");
  dragStates.delete(root);
};

const initializeDocumentDragAndDrop = (root: HTMLElement): void => {
  root.addEventListener("dragstart", (event): void => {
    const target = event.target instanceof Element ? event.target : null;
    const handle = target?.closest<HTMLButtonElement>("[data-ie-document-drag]");
    const row = handle?.closest<HTMLElement>(itemSelector);
    const section = row?.closest<HTMLElement>(sectionSelector);
    const items = row?.closest<HTMLElement>(itemsSelector);
    if (
      handle === null ||
      handle === undefined ||
      handle.disabled ||
      row === null ||
      row === undefined ||
      section === null ||
      section === undefined ||
      items === null ||
      items === undefined ||
      section.dataset.sectionSortable !== "1"
    ) {
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
    if (event.dataTransfer !== null) {
      event.dataTransfer.effectAllowed = "move";
      event.dataTransfer.setData("text/plain", row.dataset.itemUid ?? "");
      const bounds = row.getBoundingClientRect();
      const offsetX = Math.min(Math.max(event.clientX - bounds.left, 0), bounds.width);
      const offsetY = Math.min(Math.max(event.clientY - bounds.top, 0), bounds.height);
      event.dataTransfer.setDragImage(row, offsetX, offsetY);
    }
  });
  root.addEventListener("dragover", (event): void => {
    const state = dragStates.get(root);
    const target = event.target instanceof Element ? event.target : null;
    if (state === undefined || target === null) {
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
  root.addEventListener("drop", (event): void => {
    const state = dragStates.get(root);
    const target = event.target instanceof Element ? event.target : null;
    if (state === undefined || target?.closest(sectionSelector) !== state.section) {
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
    const changed =
      order.length === previousOrder.length &&
      order.some((uid, index): boolean => uid !== previousOrder[index]);
    const section = state.section;
    clearDragState(root);
    refreshDocumentRows(section);
    if (changed) {
      void persistDocumentOrder(root, section, order, previousOrder);
    }
  });
  root.addEventListener("dragend", (): void => clearDragState(root));
};

export const createDocumentEditing = (
  root: HTMLElement,
): DocumentEditingController => {
  const documentState = reactive<DocumentState>({
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
    values: {},
  });
  const contractContactState = reactive<ContractContactState>({
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
    values: {},
  });
  let activeSection: HTMLElement | null = null;
  let trigger: HTMLElement | null = null;
  let contractContactTrigger: HTMLElement | null = null;
  let rowPendingRemoval: HTMLElement | null = null;
  let sectionPendingRefresh: HTMLElement | null = null;

  const initializeDocumentEditors = async (): Promise<void> => {
    await nextTick();
    const view = root.querySelector<HTMLElement>(documentViewSelector);
    if (view === null) {
      return;
    }
    initializePopover(view);
    await Promise.all(
      Array.from(
        view.querySelectorAll<HTMLTextAreaElement>("textarea[data-ie-rich-text]"),
      ).map((field) => ensureRichTextEditor(root, field)),
    );
    if (documentState.mode === "add" || documentState.mode === "edit") {
      const firstField = view.querySelector<
        HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
      >("[data-ie-document-field]:not([disabled])");
      if (
        firstField instanceof HTMLTextAreaElement &&
        firstField.matches("[data-ie-rich-text]")
      ) {
        const editor = await ensureRichTextEditor(root, firstField);
        editor?.editing.view.focus();
      } else {
        firstField?.focus();
      }
      return;
    }
    view.querySelector<HTMLElement>("[data-ie-document-heading]")?.focus();
  };

  const collapseDocument = (): void => {
    contractContactState.open = false;
    documentState.open = false;
    trigger?.setAttribute("aria-expanded", "false");
  };

  const openDocument = async (modeValue: string, event: Event): Promise<void> => {
    if (!isDocumentMode(modeValue) || documentState.pending) {
      return;
    }
    const button = event.currentTarget instanceof HTMLButtonElement
      ? event.currentTarget
      : event.target instanceof Element
        ? event.target.closest<HTMLButtonElement>("button")
        : null;
    const section = button?.closest<HTMLElement>(sectionSelector);
    if (button === null || section === null || section === undefined) {
      return;
    }
    if (
      documentState.open &&
      trigger === button &&
      documentState.mode === modeValue
    ) {
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
        mode: modeValue,
      });
      const fields = getResponseFields(response.fields);
      activeSection = section;
      documentState.fields = fields;
      documentState.contactSections = getResponseContactSections(
        response.contactSections,
      );
      documentState.kind =
        section.dataset.sectionKind === "contract" ? "contract" : "document";
      documentState.mode = modeValue;
      documentState.record =
        typeof response.record === "number" ? response.record : record;
      documentState.section = section.dataset.sectionKey ?? "";
      documentState.title = [
        getModeLabel(root, modeValue),
        getDocumentSubject(section, fields),
      ]
        .filter(Boolean)
        .join(": ");
      documentState.values = Object.fromEntries(
        fields.map((field): [string, DocumentValue] => [field.name, field.value]),
      );
      trigger?.setAttribute("aria-expanded", "false");
      trigger = button;
      documentState.target = getDocumentCollapseTargetSelector(collapseTarget);
      button.setAttribute("aria-controls", collapseTarget.id);
      button.setAttribute("aria-expanded", "true");
      documentState.open = true;
      documentState.pending = false;
      await initializeDocumentEditors();
    } catch (error) {
      showStatus(root, "danger", (error as RequestError).result?.message ?? null);
    } finally {
      documentState.pending = false;
    }
  };

  const closeDocument = (): void => {
    if (documentState.pending) {
      return;
    }
    collapseDocument();
  };

  const finishDocumentClose = (): void => {
    const focusTarget = trigger;
    rowPendingRemoval?.remove();
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
    if (focusTarget?.isConnected === true) {
      focusTarget.focus({ preventScroll: true });
    }
  };

  const collectDocumentValues = (): Record<string, DocumentValue> => {
    const values = { ...documentState.values };
    const view = root.querySelector<HTMLElement>(documentViewSelector);
    documentState.fields.forEach((field): void => {
      if (!field.richText || view === null) {
        return;
      }
      const control = view.querySelector<HTMLTextAreaElement>(
        `[data-ie-document-field="${CSS.escape(field.name)}"]`,
      );
      if (control !== null) {
        values[field.name] = getRichTextEditorValue(control) ?? control.value;
      }
    });
    return values;
  };

  const submitDocument = async (): Promise<void> => {
    if (documentState.pending || documentState.mode === "view" || activeSection === null) {
      return;
    }
    const form = root.querySelector<HTMLFormElement>("[data-ie-document-form]");
    if (documentState.mode !== "delete" && form !== null && !form.reportValidity()) {
      return;
    }
    const endpoint =
      documentState.mode === "add"
        ? root.dataset.createDocumentUrl
        : documentState.mode === "edit"
          ? root.dataset.updateDocumentUrl
          : root.dataset.deleteDocumentUrl;
    const data: Record<string, unknown> = { section: documentState.section };
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
      const item = response.item as DocumentItem | undefined;
      if (documentState.mode === "add" && item !== undefined) {
        insertDocumentRow(activeSection, item);
      } else if (documentState.mode === "edit" && item !== undefined) {
        const row = activeSection.querySelector<HTMLElement>(
          `${itemSelector}[data-item-uid="${CSS.escape(String(documentState.record))}"]`,
        );
        if (row !== null) {
          updateDocumentRow(activeSection, row, item);
        }
        refreshDocumentRows(activeSection);
      } else if (documentState.mode === "delete") {
        rowPendingRemoval = activeSection.querySelector<HTMLElement>(
          `${itemSelector}[data-item-uid="${CSS.escape(String(documentState.record))}"]`,
        );
        sectionPendingRefresh = activeSection;
      }
      const successMessage =
        documentState.mode === "delete"
          ? root.dataset.messageDocumentDeleted
          : root.dataset.messageDocumentSaved;
      documentState.pending = false;
      closeDocument();
      showStatus(root, "success", successMessage ?? null);
    } catch (error) {
      const result = (error as RequestError).result;
      documentState.error = result?.message ?? root.dataset.messageErrorMessage ?? "";
      documentState.errors = Object.fromEntries(
        Object.entries(result?.errors ?? {}).map(([name, messages]): [string, string] => [
          name,
          Array.isArray(messages) ? messages.map(String).join(" ") : String(messages),
        ]),
      );
    } finally {
      documentState.pending = false;
    }
  };

  const sortDocument = async (direction: string, event: Event): Promise<void> => {
    const button = event.currentTarget instanceof HTMLButtonElement
      ? event.currentTarget
      : event.target instanceof Element
        ? event.target.closest<HTMLButtonElement>("button")
        : null;
    const section = button?.closest<HTMLElement>(sectionSelector);
    const record = button === null ? null : getRecordFromButton(button);
    if (
      button === null ||
      section === null ||
      section === undefined ||
      record === null ||
      !["up", "down"].includes(direction)
    ) {
      return;
    }
    setSectionPending(section, true);
    try {
      const response = await requestDocument(root, root.dataset.sortDocumentUrl, {
        section: section.dataset.sectionKey,
        record,
        direction,
      });
      applyDocumentOrder(
        section,
        Array.isArray(response.order) ? response.order.map(Number) : [],
      );
      showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
    } catch (error) {
      showStatus(root, "danger", (error as RequestError).result?.message ?? null);
    } finally {
      setSectionPending(section, false);
      refreshDocumentRows(section);
    }
  };

  const openContractContact = async (
    modeValue: string,
    section: string,
    event: Event,
    record = 0,
  ): Promise<void> => {
    if (
      !isDocumentMode(modeValue) ||
      documentState.record === null ||
      contractContactState.pending
    ) {
      return;
    }
    const normalizedRecord = modeValue === "add" ? null : record;
    if (modeValue !== "add" && (!Number.isInteger(record) || record <= 0)) {
      return;
    }
    const button = event.currentTarget instanceof HTMLElement
      ? event.currentTarget
      : event.target instanceof Element
        ? event.target.closest<HTMLElement>("button")
        : null;
    if (
      contractContactState.open &&
      contractContactState.mode === modeValue &&
      contractContactState.section === section &&
      contractContactState.record === normalizedRecord
    ) {
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
          mode: modeValue,
        },
      );
      const fields = getResponseFields(response.fields);
      contractContactState.fields = fields;
      contractContactState.mode = modeValue;
      contractContactState.record =
        typeof response.record === "number" ? response.record : normalizedRecord;
      contractContactState.section = section;
      contractContactState.title = [
        getModeLabel(root, modeValue),
        String(response.title ?? ""),
      ]
        .filter(Boolean)
        .join(": ");
      contractContactState.values = Object.fromEntries(
        fields.map((field): [string, DocumentValue] => [field.name, field.value]),
      );
      contractContactTrigger = button;
      contractContactState.open = true;
      await nextTick();
      const editor = root.querySelector<HTMLElement>(
        "[data-ie-contract-contact-editor]",
      );
      if (editor !== null) {
        initializePopover(editor);
        editor.scrollIntoView({ behavior: "smooth", block: "nearest" });
        const focusTarget =
          contractContactState.mode === "add" || contractContactState.mode === "edit"
            ? editor.querySelector<HTMLElement>(
                "input:not([disabled]), select:not([disabled])",
              )
            : editor.querySelector<HTMLElement>("[data-ie-contract-contact-heading]");
        focusTarget?.focus({ preventScroll: true });
      }
    } catch (error) {
      showStatus(root, "danger", (error as RequestError).result?.message ?? null);
    } finally {
      contractContactState.pending = false;
    }
  };

  const closeContractContact = (): void => {
    if (!contractContactState.pending) {
      contractContactState.open = false;
      const focusTarget = contractContactTrigger;
      contractContactTrigger = null;
      void nextTick().then((): void => {
        if (focusTarget?.isConnected === true) {
          focusTarget.focus({ preventScroll: true });
        }
      });
    }
  };

  const submitContractContact = async (): Promise<void> => {
    if (
      contractContactState.pending ||
      contractContactState.mode === "view" ||
      documentState.record === null
    ) {
      return;
    }
    const form = root.querySelector<HTMLFormElement>("[data-ie-document-form]");
    if (
      contractContactState.mode !== "delete" &&
      form !== null &&
      !form.reportValidity()
    ) {
      return;
    }
    const endpoint =
      contractContactState.mode === "add"
        ? root.dataset.createContractContactUrl
        : contractContactState.mode === "edit"
          ? root.dataset.updateContractContactUrl
          : root.dataset.deleteContractContactUrl;
    const data: Record<string, unknown> = {
      contract: documentState.record,
      section: contractContactState.section,
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
        (candidate): boolean =>
          candidate.identifier === contractContactState.section,
      );
      const item = asContractContactItem(response.item);
      if (section !== undefined) {
        if (contractContactState.mode === "add" && item !== null) {
          section.items.push(item);
        } else if (contractContactState.mode === "edit" && item !== null) {
          const index = section.items.findIndex(
            (candidate): boolean => candidate.uid === contractContactState.record,
          );
          if (index >= 0) {
            section.items.splice(index, 1, item);
          }
        } else if (contractContactState.mode === "delete") {
          const index = section.items.findIndex(
            (candidate): boolean => candidate.uid === contractContactState.record,
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
        contractContactState.mode === "delete"
          ? root.dataset.messageDocumentDeleted ?? null
          : root.dataset.messageDocumentSaved ?? null,
      );
    } catch (error) {
      const result = (error as RequestError).result;
      contractContactState.error =
        result?.message ?? root.dataset.messageErrorMessage ?? "";
      contractContactState.errors = Object.fromEntries(
        Object.entries(result?.errors ?? {}).map(
          ([name, messages]): [string, string] => [
            name,
            Array.isArray(messages)
              ? messages.map(String).join(" ")
              : String(messages),
          ],
        ),
      );
    } finally {
      contractContactState.pending = false;
    }
  };

  const sortContractContact = async (
    direction: string,
    sectionIdentifier: string,
    record: number,
  ): Promise<void> => {
    if (
      contractContactState.pending ||
      documentState.record === null ||
      !["up", "down"].includes(direction)
    ) {
      return;
    }
    const section = documentState.contactSections.find(
      (candidate): boolean => candidate.identifier === sectionIdentifier,
    );
    if (section === undefined) {
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
          direction,
        },
      );
      const itemsByUid = new Map(
        section.items.map((item): [number, ContractContactItem] => [item.uid, item]),
      );
      const order = Array.isArray(response.order)
        ? response.order.map(Number)
        : [];
      const sortedItems = order.flatMap((uid): ContractContactItem[] => {
        const item = itemsByUid.get(uid);
        return item === undefined ? [] : [item];
      });
      if (sortedItems.length === section.items.length) {
        sortedItems.forEach((item, index): void => {
          item.sorting = (index + 1) * 10;
        });
        section.items.splice(0, section.items.length, ...sortedItems);
      }
      showStatus(root, "success", root.dataset.messageDocumentSorted ?? null);
    } catch (error) {
      showStatus(root, "danger", (error as RequestError).result?.message ?? null);
    } finally {
      contractContactState.pending = false;
    }
  };

  const documentFieldHtml = (fieldValue: unknown): string => {
    const field = asDocumentField(fieldValue);
    if (field === null || !field.richText) {
      return "";
    }
    return parseRichTextPreview(field.displayValue ?? "").body.innerHTML;
  };

  const controller: DocumentEditingController = {
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
    documentFieldHtml,
  };
  controllers.set(root, controller);
  return controller;
};

export const initializeDocumentSections = (root: HTMLElement): void => {
  root.querySelectorAll<HTMLElement>(sectionSelector).forEach(refreshDocumentRows);
  if (initializedRoots.has(root)) {
    return;
  }
  initializedRoots.add(root);
  initializeDocumentDragAndDrop(root);
  root.addEventListener("click", (event): void => {
    const target = event.target instanceof Element ? event.target : null;
    const button = target?.closest<HTMLButtonElement>(
      "[data-ie-document-add], [data-ie-document-view], [data-ie-document-edit], " +
        "[data-ie-document-delete], [data-ie-document-sort]",
    );
    if (button === null || button === undefined || button.disabled) {
      return;
    }
    const controller = controllers.get(root);
    if (controller === undefined) {
      return;
    }
    const direction = button.dataset.ieDocumentSort;
    if (direction !== undefined) {
      void controller.sortDocument(direction, event);
      return;
    }
    const mode = button.matches("[data-ie-document-add]")
      ? "add"
      : button.matches("[data-ie-document-view]")
        ? "view"
        : button.matches("[data-ie-document-edit]")
          ? "edit"
          : "delete";
    void controller.openDocument(mode, event);
  });
};
