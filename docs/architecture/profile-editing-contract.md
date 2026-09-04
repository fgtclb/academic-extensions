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
