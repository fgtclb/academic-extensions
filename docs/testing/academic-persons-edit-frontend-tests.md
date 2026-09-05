# Frontend verification for `academic-persons-edit`

The profile editing frontend is the largest piece of TypeScript in this
repository — one entry point and seven modules under
[`Resources/Private/TypeScript/frontend/`](../../packages/fgtclb/academic-persons-edit/Resources/Private/TypeScript/),
compiled by the repository build into committed JavaScript under
[`Resources/Public/JavaScript/`](../../packages/fgtclb/academic-persons-edit/Resources/Public/JavaScript/).

This page says how it is verified, and — more importantly — how it is **not**.

## There is no JavaScript test runner

Nothing in this repository executes the modules. There is no Jest, no Vitest and
no jsdom environment, in this extension or in any other. The former
`Resources/Public/Development/` tree of this extension was removed when the
frontend moved to the repository-wide toolchain; do not recreate test
infrastructure below `Resources/Public/`, which holds distributable artifacts.

That is a gap, and it is named rather than papered over. A behavioural harness
is the first task of the Lit port (ACE-509), because the port rewrites exactly
the sequencing — open, close, focus return, scroll restore — that nothing
currently checks.

One defect this gap has already let through, kept here as the concrete case a
harness has to be able to reproduce: the document editor closes through Vue's
`after-leave` hook, which runs *after* the leaving element has been removed from
the document. Handing the plugin root to `destroyRichTextEditors()` there
therefore destroyed no editor of the closing view — its textareas were no longer
below the root — while it did destroy every permanently rendered profile-field
CKEditor, because `Partials/Profile/Field/Control.html` puts a
`data-pe-rich-text` textarea under the root for each of them. Nothing in this
repository can observe that: it takes a DOM, a Vue transition and a live editor
instance, and none of the three exists in either PHP suite. The fix is verified
by reading Vue's `BaseTransition` and by the argument the hook is called with,
not by a test.

A second defect of the same class, and the one that says most about what the
harness has to reach: the templates emit their hooks as `data-pe-*`, and the
TypeScript read every one of them as `element.dataset.ie…` — the spelling of a
pre-rename draft. `data-pe-for` is `dataset.peFor`, never `dataset.ieFor`, so
thirteen keys across `fields.ts`, `documents.ts` and `rich-text.ts` read
`undefined` at runtime: the per-field clear, cancel and save buttons, the
visibility checkbox preview, the field groups and their preview mode, the
edit-all label, the rich text character limit and the document value and sort
handling. Eleven of the thirteen have a rendered counterpart in the markup and
were therefore broken; `peFor` is both written and queried by the module itself
*and* emitted by twelve template sites, so it was broken on both sides; only
`peDocumentWasDisabled` is purely internal and was self-consistent.

**No gate in this repository could see it.** The functional tests assert the
rendered HTML and never execute the module, `lintTypescript` has no rule for it,
and `typecheckJs` is satisfied because `dataset` is a `DOMStringMap` — every key
of it is `string | undefined`, including one that no element ever carries.

Two things came out of that, and only one of them is coverage:

- `profile/common.ts` declares the hook set as a type and reads and writes it
  through `hooks(element)`. An unknown key is a `typecheckJs` failure now, so a
  prefix rename cannot half-apply *inside* the TypeScript again. That is a
  compile-time guard, not a test.
- The other direction — an attribute renamed in a template and nowhere else, or
  a `querySelector` whose selector no element matches — is still uncovered, and
  a grep of one source tree against the other is exactly the source-text test
  this page rules out below. **The only real coverage for it is the behavioural
  JavaScript harness of ACE-509**, which drives the rendered markup through the
  module in a DOM. Those thirteen readers are the concrete case it has to be
  able to fail on.

**Do not close the gap with assertions on source text.** The previous attempt
did: a unit test compared the `.ts` files against literal strings, down to the
whitespace between two statements and the number of `requestAnimationFrame(`
calls in a module. Tests of that shape cannot fail for a behavioural regression
and fail for every refactor, so they were removed with the editing rewrite
(ACE-262) rather than carried.

## Repository gates

Run them from the repository root. All four are core-version independent and
need no `composerUpdate`; they use the pinned Node.js container, so no
package-local `npm install` is needed either.

```bash
Build/Scripts/runTests.sh -s buildJs
Build/Scripts/runTests.sh -s checkJsBuildClean
Build/Scripts/runTests.sh -s lintTypescript -n
Build/Scripts/runTests.sh -s typecheckJs
```

| Suite               | What it establishes                                                                 |
|---------------------|-------------------------------------------------------------------------------------|
| `buildJs`           | Compiles the TypeScript and the SCSS and updates the committed artifacts            |
| `checkJsBuildClean` | Rebuilds only the generated outputs and fails when the result differs from the tree |
| `lintTypescript -n` | Checks the TypeScript without modifying it                                          |
| `typecheckJs`       | Runs the type checker — esbuild strips types, it does not validate them             |

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

- [Frontend assets](../development/frontend-assets.md) — the build, the
  committed artifacts and the vendored-library rule.
- [Functional tests](functional-tests.md) — the harness these plugin tests use,
  and the JSON endpoint test pattern.
- [Testing](Index.md) — both suites and their conventions.
- `packages/fgtclb/academic-persons-edit/Documentation/ProfileEditing/Index.rst`
  — the integrator-facing description of the endpoints being driven.
