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
  if (editor && editor.getData() !== value) {
    editor.setData(value);
  }
};

export const getRichTextInitialValue = (field) => richTextInitialValues.get(field);

export const getPlainText = (value) => {
  const parsedDocument = new DOMParser().parseFromString(value, "text/html");
  return (parsedDocument.body.textContent ?? "")
    .replaceAll("\u00a0", " ")
    .replace(/\s+/g, " ")
    .trim();
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
