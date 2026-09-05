/* Generated from Resources/Private/TypeScript — do not edit. */
import { nothing } from "lit";
const editingIcon = (context, name) => {
  const template = context == null ? void 0 : context.root.querySelector(
    `template[data-pe-icon="${CSS.escape(name)}"]`
  );
  return template === null || template === void 0 ? nothing : document.importNode(template.content, true);
};
export {
  editingIcon
};
