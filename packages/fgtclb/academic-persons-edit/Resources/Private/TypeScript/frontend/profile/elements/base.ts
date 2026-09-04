/**
 * `ProfileEditingElement` - the base class of every custom element of the
 * profile editor, and the mechanism that keeps lit-html away from the markup
 * Fluid rendered.
 *
 * ## The elements control markup, they do not produce it
 *
 * Every editor of this extension is a *controller over server rendered
 * markup*. Fluid renders the frame, the field rows, the panels and the
 * `<template data-pe-proto>` prototypes; an element clones a prototype, fills
 * its slots and toggles what the state changed. An integrator therefore
 * overrides a Fluid partial, never a JavaScript module. That is the constraint
 * this base class exists to make structural rather than a matter of care.
 *
 * ## Why `LitElement` at all
 *
 * None of the five elements renders markup, so the render pipeline of
 * `LitElement` produces nothing. What is kept is everything around it:
 * `static properties` with their accessors, the batching of several property
 * writes into one DOM pass, `changedProperties` in `willUpdate()`/`updated()`,
 * `firstUpdated()`, `updateComplete` and `requestUpdate()`. The document
 * editor is the element that needs all of it - a keystroke must not rebuild a
 * panel that holds a live CKEditor, and `changedProperties` is what separates
 * a structural change from a value change.
 *
 * ## The trap, verified against the sources both cores ship
 *
 * Read from `EXT:core/Resources/Public/JavaScript/Contrib/{lit,lit-element,
 * lit-html,@lit/reactive-element}`, byte identical on TYPO3 13.4.34 and
 * 14.3.6 (lit 3.2.0, lit-element 4.1.0, @lit/reactive-element 2.0.4):
 *
 * - `LitElement.update()` ends in
 *   `this.__childPart = render(value, this.renderRoot, this.renderOptions)`.
 *   *Every* update reaches lit-html, whatever `render()` returned.
 * - `LitElement.createRenderRoot()` calls `ReactiveElement`'s - which is
 *   `this.shadowRoot ?? this.attachShadow(...)` - and then sets
 *   `renderOptions.renderBefore ??= renderRoot.firstChild`.
 * - `ReactiveElement.connectedCallback()` is
 *   `this.renderRoot ??= this.createRenderRoot(); this.enableUpdating(true)`,
 *   so the render root is fixed at the first connection.
 * - lit-html's `render()` inserts a `document.createComment("")` marker into
 *   the container before `renderBefore ?? null` and stores the part on
 *   `container._$litPart$`. `ChildPart._$clear()` removes everything from
 *   `startNode.nextSibling` up to `endNode`.
 *
 * So `createRenderRoot() { return this; }` with a `render()` that only ever
 * returns `noChange` happens to leave Fluid's children alone today - but only
 * because of three lit-html internals, none of them a documented API, and it
 * breaks the moment either the element is upgraded before its children are
 * parsed (the marker lands first, the children land inside the part's range,
 * and the first committed value removes them) or somebody returns a template
 * from `render()`.
 *
 * ## The mechanism
 *
 * `createRenderRoot()` returns a fresh `<div>` that is never inserted
 * anywhere. lit-html then renders into a detached node: no marker enters the
 * element, `element._$litPart$` is never set, no shadow root is attached, and
 * a `render()` that returns a template is a silent no-op instead of a
 * destroyed form. The children below an element are Fluid's, structurally.
 *
 * Rejected: `createRenderRoot() { return this; }` plus `render()` returning
 * `noChange` - safe today, by internals, and its pinning test could only
 * assert the *shape* of `render()`, which is a test that cannot fail for a
 * behavioural regression. Also rejected: overriding `update()` to call
 * `ReactiveElement.prototype.update` directly, which does not type check -
 * `update` is `protected` (TS 2446).
 *
 * Pinned by `Tests/JavaScript/lit-lifecycle.test.ts`.
 */
import { LitElement } from "lit";

/**
 * The base class of `elements/root.ts`, `elements/image-editor.ts`,
 * `elements/document-editor.ts`, `elements/contract-contacts.ts` and
 * `elements/rich-text.ts`.
 */
export abstract class ProfileEditingElement extends LitElement {
  /**
   * A detached `<div>`, never inserted into the document.
   *
   * Do not "modernise" this to `return this`. The whole file above says why,
   * and `lit-lifecycle.test.ts` fails in three places when it is changed.
   */
  protected override createRenderRoot(): HTMLElement {
    return document.createElement("div");
  }
}
