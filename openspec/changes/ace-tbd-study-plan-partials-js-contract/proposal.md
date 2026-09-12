## Why

The study plan element of `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) renders all of its markup from one
template, and its module finds the parts by class name (`.module`, `.header`,
`.filter`, `.modal-trigger`, `.module dialog`). Every markup change a project
needs, such as the accordion filter of ace-demo and another project or the
module as dialog trigger in a third project's fork, breaks those selectors, so
all three forked the module. The partial and layout constants already point at
`Resources/Private/Frontend/Default/Partials/` and `Layouts/`, which do not
exist.

## What Changes

- The template is split into four partials: filter, semester, module and
  module dialog.
- The module finds its parts by `data-study-plan-*` attributes. Markup that
  carries only the class names of 3.0 keeps working until 4.0 and is
  **deprecated**.
- An optional collapsible filter, switched by a site setting, off by default.
- The markup contract is documented for integrators.

The default output a visitor sees does not change. Behaviour is identical on
TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-study-plan/frontend-markup-contract`: which markup the study plan
  script relies on, how an integrator overrides a part of it, and the
  collapsible filter.

### Modified Capabilities

None.

## Impact

- Template and four new partials; TypeScript source and committed build
  output of the module.
- One more setting in the settings definitions added by
  `ace-tbd-study-plan-asset-switch`.
- New `testJs` coverage below
  `packages/fgtclb/academic-study-plan/Tests/JavaScript/`.

## Non-goals

- The module-as-trigger markup of that project's fork as an upstream option.
  A module partial override can place the trigger attribute on the module
  element itself, and the script supports that (see the design).
- Multi-semester modules and dual study phases; not an upstream epic until a
  second project asks.
- Configurable selectors.
- Backporting to branch `2`, which has no TypeScript build.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-18`). Three of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-study-plan-partials-js-contract` when the issue is filed after
implementation.

Depends on `ace-tbd-study-plan-appearance-layout` and
`ace-tbd-study-plan-asset-switch`.
