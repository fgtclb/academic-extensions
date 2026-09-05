# JavaScript tests

The third suite. It executes the frontend TypeScript of the extensions against
a real DOM, which neither PHP suite can do and which the two node gates —
`lintTypescript` and `typecheckJs` — deliberately do not attempt: one checks
style, the other checks types, and neither has ever run a line of it.

```bash
Build/Scripts/runTests.sh -s testJs
```

Core version independent, like every other node suite: it runs the sources of
this repository, never the installed core, so it needs neither `-t` nor a
`composerUpdate`.

## Why it exists

Because a whole class of defect in this repository is invisible to everything
else. The one it was built for: a rewrite of the profile editing frontend
shipped thirteen dead `dataset.ie*` keys — property names that no longer
matched the attributes the templates render, read at twenty-five sites —
through thirty-five green CI jobs. Every gate passed. `lintTypescript` had
nothing to say about a valid property access, `typecheckJs` types `dataset` as
an index signature, the build compiled it, and the PHP suites asserted on
rendered markup that was correct. Nothing executed the module.

A second one, from the same rewrite, is the shape this suite is really about:
the document editor closes through a transition hook that runs *after* the
leaving element has been removed from the document, and is handed that element.
Destroying "the rich text editors below the plugin root" there destroys none of
the closing view — its textareas are no longer below the root — and destroys
every permanently rendered profile-field editor instead, taking a field the
visitor still has open off screen with it. It takes a DOM, a transition and a
live editor instance to observe.

The harness comes *before* the code it guards, deliberately. A guard added
afterwards proves nothing about what was written before it.

## What must never come back

**Assertions on source text.** A previous attempt to close this gap compared the
`.ts` files against literal strings from PHP — down to the whitespace between
two statements and the number of `requestAnimationFrame(` calls in a module.
Tests of that shape cannot fail for a behavioural regression and fail for every
refactor. They are not to be recreated in any language, including this one.

A test here drives the shipped module and asserts on what the DOM looks like
afterwards. If a test can be satisfied without executing the code it is about,
it does not belong.

## Layout

| Path                                       | Contents                                                                         |
|--------------------------------------------|----------------------------------------------------------------------------------|
| `packages/*/*/Tests/JavaScript/*.test.ts`  | The tests, next to the extension, mirroring `Tests/Unit` and `Tests/Functional`. |
| `packages/*/*/Tests/JavaScript/Fixtures/*` | The markup the tests drive, extracted from the extension's own Fluid partials.   |
| `Build/tests/register.mjs`                 | The `--import` entry: installs the resolve hook and the DOM.                     |
| `Build/tests/resolve-hook.mjs`             | Models the TYPO3 import map for node.                                            |
| `Build/tests/dom.mjs`, `dom.d.mts`         | The jsdom window, the browser globals and the DOM helpers.                       |
| `Build/tests/fetch.mjs`, `fetch.d.mts`     | The recording request double.                                                    |
| `Build/tests/stubs/*.mjs`                  | The libraries this repository does not own.                                      |
| `Build/tsconfig.tests.json`                | The type check of the tests, a project of its own.                               |

A fixture is imported by its real file name — `./Fixtures/profile-editing.ts` —
because node's type stripping rewrites no relative specifier, and only the bare
specifiers of the shipped modules go through the resolve hook.
`Build/tsconfig.tests.json` therefore sets `allowImportingTsExtensions`, which
is safe where nothing is emitted.

The tests live under `Tests/` and not below `Resources/Private/TypeScript/`
because [the build](../development/frontend-assets.md) walks only the latter for
entry points: a test file below it would be compiled into
`Resources/Public/JavaScript/` and committed as a distributable artifact.

The harness's own tests are in
`packages/fgtclb/academic-persons-edit/Tests/JavaScript/Harness/`, for the one
reason that `node --test` is pointed at `packages/*/*/Tests/JavaScript/` and
`Build/tests/` is not an extension. They cover what everything else here rests
on: that the DOM is installed before a module is evaluated, that each modelled
browser API behaves the way the browser does, that the resolve hook maps the
import map specifiers and the stubs, that the request double records and
refuses what it says it does, and that the pinned Lit is core's Lit.

### Where a fixture comes from

Fixtures carry the markup the modules are driven against, and it is **extracted
from the Fluid partials rather than invented**, with the partial and its lines
named at each block: `f:translate` becomes the text it resolves to and
`core:icon` becomes nothing. Everything a module queries — the `data-pe-*`
hooks, the ids, the toggled class names, the structure the `closest()` calls
walk — is kept verbatim, so a template that drops one of them turns the tests
red.

That is a copy, and a copy drifts. The rule that keeps it honest: **a fixture
that stands in for a Fluid prototype is accompanied by a functional test that
asserts the same inventory** — the same `<template data-pe-proto>` blocks, the
same slot names — against the really rendered page. Drift between the fixture
and the template is then a failure of that functional test rather than a
silently green JavaScript suite. A fixture without such a counterpart is a
fixture nobody is checking.

## The runner

Node's own `node --test`, with [jsdom](https://github.com/jsdom/jsdom) for the
DOM. The container image is the one the other node suites already use,
`ghcr.io/typo3/core-testing-nodejs24:1.1`, and node 24 brings three things that
make the choice work: the test runner itself, a spec reporter, and native
TypeScript type stripping — so the `.ts` sources are imported directly, with no
compile step between the test and the module it is about.

Two alternatives were rejected:

- **A browser runner** (`@web/test-runner` with Playwright or Chrome) would give
  real fidelity: real CKEditor, real CropperJS, real CSS transitions. It needs
  browser binaries, so a second container image or a download step, and the rule
  in this repository is that a suite runs with nothing installed on the host. It
  stays the escape hatch if jsdom turns out to be insufficient for a specific
  component — as an *additional* suite, never as a weakening of this one.
- **vitest** has the better developer experience and installs vite, rollup and
  their trees for a repository whose entire build is five `.mjs` files.

The cost is two dependencies, and it is not free: `jsdom` brings 37 packages and
the Lit pin six, on top of the 158 `Build/node_modules` already held. None of
them is distributed — the repository root is a composer `project`, and nothing
below `Build/` reaches a composer dist or a TER archive.

## Module resolution

In a browser the modules address each other by the bare specifier the TYPO3
import map resolves, `@fgtclb/<package>/frontend/<module>.js`, because only a
specifier that goes through the map receives TYPO3's `?bust=` cache key. Node
knows nothing about that map, so
[`resolve-hook.mjs`](../../Build/tests/resolve-hook.mjs) models it: the prefix
is derived from the package directory by the same discovery the build uses
([`Build/extensions.mjs`](../../Build/extensions.mjs)), and it resolves to the
**TypeScript source**, never to the compiled artifact — a test that ran against
the artifact would pass on a stale one, which is precisely what
`checkJsBuildClean` exists to prevent.

`Build/extensions.mjs` is shared with `Build/esbuild.mjs` on purpose. The build
and the tests would otherwise each carry their own idea of what an extension is
and which prefix it publishes under, and two such lists disagree eventually.

A specifier that looks like one of this repository's modules and has no source
behind it raises an error naming both, rather than falling through to node's
"cannot find package".

## Lit, and the pin that has to be watched

`lit` is the one library that is resolved for real and not from where the
importing file stands. TYPO3 core delivers it through the import map — `EXT:core`
maps `lit`, `lit/`, `lit-element` and `lit-html` with no tag and no dependency —
while node resolves a bare specifier from the importer upwards, and the sources
live in `packages/`, where there is no `node_modules` at all. The hook therefore
retries the resolution from `Build/`, whose `package.json` pins the exact
versions both supported cores ship: lit 3.2.0, lit-html 3.2.0, lit-element 4.1.0
and `@lit/reactive-element` 2.0.4, the last three through npm `overrides`
because `lit` itself is a facade that depends on them by range. It is a real
dependency and never a stub: the elements are written in Lit, and a stub would
test nothing.

Nothing else notices when the two drift apart. A core update that ships a newer
Lit changes what the browser runs and leaves the pinned copy where it was, and
the suite stays green while it stops describing the shipped behaviour. The guard
is `Harness/lit-version.test.ts`: it reads the version each of the three
packages registers on `globalThis` in the file core delivers, compares it with
what is installed below `Build/node_modules/`, and fails with both numbers.

**Its limit, stated rather than discovered**: it needs an installed core, and
the node suites run without a `composerUpdate`. In the local gate chain a core
is there and the guard bites; in the `frontend-assets` job of CI there is none
and the test reports itself as skipped rather than as passed. Moving `testJs`
into a job that installs PHP dependencies would close that at the price of
running it four times over sources that cannot differ, which is why it is a
named gap and not a change.

## The stubs, and what they cost

Two libraries are replaced: the six `@ckeditor/ckeditor5-*` bundles and the
vendored CropperJS. Both are browser-only and neither is ours.

A stub is a liability, so the list stays short on purpose and every entry is a
library this repository does not own. Anything written here is tested for real,
Lit included.

The CKEditor and CropperJS stubs report through the DOM —
`data-test-ckeditor="live"` / `"destroyed"` and `data-test-ckeditor-destroys` on
the textarea, `data-test-cropper` on the cropper's container — so a test asserts
on the element it already has and imports nothing from the harness.

Two things about the CKEditor stub are read *from* the markup rather than
reported into it. `data-test-ckeditor-initial` on the textarea is what the
editor makes of the rendered source — the real one normalises, so the value it
hands back is not always the value the template wrote, and the baseline the
field editing compares against depends on the difference. And its
`editing.view.focus()` focuses the textarea: jsdom has no contenteditable view,
so the textarea stands in for it, and `document.activeElement === textarea` is
the only way a focus that goes through the editor can be observed at all.

What this buys is the lifecycle: which editor is created, on which field, when
it is destroyed, and in which order. What it does not buy is anything about the
libraries themselves. Their integration is proven by a manual check per core
version, and that is a real gap, named rather than papered over.

The same applies to layout and animation. jsdom computes no transitions and
every `getBoundingClientRect()` is zero, so scroll and drop-position arithmetic
is tested with injected rectangles (`setBoundingRect()`) and a box the cropper
can measure with an injected client size (`setClientSize()`), not with real
geometry.

## The requests

`installFetch()` replaces `globalThis.fetch` with a queue of prepared responses
and a log of the calls that took them. The responses are real `Response`
objects, so `ok`, `status` and `json()` behave as they do in a browser,
including the rejection on a body that is not JSON.

A request that nobody queued a response for is rejected with a message naming
its url rather than being answered with a default, and `respondLater()` queues
one the test settles by hand — a prepared response otherwise resolves in the
same microtask as the call that takes it, so two "overlapping" requests would in
truth run one after the other.

What a test asserts on a recorded call is the method, the url, the headers and
the decoded body. The `X-Requested-With` header is asserted everywhere it is
sent: it is the guard every writing endpoint checks, because a custom header
cannot be set cross origin without a preflight.

## What jsdom does not have, and what stands in for it

Modelled in `dom.mjs`. Each is there because a shipped module reaches for it,
with one exception that is marked: `KeyboardEvent` exists in jsdom and is simply
not on `globalThis`, and nothing but a test constructs one.

| Name                              | Modelled as                                                                          |
|-----------------------------------|--------------------------------------------------------------------------------------|
| `CSS.escape`                      | The CSSOM algorithm, transcribed rather than approximated.                           |
| `matchMedia`                      | Nothing matches, which is what a browser answers for a reduced-motion query.         |
| `scrollIntoView`                  | Records the alignment as `data-test-scrolled-into-view` on the element.              |
| `URL.createObjectURL` / `revoke…` | A register per object, so `isObjectUrlAlive()` can prove a preview url was released. |
| `DragEvent`, `DataTransfer`       | `createDragEvent()`, with the pointer position and a recording data transfer.        |
| `KeyboardEvent` (not a global)    | `createKeyboardEvent()`, from the window's own constructor — only a test raises one. |
| `getBoundingClientRect`           | `setBoundingRect()`, per element and per test.                                       |
| `clientWidth` / `clientHeight`    | `setClientSize()`, shadowed on the instance because both are prototype getters.      |

`settle()` drains microtasks and never reaches a timer, which is what makes it
useful — but the document editor reports its finished close one animation frame
after the leave transition, deliberately, so that its owner does not tear it out
of the document from inside Lit's own update cycle. A test that is about a close
therefore waits with `nextFrame()`.

The object urls are modelled rather than delegated because the two realms
disagree: node's `URL.createObjectURL` takes only a node `Blob`, jsdom's
`FormData` takes only a jsdom one, and a browser has one realm and no such
split. `Blob` and `File` on `globalThis` are therefore jsdom's.

The list of browser globals is explicit rather than a wholesale copy of the
window, so a source reaching for something new fails loudly here instead of
silently picking up a node global of the same name.

## Two constraints the harness imposes

1. **No TypeScript that is not erasable.** Node strips type annotations; it does
   not transform. An `enum`, a `namespace`, a constructor parameter property or
   a decorator needs emitted JavaScript and fails to run.
   `Build/tsconfig.tests.json` sets `erasableSyntaxOnly`, so that is a type
   error in every module a test imports rather than a runtime surprise. It also
   means the Lit elements use `static properties = { … }` and a bare
   `customElements.define()` instead of the `@customElement` / `@property`
   decorators — which keeps the esbuild output plain as well. It has a second
   consequence that is easy to step on: `target` is ES2022, so
   `useDefineForClassFields` is on, and a class *field* would define an own
   property that shadows the accessor Lit installs on the prototype. A reactive
   property is therefore declared with `declare` and given its value in the
   constructor, which is erasable and which the element would render exactly
   once without.
2. **One window per process, not per test.** The shipped modules keep
   module-level state — WeakMaps of live editors, a request counter — and node's
   module cache hands every test in a file the same instance, so a fresh window
   per test would leave that state pointing at a document nobody sees.
   `customElements` adds a harder reason: it is a per-window registry and an
   element cannot be undefined again. Node runs each test file in a child
   process, so `register.mjs` installs the window once and a test file calls
   `resetBody()` per test.

## Type checking and linting

`typecheckJs` runs **two** projects: `Build/tsconfig.json` for the shipped
modules, which run in a browser and have `"types": []`, and
`Build/tsconfig.tests.json` for the tests, which run in node and need
`@types/node`. `types` is a property of a whole program, so one project cannot
serve both without letting a shipped module reach for `process` and still pass.

`lintTypescript` covers the tests with the same house rules as the sources, with
the browser and node globals both in scope, and covers `Build/tests/**` as node
code.

## In CI

One step in the `frontend-assets` job of
[`ci.yml`](../../.github/workflows/ci.yml), between the type check and
`checkJsBuildClean`. It belongs there because it runs in the same node
container, reuses the npm cache the job restored and has no core version — in
the `unit` matrix it would install PHP dependencies and run four times over
sources that cannot differ between those runs.

## See also

- [Testing](Index.md) — the two PHP suites and the rules that apply to all three.
- [Frontend assets](../development/frontend-assets.md) — the build, the
  committed artifacts and the import map convention this harness models.
- [Quality gates](../development/quality-gates.md) — where this suite sits among
  the others.
