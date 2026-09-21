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

Because this branch had no way to observe what its JavaScript does. The defect
that settled it: the category filter of the study plan built its buttons by
substituting a category's title into the rendered markup as a **string** and
parsing the result with `innerHTML`, so a title an editor typed became markup.
Every gate here was green over it for the whole life of the 2.x line, and the
fix arrived as a backport that could not bring its own test along.

The test that now covers it — a fixture with a category titled
`<img src=x onerror=…>`, asserting the title as the button's text and asserting
that nothing it names reached the document — is the first behavioural test of a
shipped module on this branch.

## What must never come back

**Assertions on source text.** A test that compares a `.ts` file against literal
strings cannot fail for a behavioural regression and fails for every refactor.

A test here drives the shipped module and asserts on what the DOM looks like
afterwards. If a test can be satisfied without executing the code it is about,
it does not belong.

## Layout

| Path                                        | Contents                                                                          |
|---------------------------------------------|-----------------------------------------------------------------------------------|
| `packages/*/*/Tests/JavaScript/*.test.ts`   | The tests, next to the extension, mirroring `Tests/Unit` and `Tests/Functional`.  |
| `Build/tests/register.mjs`                  | The `--import` entry: installs the resolve hook and the DOM.                      |
| `Build/tests/resolve-hook.mjs`              | Models the TYPO3 import map for node.                                             |
| `Build/tests/dom.mjs`, `dom.d.mts`          | The jsdom window, the browser globals and the DOM helpers.                        |
| `Build/tsconfig.tests.json`                 | The type check of the tests, a project of its own.                                |

The tests live under `Tests/` and not below `Resources/Private/TypeScript/`
because [the build](../development/frontend-assets.md) walks only the latter for
entry points: a test file below it would be compiled into
`Resources/Public/JavaScript/` and committed as a distributable artifact.

The harness's own tests are in
`packages/fgtclb/academic-study-plan/Tests/JavaScript/Harness/`, for the one
reason that `node --test` is pointed at `packages/*/*/Tests/JavaScript/` and
`Build/tests/` is not an extension. `academic_study_plan` is the extension the
suite was built for here — it ships the only module on this branch that does
more than configure a library loaded from elsewhere.

### Where a fixture comes from

Fixtures carry the markup the modules are driven against, and it is **extracted
from the Fluid templates rather than invented**, with the template named at each
block: `f:translate` becomes the text it resolves to and `core:icon` becomes
nothing. Everything a module queries — the class names, the `data-*` attributes
it reads, the structure the `closest()` calls walk — is kept verbatim, so a
template that drops one of them turns the tests red.

That is a copy, and a copy drifts. A fixture standing in for rendered markup
wants a functional test asserting the same inventory against the really rendered
page, so that drift is a failure of that test rather than a silently green
JavaScript suite.

## The runner

Node's own `node --test`, with [jsdom](https://github.com/jsdom/jsdom) for the
DOM. The container image is the one the other node suites already use,
`ghcr.io/typo3/core-testing-nodejs24:1.1`, and node 24 brings three things that
make the choice work: the test runner itself, a spec reporter, and native
TypeScript type stripping — so the `.ts` sources are imported directly, with no
compile step between the test and the module it is about.

Two alternatives were rejected:

- **A browser runner** would give real fidelity, and needs browser binaries — so
  a second container image or a download step, against the rule that a suite
  runs with nothing installed on the host. It stays the escape hatch if jsdom
  turns out to be insufficient for a specific module, as an *additional* suite.
- **vitest** has the better developer experience and installs vite, rollup and
  their trees for a repository whose entire build is four `.mjs` files.

The cost is one dependency: `jsdom` brings 37 packages on top of the 159
`Build/node_modules` already held. None of them is distributed — the repository
root is a composer `project`, and nothing below `Build/` reaches a composer dist
or a TER archive.

## Module resolution

In a browser the modules are addressed by the bare specifier the TYPO3 import
map resolves, `@fgtclb/<package>/frontend/<module>.js`. Node knows nothing about
that map, so [`resolve-hook.mjs`](../../Build/tests/resolve-hook.mjs) models it:
the prefix is derived from the package directory by the same discovery the build
uses ([`Build/extensions.mjs`](../../Build/extensions.mjs)), and it resolves to
the **TypeScript source**, never to the compiled artifact — a test that ran
against the artifact would pass on a stale one, which is precisely what
`checkJsBuildClean` exists to prevent.

`Build/extensions.mjs` is shared with `Build/esbuild.mjs` on purpose. The build
and the tests would otherwise each carry their own idea of what an extension is
and which prefix it publishes under, and two such lists disagree eventually.

A specifier that looks like one of this repository's modules and has no source
behind it raises an error naming both, rather than falling through to node's
"cannot find package".

## Nothing is stubbed here

There is no list of stubbed libraries to keep short: **none of the four frontend
modules of this branch imports a library at all.** The two CKEditor 4 modules
and the partner map reach for a global that their template loads from a content
delivery network, which is not a module specifier and cannot be resolved — a
test of one of those has to put that global in place itself, and none does yet.

## What jsdom does not have, and what stands in for it

Modelled in `dom.mjs`. Each is there because a shipped module reaches for it,
with one exception that is marked: `KeyboardEvent` exists in jsdom and is simply
not on `globalThis`, and nothing but a test constructs one.

| Name                            | Modelled as                                                                    |
|---------------------------------|--------------------------------------------------------------------------------|
| `<dialog>` show/showModal/close | The reflected `open` attribute, the `close` event, the focus and the modality. |
| `KeyboardEvent` (not a global)  | `createKeyboardEvent()`, from the window's own constructor.                    |
| `window.innerWidth`             | `setViewportWidth()`; jsdom reports a fixed 1024 and cannot resize.            |

jsdom declares `HTMLDialogElement` and reflects its `open` property, and
implements **none** of `show()`, `showModal()` and `close()` — a module that
opens a dialog dies with a `TypeError`. The model sets the attribute jsdom
already reflects, fires the `close` event, keeps the return value and moves the
focus into the dialog the way the dialog focusing steps do, and records the
modality as `data-test-dialog` so that a module meaning `showModal()` and
calling `show()` is observable. Not modelled, deliberately: the top layer, the
backdrop, the escape key, the focus a browser restores on close, and the fact
that a browser queues the `close` event rather than dispatching it synchronously.

The rule behind that list: **under-model rather than over-model.** A missing
model fails loudly; a wrong one turns a defect into a green test.

The list of browser globals is explicit rather than a wholesale copy of the
window, so a source reaching for something new fails loudly here instead of
silently picking up a node global of the same name. It is short because this
branch ships four modules.

## Two constraints the harness imposes

1. **No TypeScript that is not erasable.** Node strips type annotations; it does
   not transform. An `enum`, a `namespace`, a constructor parameter property or
   a decorator needs emitted JavaScript and fails to run.
   `Build/tsconfig.tests.json` sets `erasableSyntaxOnly`, so that is a type
   error in every module a test imports rather than a runtime surprise. The
   study plan module carried such a parameter property until this suite existed.
2. **One window per process, not per test.** A shipped module keeps module-level
   state — the study plan holds a map of the plans it has started — and node's
   module cache hands every test in a file the same instance, so a fresh window
   per test would leave that state pointing at a document nobody sees.
   `register.mjs` installs the window once and a test file calls `resetBody()`
   per test. A module that starts itself on import therefore needs an exported
   initialiser for the second test of a file to drive it.

## Type checking and linting

`typecheckJs` runs **two** projects: `Build/tsconfig.json` for the shipped
modules, which run in a browser and have `"types": []`, and
`Build/tsconfig.tests.json` for the tests, which run in node and need
`@types/node`. `types` is a property of a whole program, so one project cannot
serve both without letting a shipped module reach for `process` and still pass.

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
