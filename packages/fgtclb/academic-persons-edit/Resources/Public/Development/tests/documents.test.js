import { beforeEach, describe, expect, jest, test } from "@jest/globals";
import { ClassicEditor } from "./mocks/ckeditor-modules.js";
import {
  applyDocumentOrder,
  getDocumentOrder,
  initializeDocumentSections,
  refreshDocumentRows,
  updateDocumentRow,
} from "../../JavaScript/frontend/profile/documents.js";

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

const response = (result, ok = true) => ({
  ok,
  json: jest.fn().mockResolvedValue(result),
});

const actionButtons = `
  <div data-ie-document-actions>
    <button type="button" draggable="true" data-ie-document-drag>Drag</button>
    <button type="button" data-ie-document-view>View</button>
    <button type="button" data-ie-document-sort="down">Down</button>
    <button type="button" data-ie-document-sort="up">Up</button>
    <button type="button" data-ie-document-delete>Delete</button>
    <button type="button" data-ie-document-edit>Edit</button>
  </div>
`;

const itemMarkup = (uid, title) => `
  <article data-ie-document-item data-item-uid="${uid}" data-item-sorting="${uid * 10}">
    <div data-ie-document-value="year">202${uid}</div>
    <div data-ie-document-title><span>${title}</span></div>
    <div class="d-none" data-ie-document-value="bodytext"></div>
    ${actionButtons}
  </article>
`;

const createRoot = () => {
  const root = document.createElement("section");
  Object.assign(root.dataset, {
    profileUid: "9",
    documentFormUrl: "/document-form",
    createDocumentUrl: "/document-create",
    updateDocumentUrl: "/document-update",
    deleteDocumentUrl: "/document-delete",
    sortDocumentUrl: "/document-sort",
    labelDocumentAdd: "Add",
    labelDocumentView: "View",
    labelDocumentEdit: "Edit",
    labelDocumentDelete: "Delete",
    labelDocumentSave: "Save",
    labelDocumentEmpty: "Not specified",
    messageDocumentDeleteConfirm: "Delete this entry?",
    messageDocumentSaved: "Saved",
    messageDocumentDeleted: "Deleted",
    messageDocumentSorted: "Sorted",
    messageEditorError: "Editor unavailable",
    messageErrorTitle: "Error",
    messageErrorMessage: "Failed",
    messageSuccessTitle: "Success",
    messageSuccessMessage: "Saved",
    messageInfoTitle: "Info",
    messageInfoMessage: "Working",
  });
  root.innerHTML = `
    <section data-ie-document-section data-section-key="publications" data-section-date-mode="year" data-section-sortable="1">
      <h2>Publications</h2>
      <button type="button" data-ie-document-add>Add</button>
      <div data-ie-document-items>
        ${itemMarkup(1, "First")}
        ${itemMarkup(2, "Second")}
      </div>
      <template data-ie-document-item-template>${itemMarkup("", "")}</template>
      <div class="d-none" data-ie-document-empty-state>Empty</div>
    </section>
    <template data-ie-document-helptext-button-template>
      <button type="button" data-ie-helptext data-bs-toggle="popover"></button>
    </template>
    <div class="modal" data-ie-document-modal>
      <form data-ie-document-form>
        <h2 data-ie-document-modal-title></h2>
        <div data-ie-document-error class="d-none"></div>
        <div data-ie-document-fields></div>
        <p data-ie-document-delete-confirmation class="d-none"></p>
        <button class="btn-close rounded-0" type="button" data-ie-document-modal-close>Close</button>
        <button class="btn rounded-0 btn-outline-secondary" type="button" data-ie-document-cancel>Cancel</button>
        <button class="btn rounded-0 btn-primary" type="submit" data-ie-document-submit>
          <span data-ie-document-submit-label></span>
          <span data-ie-document-spinner class="d-none"></span>
        </button>
      </form>
    </div>
    <div class="d-none" data-ie-status-toast>
      <span class="status-title"></span><span class="status-message"></span>
    </div>
  `;
  document.body.append(root);
  return root;
};

const publicationFields = (title = "First") => [
  {
    name: "title",
    label: "Title",
    type: "text",
    required: true,
    readOnly: false,
    disabled: false,
    richText: false,
    value: title,
    displayValue: title,
    helptext: "Title help",
    options: [],
  },
  {
    name: "year",
    label: "Year",
    type: "number",
    required: false,
    readOnly: false,
    disabled: false,
    richText: false,
    value: 2025,
    displayValue: "2025",
    options: [],
  },
  {
    name: "bodytext",
    label: "Description",
    type: "textarea",
    required: false,
    readOnly: false,
    disabled: false,
    richText: true,
    characterLimit: 100,
    value: "<p>Text</p>",
    displayValue: "<p>Text</p>",
    helptext: "Description help",
    options: [],
  },
];

const configuredDateFields = () => [
  {
    name: "year",
    label: "Date",
    type: "date",
    required: true,
    readOnly: false,
    disabled: false,
    richText: false,
    columnClass: "col-12 col-md-3",
    compactCheckbox: false,
    value: "2025-01-01",
    displayValue: "01.01.2025",
    helptext: "Date help",
    options: [],
  },
  {
    name: "yearStart",
    label: "Start date",
    type: "date",
    required: false,
    readOnly: false,
    disabled: false,
    richText: false,
    columnClass: "col-12 col-md-3",
    compactCheckbox: false,
    value: "",
    displayValue: "",
    options: [],
  },
  {
    name: "yearEnd",
    label: "End date",
    type: "date",
    required: false,
    readOnly: false,
    disabled: false,
    richText: false,
    columnClass: "col-12 col-md-3",
    compactCheckbox: false,
    value: "",
    displayValue: "",
    options: [],
  },
  {
    name: "yearOnly",
    label: "Show year only",
    type: "checkbox",
    required: false,
    readOnly: false,
    disabled: false,
    richText: false,
    columnClass: "col-12 col-md-3",
    compactCheckbox: true,
    value: true,
    displayValue: "Yes",
    helptext: "Year only help",
    options: [],
  },
];

const createEditor = (initialData) => {
  let data = initialData;
  return {
    editing: { view: { focus: jest.fn() } },
    getData: jest.fn(() => data),
    setData: jest.fn((value) => {
      data = value;
    }),
    model: { document: { on: jest.fn() } },
  };
};

const dispatchDragEvent = (element, type, { clientX = 0, clientY = 0 } = {}) => {
  const event = new Event(type, { bubbles: true, cancelable: true });
  Object.defineProperties(event, {
    clientX: { value: clientX },
    clientY: { value: clientY },
  });
  Object.defineProperty(event, "dataTransfer", {
    value: {
      effectAllowed: "",
      dropEffect: "",
      setData: jest.fn(),
      setDragImage: jest.fn(),
    },
  });
  element.dispatchEvent(event);
  return event;
};

describe("profile/documents", () => {
  let modalInstance;
  beforeEach(() => {
    modalInstance = { show: jest.fn(), hide: jest.fn() };
    globalThis.bootstrap = {
      Modal: { getOrCreateInstance: jest.fn(() => modalInstance) },
      Popover: jest.fn(),
    };
    globalThis.fetch = jest.fn();
    ClassicEditor.create.mockReset();
    ClassicEditor.create.mockImplementation((field) => Promise.resolve(createEditor(field.value)));
  });
  test("refreshes stripes and enables only possible sort directions", () => {
    const root = createRoot();
    const section = root.querySelector("[data-ie-document-section]");
    refreshDocumentRows(section);
    const rows = section.querySelectorAll("[data-ie-document-item]");
    expect(rows[0].classList.contains("bg-body-tertiary")).toBe(true);
    expect(rows[1].classList.contains("bg-body-tertiary")).toBe(false);
    expect(rows[0].querySelector('[data-ie-document-sort="up"]').disabled).toBe(true);
    expect(rows[0].querySelector('[data-ie-document-sort="down"]').disabled).toBe(false);
    expect(rows[1].querySelector('[data-ie-document-sort="down"]').disabled).toBe(true);
    expect(rows[0].querySelector("[data-ie-document-drag]").disabled).toBe(false);
    expect(rows[1].querySelectorAll("[data-ie-document-actions] button")).toHaveLength(6);
  });
  test("updates plain, linked and sanitized rich-text row values", () => {
    const root = createRoot();
    const section = root.querySelector("[data-ie-document-section]");
    const row = section.querySelector("[data-ie-document-item]");
    updateDocumentRow(section, row, {
      uid: 1,
      sorting: 30,
      values: { link: "https://example.test/publication" },
      display: {
        title: "Updated",
        year: "2030",
        bodytext: "<p><strong>Safe</strong><script>bad()</script></p>",
      },
    });
    expect(row.dataset.itemSorting).toBe("30");
    expect(row.querySelector('[data-ie-document-value="year"]').textContent).toBe("2030");
    expect(row.querySelector("[data-ie-document-title] a").textContent).toBe("Updated");
    expect(row.querySelector('[data-ie-document-value="bodytext"] strong').textContent).toBe("Safe");
    expect(row.querySelector('[data-ie-document-value="bodytext"] script')).toBeNull();
  });
  test("opens an enabled modal with a full-width CKEditor field", async () => {
    const root = createRoot();
    initializeDocumentSections(root);
    globalThis.fetch.mockResolvedValueOnce(response({ success: true, record: null, fields: publicationFields("") }));
    root.querySelector("[data-ie-document-add]").click();
    await flushPromises();
    const textarea = root.querySelector('textarea[name="bodytext"]');
    const submit = root.querySelector("[data-ie-document-submit]");
    expect(modalInstance.show).toHaveBeenCalledTimes(1);
    expect(submit.disabled).toBe(false);
    expect(submit.classList.contains("btn-primary")).toBe(true);
    expect(submit.classList.contains("btn-danger")).toBe(false);
    expect(submit.classList.contains("btn-success")).toBe(false);
    expect(textarea.dataset.ieRichText).toBe("");
    expect(textarea.dataset.ieCharacterLimit).toBe("100");
    expect(textarea.parentElement.classList.contains("col-12")).toBe(true);
    expect(textarea.parentElement.classList.contains("col-md-6")).toBe(false);
    expect(ClassicEditor.create).toHaveBeenCalledWith(textarea, expect.any(Object));
    const counter = root.querySelector("[data-ie-character-counter]");
    expect(counter.dataset.ieFor).toBe(textarea.id);
    expect(counter.getAttribute("aria-live")).toBe("polite");
    expect(counter.textContent).toBe("4 / 100");
    const helptextButtons = root.querySelectorAll("[data-ie-document-fields] [data-ie-helptext]");
    expect(helptextButtons).toHaveLength(2);
    const titleHelptext = root.querySelector('[data-ie-helptext][data-ie-for$="-title"]');
    expect(titleHelptext.dataset.bsTitle).toBe("Title");
    expect(titleHelptext.dataset.bsContent).toBe("Title help");
    const bodytextHelptext = root.querySelector(`[data-ie-helptext][data-ie-for="${textarea.id}"]`);
    expect(bodytextHelptext.dataset.bsContent).toBe("Description help");
    expect(globalThis.bootstrap.Popover).toHaveBeenCalledTimes(2);
    expect(root.querySelector("[data-ie-document-modal-title]").textContent)
      .toBe("Add: Publications");
    const request = JSON.parse(globalThis.fetch.mock.calls[0][1].body);
    expect(request.data).toEqual({ section: "publications", record: 0, mode: "add" });
  });
  test("renders configured date requirements and compact checkbox in one responsive row", async () => {
    const root = createRoot();
    initializeDocumentSections(root);
    globalThis.fetch.mockResolvedValueOnce(response({
      success: true,
      record: null,
      fields: configuredDateFields(),
    }));
    root.querySelector("[data-ie-document-add]").click();
    await flushPromises();
    const year = root.querySelector('input[name="year"]');
    const yearStart = root.querySelector('input[name="yearStart"]');
    const yearEnd = root.querySelector('input[name="yearEnd"]');
    const yearOnly = root.querySelector('input[name="yearOnly"]');
    expect(year.required).toBe(true);
    expect(yearStart.required).toBe(false);
    expect(yearEnd.required).toBe(false);
    expect(year.closest("div.col-md-3").querySelector("label .text-danger").textContent).toBe("*");
    expect(yearStart.closest("div.col-md-3").querySelector("label .text-danger")).toBeNull();
    expect(yearEnd.closest("div.col-md-3").querySelector("label .text-danger")).toBeNull();
    const dateColumns = root.querySelectorAll("[data-ie-document-fields] > .col-12.col-md-3");
    expect(dateColumns).toHaveLength(4);
    const formCheck = yearOnly.parentElement;
    expect(formCheck.classList.contains("form-check")).toBe(true);
    expect(formCheck.classList.contains("mt-auto")).toBe(true);
    expect(formCheck.parentElement.classList.contains("d-flex")).toBe(true);
    expect(formCheck.querySelector("label").htmlFor).toBe(yearOnly.id);
    const yearOnlyHelptext = formCheck.querySelector("[data-ie-helptext]");
    expect(yearOnlyHelptext.dataset.ieFor).toBe(yearOnly.id);
    expect(yearOnlyHelptext.dataset.bsContent).toBe("Year only help");
    expect(year.closest("div.col-md-3").querySelector("[data-ie-helptext]").dataset.bsContent)
      .toBe("Date help");
    expect(formCheck.querySelector('[data-ie-document-field-error="yearOnly"]')).not.toBeNull();
  });
  test("creates, edits, sorts, views and deletes records through JSON endpoints", async () => {
    const root = createRoot();
    modalInstance.hide.mockImplementation(() => {
      expect(root.querySelector("[data-ie-document-modal]").hasAttribute("aria-busy")).toBe(false);
    });
    initializeDocumentSections(root);
    const section = root.querySelector("[data-ie-document-section]");
    globalThis.fetch
      .mockResolvedValueOnce(response({ success: true, record: null, fields: publicationFields("") }))
      .mockResolvedValueOnce(response({
        success: true,
        item: {
          uid: 3,
          sorting: 30,
          values: { title: "Third", year: 2025, link: "", bodytext: "<p>Text</p>" },
          display: { title: "Third", year: "2025", link: "", bodytext: "<p>Text</p>" },
        },
      }))
      .mockResolvedValueOnce(response({ success: true, changed: true, order: [2, 1, 3] }))
      .mockResolvedValueOnce(response({ success: true, record: 2, fields: publicationFields("Second") }))
      .mockResolvedValueOnce(response({
        success: true,
        item: {
          uid: 2,
          sorting: 10,
          values: { title: "Second updated", year: 2025, link: "", bodytext: "<p>Text</p>" },
          display: { title: "Second updated", year: "2025", link: "", bodytext: "<p>Text</p>" },
        },
      }))
      .mockResolvedValueOnce(response({ success: true, record: 2, fields: publicationFields("Second updated") }))
      .mockResolvedValueOnce(response({ success: true, record: 2, fields: publicationFields("Second updated") }))
      .mockResolvedValueOnce(response({ success: true, deleted: 2 }));
    section.querySelector("[data-ie-document-add]").click();
    await flushPromises();
    const form = root.querySelector("[data-ie-document-form]");
    const modalTitle = root.querySelector("[data-ie-document-modal-title]");
    expect(modalTitle.textContent).toBe("Add: Publications");
    form.elements.namedItem("title").value = "Third";
    form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
    await flushPromises();
    expect(section.querySelectorAll("[data-ie-document-item]")).toHaveLength(3);
    expect(section.querySelector('[data-item-uid="3"] [data-ie-document-title]').textContent).toBe("Third");
    section.querySelector('[data-item-uid="1"] [data-ie-document-sort="down"]').click();
    await flushPromises();
    expect(getDocumentOrder(section)).toEqual([2, 1, 3]);
    section.querySelector('[data-item-uid="2"] [data-ie-document-edit]').click();
    await flushPromises();
    expect(modalTitle.textContent).toBe("Edit: Second");
    form.elements.namedItem("title").value = "Second updated";
    form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
    await flushPromises();
    expect(section.querySelector('[data-item-uid="2"] [data-ie-document-title]').textContent).toBe("Second updated");
    section.querySelector('[data-item-uid="2"] [data-ie-document-view]').click();
    await flushPromises();
    expect(modalTitle.textContent).toBe("View: Second updated");
    expect(root.querySelector("[data-ie-document-submit]").classList.contains("d-none")).toBe(true);
    expect(root.querySelector("[data-ie-document-fields]").textContent).toContain("Second updated");
    const viewHelptext = root.querySelector("[data-ie-document-fields] dt [data-ie-helptext]");
    expect(viewHelptext.dataset.bsTitle).toBe("Title");
    expect(viewHelptext.dataset.bsContent).toBe("Title help");
    section.querySelector('[data-item-uid="2"] [data-ie-document-delete]').click();
    await flushPromises();
    expect(modalTitle.textContent).toBe("Delete: Second updated");
    expect(form.querySelector("[data-ie-document-submit]").classList.contains("btn-danger"))
      .toBe(true);
    expect(form.querySelector("[data-ie-document-submit]").classList.contains("btn-primary"))
      .toBe(false);
    expect(form.querySelector("[data-ie-document-submit]").classList.contains("btn-success"))
      .toBe(false);
    form.dispatchEvent(new Event("submit", { bubbles: true, cancelable: true }));
    await flushPromises();
    expect(section.querySelector('[data-item-uid="2"]')).toBeNull();
    expect(modalInstance.hide).toHaveBeenCalledTimes(3);
    const createRequest = JSON.parse(globalThis.fetch.mock.calls[1][1].body);
    expect(createRequest).toEqual({
      profile: 9,
      data: {
        section: "publications",
        fields: { title: "Third", year: "2025", bodytext: "<p>Text</p>" },
      },
    });
  });
  test("persists an exact row order after drag and drop", async () => {
    const root = createRoot();
    initializeDocumentSections(root);
    const section = root.querySelector("[data-ie-document-section]");
    const items = section.querySelector("[data-ie-document-items]");
    globalThis.fetch.mockResolvedValueOnce(response({ success: true, changed: true, order: [2, 1] }));
    const firstRow = section.querySelector('[data-item-uid="1"]');
    const firstHandle = section.querySelector('[data-item-uid="1"] [data-ie-document-drag]');
    const secondRow = section.querySelector('[data-item-uid="2"]');
    jest.spyOn(firstRow, "getBoundingClientRect").mockReturnValue({
      top: 0,
      right: 320,
      bottom: 80,
      left: 0,
      width: 320,
      height: 80,
      x: 0,
      y: 0,
      toJSON: () => ({}),
    });
    jest.spyOn(secondRow, "getBoundingClientRect").mockReturnValue({
      top: 100,
      right: 320,
      bottom: 180,
      left: 0,
      width: 320,
      height: 80,
      x: 0,
      y: 100,
      toJSON: () => ({}),
    });
    const unrelatedDragEvent = dispatchDragEvent(root.querySelector("h2"), "dragstart");
    expect(unrelatedDragEvent.defaultPrevented).toBe(false);
    const dragStartEvent = dispatchDragEvent(firstHandle, "dragstart", { clientX: 280, clientY: 40 });
    expect(dragStartEvent.dataTransfer.setDragImage).toHaveBeenCalledWith(firstRow, 280, 40);
    expect(firstRow.classList.contains("is-dragging")).toBe(true);
    expect(items.classList.contains("is-drag-active")).toBe(true);
    dispatchDragEvent(items, "dragover");
    expect(items.classList.contains("is-drop-at-end")).toBe(true);
    const dropBeforeEvent = dispatchDragEvent(secondRow, "dragover", { clientY: 110 });
    expect(dropBeforeEvent.defaultPrevented).toBe(true);
    expect(items.classList.contains("is-drop-at-end")).toBe(false);
    expect(secondRow.classList.contains("is-drop-before")).toBe(true);
    expect(secondRow.classList.contains("is-drop-after")).toBe(false);
    dispatchDragEvent(secondRow, "dragover", { clientY: 170 });
    expect(secondRow.classList.contains("is-drop-before")).toBe(false);
    expect(secondRow.classList.contains("is-drop-after")).toBe(true);
    const dropEvent = dispatchDragEvent(secondRow, "drop", { clientY: 170 });
    await flushPromises();
    expect(dropEvent.defaultPrevented).toBe(true);
    expect(getDocumentOrder(section)).toEqual([2, 1]);
    expect(firstRow.classList.contains("is-dragging")).toBe(false);
    expect(secondRow.classList.contains("is-drop-after")).toBe(false);
    expect(items.classList.contains("is-drag-active")).toBe(false);
    const sortRequest = JSON.parse(globalThis.fetch.mock.calls[0][1].body);
    expect(sortRequest).toEqual({
      profile: 9,
      data: { section: "publications", order: [2, 1] },
    });
  });
  test("applies an explicit order without losing rows", () => {
    const root = createRoot();
    const section = root.querySelector("[data-ie-document-section]");
    applyDocumentOrder(section, [2, 1]);
    expect(getDocumentOrder(section)).toEqual([2, 1]);
  });
});
