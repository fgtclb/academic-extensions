import { beforeEach, describe, expect, jest, test } from "@jest/globals";
import { ClassicEditor } from "./mocks/ckeditor-modules.js";
import * as fields from "../../JavaScript/frontend/profile/fields.js";

const flushPromises = () => new Promise((resolve) => setTimeout(resolve, 0));

const createRoot = () => {
  const root = document.createElement("section");
  root.dataset.profileUid = "1";
  root.dataset.updateUrl = "/profile/update";
  root.dataset.messageSaving = "Saving";
  root.dataset.messageUnchanged = "Unchanged";
  root.dataset.messageValidation = "Validation failed";
  root.dataset.messageErrorTitle = "Error";
  root.dataset.messageErrorMessage = "Failed";
  root.dataset.messageSuccessTitle = "Success";
  root.dataset.messageSuccessMessage = "Saved";
  root.dataset.messageInfoTitle = "Info";
  root.dataset.messageInfoMessage = "Working";
  root.dataset.messageWarningTitle = "Warning";
  root.innerHTML = `
    <h1 data-ie-profile-name data-ie-profile-name-field-ids="firstName gender"></h1>
    <button
      type="button"
      data-academic-persons-inline-edit-edit-all-btn
      data-ie-edit-all-label="Edit all"
      data-ie-close-all-label="Close all"
      aria-pressed="false"><span data-ie-edit-all-button-label>Edit all</span></button>
    <template data-ie-new-button-template>
      <button type="button" data-academic-persons-inline-edit-activate-btn><span data-ie-button-label></span></button>
    </template>
    <template data-ie-edit-button-template>
      <button type="button" data-academic-persons-inline-edit-activate-btn><span data-ie-button-label></span></button>
    </template>
    <form data-ie-fields-form>
      <div class="field-row" data-ie-field-wrapper>
        <div data-ie-field-preview data-ie-for="inline-profile-1-firstName" data-empty-label="Empty">
          <span data-ie-field-preview-content>Jane</span>
          <button type="button" data-academic-persons-inline-edit-activate-btn data-ie-for="inline-profile-1-firstName" aria-expanded="false"></button>
        </div>
        <div id="inline-profile-1-firstName-editor" class="d-none" data-ie-field-editor>
          <input id="inline-profile-1-firstName" name="profile[firstName]" value="Jane" class="academic-persons-inline-edit__field">
          <div class="invalid-feedback"></div>
        </div>
        <div class="d-none" data-ie-field-actions data-ie-for="inline-profile-1-firstName">
          <button type="button" data-ie-dismiss data-ie-for="inline-profile-1-firstName"></button>
          <button type="button" data-ie-cancel data-ie-for="inline-profile-1-firstName"></button>
          <button type="button" data-ie-save data-ie-for="inline-profile-1-firstName"></button>
        </div>
      </div>
      <div class="field-row" data-ie-field-wrapper>
        <div data-ie-field-preview data-ie-for="inline-profile-1-gender" data-empty-label="Empty">
          <span data-ie-field-preview-content>Female</span>
          <button type="button" data-academic-persons-inline-edit-activate-btn data-ie-for="inline-profile-1-gender" aria-expanded="false"></button>
        </div>
        <div id="inline-profile-1-gender-editor" class="d-none" data-ie-field-editor>
          <select id="inline-profile-1-gender" name="profile[gender]" class="academic-persons-inline-edit__field" data-ie-autosave-on-change>
            <option value="">---</option><option value="f" selected>Female</option><option value="m">Male</option>
          </select>
          <div class="invalid-feedback"></div>
          <button type="button" data-ie-autosave-undo data-ie-cancel data-ie-for="inline-profile-1-gender"></button>
        </div>
      </div>
      <div class="field-row" data-ie-field-wrapper>
        <div data-ie-field-preview data-ie-for="inline-profile-1-skipSync" data-empty-label="Empty">
          <span data-ie-field-preview-content>Enabled</span>
          <button type="button" data-academic-persons-inline-edit-activate-btn data-ie-for="inline-profile-1-skipSync" aria-expanded="false"></button>
        </div>
        <div id="inline-profile-1-skipSync-editor" class="d-none" data-ie-field-editor>
          <div class="form-check">
            <input id="inline-profile-1-skipSync" name="profile[skipSync]" type="checkbox"
              class="academic-persons-inline-edit__field" data-ie-autosave-on-change
              data-ie-checked-label="Disabled" data-ie-unchecked-label="Enabled">
            <div class="invalid-feedback"></div>
          </div>
          <button type="button" data-ie-autosave-undo data-ie-cancel data-ie-for="inline-profile-1-skipSync"></button>
        </div>
      </div>
      <div data-ie-field-group data-ie-field-ids="website websiteTitle" data-ie-display-field-ids="websiteTitle website" data-ie-display-mode="first">
        <div data-ie-group-preview><span data-ie-group-preview-content data-empty-label="No website"></span></div>
        <button type="button" data-ie-group-edit aria-expanded="false"></button>
        <div class="d-none" data-ie-group-editor>
          <div data-ie-group-control>
            <input id="inline-profile-1-website" name="profile[website]" value="https://example.test" class="academic-persons-inline-edit__field">
            <div class="invalid-feedback"></div>
          </div>
          <div data-ie-group-control>
            <input id="inline-profile-1-websiteTitle" name="profile[websiteTitle]" value="Website" class="academic-persons-inline-edit__field">
            <div class="invalid-feedback"></div>
          </div>
          <button type="button" data-ie-group-dismiss></button>
          <button type="button" data-ie-group-cancel></button>
          <button type="button" data-ie-group-save></button>
        </div>
      </div>
      <div data-ie-field-wrapper>
        <div data-ie-rich-text-preview data-ie-for="inline-profile-1-biography" data-empty-label="No content">
          <div data-ie-rich-text-preview-content></div>
          <button type="button" data-academic-persons-inline-edit-activate-btn data-ie-for="inline-profile-1-biography"></button>
        </div>
        <div id="inline-profile-1-biography-editor" class="d-none" data-ie-field-editor data-ie-editor-container>
          <textarea id="inline-profile-1-biography" name="profile[biography]" class="academic-persons-inline-edit__field" data-ie-rich-text><p>Stored</p></textarea>
          <div class="invalid-feedback"></div>
        </div>
        <div class="d-none" data-ie-field-actions data-ie-for="inline-profile-1-biography">
          <button type="button" data-ie-save data-ie-for="inline-profile-1-biography"></button>
        </div>
      </div>
    </form>
    <div class="d-none" data-ie-status-toast>
      <span class="status-title"></span><span class="status-message"></span>
    </div>
  `;
  document.body.append(root);
  return root;
};

const get = (root, identifier) =>
  root.querySelector(`#inline-profile-1-${CSS.escape(identifier)}`);

const click = (root, selector) => {
  const button = root.querySelector(selector);
  button.click();
  return button;
};

const response = (result, ok = true) => ({
  ok,
  json: jest.fn().mockResolvedValue(result),
});

const createEditor = (initialData = "<p>Stored</p>") => {
  let data = initialData;
  const listeners = new Map();
  return {
    editing: { view: { focus: jest.fn() } },
    getData: jest.fn(() => data),
    setData: jest.fn((value) => {
      data = value;
    }),
    model: {
      document: {
        on: jest.fn((event, listener) => listeners.set(event, listener)),
      },
    },
    emit: (event) => listeners.get(event)?.(),
  };
};

describe("profile/fields helpers", () => {
  beforeEach(() => {
    globalThis.fetch = jest.fn();
    ClassicEditor.create.mockReset();
  });

  test("reads, writes and formats every supported field type", () => {
    const root = createRoot();
    const firstName = get(root, "firstName");
    const gender = get(root, "gender");
    const skipSync = get(root, "skipSync");
    const biography = get(root, "biography");

    expect(fields.getFieldValue(firstName)).toBe("Jane");
    expect(fields.getFieldValue(skipSync)).toBe(false);
    fields.setFieldValue(firstName, null);
    fields.setFieldValue(skipSync, 1);
    expect(firstName.value).toBe("");
    expect(skipSync.checked).toBe(true);

    expect(fields.getFieldDisplayValue(firstName, " Name ")).toBe("Name");
    expect(fields.getFieldDisplayValue(skipSync, true)).toBe("Disabled");
    expect(fields.getFieldDisplayValue(skipSync, false)).toBe("Enabled");
    expect(fields.getFieldDisplayValue(gender, "f")).toBe("Female");
    expect(fields.getFieldDisplayValue(biography, "<p>Rich <strong>text</strong></p>"))
      .toBe("Rich text");
    expect(fields.getFieldPropertyName(firstName)).toBe("firstName");
    firstName.name = "plainName";
    expect(fields.getFieldPropertyName(firstName)).toBe("plainName");
  });

  test("resolves controls, previews, buttons, ids and edit containers", () => {
    const root = createRoot();
    const firstName = get(root, "firstName");
    const website = get(root, "website");
    const group = website.closest("[data-ie-field-group]");
    expect(fields.getFieldById(root, "firstName")).toBe(firstName);
    expect(fields.getFieldById(root, firstName.id)).toBe(firstName);
    expect(fields.getFieldById(root, "missing")).toBeNull();
    expect(fields.getFieldById(root, "")).toBeNull();
    expect(fields.getActivateButton(root, firstName)).toBeInstanceOf(HTMLButtonElement);
    expect(fields.getFieldPreview(root, firstName)).toBeInstanceOf(HTMLElement);

    const withoutId = document.createElement("input");
    expect(fields.getActivateButton(root, withoutId)).toBeNull();
    expect(fields.getFieldPreview(root, withoutId)).toBeNull();
    expect(fields.parseFieldIds(" firstName   gender ")).toEqual(["firstName", "gender"]);
    expect(fields.parseFieldIds(null)).toEqual([]);
    expect(fields.getFieldsByIds(root, "firstName missing gender"))
      .toEqual([firstName, get(root, "gender")]);
    expect(fields.getGroupFields(root, group)).toEqual([
      website,
      get(root, "websiteTitle"),
    ]);
    expect(fields.getFieldEditElement(website)).toBe(group.querySelector("[data-ie-group-editor]"));
    expect(fields.getFieldEditElement(firstName).id).toBe(`${firstName.id}-editor`);
  });

  test("renders profile names and grouped previews", () => {
    const root = createRoot();
    fields.renderProfileName(root);
    expect(root.querySelector("[data-ie-profile-name]").textContent).toBe("Jane Female");

    const group = root.querySelector("[data-ie-field-group]");
    fields.renderFieldGroupPreview(root, group);
    const content = group.querySelector("[data-ie-group-preview-content]");
    expect(content.textContent).toBe("Website");
    expect(content.classList.contains("text-body-secondary")).toBe(false);

    group.dataset.ieDisplayMode = "joined";
    fields.setFieldValue(get(root, "websiteTitle"), "");
    fields.setFieldValue(get(root, "website"), "");
    fields.renderFieldGroupPreview(root, group);
    expect(content.textContent).toBe("No website");
    expect(content.classList.contains("text-body-secondary")).toBe(true);

    root.querySelector("[data-ie-profile-name]").remove();
    expect(() => fields.renderProfileName(root)).not.toThrow();
    content.remove();
    expect(() => fields.renderFieldGroupPreview(root, group)).not.toThrow();
  });

  test("creates and replaces activate buttons and renders ordinary previews", () => {
    const root = createRoot();
    const firstName = get(root, "firstName");
    const template = root.querySelector("[data-ie-edit-button-template]");
    expect(fields.getTemplateButton(template)).toBeInstanceOf(HTMLButtonElement);
    expect(fields.getTemplateButton(document.createElement("div"))).toBeNull();

    const editButton = fields.createActivateButton(root, firstName, "Jane");
    expect(editButton.dataset.ieFor).toBe(firstName.id);
    expect(editButton.querySelector("[data-ie-button-label]").textContent).toBe("Jane");
    const newButton = fields.createActivateButton(root, firstName, "");
    expect(newButton.querySelector("[data-ie-button-label]").textContent).toBe("+");

    fields.renderActivateButton(root, firstName, "Janet");
    expect(root.querySelector('[data-ie-field-preview-content]').textContent).toBe("Janet");

    const preview = fields.getFieldPreview(root, firstName);
    preview.querySelector("[data-ie-field-preview-content]").remove();
    const originalButton = fields.getActivateButton(root, firstName);
    originalButton.classList.add("d-none");
    fields.renderActivateButton(root, firstName, "Replacement");
    const replacement = fields.getActivateButton(root, firstName);
    expect(replacement).not.toBe(originalButton);
    expect(replacement.classList.contains("d-none")).toBe(true);

    root.querySelectorAll("template").forEach((element) => element.remove());
    expect(fields.createActivateButton(root, firstName, "x")).toBeNull();
    expect(() => fields.renderActivateButton(root, firstName, "x")).not.toThrow();
  });

  test("opens, closes and validates individual and grouped fields", () => {
    const root = createRoot();
    const firstName = get(root, "firstName");
    const group = root.querySelector("[data-ie-field-group]");

    fields.toggleEditField(root, firstName.id, true);
    expect(fields.getFieldEditElement(firstName).classList.contains("d-none")).toBe(false);
    expect(fields.getFieldPreview(root, firstName).classList.contains("d-none")).toBe(true);
    expect(document.activeElement).toBe(firstName);
    fields.toggleEditField(root, firstName.id, false);
    expect(fields.getFieldEditElement(firstName).classList.contains("d-none")).toBe(true);

    fields.toggleEditGroup(root, group, true);
    expect(group.querySelector("[data-ie-group-editor]").classList.contains("d-none"))
      .toBe(false);
    expect(group.querySelector("[data-ie-group-edit]").getAttribute("aria-expanded"))
      .toBe("true");
    fields.closeFields(root, [get(root, "website"), firstName]);
    expect(group.querySelector("[data-ie-group-editor]").classList.contains("d-none"))
      .toBe(true);

    firstName.classList.add("is-invalid");
    fields.getFieldEditElement(firstName).classList.add("is-invalid");
    fields.clearValidationErrors([firstName]);
    expect(firstName.classList.contains("is-invalid")).toBe(false);
    fields.showValidationErrors(root, [firstName], {
      "profile.firstName": ["Required", "Too short"],
      unknown: "Ignored",
    });
    expect(firstName.classList.contains("is-invalid")).toBe(true);
    expect(firstName.closest("[data-ie-field-wrapper]").querySelector(".invalid-feedback").textContent)
      .toBe("Required Too short");

    firstName.disabled = true;
    fields.toggleEditField(root, firstName.id, true);
    firstName.disabled = false;
    get(root, "website").disabled = true;
    get(root, "websiteTitle").disabled = true;
    expect(() => fields.toggleEditGroup(root, group, true)).not.toThrow();
  });

  test("updates edit-all button state", () => {
    const root = createRoot();
    const button = root.querySelector("[data-academic-persons-inline-edit-edit-all-btn]");
    fields.setEditAllButtonState(root, true);
    expect(button.classList.contains("active")).toBe(true);
    expect(button.getAttribute("aria-pressed")).toBe("true");
    expect(button.querySelector("[data-ie-edit-all-button-label]").textContent)
      .toBe("Close all");
    fields.setEditAllButtonState(root, false);
    expect(button.querySelector("[data-ie-edit-all-button-label]").textContent)
      .toBe("Edit all");
    button.remove();
    expect(() => fields.setEditAllButtonState(root, true)).not.toThrow();
  });
});

describe("profile/fields interactions", () => {
  beforeEach(() => {
    globalThis.fetch = jest.fn();
    ClassicEditor.create.mockReset();
    ClassicEditor.create.mockImplementation((field) =>
      Promise.resolve(createEditor(field.value)),
    );
  });

  test.each([
    ["gender", "m", "f"],
    ["skipSync", true, false],
  ])("undo restores and closes the %s editor", (identifier, draftValue, storedValue) => {
    const root = createRoot();
    fields.initializeFieldEditing(root);
    const field = get(root, identifier);
    click(root, `[data-academic-persons-inline-edit-activate-btn][data-ie-for="${field.id}"]`);
    expect(fields.getFieldEditElement(field).classList.contains("d-none")).toBe(false);

    fields.setFieldValue(field, draftValue);
    click(root, `[data-ie-autosave-undo][data-ie-for="${field.id}"]`);
    expect(fields.getFieldValue(field)).toBe(storedValue);
    expect(fields.getFieldEditElement(field).classList.contains("d-none")).toBe(true);
    expect(fields.getFieldPreview(root, field).classList.contains("d-none")).toBe(false);
    expect(globalThis.fetch).not.toHaveBeenCalled();
  });

  test("autosaves a select, refreshes its preview and uses the saved value as undo baseline", async () => {
    const root = createRoot();
    globalThis.fetch.mockResolvedValue(response({ success: true, data: { gender: "m" } }));
    fields.initializeFieldEditing(root);
    const gender = get(root, "gender");
    fields.toggleEditField(root, gender.id, true);
    gender.value = "m";
    gender.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();

    expect(globalThis.fetch).toHaveBeenCalledWith("/profile/update", {
      credentials: "same-origin",
      method: "POST",
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ profile: 1, data: { gender: "m" } }),
    });
    expect(fields.getFieldPreview(root, gender).querySelector("[data-ie-field-preview-content]").textContent)
      .toBe("Male");
    expect(fields.getFieldEditElement(gender).classList.contains("d-none")).toBe(true);

    fields.toggleEditField(root, gender.id, true);
    gender.value = "f";
    click(root, `[data-ie-autosave-undo][data-ie-for="${gender.id}"]`);
    expect(gender.value).toBe("m");
  });

  test("closes an unchanged autosave field without sending a request", async () => {
    const root = createRoot();
    fields.initializeFieldEditing(root);
    const gender = get(root, "gender");
    fields.toggleEditField(root, gender.id, true);
    gender.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();
    expect(globalThis.fetch).not.toHaveBeenCalled();
    expect(fields.getFieldEditElement(gender).classList.contains("d-none")).toBe(true);
    expect(root.querySelector(".status-message").textContent).toBe("Unchanged");
  });

  test("keeps invalid and server-rejected fields open with useful feedback", async () => {
    const root = createRoot();
    fields.initializeFieldEditing(root);
    const gender = get(root, "gender");
    jest.spyOn(gender, "checkValidity").mockReturnValue(false);
    jest.spyOn(gender, "reportValidity").mockImplementation(() => true);
    gender.value = "m";
    gender.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();
    expect(gender.reportValidity).toHaveBeenCalledTimes(1);
    expect(globalThis.fetch).not.toHaveBeenCalled();

    gender.checkValidity.mockReturnValue(true);
    globalThis.fetch.mockResolvedValue(response({
      success: false,
      errors: { gender: ["Select a valid gender"] },
    }, false));
    gender.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();
    expect(gender.classList.contains("is-invalid")).toBe(true);
    expect(gender.closest("[data-ie-field-wrapper]").querySelector(".invalid-feedback").textContent)
      .toBe("Select a valid gender");
    expect(fields.getFieldEditElement(gender).classList.contains("d-none")).toBe(false);
  });

  test("supports clear, cancel, save, group actions and edit-all", async () => {
    const root = createRoot();
    globalThis.fetch.mockResolvedValue(response({
      success: true,
      data: { firstName: "Janet", website: "", websiteTitle: "" },
    }));
    fields.initializeFieldEditing(root);
    const firstName = get(root, "firstName");
    const group = root.querySelector("[data-ie-field-group]");

    click(root, `[data-ie-dismiss][data-ie-for="${firstName.id}"]`);
    expect(firstName.value).toBe("");
    click(root, `[data-ie-cancel][data-ie-for="${firstName.id}"]`);
    expect(firstName.value).toBe("Jane");
    fields.toggleEditField(root, firstName.id, true);
    firstName.value = "Janet";
    click(root, `[data-ie-save][data-ie-for="${firstName.id}"]`);
    await flushPromises();
    expect(firstName.value).toBe("Janet");

    click(root, "[data-ie-group-edit]");
    click(root, "[data-ie-group-dismiss]");
    expect(get(root, "website").value).toBe("");
    click(root, "[data-ie-group-cancel]");
    expect(get(root, "website").value).toBe("https://example.test");
    click(root, "[data-ie-group-edit]");
    get(root, "website").value = "";
    get(root, "websiteTitle").value = "";
    click(root, "[data-ie-group-save]");
    await flushPromises();

    const editAll = click(root, "[data-academic-persons-inline-edit-edit-all-btn]");
    expect(editAll.getAttribute("aria-pressed")).toBe("true");
    editAll.click();
    expect(editAll.getAttribute("aria-pressed")).toBe("false");

    const form = root.querySelector("form");
    const submit = new Event("submit", { cancelable: true });
    const reset = new Event("reset", { cancelable: true });
    form.dispatchEvent(submit);
    form.dispatchEvent(reset);
    expect(submit.defaultPrevented).toBe(true);
    expect(reset.defaultPrevented).toBe(true);
  });

  test("initializes and focuses rich text while preserving its editor baseline", async () => {
    const root = createRoot();
    const editor = createEditor();
    ClassicEditor.create.mockResolvedValue(editor);
    globalThis.fetch.mockResolvedValue(response({
      success: true,
      data: { biography: "<p>Changed</p>" },
    }));
    fields.initializeFieldEditing(root);
    const biography = get(root, "biography");
    fields.toggleEditField(root, biography.id, true);
    await flushPromises();
    expect(editor.editing.view.focus).toHaveBeenCalled();
    editor.setData("<p>Changed</p>");
    editor.emit("change:data");
    click(root, `[data-ie-save][data-ie-for="${biography.id}"]`);
    await flushPromises();
    expect(root.querySelector("[data-ie-rich-text-preview-content]").textContent)
      .toBe("Changed");
  });

  test("reports missing update configuration and ignores incomplete roots", async () => {
    expect(() => fields.initializeFieldEditing(document.createElement("section"))).not.toThrow();
    const root = createRoot();
    delete root.dataset.updateUrl;
    fields.initializeFieldEditing(root);
    const gender = get(root, "gender");
    gender.value = "m";
    gender.dispatchEvent(new Event("change", { bubbles: true }));
    await flushPromises();
    expect(globalThis.fetch).not.toHaveBeenCalled();
    expect(root.querySelector("[data-ie-status-toast]").classList.contains("bg-danger"))
      .toBe(true);
  });
});
