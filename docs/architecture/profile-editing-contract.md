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
| Labels    | 7     | `data-label-document-add`, `data-label-document-empty`        |

Only the labels a *value* is composed from travel this way — the heading of a
contact editor is `${documentAdd} ${section.singularLabel}`, an empty display
value shows `documentEmpty`. A label that is only ever *rendered* is written by
Fluid into the prototype that carries it, so it is overridable together with the
markup around it.

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

## Fluid owns the markup

The editor is server rendered and the elements below the root **control** that
markup; none of them produces any. That is the constraint the whole design turns
on, because it is what keeps an integrator's override a Fluid file rather than a
JavaScript module.

Two regions cannot be server rendered as finished markup — a document editor and
the contacts of a contract, whose fields, labels, options and display values all
come from the `documentForm` and `contractContactForm` responses. Those
endpoints stay purely machine readable, and Fluid renders the *shapes* instead:
[`Partials/Profile/Prototypes.html`](../../packages/fgtclb/academic-persons-edit/Resources/Private/Partials/Profile/Prototypes.html)
emits one `<template data-pe-proto="…">` per shape, and
[`profile/prototypes.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/prototypes.ts)
clones and fills them through exactly four attributes:

| Attribute                        | Meaning                                                                   |
|----------------------------------|---------------------------------------------------------------------------|
| `data-pe-slot="key"`             | The node's `textContent` becomes the value.                               |
| `data-pe-attr="attribute:key …"` | Those attributes take the value; `false`/`undefined` removes them.        |
| `data-pe-when="key"`             | The node is removed when the value is falsy.                              |
| `data-pe-list="key"`             | Where repeated clones go — into the element, or in place of a `template`. |

**A prototype contains no `<f:if>`.** The only decision a prototype partial
makes is which prototype it emits, and a case that would need a fifth verb is
the signal that the region should have been server rendered. Two such cases came
up while this was written and both were answered in Fluid or in CSS rather than
in TypeScript: a save button that is red for a delete is two buttons and a
`data-pe-when`, and the striping of both list kinds - the contacts of a
contract and the rows of a document section - is a `:nth-child` rule.

**No element and no filler writes markup**: every tag, every `class` attribute
and every label of the two editors is authored in Fluid. That is not the same as
"JavaScript never names a class", and the difference is worth stating exactly,
because an override that renames one of these breaks the editor silently. They
are part of the contract and are listed in the extension's manual as well.

The feature modules below the elements toggle *state* classes on markup that
already exists — `d-none`, `d-md-flex`, `is-invalid`, `text-danger`,
`text-body-secondary`, `active`, the four `bg-*` severities of the toast, the
five `is-drag-*`/`is-drop-*` classes of the drag sort, `is-image-closing`, the
three `col-lg-*` widths of the image column, and the transition classes derived
from a prefix — and they select nodes by class in eleven places:

| Selector                                                   | Read by                                  |
|------------------------------------------------------------|------------------------------------------|
| `.academic-persons-profile-editing__field`                 | `fields.ts`, every editable control      |
| `.academic-persons-profile-editing__sync-checkbox`         | `sync.ts`                                |
| `.academic-persons-profile-editing__profile-fields-column` | `image.ts`, `elements/image-editor.ts`   |
| `.status-title`, `.status-message`                         | `common.ts`, the toast                   |
| `.invalid-feedback`, `.form-check`, `.mb-3`                | `fields.ts`, the validation message      |
| `.ck`                                                      | `fields.ts`, the CKEditor `Escape` guard |
| `.alert[role="alert"]`, `.spinner-border`                  | the two editor elements                  |

`sticky-image.ts` adds `#page-header.navbar-fixed-top`, which belongs to the
site's theme rather than to this extension.

Three nodes carrying markup are built in JavaScript rather than cloned from a
prototype, all of them outside the two editors and each a leaf:
`profile/documents.ts` writes a document row's title as an `<a>` or a `<span>`
depending on whether the record carries an allowed link, and puts an em dash
where the value is empty — the same placeholder Fluid spells as
`prependOptionLabel`; and `profile/rich-text.ts` writes the empty-state label of
a rich text preview as `<span class="text-body-secondary">`. The remaining
`document.createElement()` calls make a custom element host, which carries no
markup of its own until it is filled from a prototype, or the detached render
root of `elements/base.ts`. Anything larger than a leaf is a prototype.

```bash
grep -rnoP 'classList\.\w+\([^)]*\)|document\.createElement' \
  packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend
```

The keys are a closed type. `PrototypeSlots` and `PrototypeLists` name them
exactly as `ProfileEditingHooks` names the hooks, so a key an element fills that
a prototype does not declare is a `typecheckJs` error — and, because a type says
nothing about a partial, a runtime error as well. The other direction, a slot a
partial stops emitting, is a failure of
`AcademicPersonsEditProfileEditingPrototypesTest`, which asserts the inventory
and the keys against the rendered partial.

## Two editing states, and one control set each

The profile fields have exactly two states and they are exclusive.

**Single field.** One field, or one group of fields, is opened by its own
control and carries the three buttons of
[`Partials/Profile/Field/Actions.html`](../../packages/fgtclb/academic-persons-edit/Resources/Private/Partials/Profile/Field/Actions.html):
clear, undo, save. Every document and contact editor works the same way and is
not affected by anything below.

**Full form.** `Edit all` opens every editable field at once. For as long as it
is open, every `[data-pe-field-actions]`, every `[data-pe-group-actions]` and
every `[data-pe-autosave-undo]` **inside a `[data-pe-fields-form]`** carries
`hidden`, and the bar of
[`Partials/Profile/Field/FormActions.html`](../../packages/fgtclb/academic-persons-edit/Resources/Private/Partials/Profile/Field/FormActions.html)
loses it. The bar is rendered once per fields form, by
`Partials/Profile/Profile/Fields.html`, and each of them governs the whole
profile: the two forms of the shipped template — personal data and about me —
are one form as far as this state is concerned.

| Hook                            | On                  | Meaning                                                                        |
|---------------------------------|---------------------|--------------------------------------------------------------------------------|
| `data-pe-form-actions`          | the bar             | Shown while the form is open, `hidden` otherwise.                              |
| `data-pe-form-reverted-message` | the bar             | The polite status text of `undo` — the only label the bar hands to JavaScript. |
| `data-pe-form-apply`            | a button in the bar | One request with every changed field.                                          |
| `data-pe-form-undo`             | a button in the bar | Back to the persisted values, form stays open.                                 |
| `data-pe-form-discard`          | a button in the bar | Back to the persisted values, form closes.                                     |

The qualification matters: the bar's own three labels are Fluid and never
travel, but the editor as a whole *does* hand labels to JavaScript elsewhere —
`data-pe-edit-all-label` and `data-pe-close-all-label` on the toggle,
`data-pe-checked-label` and `data-pe-unchecked-label` on a checkbox, and every
status text in `context.messages`.

Nothing is removed from the document for the state change — single-field editing
has to work again the moment the form closes — and nothing is added to it
either: the bar is server rendered like everything else, so its three labels,
its `role`, its accessible name and its classes are Fluid and are overridable
with it.

**Apply is one request.** `updateAction()` takes an arbitrary partial field map,
validates all of it and persists once, so a refusal leaves the profile untouched
and there is no partial result to undo. That is why nothing on screen is
reverted on a refusal: the entered values stay, the refused fields are marked,
and the message is announced once rather than once per field.

**A transition is refused while an apply is in flight.** Undo, discard, the
toggle and `Escape` all check the same flag `applyForm()` sets before its first
`await`. The request cannot be un-persisted, so reverting under it would let the
response write the reverted values into `persistedValues` for every property the
endpoint does not echo — the baseline would then say "unchanged" for a value the
database does not hold, and the next apply would not resend it.

**`Escape` and `Ctrl`/`Cmd`+`Enter` are bound to each `[data-pe-fields-form]`,
never to the root.** A document, contact or image editor may be open at the same
time, those panels are outside every fields form, and they keep their own
handling of both keys. Inside the form, `Escape` is still left to CKEditor while
the caret is in one (`event.target.closest(".ck")`).

**A refusal of the form interrupts.** `showStatus()` normally picks the region
from the severity — only `danger` is assertive — and it takes an explicit region
for the case where the severity is the wrong answer. Applying the form moves the
caret to the first refused field in the same turn, and a polite region queued
behind that focus change is routinely dropped, so the form's validation message
goes to `[data-pe-status-toast="alert"]`. Beside a single field the message
stands next to the control the visitor is already in and stays polite, which is
also what keeps single-field editing unchanged.

**The rich text baseline comes from the editor, not from the markup.** CKEditor
normalises what it is handed, so the rendered value and the value it hands back
differ for the same content. The correction runs on the save path *and* on the
revert path: undo would otherwise put the un-normalised source into the editor
and the next apply would post it as a change nobody made.

**Autosave is suppressed while the form is open.** A `[data-pe-autosave-on-change]`
checkbox participates in apply instead of writing on change — otherwise it would
reach the database while the visitor is still deciding, and discard could not
take it back. The synchronisation switch of `Header.html` sits *outside*
`[data-pe-fields-form]` and keeps its immediate save.

## The sixteen prototypes

| Prototype                                                                                      | Rendered by                            |
|------------------------------------------------------------------------------------------------|----------------------------------------|
| `control-input`, `control-textarea`, `control-rich-text`, `control-select`, `control-checkbox` | `Field/Control.html`                   |
| `field-default`, `field-wide`, `field-checkbox`                                                | `Field/PrototypeWrapper.html`          |
| `option`, `helptext-button`, `display-row`                                                     | `Prototypes.html`                      |
| `document-panel`                                                                               | `Documents/Editor.html`                |
| `contact-section`, `contact-row`, `contact-summary-cell`                                       | `Documents/ContractContacts.html`      |
| `contact-editor-panel`                                                                         | `Documents/ContractContactEditor.html` |

`Partials/Profile/Field/Control.html` is the one place a form control of this
editor is spelled, and it is rendered from three call sites: inline for the
permanent profile fields, once per control type into the prototypes, and —
through them — for every field of a document or contact editor. It takes one
optional `prototype` flag, which is the file's only branch: in that mode the
concrete `id`, `name` and value are empty and the `data-pe-attr` bindings carry
the slot names instead. There were three implementations of one control set
before, two of them already drifted; a functional test now asserts that a
prototype control and the live control of the same type carry the same tag, the
same classes and the same attributes, with no exception list.

`profile/elements/field-clone.ts` is the TypeScript half of the same
consolidation: one builder turns a `documentForm` or `contractContactForm`
field descriptor into DOM by cloning those prototypes, and the document editor
and the contact editor differ in two arguments — the id prefix and which of the
two field hooks the control carries. It is where the drifted checkbox came
from, so it is deliberately one function and not two.

## The five elements

| Element                                     | Renders | Responsibility                                                         |
|---------------------------------------------|---------|------------------------------------------------------------------------|
| `<academic-persons-edit-profile-editing>`   | nothing | Wraps the plugin root, reads the contract once and starts the editor.  |
| `<academic-persons-edit-image-editor>`      | nothing | Controller over the server rendered upload form.                       |
| `<academic-persons-edit-document-editor>`   | nothing | Clones `document-panel` and fills it from the `documentForm` response. |
| `<academic-persons-edit-contract-contacts>` | nothing | Clones the section, row and editor prototypes of a contract.           |
| `<academic-persons-edit-rich-text>`         | nothing | Owns the textarea its prototype carries and the CKEditor 5 on it.      |

All five extend `ProfileEditingElement`
([`profile/elements/base.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/base.ts)),
which extends `LitElement` and overrides one method — see below.

Five events cross the element boundary. Four are dispatched by the document
editor and consumed by `profile/documents.ts`, which created it; the fifth is
listened for on the root so that a descendant which holds no context can still
have a status written. All of them bubble; none is `composed`, because there is
no shadow boundary anywhere for one to cross.

| Event                | Dispatched by       | Detail               |
|----------------------|---------------------|----------------------|
| `pe:status`          | nothing shipped yet | `{ type, message? }` |
| `pe:document-close`  | document editor     | —                    |
| `pe:document-submit` | document editor     | —                    |
| `pe:document-input`  | document editor     | `{ name, value }`    |
| `pe:document-closed` | document editor     | —                    |

The two libraries sit outside that picture. **CKEditor 5** comes from
`EXT:rte_ckeditor` through the import map and is created and destroyed by
`<academic-persons-edit-rich-text>` for a document field, and by
`profile/rich-text.ts` directly for a permanently rendered profile field.
**CropperJS 2.2.0** is vendored under `Resources/Public/JavaScript/vendor/` and
is driven by `profile/image.ts`, never by an element — the element only says
which panel is shown.

## The base class, and the trap it closes

`ProfileEditingElement` overrides `createRenderRoot()` to return a **detached
`<div>` that is never inserted anywhere**. Read from the `lit` sources both
cores ship — byte identical on 13.4.34 and 14.3.6, lit 3.2.0 / lit-element 4.1.0
/ `@lit/reactive-element` 2.0.4:

- `LitElement.update()` ends in `render(value, this.renderRoot, …)`, so *every*
  update reaches lit-html whatever `render()` returned.
- `createRenderRoot()` is called once, on the first connection, and its result
  is the container lit-html renders into; it also seeds
  `renderOptions.renderBefore` from that container's first child.
- lit-html inserts a comment marker into the container and stores the part on
  `container._$litPart$`; clearing a part removes everything between its markers.

`createRenderRoot() { return this; }` with a `render()` that returns `noChange`
therefore happens to leave Fluid's children alone — but only because of three
lit-html internals, none of them a documented API, and it breaks the moment the
element is upgraded before its children are parsed or somebody returns a
template from `render()`. Rendering into a detached node makes it structural
instead: no marker enters the element, `_$litPart$` is never set, no shadow root
is attached, and a `render()` that returns a template is a silent no-op rather
than a destroyed form. `Tests/JavaScript/lit-lifecycle.test.ts` pins all five
properties, each with the mutation that turns it red.

**Why `LitElement` at all**, when nothing renders: the reactive properties and
their accessors, the batching of several writes into one DOM pass,
`changedProperties`, `firstUpdated()` and `updateComplete`. The document editor
is the element that needs all of it — a keystroke must not rebuild a panel that
holds a live CKEditor, and `changedProperties` is what separates the structural
change that rebuilds from the value change that patches.

### Light DOM, and why there is no choice

No element opens a shadow root. Five independent reasons, any one of which
decides it: the theme's Bootstrap stylesheet has to reach the controls;
CKEditor 5 does not support a classic editor inside a shadow root; Bootstrap's
own popover JavaScript positions against `document`; the class names are the
integrator contract; and the SCSS pipeline emits one stylesheet per extension.

### Where `lit` comes from

`import { LitElement } from "lit"` — a bare specifier, resolved by TYPO3 core's
import map. `EXT:core/Configuration/JavaScriptModules.php` maps `lit`, `lit/`,
`lit-element` and `lit-html` with **no tag and no dependency**, unlike
`EXT:rte_ckeditor`, and `academic_persons_edit` already declares
`'dependencies' => ['core', 'academic_persons']`, so the frontend import map
carries them. Nothing
is added to the extension's own `JavaScriptModules.php`: an own mapping would
pin a copy that diverges from the core the site runs.

Both supported cores ship the same release, verified in the version markers of
the files themselves. `Build/package.json` pins exactly those as a development
dependency, with npm `overrides` on the three transitive packages, so the
behavioural suite runs the Lit the browser will and the type check checks
against it. It is never bundled: the TypeScript build emits one module per
source and leaves every import as written.

## The element that owns the root

The template wraps the plugin root in
`<academic-persons-edit-profile-editing>`
([`profile/elements/root.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/root.ts)).
It renders nothing: Fluid renders the editor, the element takes that markup as
its light DOM children, reads the contract of the root below it once and starts
the editing modules. The `data-*` attributes stay where they were, on
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
`__trustedProperties` HMAC — signed with the installation's encryption key —
that the property mapper validates the upload against, and nothing in a browser
can recompute it. A component that rendered that form would have to reproduce a
signature only the server can make, so the form stays server rendered and the
element is a controller over it.

The state, the cropper and every request stay in `profile/image.ts`, which is
driven and pinned without a registry. The element creates one controller, is
called back on every change it accepts, and writes onto the partial what that
state means: which panel is shown, what is disabled, which preview is visible.

| Member                                 | Contract                                                                             |
|----------------------------------------|--------------------------------------------------------------------------------------|
| `<academic-persons-edit-image-editor>` | The tag name. It observes no attributes and dispatches no events.                    |
| `context`                              | The `EditingContext`, assignable; resolved from the element above it when it is not. |
| `controller`                           | The image editing it drives, or `null` until it is connected.                        |
| `applyState()`                         | Writes everything derived from the state. Called on every change and on connection.  |

Two consequences worth knowing before the next component is written:

- **It is called `applyState()` and not `render()`.** `render()` belongs to
  `LitElement`, and this element renders nothing.
- **A child never reads the root's attributes.** It is handed the contract as a
  property, or it takes it from the `<academic-persons-edit-profile-editing>`
  above it — which is the path an element Fluid rendered has to take, because
  there is no creating caller to assign it.
- **It reports a status through `profile/common.ts`, not through `pe:status`.**
  The controller holds the context, so it needs no address; the event exists for
  a component that has none.

## The element that drives the document editor

`<academic-persons-edit-document-editor>`
([`profile/elements/document-editor.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/document-editor.ts))
clones the `document-panel` prototype of
`Partials/Profile/Documents/Editor.html` and fills it from the `documentForm`
response. Every tag, class and label of the panel is therefore in a Fluid file;
what is here is the order things are inserted in and which slot carries which
value.

`profile/documents.ts` creates one element per open, **inside the collapse
target of the row or of the section**, hands it the state as properties and
removes it when the close transition reports back. Nothing is ever moved: a move
disconnects the element, and a disconnect destroys the CKEditor instances below
it. The collapse target keeps its generated id for `aria-controls`.

**A rebuild is a decision, not a default.** `changedProperties` separates the
structural change — `fields`, `mode`, `kind`, `record`, `heading`,
`deleteConfirmation`, `context` — which clones the panel again, from the value
change — `pending`, `error`, `errors` — which is written onto the panel that is
there. A refusal arrives while the visitor is looking at what they typed, and
rebuilding would replace every control and every live CKEditor with a fresh one.

The rebuild uses `replaceChildren()`, and that is the whole teardown story: the
platform disconnects everything it removes, so every rich text element of the
previous panel is destroyed by its own `disconnectedCallback()`. Scoping the
teardown by hand is what once destroyed the *profile* field editors instead.

| Member                                                    | Contract                                                                                          |
|-----------------------------------------------------------|---------------------------------------------------------------------------------------------------|
| `<academic-persons-edit-document-editor>`                 | The tag name. It observes no attributes — every input is a property.                              |
| `context`                                                 | The `EditingContext`, assignable; resolved from the element above it when it is not.              |
| `open`                                                    | Runs the enter transition when it becomes true and the leave transition when it becomes false.    |
| `mode`, `kind`, `heading`, `record`                       | Which of the four views is rendered, for a document or a contract, and under which heading.       |
| `fields`, `values`, `errors`, `error`                     | The `documentForm` response, what the visitor typed, and what the server refused.                 |
| `pending`, `deleteConfirmation`                           | The busy state, and the question a deletion asks.                                                 |
| `contactSections`, `contactEmptyMessage`, `contactEditor` | Handed on to the contract contacts of a contract in view mode: the two lists and the open editor. |
| `pe:document-close`                                       | The cancel button was pressed.                                                                    |
| `pe:document-submit`                                      | The form was submitted; the browser's own submit is prevented.                                    |
| `pe:document-input`                                       | A control changed: `{ name, value }`.                                                             |
| `pe:document-closed`                                      | The leave transition is over and the owner may remove the element.                                |

The property is called `heading` and not `title` for a reason that is easy to
step on: `title` is a property of every `HTMLElement`, and a reactive property
of that name would shadow it.

## The element that drives the contacts of a contract

`<academic-persons-edit-contract-contacts>`
([`profile/elements/contract-contacts.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/contract-contacts.ts))
clones the `contact-section`, `contact-row` and `contact-summary-cell`
prototypes of `Partials/Profile/Documents/ContractContacts.html` and the
`contact-editor-panel` of `…/ContractContactEditor.html`. Both partials keep the
names and the role they had: they are the override point for the contact list,
they render a shape rather than finished markup, because the sections, the rows
and the fields of the editor all come from the `documentForm` and
`contractContactForm` responses.

The document editor creates it and hands it five properties. It calls no
endpoint: `profile/documents.ts` keeps `openContractContact()`,
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

**The list is reconciled, never replaced.** The sections are keyed by
identifier and the rows by uid, and a row is built again only when its item
changed *by value*. A save hands over a whole new sections array with new item
objects in it, and rebuilding every row would take the control the visitor is
standing in — and the caret with it — out of the document. Opening and closing an
editor touches no row at all: the panel is placed into the row's own insertion
point, so the control that opened it keeps its identity, its `aria-expanded` and
the focus.

**The arrows are disabled from the position in the list this element renders**,
never from a flag the server computed. That was the alternative the design
prepared, and it is worse: the list is edited in the browser — a create appends,
a delete removes, a sort reorders — so a server side `sortable` would be stale
one interaction later. What reaches the filler is two booleans, and the filler
still only toggles an attribute.

**It reports presses as native clicks, not as custom events.** The controls
carry `data-pe-contract-contact-add`, `-view`, `-edit`, `-delete`, `-sort`,
`-cancel` and `-save`, and the controller delegates on the plugin root — the
same mechanism the document list uses. The document editor creates this element
inside its own panel, so the controller never holds it and has nothing to bind a
listener to, and `openContractContact()` reads the pressed button off the event
because that is where focus returns when the editor closes.

## The element that owns a rich text field

`<academic-persons-edit-rich-text>`
([`profile/elements/rich-text.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/rich-text.ts))
owns one `<textarea>` and the one CKEditor 5 on it. It does **not** create the
textarea: that is the `control-rich-text` prototype, which is the same
`Field/Control.html` the permanent profile fields are rendered from, wrapped in
this element. The filler writes the id, the name and the validation attributes
into it, and the element takes the textarea it finds below itself.

It exists because `ClassicEditor.create(textarea)` replaces the textarea in the
document with its own container and owns everything below that point. Nothing
else may touch that subtree, and an element is the unit that owns one.

| Member                              | Contract                                                                       |
|-------------------------------------|--------------------------------------------------------------------------------|
| `<academic-persons-edit-rich-text>` | The tag name. It observes no attributes.                                       |
| `context`                           | The `EditingContext`, assignable; resolved from the element above it when not. |
| `value`                             | The value of the textarea, which a live editor mirrors into on every change.   |
| `field`                             | The textarea of the prototype, or `null` for an element nothing has filled.    |

The editor is created in `connectedCallback()` and destroyed in
`disconnectedCallback()`, which is what makes the teardown **structural**: the
document editor replaces its panel with `replaceChildren()`, the platform
disconnects what it removed, and every editor of the leaving subtree — and only
those — is destroyed. Scoping it by hand is what once destroyed the profile field
editors instead. A close followed by a reopen builds a new element with a new
textarea, so the only case where a create can race a destroy is an element that
is moved in the document, and the creation is chained behind the teardown
promise for exactly that.

## Where the icons come from

`<core:icon>` resolves an identifier through the icon registry, which knows the
set the extension registers and whatever a site overrode, and a browser can ask
neither. Under the prototype design that is not a problem to solve: an icon is
rendered by Fluid **inside the prototype that draws it** — the help button, the
five row controls of a contact, the add control of a section — so it is part of
the markup an override reaches, and no module ever looks one up. There is no
icon registry, no icon module and no `<template data-pe-icon>` block.

## The transition the editors open and close with

The document editor and the image editor open and close with a CSS transition,
and the classes that drive it — `-enter-from`, `-enter-active`, `-leave-active`,
`-leave-to` — are declared in
`Resources/Private/Scss/frontend/profile-editing.scss` and applied by the runner
`createElementTransition()` builds
([`profile/elements/transition.ts`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/frontend/profile/elements/transition.ts)).
One runner per editor, because the two differ only in the class name prefix.

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
ends up patching detached nodes.

## Nothing else ships with it

`Resources/Public/JavaScript/vendor/` holds exactly one library: CropperJS 2.2.0,
with its licence. Core maps a `cropperjs` specifier of its own, but it is
**1.6.1** on both 13.4.34 and 14.3.6 — the API before CropperJS became a set of
custom elements — so it is not a replacement, and the vendored copy stays. Lit
and CKEditor 5 are delivered by the core and are not vendored. See
[Frontend assets](../development/frontend-assets.md#vendored-libraries).

The five element names, the `pe:*` events, the `data-pe-*` hooks, the root's
`data-*` attributes, the four prototype verbs and the state classes and
selectors listed above are the whole contract between Fluid and the JavaScript.
Everything else — every tag, class and label of the editor — is in a Fluid
partial an integrator overrides.

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
