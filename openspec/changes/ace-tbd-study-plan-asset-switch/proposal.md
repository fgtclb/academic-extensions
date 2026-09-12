## Why

The study plan element of `academic_study_plan`
(`packages/fgtclb/academic-study-plan`) always registers its stylesheet and
its module from inside its template. A project that styles the element itself
therefore overrides the whole template, and then either loses the module or
copies it. ace-demo and another project ship older copies of the module
without the keyboard handlers the upstream one has, which is an accessibility
regression. A third project unsets the stylesheet.

## What Changes

- Two site settings, `plugin.tx_academicstudyplan.assets.css` and
  `plugin.tx_academicstudyplan.assets.js` (boolean, both on by default), with
  TypoScript constants of the same names for installations using the static
  template.
- With a switch off, the element no longer registers the stylesheet or the
  module respectively; its markup is unchanged.
- The defaults keep today's output.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-study-plan/frontend-assets`: which stylesheet and script the
  study plan element brings to a page, and how an integrator switches them
  off.

### Modified Capabilities

None.

## Impact

- `Configuration/Sets/ContentElement/settings.definitions.yaml` (new),
  `Configuration/TypoScript/ContentElement/constants.typoscript` and
  `setup.typoscript`, and the element template.
- No database or PHP change.

## Non-goals

- Going back to `page.includeCSS`/`page.includeJSFooter`, which loaded the
  files on every page.
- Splitting the template into partials; that is
  `ace-tbd-study-plan-partials-js-contract`.
- Backporting to branch `2`, where the assets are `page.include*` entries a
  project can already unset.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-17`). Three of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-study-plan-asset-switch` when the issue is filed after
implementation.
