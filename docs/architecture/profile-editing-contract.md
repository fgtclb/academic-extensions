# The profile editing contract

`academic_persons_edit` renders the profile editor server side and drives it
with TypeScript. Everything the TypeScript needs to know about *this* profile —
where to write, what to say, which image settings apply — crosses that boundary
as `data-*` attributes on one element, the plugin root of
[`Templates/Profile/Index.html`](../../packages/fgtclb/academic-persons-edit/Resources/Private/Templates/Profile/Index.html).

## What the root carries

| Group     | Count | Examples                                                      |
|-----------|-------|---------------------------------------------------------------|
| Endpoints | 13    | `data-update-url`, `data-sort-contract-contact-url`           |
| Profile   | 2     | `data-profile-uid`, `data-editor-language`                    |
| Image     | 5     | `data-has-image`, `data-image-cropper-ratio`                  |
| Messages  | 20    | `data-message-saving`, `data-message-document-delete-confirm` |
| Labels    | 9     | `data-label-document-add`, `data-label-sort-up`               |

The per-element hooks below the root — `data-pe-field-ids`, `data-pe-for`,
`data-pe-document-sort` and the rest — are a different vocabulary, read of
buttons, rows and previews rather than of the root, and typed separately as
`ProfileEditingHooks` in
[`profile/common.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/common.ts).

## It is read once

[`profile/context.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/context.ts)
is the only reader. `readEditingContext(root)` parses the attributes into a
frozen `EditingContext`, the entry point builds one per root at start-up, and
every controller is handed that object. **No module reads `root.dataset`
again** — the single exception is `data-has-image`, which the image controller
writes back so that the rendered markup keeps saying what it shows.

Three things follow:

- **A misspelled key is a type error.** `HTMLElement.dataset` is a
  `DOMStringMap`, so `root.dataset.messageSavng` compiles and reads `undefined`
  for good. The closed contract type in `context.ts` turns that into a
  `typecheckJs` failure, the same mechanism `ProfileEditingHooks` uses.
- **The coercions have one home.** Whether the profile uid is an integer,
  whether the editor language is trimmed, and what `data-has-image` means are
  decided in `context.ts` and nowhere else.
- **A component can be handed the contract.** An element below the root neither
  knows nor asks which element the attributes came from — which is what the Lit
  port needs, and why the reader exists before the elements do.

## Two rules that look like details and are not

**The strings are not defaulted by the reader.** A message arrives as
`string | undefined`, and each call site keeps the fallback it has:
`showStatus(context, "warning", context.messages.validation ?? null)` falls back
to the severity's own text, while `image.error = context.messages.validation ??
""` falls back to no text at all. Defaulting to `""` in the reader would
silently turn the first into the second.

**The context is a snapshot, taken when the editor is built.** An attribute
changed afterwards is not seen. Nothing in the rendered page changes one — Fluid
writes them once — but a test that wants a root without an endpoint has to build
the controller *after* removing it, not before.

## The element that owns the root

The template wraps the plugin root in
`<academic-persons-edit-profile-editing>`
([`profile/elements/root.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/root.ts)).
It renders nothing at all: Fluid renders the editor, the element takes that
markup as its light DOM children, reads the contract of the root below it once
and starts the editing modules. The `data-*` attributes stay where they were, on
the root, so every reader above keeps working unchanged.

What it replaces is a start-up scan for `[data-academic-persons-profile-editing]`
and a module level `WeakSet` of the roots that had already been mounted. The
custom element registry does that bookkeeping itself, and does more of it: an
editor rendered into the page later starts by itself, and an editor that is moved
in the document is not started twice.

Its public surface is small and is API from the moment it ships:

| Member                                    | Contract                                                                              |
|-------------------------------------------|---------------------------------------------------------------------------------------|
| `<academic-persons-edit-profile-editing>` | The tag name. It observes no attributes.                                              |
| `context`                                 | The frozen `EditingContext`, or `null` for an element that carries no root.           |
| `showStatus(type, message?)`              | Writes one of the two live regions — assertive for `danger`, polite for the rest.     |
| `pe:status`                               | The event a descendant dispatches, `{ type, message? }`, to have that written for it. |

**The prefix is the extension key**, `academic-persons-edit-`, with its
underscores replaced — the same token the icon identifiers and the import map
specifier use. A custom element name is global and has no scoping mechanism of
any kind, so the prefix has to be one this extension provably owns, and the
extension key is the only such token. Every element the profile editor adds
carries it.

The element defines itself with a plain `customElements.define()` and no
decorator, and the components below it declare `static properties` rather than
`@property`. The behavioural suite runs the TypeScript sources under node's type
stripping, which erases annotations but does not transform them, so a decorator
would not run — `Build/tsconfig.tests.json` sets `erasableSyntaxOnly` to make
that a type error instead of a runtime one.

## The element that owns the image editor

`<academic-persons-edit-image-editor>`
([`profile/elements/image-editor.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/image-editor.ts))
is the first of the editor's components, and it deliberately renders nothing
either. The editor it drives is an Extbase `<f:form>`: it carries the
`__trustedProperties` signature the property mapper validates the upload
against, and nothing in a browser can recompute it. A component that rendered
that form would have to reproduce a signature only the server can make, so the
form stays server rendered and the element is a controller over it.

The state, the cropper and every request stay in `profile/image.ts`, which is
driven and pinned without a registry. The element creates one controller, is
called back on every change it accepts, and writes what the twenty-seven Vue
directives of `Partials/Profile/Image/Editor.html` used to derive.

| Member                                 | Contract                                                                             |
|----------------------------------------|--------------------------------------------------------------------------------------|
| `<academic-persons-edit-image-editor>` | The tag name. It observes no attributes and dispatches no events.                    |
| `context`                              | The `EditingContext`, assignable; resolved from the element above it when it is not. |
| `controller`                           | The image editing it drives, or `null` until it is connected.                        |
| `render()`                             | Writes everything derived from the state. Called on every change and on connection.  |

Two consequences worth knowing before the next component is written:

- **A child never reads the root's attributes.** It is handed the contract as a
  property, or it takes it from the `<academic-persons-edit-profile-editing>`
  above it — which is the path an element Fluid rendered has to take, because
  there is no creating caller to assign it.
- **It reports a status through `profile/common.ts`, not through `pe:status`.**
  The controller holds the context, so it needs no address; the event exists for
  a component that has none.

## The element that renders the document editor

`<academic-persons-edit-document-editor>`
([`profile/elements/document-editor.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/document-editor.ts))
is the first component that is a `LitElement`, and it is one because its markup
cannot be server rendered at all: the fields, their labels, their options and
their display values come from the `documentForm` response.
`Partials/Profile/Documents/Editor.html` was 266 lines of Fluid with 114 Vue
directives in them and is now a mount point that renders nothing.

`profile/documents.ts` creates one element per open, **inside the collapse
target of the row or of the section**, hands it the state as properties and
removes it when the close transition reports back. Nothing is ever moved: a move
disconnects the element, and a disconnect destroys the CKEditor instances below
it. That also replaced `<Teleport to="#…">`, which was the only reason the
collapse target needed a generated id; the id stays for `aria-controls`.

| Member                                    | Contract                                                                                       |
|-------------------------------------------|------------------------------------------------------------------------------------------------|
| `<academic-persons-edit-document-editor>` | The tag name. It observes no attributes — every input is a property.                           |
| `context`                                 | The `EditingContext`, assignable; resolved from the element above it when it is not.           |
| `open`                                    | Runs the enter transition when it becomes true and the leave transition when it becomes false. |
| `mode`, `kind`, `heading`, `record`       | Which of the four views is rendered, for a document or a contract, and under which heading.    |
| `fields`, `values`, `errors`, `error`     | The `documentForm` response, what the visitor typed, and what the server refused.              |
| `pending`, `deleteConfirmation`           | The busy state, and the question a deletion asks.                                              |
| `contactSections`, `contactEmptyMessage`  | Handed on to the contract contacts of a contract in view mode.                                 |
| `pe:document-close`                       | The cancel button was pressed.                                                                 |
| `pe:document-submit`                      | The form was submitted; the browser's own submit is prevented.                                 |
| `pe:document-input`                       | A control changed: `{ name, value }`.                                                          |
| `pe:document-closed`                      | The leave transition is over and the owner may remove the element.                             |

The property is called `heading` and not `title` for a reason that is easy to
step on: `title` is a property of every `HTMLElement`, and a reactive property
of that name would shadow it.

### Light DOM, and why there is no choice

`createRenderRoot()` returns `this`. Six independent reasons, any one of which
decides it: the theme's Bootstrap stylesheet has to reach the controls;
CKEditor 5 does not support a classic editor inside a shadow root; Bootstrap's
own popover JavaScript positions against `document`; the class names are the
integrator contract; the SCSS pipeline emits one stylesheet per extension; and
jsdom implements neither `adoptedStyleSheets` nor `CSSStyleSheet.replaceSync`,
so the behavioural suite could not render the element outside a browser.

The consequence is stated rather than hidden: the element provides no style
encapsulation, and the three partials it replaces cease to be Fluid override
points.

### Where `lit` comes from

`import { LitElement, html } from "lit"` — a bare specifier, resolved by TYPO3
core's import map. `EXT:core/Configuration/JavaScriptModules.php` maps `lit`,
`lit/`, `lit-element` and `lit-html` with **no tag and no dependency**, unlike
`EXT:rte_ckeditor`, and `academic_persons_edit` already declares
`'dependencies' => ['core']`, so the frontend import map carries them. Nothing
is added to the extension's own `JavaScriptModules.php`: an own mapping would
pin a copy that diverges from the core the site runs.

Both supported cores ship the same release — lit-html 3.2.0, lit-element 4.1.0
and `@lit/reactive-element` 2.0.4, which is `lit` 3.2.0 — verified in the
version markers of the files themselves on 13.4.34 and 14.3.6. `Build/package.json`
pins exactly those as a development dependency, with npm `overrides` on the
three transitive packages, so the behavioural suite runs the Lit the browser
will and the type check checks against it. It is never bundled: the TypeScript
build emits one module per source and leaves every import as written.

## The element that renders the contacts of a contract

`<academic-persons-edit-contract-contacts>`
([`profile/elements/contract-contacts.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/contract-contacts.ts))
is the second `LitElement`, and it replaces two partials at once:
`Partials/Profile/Documents/ContractContacts.html` and
`…/ContractContactEditor.html`, 315 lines of Fluid with 106 Vue directives.
Neither could be server rendered — the sections, the rows and the fields of the
editor all come from the `documentForm` and `contractContactForm` responses —
and both were rendering the same editor state, once below the section heading
for an addition and once inside the row for everything else.

The document editor's template creates it and hands it five properties. It
calls no endpoint: `profile/documents.ts` keeps `openContractContact()`,
`closeContractContact()`, `submitContractContact()` and `sortContractContact()`
and stays drivable without a custom element registry, which is what makes those
four testable.

| Member                                      | Contract                                                                                          |
|---------------------------------------------|---------------------------------------------------------------------------------------------------|
| `<academic-persons-edit-contract-contacts>` | The tag name. It observes no attributes and dispatches no events.                                 |
| `context`                                   | The `EditingContext`, assignable; resolved from the element above it when it is not.              |
| `contract`                                  | The record the contacts belong to.                                                                |
| `sections`                                  | The `contactSections` of the contract's own response: one list per address, phone number or mail. |
| `emptyMessage`                              | What a section with no contacts says.                                                             |
| `editor`                                    | The open editor as one frozen object: mode, record, section, fields, values, errors and pending.  |

**It reports presses as native clicks, not as custom events.** The controls
carry `data-pe-contract-contact-add`, `-view`, `-edit`, `-delete`, `-sort`,
`-cancel` and `-save`, and the controller delegates on the plugin root — the
same mechanism the document list uses. Lit creates this element inside another
element's template, so the controller never holds it and has nothing to bind a
listener to, and `openContractContact()` reads the pressed button off the event
because that is where focus returns when the editor closes.

**A write to the list is a reassignment, never a splice.** Lit compares a
property by identity, so `replaceContactItems()` in `profile/documents.ts` is
the one path a save, a change, a deletion and a sort take: the section that
changed gets a new object with a new items array, the others keep theirs, and
`repeat()` keyed by uid moves no node it does not have to. The same holds for
`pending` — Vue re-rendered on the write, and every place that sets it now says
when the editor is rendered.

## The element that owns a rich text field

`<academic-persons-edit-rich-text>`
([`profile/elements/rich-text.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/rich-text.ts))
owns one `<textarea>` and the one CKEditor 5 on it. It exists because
`ClassicEditor.create(textarea)` replaces the textarea in the document with its
own container and owns everything below that point — so a `lit-html` template
containing the textarea would patch nodes it does not own on the next re-render,
and a re-render is exactly what a validation error causes.

Lit therefore creates and removes the *element* and never renders into it. The
editor is created in `connectedCallback()` and destroyed in
`disconnectedCallback()`, which is what makes the destroy structural rather than
a call in one close path.

| Member                              | Contract                                                                       |
|-------------------------------------|--------------------------------------------------------------------------------|
| `<academic-persons-edit-rich-text>` | The tag name. It observes no attributes.                                       |
| `context`                           | The `EditingContext`, assignable; resolved from the element above it when not. |
| `configuration`                     | Everything the textarea needs, in one object, so no partial state is rendered. |
| `value`                             | Read through the textarea, so a live editor is the source of it.               |
| `field`                             | The textarea, or `null` before the first connection.                           |

The close path destroys the editors of the closing subtree as well, and the two
are idempotent by construction: `destroyRichTextEditors()` forgets an editor
before it awaits its `destroy()`, so whichever runs second finds none. The scope
is the subtree that is going away and never the plugin root — the profile fields
render a permanent rich text textarea each, and a query from the root would take
a field the visitor still has open off screen.

## Where the icons come from

`<core:icon>` resolves an identifier through the icon registry, which knows the
set the extension registers and whatever a site overrode, and a browser can ask
neither. So `Templates/Profile/Index.html` renders one
`<template data-pe-icon="…">` per icon a browser rendered editor draws, and the
element clones what it needs from there — the same idiom
`Partials/Profile/ButtonTemplates.html` already uses for its two button
templates. The list grows with the editors that move; it is `help` for the
document editor and `add`, `view`, `edit`, `delete`, `move-up` and `move-down`
for the contact list. `editingIcon()`
([`profile/elements/icons.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/icons.ts))
is the one lookup, wrapped in `guard()` by both callers so that a re-render does
not replace an icon that has not changed.

## The transition the editors open and close with

`<Transition>` applied Vue's `-enter-from` / `-enter-active` / `-leave-active` /
`-leave-to` classes and called back when the animation was over. The classes are
kept — the declarations that select them are unchanged — and the runner built by
`createElementTransition()`
([`profile/elements/transition.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/transition.ts))
applies them instead. One runner per editor, because the two differ only in the
class name prefix `<Transition name="…">` derived its classes from.

It is a function rather than a `transitionend` listener because the callback is
where the close path hangs: the focus returns to the trigger there and the
collapsed layout is restored there. `transitionend` does not fire for an element
that is `display: none`, for a property that does not change, or for one that is
removed halfway, and a transition that never ends is therefore a silent,
intermittent defect rather than a missing animation. So it ends on the event, on
a timeout past the computed duration, or at once when the computed duration is
zero — which is what `prefers-reduced-motion` produces — and it can be cancelled
when the editor is reopened while it closes.

The document editor reports its finished close **one frame later**, never inside
the update that started it: the owner removes the element when it hears that,
and tearing the tree out from inside Lit's `updated()` is how a reactive element
ends up patching detached nodes. Vue's `after-leave` was a frame away for the
same reason.

## The seam for a caller that has only the element

The entry points take `EditingTarget`, which is `HTMLElement | EditingContext`.
`profile.ts` passes the context it built; a caller holding only the element —
a test, or a second editor discovered later — passes that, and the entry point
reads the contract itself. `toEditingContext()` is the one place that decides.

## See also

- [Frontend assets](../development/frontend-assets.md) — how the TypeScript is
  built and how a module is loaded.
- [Frontend verification for `academic-persons-edit`](../testing/academic-persons-edit-frontend-tests.md)
  — what the behavioural suite pins, and what it deliberately does not.
- [JavaScript tests](../testing/javascript-tests.md) — the harness the contract
  is tested with.
