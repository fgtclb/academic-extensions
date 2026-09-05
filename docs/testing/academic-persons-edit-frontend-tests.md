# Frontend verification for `academic-persons-edit`

The profile editing frontend is the largest piece of TypeScript in this
repository — one entry point, nine feature modules and nine element modules
under
[`Resources/Private/TypeScript/frontend/`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/),
compiled by the repository build into committed JavaScript under
[`Resources/Public/JavaScript/`](../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/).

This page says how it is verified, and — more importantly — how it is **not**.

## The behavioural tests

There is a JavaScript suite now: `node --test` with jsdom, described in
[JavaScript tests](javascript-tests.md), and this extension is the one it was
built for.

```bash
Build/Scripts/runTests.sh -s testJs
```

The first test file,
[`Tests/JavaScript/rich-text-editor-scope.test.ts`](../../packages/fgtclb/academic-persons-edit/Tests/JavaScript/rich-text-editor-scope.test.ts),
pins the defect that motivated the harness. The document editor closes through a
transition hook that runs *after* the leaving element has been removed from the
document. Handing the plugin root to `destroyRichTextEditors()` there destroys
no editor of the closing view — its textareas are no longer below the root —
while it does destroy every permanently rendered profile-field editor, because
`Partials/Profile/Field/Control.html` puts a `data-pe-rich-text` textarea under
the root for each of them. That took a DOM, a transition and a live editor
instance to observe, which is why neither PHP suite ever could. The teardown is
structural now — `<academic-persons-edit-rich-text>` destroys its own editor
when it is disconnected — and this file is what says so.

A second defect of the same class, and the one that says most about what the
harness has to reach: a rename of the markup hooks that stopped at the
templates. They emit `data-pe-*`; a draft of the TypeScript read every one of
them as `element.dataset.ie…`. `data-pe-for` is `dataset.peFor`, never
`dataset.ieFor`, so thirteen keys across `fields.ts`, `documents.ts` and
`rich-text.ts` would read `undefined` at runtime: the per-field clear, cancel
and save buttons, the visibility checkbox preview, the field groups and their
preview mode, the edit-all label, the rich text character limit and the document
value and sort handling.

**No gate in this repository could see it.** The functional tests assert the
rendered HTML and never execute the module, `lintTypescript` has no rule for it,
and `typecheckJs` is satisfied because `dataset` is a `DOMStringMap` — every key
of it is `string | undefined`, including one that no element ever carries.

Three things answer it, and only one of them is coverage:

- `profile/common.ts` declares the hook set as a type and reads and writes it
  through `hooks(element)`. An unknown key is a `typecheckJs` failure, so a
  prefix rename cannot half-apply *inside* the TypeScript. `profile/context.ts`
  applies the same mechanism to the root's own `data-*` contract, and
  `profile/prototypes.ts` to the slot names of the Fluid prototypes — see
  [Profile editing contract](../architecture/profile-editing-contract.md).
- The other direction — an attribute renamed in a template and nowhere else — is
  the PHP functional suite's, which reads the partials themselves.
- What neither of them reaches is the behaviour between the two, and that is
  this suite.

| File                             | What it pins                                                                                                                                                  |
|----------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `request-layer.test.ts`          | `requestJson()`: the headers including the `X-Requested-With` guard, the failure shapes, the wait cursor, and which status region a severity writes.          |
| `skip-sync.test.ts`              | The synchronisation switch: its payload, the revert of a control that saves without a button, and the two listeners that reach it.                            |
| `field-editing.test.ts`          | Per-field edit, clear, undo and save; only changed fields travel; the validation messages; the visibility switch; the field groups and their preview modes.   |
| `full-form-editing.test.ts`      | The other editing state: what entering hides and opens, apply as one request, undo, discard, the keyboard, and autosave suppressed while the form is open.    |
| `rich-text-preview.test.ts`      | The sanitiser's allow-list, the link schemes, the preview, and the character limit.                                                                           |
| `rich-text-editor-scope.test.ts` | The teardown scope of the rich text editors — the defect the harness was built for, described above.                                                          |
| `document-rows.test.ts`          | The list a section renders: numbering, the arrow at each end, the empty state, sorting by arrow and by drag, and the rollback of both.                        |
| `document-editor.test.ts`        | The open and close cycle, the collapse target, the focus, the created editors, the three save modes, and the values a row is written with.                    |
| `contract-contacts.test.ts`      | The contacts of a contract: their endpoints, their editor, and what a save does to the list the element is handed.                                            |
| `image-editing.test.ts`          | Choosing, uploading and deleting the image, the previews, and the object urls that are released.                                                              |
| `sticky-image.test.ts`           | The offset below a fixed page header, and its teardown.                                                                                                       |
| `editing-context.test.ts`        | The root's `data-*` contract: every key of a complete root, what a minimal one reads as, the four coercions, and that the result is frozen.                   |
| `prototypes.test.ts`             | The four verbs of the filler, a value that contains markup, an unknown slot, an unknown list, and a prototype the page does not render.                       |
| `lit-lifecycle.test.ts`          | The base class: that lit-html reaches no child of an element, whatever `render()` returns, and that the reactive plumbing the base class is kept for is live. |

The elements add one file each for what they do with the markup they are handed,
and one for the order they may be defined in:

| File                                     | What it pins                                                                                                                                                                                                                                                                          |
|------------------------------------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `profile-editing-element.test.ts`        | One context per element, the initialisers it runs, that a move in the document starts nothing twice, and both status regions.                                                                                                                                                         |
| `image-editor-element.test.ts`           | What the element derives from the state, that the `<f:form>` and its hidden fields are untouched, the cropping path, and the transition helper.                                                                                                                                       |
| `document-editor-element.test.ts`        | Which prototype is cloned in each of the four modes, the slots each control is filled with, the errors, the busy state, the four events it reports, the collapse transition, and that a message written onto an open panel leaves a live CKEditor alone.                              |
| `contract-contacts-element.test.ts`      | The sections and rows the property renders, the empty message, where the editor stands for each mode, its controls and messages, that a replaced list leaves an open editor and the caret in it alone — and, driven through the real controls, the payload of every contact endpoint. |
| `rich-text-element.test.ts`              | That it wraps the textarea its prototype carries, one editor per element, created on connect and destroyed on disconnect, exactly once each.                                                                                                                                          |
| `entry-point.test.ts`                    | That `frontend/profile.js` defines every element of the editor.                                                                                                                                                                                                                       |
| `*-element-upgrade.test.ts` (four files) | That an element works in both orders: properties assigned before the definition ran, and an element created after it.                                                                                                                                                                 |

With the four harness self-tests under `Tests/JavaScript/Harness/` that is
**380 cases in 62 suites**, across 28 files. Take the figure from the runner
rather than from a `grep` over the sources: `prototypes.test.ts` generates one
case per prototype, so the 365 cases written there are 380 executed ones.

```bash
Build/Scripts/runTests.sh -s testJs   # the "tests" and "suites" lines it ends with
```

The fixture is a transcription of the Fluid prototypes and not the partial, so
nothing in this suite would notice a hook renamed in the partial. Two tests hold
the two sides against one table, `prototypeSlots`/`prototypeLists` in
`frontend/profile/prototypes.ts`, which is a runtime value for exactly that
reason:

- `prototypes.test.ts`, "the transcription in the fixture", asserts that the
  fixture carries the declared prototypes and that their `data-pe-slot`,
  `data-pe-attr`, `data-pe-when` and `data-pe-list` keys are the declared ones.
- [`AcademicPersonsEditProfileEditingPrototypesTest`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileEditingPrototypesTest.php)
  asserts the same inventory and the same keys against the *rendered* partial,
  from a second hand copy of the table, and additionally that a prototype
  control and the live control of the same type are the same control.

**A jsdom fixture that stands in for a Fluid prototype is only worth what those
two are worth.** Neither reads the other's copy: a key renamed in the partial
alone fails in PHP, a key renamed in the fixture alone fails in jsdom, and a key
renamed in the table fails in both.

It is still not everything. The CSS transitions, the real CKEditor and the real
CropperJS are covered by nothing that executes them, and are named
in [JavaScript tests](javascript-tests.md) rather than papered over.

**Do not close the rest of it with assertions on source text.** The editor this
one replaced was verified that way: a unit test compared the `.ts` files against
literal strings, down to the whitespace between two statements and the number of
`requestAnimationFrame(` calls in a module. Tests of that shape cannot fail for
a behavioural regression and fail for every refactor, so they were removed with
the editing rewrite (ACE-262) rather than carried. They are not to come back in
TypeScript either.

Do not put test infrastructure below `Resources/Public/`, which holds
distributable artifacts — the former `Resources/Public/Development/` tree of this
extension was removed when the frontend moved to the repository-wide toolchain.

## Repository gates

Run them from the repository root. All five are core-version independent and
need no `composerUpdate`; they use the pinned Node.js container, so no
package-local `npm install` is needed either.

```bash
Build/Scripts/runTests.sh -s buildJs
Build/Scripts/runTests.sh -s checkJsBuildClean
Build/Scripts/runTests.sh -s lintTypescript -n
Build/Scripts/runTests.sh -s typecheckJs
Build/Scripts/runTests.sh -s testJs
```

| Suite               | What it establishes                                                                 |
|---------------------|-------------------------------------------------------------------------------------|
| `buildJs`           | Compiles the TypeScript and the SCSS and updates the committed artifacts            |
| `checkJsBuildClean` | Rebuilds only the generated outputs and fails when the result differs from the tree |
| `lintTypescript -n` | Checks the TypeScript without modifying it                                          |
| `typecheckJs`       | Runs the type checker — esbuild strips types, it does not validate them             |
| `testJs`            | Runs the modules against a DOM, which is the only gate that executes them           |

`typecheckJs` only checks the real modules because `Build/tsconfig.json` maps
`@fgtclb/academic-persons-edit/frontend/*` onto the TypeScript sources. Without
that `paths` entry TypeScript resolves the bare specifiers to whatever ambient
`declare module` it finds, which is a hand-written copy that drifts — see
[Frontend assets](../development/frontend-assets.md#vendored-libraries) for the
rule and for what belongs in `_dependencies.d.ts` instead.

## What the PHP tests do cover

The functional plugin tests under
[`Tests/Functional/Plugins/`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/)
render the shipped templates through the real frontend and drive the endpoints
the JavaScript calls. They cover the two halves the JavaScript sits between:

- **The markup contract.** The `data-pe-*` hooks, the element ids, the icon
  identifiers and the `data-*` configuration on the root element are asserted on
  the rendered page. A template change that removes a hook the JavaScript queries
  turns those red.
- **The prototypes.** Their inventory, the slot keys of each of them, and that a
  prototype control and the live control of the same type are one control. This
  is the half of the contract the jsdom fixture transcribes, so it is also what
  keeps that transcription honest.
- **The endpoint contract.** Every JSON action is driven with a real request and
  a real database: the payload shapes, the response shapes, the persisted rows,
  and — in
  [`AcademicPersonsEditProfileEditingAuthorizationTest`](../../packages/fgtclb/academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditProfileEditingAuthorizationTest.php)
  — every way a request is refused.

What they do not cover is everything between the two: the event handling, the
optimistic updates, the transitions and the focus management.

```bash
Build/Scripts/runTests.sh -t 13 -s composerUpdate
Build/Scripts/runTests.sh -t 13 -s functional packages/fgtclb/academic-persons-edit/Tests/Functional
Build/Scripts/runTests.sh -t 14 -s composerUpdate
Build/Scripts/runTests.sh -t 14 -s functional packages/fgtclb/academic-persons-edit/Tests/Functional
```

The image upload tests carry the phpunit group `not-core-13`: TYPO3 v13's
`ResourceStorage` checks `is_uploaded_file()`, which is never true in a CLI test
run. They therefore only run on v14 — see
[Core version aware code](../architecture/core-version-aware-code.md) for the
group mechanism.

## See also

- [JavaScript tests](javascript-tests.md) — the harness, the stubs, and what
  they deliberately do not cover.
- [Frontend assets](../development/frontend-assets.md) — the build, the
  committed artifacts and the vendored-library rule.
- [Functional tests](functional-tests.md) — the harness these plugin tests use,
  and the JSON endpoint test pattern.
- [Testing](Index.md) — both suites and their conventions.
- `packages/fgtclb/academic-persons-edit/Documentation/ProfileEditing/Index.rst`
  — the integrator-facing description of the endpoints being driven.
