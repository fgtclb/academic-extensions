import { beforeEach, describe, expect, jest, test } from "@jest/globals";
import { ClassicEditor } from "./mocks/ckeditor-modules.js";
import {
  countRichTextCharacters,
  ensureRichTextEditor,
  getPlainText,
  getRichTextCharacterLimit,
  getRichTextEditorValue,
  getRichTextInitialValue,
  isAllowedRichTextLink,
  isRichTextField,
  parseRichTextPreview,
  renderRichTextPreview,
  setRichTextEditorValue,
  updateRichTextCharacterCounter,
} from "../../JavaScript/frontend/profile/rich-text.js";

const createRoot = (
  fieldId = "inline-profile-1-biography",
  characterLimit = 0,
) => {
  const root = document.createElement("section");
  root.dataset.messageEditorError = "Editor unavailable";
  root.dataset.messageErrorTitle = "Error";
  root.dataset.messageErrorMessage = "Failed";
  root.innerHTML = `
    <textarea
      id="${fieldId}"
      data-ie-rich-text
      ${characterLimit > 0 ? `data-ie-character-limit="${characterLimit}"` : ""}></textarea>
    ${characterLimit > 0 ? `
      <div
        data-ie-character-counter
        data-ie-for="${fieldId}"
        aria-live="polite"></div>
    ` : ""}
    <div data-ie-rich-text-preview data-ie-for="${fieldId}" data-empty-label="No content">
      <div data-ie-rich-text-preview-content></div>
    </div>
    <div class="d-none" data-ie-status-toast>
      <span class="status-title"></span><span class="status-message"></span>
    </div>
  `;
  document.body.append(root);
  return {
    root,
    field: root.querySelector("textarea"),
    preview: root.querySelector("[data-ie-rich-text-preview]"),
    content: root.querySelector("[data-ie-rich-text-preview-content]"),
  };
};

const createEditor = (initialData = "<p>Initial</p>") => {
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
        on: jest.fn((eventName, listener) => listeners.set(eventName, listener)),
      },
    },
    emit: (eventName) => listeners.get(eventName)?.(),
  };
};

describe("profile/rich-text", () => {
  beforeEach(() => {
    ClassicEditor.create.mockReset();
  });

  test("recognizes rich-text fields and converts markup to normalized plain text", () => {
    const { field } = createRoot();
    expect(isRichTextField(field)).toBe(true);
    expect(isRichTextField(document.createElement("textarea"))).toBe(false);
    expect(getPlainText("<p>Hello&nbsp; <strong>world</strong></p>\n<p>Again</p>"))
      .toBe("Hello world Again");
    expect(countRichTextCharacters("<p>Grüße&nbsp;<strong>Welt</strong></p>"))
      .toBe(10);
  });

  test("reads positive limits and updates the visible character counter", () => {
    const { root, field } = createRoot("limited-field", 5);
    expect(getRichTextCharacterLimit(field)).toBe(5);
    updateRichTextCharacterCounter(root, field, "<p>123456</p>");
    const counter = root.querySelector("[data-ie-character-counter]");
    expect(counter.textContent).toBe("6 / 5");
    expect(counter.classList.contains("text-danger")).toBe(true);

    delete field.dataset.ieCharacterLimit;
    expect(getRichTextCharacterLimit(field)).toBe(0);
  });

  test.each([
    ["https://example.test", true],
    ["HTTP://example.test", true],
    ["mailto:test@example.test", true],
    ["tel:+49123", true],
    ["relative/path", true],
    [" javascript:alert(1)", false],
    ["//example.test", false],
    ["  ", false],
  ])("validates preview link %s", (value, expected) => {
    expect(isAllowedRichTextLink(value)).toBe(expected);
  });

  test("sanitizes preview markup without using executable HTML", () => {
    const parsed = parseRichTextPreview(`
      <p class="drop">Hello <span>wrapped</span></p>
      <script>alert(1)</script><svg><circle /></svg>
      <a href="https://example.test" target="_blank" onclick="bad()">safe</a>
      <a href="javascript:bad()">unsafe</a>
    `);

    expect(parsed.body.querySelector("script, svg, span")).toBeNull();
    expect(parsed.body.querySelector("p").hasAttribute("class")).toBe(false);
    const links = parsed.body.querySelectorAll("a");
    expect(links[0].getAttribute("href")).toBe("https://example.test");
    expect(links[0].hasAttribute("target")).toBe(false);
    expect(links[0].hasAttribute("onclick")).toBe(false);
    expect(links[1].hasAttribute("href")).toBe(false);
  });

  test("renders empty and sanitized rich-text previews", () => {
    const { root, field, content } = createRoot();
    renderRichTextPreview(root, field, "<p>&nbsp;</p>");
    expect(content.textContent).toBe("No content");
    expect(content.firstElementChild.classList.contains("text-body-secondary")).toBe(true);

    renderRichTextPreview(root, field, "<p><strong>Visible</strong><script>bad()</script></p>");
    expect(content.innerHTML).toBe("<p><strong>Visible</strong></p>");

    field.removeAttribute("id");
    expect(() => renderRichTextPreview(root, field, "ignored")).not.toThrow();
    field.id = "missing-preview";
    expect(() => renderRichTextPreview(root, field, "ignored")).not.toThrow();
  });

  test("creates one editor, synchronizes values and exposes its baseline", async () => {
    const { root, field } = createRoot();
    const editor = createEditor();
    ClassicEditor.create.mockResolvedValue(editor);
    const inputListener = jest.fn();
    field.addEventListener("input", inputListener);

    const firstPromise = ensureRichTextEditor(root, field);
    const secondPromise = ensureRichTextEditor(root, field);
    expect(secondPromise).toBe(firstPromise);
    await expect(firstPromise).resolves.toBe(editor);
    expect(ClassicEditor.create).toHaveBeenCalledTimes(1);
    expect(getRichTextInitialValue(field)).toBe("<p>Initial</p>");

    editor.emit("change:data");
    expect(field.value).toBe("<p>Initial</p>");
    expect(inputListener).toHaveBeenCalledTimes(1);
    expect(getRichTextEditorValue(field)).toBe("<p>Initial</p>");

    setRichTextEditorValue(field, "<p>Changed</p>");
    expect(editor.setData).toHaveBeenCalledWith("<p>Changed</p>");
    setRichTextEditorValue(field, "<p>Changed</p>");
    expect(editor.setData).toHaveBeenCalledTimes(1);
    await expect(ensureRichTextEditor(root, field)).resolves.toBe(editor);
  });

  test("keeps CKEditor at the configured visible character limit", async () => {
    const { root, field } = createRoot("limited-field", 5);
    const editor = createEditor("<p>1234</p>");
    ClassicEditor.create.mockResolvedValue(editor);
    const inputListener = jest.fn();
    field.addEventListener("input", inputListener);
    await ensureRichTextEditor(root, field);
    expect(root.querySelector("[data-ie-character-counter]").textContent).toBe("4 / 5");

    editor.setData("<p><strong>12345</strong></p>");
    editor.emit("change:data");
    expect(field.value).toBe("<p><strong>12345</strong></p>");
    expect(root.querySelector("[data-ie-character-counter]").textContent).toBe("5 / 5");

    editor.setData("<p>123456</p>");
    editor.emit("change:data");
    expect(editor.getData()).toBe("<p><strong>12345</strong></p>");
    expect(field.value).toBe("<p><strong>12345</strong></p>");
    expect(root.querySelector("[data-ie-character-counter]").textContent).toBe("5 / 5");
    expect(inputListener).toHaveBeenCalledTimes(1);
  });

  test("allows existing over-limit content to be shortened", async () => {
    const { root, field } = createRoot("legacy-limited-field", 5);
    const editor = createEditor("<p>1234567</p>");
    ClassicEditor.create.mockResolvedValue(editor);
    await ensureRichTextEditor(root, field);
    expect(root.querySelector("[data-ie-character-counter]").textContent).toBe("7 / 5");

    editor.setData("<p>123456</p>");
    editor.emit("change:data");
    expect(field.value).toBe("<p>123456</p>");
    expect(root.querySelector("[data-ie-character-counter]").textContent).toBe("6 / 5");
  });

  test("returns null for unavailable or locked editors", async () => {
    const plain = document.createElement("textarea");
    expect(getRichTextEditorValue(plain)).toBeNull();
    expect(getRichTextInitialValue(plain)).toBeUndefined();
    expect(() => setRichTextEditorValue(plain, "value")).not.toThrow();
    await expect(ensureRichTextEditor(document.body, plain)).resolves.toBeNull();

    const { root, field } = createRoot();
    field.disabled = true;
    await expect(ensureRichTextEditor(root, field)).resolves.toBeNull();
    field.disabled = false;
    field.readOnly = true;
    await expect(ensureRichTextEditor(root, field)).resolves.toBeNull();
  });

  test("reports and rethrows editor creation failures", async () => {
    const { root, field } = createRoot();
    const failure = new Error("create failed");
    ClassicEditor.create.mockRejectedValueOnce(failure);

    await expect(ensureRichTextEditor(root, field)).rejects.toBe(failure);
    expect(root.querySelector(".status-message").textContent).toBe("Editor unavailable");
    expect(
      root.querySelector("[data-ie-status-toast]").classList.contains("bg-danger"),
    ).toBe(true);
  });
});
