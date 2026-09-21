## Why

This branch has no way to execute a line of the JavaScript it ships. There is
no `testJs` suite in `Build/Scripts/runTests.sh`, no `test` script in
`Build/package.json`, no `Build/tests/` and no `Tests/JavaScript/` in any
extension. `lintTypescript` checks style and `typecheckJs` checks types;
neither has ever run the code.

ACE-705 made that concrete. The category filter of the study plan built its
buttons by substituting an editor's category title into markup and parsing the
result with `innerHTML`, so the title became markup. The fix is covered on
`main`, where the suite exists and a fixture with a hostile title goes red
without it; the backport to this branch shipped **unproven**, which was
accepted deliberately and named in the change, the commit and the pull request.

## What Changes

- `Build/tests/` — a jsdom window, the browser globals the shipped modules
  reach for, and a resolve hook that models the TYPO3 import map so a test
  imports a module by the bare specifier a browser resolves.
- `Build/extensions.mjs` — the extension discovery, extracted from
  `esbuild.mjs` so that the build and the harness cannot disagree about what an
  extension is or which prefix it publishes under.
- `Build/tsconfig.tests.json`, a second project for `typecheckJs`; a `testJs`
  suite in `runTests.sh`; a step in the `frontend-assets` job of `ci.yml`.
- The harness's own tests, and the first behavioural test of a shipped module:
  the study plan, including the hostile category title that proves ACE-705
  here.
- `docs/testing/javascript-tests.md`, written for this branch.

Nothing a visitor, an editor or an integrator can observe changes.

## Capabilities

### New Capabilities

None. A test harness specifies no behaviour of the product: it observes the
behaviour the other changes specify. The change therefore sets
`skip_specs: true` rather than inventing a requirement.

### Modified Capabilities

None.

## Impact

- One development dependency, `jsdom`: 37 packages on top of the 159
  `Build/node_modules` already held. Nothing below `Build/` reaches a composer
  dist or a TER archive.
- One shipped module changes, and only in what node needs to load it:
  `academic-study-plan.ts` declares and assigns its container instead of
  writing a constructor parameter property, and exports its initialiser. Both
  are erased at build time, so the committed artifact is equivalent.
- No PHP, TCA, TypoScript, Fluid or database change, and no changelog entry:
  nothing a user or an integrator can notice is different.

## Non-goals

- **Porting the tests of `main`.** They are almost all about the profile editor
  that ACE-262 built, which does not exist on this branch.
- **A test for the partner map.** `main`'s `map.test.ts` cannot come along: the
  module here still carries both defects `main` fixed in it — an unconditional
  `DOMContentLoaded` listener that an `async` module never sees, and
  `Number('')` being `0`, so an absent coordinate is drawn at 0/0. The test
  would fail, correctly. That is a defect report, not this change's work.
- **A test for the two CKEditor 4 modules.** They poll for a global their
  template loads from a content delivery network; a test needs a stand-in for
  it, and neither module is what this suite was built for.
- **Fixing what the suite now makes visible.** The module keys the plans it has
  started by the *value* of `data-study-plan`, so two plans of one page that
  share a value — or carry none — collapse onto one instance and only the
  first is ever started. `main` fixed that with ACE-704 by keying on the
  element. Here it is recorded, and the fixtures carry distinct values.

## Source

Backport of the test harness `main` carries, re-derived rather than moved: the
two branches ship different frontend modules, so the stubs, the recording
request double and most of the browser globals of `main` have no caller here.
