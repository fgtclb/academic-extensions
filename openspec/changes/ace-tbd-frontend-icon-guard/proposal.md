## Why

After `ace-tbd-persons-frontend-icons`, `ace-tbd-jobs-study-plan-frontend-icons`
and `ace-tbd-programs-partners-projects-frontend-icons`, every frontend template
renders its icons through the frontend icon registry of
`ace-tbd-frontend-icon-registry`, and the core icon registry serves the
backend only. Nothing keeps it that way. A template that uses `core:icon`
again still renders, from the backend registry the round decouples from, or
as the `default-not-found` placeholder. The same placeholder answers an
identifier that is registered only in `Configuration/Icons.php`, or nowhere,
because the frontend registry has no fallback. Both ship silently unless a
functional test reaches the branch that renders the icon.

## What Changes

- A new unit test in `packages-dev/monorepo-shared`, the ninth check there,
  with two rules over every Fluid template below
  `packages/fgtclb/*/Resources/Private/`:
  - no template renders an icon through a ViewHelper of the core namespace
    (`core:icon`, `core:iconForRecord`, `core:iconForResource`, under any
    prefix declared for it), in tag or inline notation. `<f:comment>` content
    is never rendered and is skipped. The page module category summary of
    `category_types` is the one allowed backend template, listed with its
    reason, and a second test fails once that file is gone.
  - every icon identifier a template names literally for the frontend icon
    ViewHelper is registered in a `Configuration/FrontendIcons.php` of the
    repository, or is a category type icon that `category_types` contributes
    from a `Configuration/CategoryTypes.yaml` of the repository.
- `AGENTS.md` and the `docs/` pages that list the checks of
  `packages-dev/monorepo-shared` name the new one, eight become nine.
- `docs/architecture/icons.md` names the check where it explains how a
  template's icons stay resolvable.

No extension changes. The test reads the templates of all twelve extensions
and names one shipped file, of `category_types` (`typo3-category-types`).
TYPO3 v13 and v14 behave the same, the test reads files and runs no TYPO3
code.

## Non-goals

- Changing a template or a registration. The test is green once the three
  changes above are merged, and is shown red by a `core:icon` reintroduced on
  purpose.
- Identifiers built from a variable (`academic_jobs-{item}`,
  `category_types.partners.{type}`, `{fact.iconIdentifier}`). They stay with
  the functional tests of their extension.
- The reverse rule, that every registered frontend icon is rendered
  somewhere. An unused registration can be deliberate.
- Whether the source file of a registration exists, which the functional
  icon tests of each extension prove by rendering.
- Project templates, manuals and test fixtures.
- Branch `2`, which has no frontend icon registry.

## Capabilities

### New Capabilities

None. The change is repository tooling, never shipped, and sets
`skip_specs: true`.

### Modified Capabilities

None.

## Impact

- One new test class in `packages-dev/monorepo-shared/Tests/Unit/`.
- `AGENTS.md`, `docs/testing/unit-tests.md`,
  `docs/development/quality-gates.md`, `docs/development/monorepo-layout.md`,
  `docs/architecture/icons.md`.
- Integrators and site packages: nothing to do. A contributor who adds
  `core:icon` to a frontend template, or names an icon not registered for the
  frontend, gets a failing `unit` run with file and line.
- Depends on the three changes named above. On `main` today the first rule
  fails for 27 template files.
