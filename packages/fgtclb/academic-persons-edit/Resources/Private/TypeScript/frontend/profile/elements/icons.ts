/**
 * The one icon lookup both `LitElement`s of this editor use.
 *
 * `<core:icon>` resolves an identifier through the icon registry, which knows
 * the set this extension registers and whatever a site overrode - and a browser
 * can ask neither. So `Templates/Profile/Index.html` renders one
 * `<template data-pe-icon="...">` per icon a browser rendered editor draws, and
 * an element clones the one it needs from there. Copying the markup into
 * TypeScript would fork the icon set at the first change.
 *
 * A module of its own rather than a private method in each element: the
 * document editor draws one icon and the contract contacts draw six, and two
 * copies of the same eight lines is how the two drift apart. It imports nothing
 * but `lit`'s `nothing` sentinel and a type, so it adds no edge to the module
 * graph either element would have to reason about.
 */
import { nothing } from "lit";
import type { EditingContext } from "@fgtclb/academic-persons-edit/frontend/profile/context.js";

/**
 * The content of `<template data-pe-icon="{name}">`, imported into the current
 * document, or `nothing` when the template is absent.
 *
 * A fresh `DocumentFragment` per call, which is what `document.importNode()`
 * has to return - a template's own content is one node set and would be moved
 * rather than copied. Callers wrap the call in `guard()` so that a re-render
 * does not replace an icon that has not changed.
 */
export const editingIcon = (
  context: EditingContext | null,
  name: string,
): Node | typeof nothing => {
  const template = context?.root.querySelector<HTMLTemplateElement>(
    `template[data-pe-icon="${CSS.escape(name)}"]`,
  );

  return template === null || template === undefined
    ? nothing
    : document.importNode(template.content, true);
};
