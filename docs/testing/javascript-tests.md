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
else. The one it was built for: the profile document editor closes through a
transition hook that runs *after* the leaving element has been removed from the
document, and is handed that element. Destroying "the rich text editors below
the plugin root" there destroys none of the closing view — its textareas are no
longer below the root — and destroys every permanently rendered profile-field
editor instead, taking a field the visitor still has open off screen with it.

Nothing in the PHP suites can observe that. It takes a DOM, a transition and a
live editor instance, and it reached a release unnoticed. It is now
[`rich-text-editor-scope.test.ts`](../../packages/fgtclb/academic-persons-edit/Tests/JavaScript/rich-text-editor-scope.test.ts).

## What must never come back

**Assertions on source text.** A previous attempt to close this gap compared the
`.ts` files against literal strings from PHP — down to the whitespace between
two statements and the number of `requestAnimationFrame(` calls in a module.
Tests of that shape cannot fail for a behavioural regression and fail for every
refactor. They were removed with the editing rewrite (ACE-262) and are not to be
recreated in any language, including this one.

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

Fixtures carry the markup the modules are driven against, and it is **extracted
from the Fluid partials rather than invented**, with the partial and its lines
named at each block: `f:translate` becomes the text it resolves to, `core:icon`
becomes nothing, and the Vue directives are dropped. Everything a module queries
— the `data-pe-*` hooks, the ids, the toggled class names, the structure the
`closest()` calls walk — is kept verbatim, so a template that drops one of them
turns the tests red.

The tests live under `Tests/` and not below `Resources/Private/TypeScript/`
because [the build](../development/frontend-assets.md) walks only the latter for
entry points: a test file below it would be compiled into
`Resources/Public/JavaScript/` and committed as a distributable artifact.

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
  their trees for a repository whose entire build is four `.mjs` files.

The cost of the choice is one dependency, `jsdom`, and it is not free: it adds
37 packages to the 158 `Build/node_modules` already held. None of them is
distributed — the repository root is a composer `project`, and nothing below
`Build/` reaches a composer dist or a TER archive.

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

A specifier that looks like one of this repository's modules and has no source
behind it raises an error naming both, rather than falling through to node's
"cannot find package".

## The stubs, and what they cost

Three libraries are replaced today: the six `@ckeditor/ckeditor5-*` bundles, the
vendored CropperJS and the Vue runtime. All three are browser-only, none is
ours, and the Vue module additionally imports its runtime through a path that
only exists in the *output* tree.

A stub is a liability, so the list stays short on purpose and every entry is a
library this repository does not own.

The CKEditor and CropperJS stubs report through the DOM —
`data-test-ckeditor="live"` / `"destroyed"` and `data-test-ckeditor-destroys` on
the textarea, `data-test-cropper` on the cropper's container — so a test asserts
on the element it already has and imports nothing from the harness.

What this buys is the lifecycle: which editor is created, on which field, when
it is destroyed, and in which order. What it does not buy is anything about the
libraries themselves. Their integration is proven by a manual check per core
version, and that is a real gap, named rather than papered over.

The same applies to layout and animation. jsdom computes no transitions and
every `getBoundingClientRect()` is zero, so scroll and drop-position arithmetic
is tested with injected rectangles (`setBoundingRect()`), not with real
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

Modelled in `dom.mjs`, each because a shipped module reaches for it:

| Name                              | Modelled as                                                                          |
|-----------------------------------|--------------------------------------------------------------------------------------|
| `CSS.escape`                      | The CSSOM algorithm, transcribed rather than approximated.                           |
| `matchMedia`                      | Nothing matches, which is what a browser answers for a reduced-motion query.         |
| `scrollIntoView`                  | Records the alignment as `data-test-scrolled-into-view` on the element.              |
| `URL.createObjectURL` / `revoke…` | A register per object, so `isObjectUrlAlive()` can prove a preview url was released. |
| `DragEvent`, `DataTransfer`       | `createDragEvent()`, with the pointer position and a recording data transfer.        |
| `getBoundingClientRect`           | `setBoundingRect()`, per element and per test.                                       |

The object urls are modelled rather than delegated because the two realms
disagree: node's `URL.createObjectURL` takes only a node `Blob`, jsdom's
`FormData` takes only a jsdom one, and a browser has one realm and no such
split. `Blob` and `File` on `globalThis` are therefore jsdom's.

## Two constraints the harness imposes

1. **No TypeScript that is not erasable.** Node strips type annotations; it does
   not transform. An `enum`, a `namespace`, a constructor parameter property or
   a decorator needs emitted JavaScript and fails to run. `Build/tsconfig.tests.json`
   sets `erasableSyntaxOnly`, so that is a type error in every module a test
   imports rather than a runtime surprise. It also means the Lit components of
   ACE-509 use `static properties = { … }` and a bare `customElements.define()`
   instead of the `@customElement` / `@property` decorators — which keeps the
   esbuild output plain as well.
2. **One window per process, not per test.** The shipped modules keep
   module-level state — WeakMaps of live editors, a request counter — and node's
   module cache hands every test in a file the same instance, so a fresh window
   per test would leave that state pointing at a document nobody sees. Once the
   Lit port lands, `customElements` adds a harder reason: it is a per-window
   registry and an element cannot be undefined again. Node runs each test file
   in a child process, so `register.mjs` installs the window once and a test
   file calls `resetBody()` per test.

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
- [Frontend verification for `academic-persons-edit`](academic-persons-edit-frontend-tests.md)
  — what the PHP tests cover on either side of the JavaScript.
- [Frontend assets](../development/frontend-assets.md) — the build, the
  committed artifacts and the import map convention this harness models.
- [Quality gates](../development/quality-gates.md) — where this suite sits among
  the others.
