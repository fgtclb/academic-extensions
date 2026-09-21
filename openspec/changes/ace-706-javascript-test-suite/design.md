## Context

Branch `2` ships four frontend TypeScript modules:

- `academic-partners/frontend/map.ts`
- `academic-study-plan/frontend/academic-study-plan.ts`
- `academic-jobs/frontend/ckeditor.ts`
- `academic-persons-edit/frontend/ckeditor.ts`

`main` ships twenty-odd, almost all of them the profile editor of ACE-262,
which does not exist here. Its harness was built for that editor.

## Goals / Non-Goals

**Goals:**

- A suite that executes the shipped modules against a real DOM, run by
  `runTests.sh` in a container and by one CI step.
- The defect of ACE-705 covered on this branch.

**Non-Goals:**

- Parity with the harness of `main`.
- Covering every module. Three of the four are left for later, with reasons.

## Decisions

### Re-derived, not copied

Four things of `main`'s harness have no caller here and are left out: the six
CKEditor 5 stub specifiers, the CropperJS stub, the recording request double
(`fetch.mjs`), and most of the browser globals. The globals list holds exactly
what these four modules reach for unqualified — `Element`, `Event`,
`HTMLDialogElement`, `HTMLElement`, `HTMLTextAreaElement`, `Node` — and it is
explicit so that a source reaching for something new fails loudly rather than
picking up a node global of the same name.

`resolve-hook.mjs` therefore has two rules instead of three, and says in its
docblock that this branch stubs nothing and why: no module here imports a
library at all. The two CKEditor 4 modules and the map reach for a global their
template loads from a content delivery network, which is not a module specifier.

Added rather than copied: `setViewportWidth()`. jsdom reports a fixed
`innerWidth` of 1024 and cannot resize, and the study plan switches between a
column layout and an accordion at 768 — so the accordion is unobservable
without it. `main` has no such helper because no module of `main` reads
`innerWidth`.

### The `<dialog>` model comes along as it is

jsdom 29.1.1 declares `HTMLDialogElement` and reflects its `open` property, and
implements **none** of `show()`, `showModal()` and `close()`. The study plan
opens a dialog per module, so without the model of ACE-704 it dies with a
`TypeError`. It is taken over unchanged, including what it deliberately does
not model — the top layer, the backdrop, the escape key, the focus a browser
restores on close, and the queued rather than synchronous `close` event.

### `Build/extensions.mjs` is extracted here too

The resolve hook derives the import map prefix from the package directory, and
the build derives its entry points from the same list. On this branch that list
lives inside `esbuild.mjs` and is not exported. Extracting it is what keeps the
build and the harness from each carrying their own idea of what an extension
is; `main` extracted it for the same reason.

### The harness tests live with `academic_study_plan`

`node --test` is pointed at `packages/*/*/Tests/JavaScript/`, and `Build/tests/`
is not an extension, so the harness's own tests have to live below one. On
`main` that is `academic-persons-edit`, the extension the harness was built for.
Here it is `academic_study_plan`, for the same reason: it ships the only module
on this branch that does more than configure a library loaded from elsewhere,
and it is the module this suite exists to cover.

### Two changes to a shipped module, both erased at build time

`academic-study-plan.ts` wrote its container as a constructor parameter
property. Node strips types and does not transform, so such a property cannot be
loaded at all — `Build/tsconfig.tests.json` sets `erasableSyntaxOnly`, which
turns it into a type error rather than a runtime surprise. It is now declared
and assigned, exactly as on `main`.

The module also starts itself on import. Node hands every test of a file the
same module instance, so the second test of a file would drive a module that
never saw its markup; the initialiser is exported for that, and the
`DOMContentLoaded` start stays. Both changes are erased by esbuild, so the
committed artifact is equivalent.

## Risks / Trade-offs

- [One more development dependency] → jsdom and its 37 packages, none of them
  distributed. The alternative, a browser runner, needs binaries on the host,
  against the rule that a suite installs nothing there.
- [A model that is wrong is worse than an absent one] → Under-model rather than
  over-model: what is not modelled fails loudly, what is modelled wrongly turns
  a defect green. Each deviation is named in the docblock and in `docs/`.
- [The fixtures are copies of rendered markup and copies drift] → The page says
  so and asks for a functional counterpart asserting the same inventory. The
  study plan fixture is small and every element in it is one the module queries.

## Migration Plan

Nothing to migrate. A developer gains `runTests.sh -s testJs`; CI gains a step.

## Open Questions

None.
