import { Bold, Italic } from "@ckeditor/ckeditor5-basic-styles";
import { ClassicEditor } from "@ckeditor/ckeditor5-editor-classic";
import { Essentials } from "@ckeditor/ckeditor5-essentials";
import { Link } from "@ckeditor/ckeditor5-link";
import { List } from "@ckeditor/ckeditor5-list";
import { Paragraph } from "@ckeditor/ckeditor5-paragraph";
import { showStatus } from "./common.js";

const richTextFieldSelector = "[data-ie-rich-text]";
const richTextPreviewSelector = "[data-ie-rich-text-preview]";
const richTextPreviewContentSelector = "[data-ie-rich-text-preview-content]";
const richTextEditors = new WeakMap();
const richTextEditorPromises = new WeakMap();
const richTextInitialValues = new WeakMap();
const richTextAcceptedValues = new WeakMap();
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

export const isRichTextField = (field) => field.matches(richTextFieldSelector);

export const getRichTextEditorValue = (field) => {
  const editor = richTextEditors.get(field);
  if (!editor) {
    return null;
  }
  const value = editor.getData();
  field.value = value;
  return value;
};

export const setRichTextEditorValue = (field, value) => {
  const editor = richTextEditors.get(field);
  richTextAcceptedValues.set(field, value);
  field.value = value;
  if (editor && editor.getData() !== value) {
    editor.setData(value);
  }
  updateRichTextCharacterCounter(field.getRootNode(), field, value);
};

export const getRichTextInitialValue = (field) => richTextInitialValues.get(field);

export const getPlainText = (value) => {
  const parsedDocument = new DOMParser().parseFromString(value, "text/html");
  return (parsedDocument.body.textContent ?? "")
    .replaceAll("\u00a0", " ")
    .replace(/\s+/g, " ")
    .trim();
};

export const countRichTextCharacters = (value) => Array.from(getPlainText(value)).length;

export const getRichTextCharacterLimit = (field) => {
  const configuredLimit = field.dataset.ieCharacterLimit?.trim() ?? "";
  if (!/^\d+$/.test(configuredLimit)) {
    return 0;
  }
  const limit = Number.parseInt(configuredLimit, 10);
  return Number.isSafeInteger(limit) && limit > 0 ? limit : 0;
};

export const updateRichTextCharacterCounter = (root, field, value) => {
  const limit = getRichTextCharacterLimit(field);
  if (limit === 0 || !field.id) {
    return;
  }
  const counter = root.querySelector(
    `[data-ie-character-counter][data-ie-for="${CSS.escape(field.id)}"]`,
  );
  if (!(counter instanceof HTMLElement)) {
    return;
  }
  const count = countRichTextCharacters(value);
  counter.textContent = `${count} / ${limit}`;
  counter.classList.toggle("text-danger", count > limit);
};

export const isAllowedRichTextLink = (value) => {
  const normalizedValue = value.trim();
  if (normalizedValue === "" || normalizedValue.startsWith("//")) {
    return false;
  }
  const scheme = normalizedValue.match(/^([a-z][a-z\d+.-]*):/i)?.[1];
  return scheme === undefined || allowedRichTextLinkSchemes.has(scheme.toLowerCase());
};

export const parseRichTextPreview = (value) => {
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

export const renderRichTextPreview = (root, field, value) => {
  if (!field.id) {
    return;
  }
  const preview = root.querySelector(
    `${richTextPreviewSelector}[data-ie-for="${CSS.escape(field.id)}"]`,
  );
  const content = preview?.querySelector(richTextPreviewContentSelector);
  if (!(preview instanceof HTMLElement) || !(content instanceof HTMLElement)) {
    return;
  }
  const normalizedValue = value === null || value === undefined ? "" : String(value);
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

export const ensureRichTextEditor = (root, field) => {
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
      const initialValue = createdEditor.getData();
      richTextInitialValues.set(field, initialValue);
      richTextAcceptedValues.set(field, initialValue);
      updateRichTextCharacterCounter(root, field, initialValue);
      createdEditor.model.document.on("change:data", () => {
        const value = createdEditor.getData();
        const acceptedValue = richTextAcceptedValues.get(field) ?? "";
        const limit = getRichTextCharacterLimit(field);
        const count = countRichTextCharacters(value);
        const acceptedCount = countRichTextCharacters(acceptedValue);
        if (limit > 0 && count > limit && count >= acceptedCount) {
          if (value !== acceptedValue) {
            createdEditor.setData(acceptedValue);
          }
          field.value = acceptedValue;
          updateRichTextCharacterCounter(root, field, acceptedValue);
          return;
        }
        richTextAcceptedValues.set(field, value);
        field.value = value;
        updateRichTextCharacterCounter(root, field, value);
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
