/* Generated from Resources/Private/TypeScript — do not edit. */
export const editorConfig = {
  language: "en",
  height: 200,
  versionCheck: false,
  format_tags: "p",
  toolbarGroups: [
    { name: "basicstyles", groups: ["basicstyles"] },
    { name: "paragraph", groups: ["list"] },
    { name: "clipboard", groups: ["cleanup"] }
  ],
  customConfig: "",
  removeButtons: [
    "Strike",
    "Subscript",
    "Superscript"
  ]
};
export const getEditor = () => window.CKEDITOR;
export const initializeEditors = (scope = document, ckeditor = getEditor()) => {
  if (ckeditor === void 0) {
    return false;
  }
  scope.querySelectorAll(".rich-text").forEach((textarea) => {
    const identifier = textarea.getAttribute("id");
    if (identifier !== null) {
      ckeditor.replace(identifier, editorConfig);
    }
  });
  return true;
};
let waitForEditor;
export const pollForEditor = () => {
  const ckeditor = getEditor();
  if (!initializeEditors(document, ckeditor)) {
    return;
  }
  window.clearInterval(waitForEditor);
};
waitForEditor = window.setInterval(() => {
  pollForEditor();
}, 100);
