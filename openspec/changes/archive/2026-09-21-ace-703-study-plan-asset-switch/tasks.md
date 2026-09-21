## 1. Tests first, on TYPO3 v13

- [x] 1.1 `AcademicStudyPlanAssetSwitchTest`. Run against `main` before the
  change: 8 tests, 5 failures — every switched-off case still carried the asset
  it was meant to drop, through both delivery mechanisms. The three that passed
  are the default cases and the page without the element, which are the
  regression guard.
- [x] 1.2 Add the static-template variant of the stylesheet case through the
  constant, asserting the stylesheet is absent.

## 2. Implementation

- [x] 2.1 Add `Configuration/Sets/ContentElement/settings.definitions.yaml`
  with both settings, and the same paths and defaults in
  `constants.typoscript`; map them in `setup.typoscript`.
- [x] 2.2 Wrap both `f:asset` lines of the template in `f:if`; run group 1
  green on v13, then on v14.
- [x] 2.3 The filter placeholder item is visible without the module - nothing
  in the stylesheet or the user agent hides it. Marked `hidden` in the
  template, un-hidden on the clones in the module, rebuilt with `buildJs`.
  Covered by `contentElementHidesTheFilterTemplateItem()` (unconditional, so it
  sits with the content element test rather than with the switches) and by a
  `testJs` assertion, which was shown to fail with the `removeAttribute` call
  taken out: `[true, true]` instead of `[false, false]`. Established from the
  sources and the rendered page rather than from the instance.

## 3. Documentation

- [x] 3.1 Document both switches, the constants and the placeholder behaviour
  in `academic-study-plan/Documentation/Configuration/Index.rst`.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-StudyPlanAssetSwitches.rst`.
- [x] 3.3 `docs/`: the matching-defaults rule was already in
  `docs/architecture/typoscript-and-site-sets.md`, but making a shipped asset
  optional is a pattern this repository did not have. Recorded as
  *Making an asset optional* in `docs/development/frontend-assets.md`.

## 4. File the issue

- [x] 4.1 ACE-703, filed after the gates were green and verified against the
  API. Type `Story`, Version `3.0.0`, `relates to` HWG-143 and HWG-275.
- [x] 4.2 Renamed to `ace-703-study-plan-asset-switch`, and the three other
  changes referencing it by name updated with it.
- [x] 4.3 Two commits rather than one, because the hidden filter template item
  is a defect that stands without the switches and needs its own changelog
  kind: `[BUGFIX] ACE-703: Hide the study plan filter template` and
  `[FEATURE] ACE-703: Make the study plan assets optional`. The bugfix commit
  was verified green on its own.

## 5. Definition of done

- [x] 5.1 After `composerUpdate` for TYPO3 v13 (v13.4.35): `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`, sqlite) green.
- [x] 5.2 After `composerUpdate` for TYPO3 v14 (v14.3.7): `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (`-j auto`, sqlite) green.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green; 2.3 touched the
  module, so `lintTypescript`, `typecheckJs`, `testJs` and `checkJsBuildClean`
  ran too.
- [x] 5.4 `docs/` gained *Making an asset optional* in
  `docs/development/frontend-assets.md`; the extension's `Documentation/` gained
  `Feature-StudyPlanAssetSwitches.rst` for the switches and
  `Important-StudyPlanFilterTemplateItemIsHidden.rst` for the markup change, and
  a chapter in `Documentation/Configuration/Index.rst`. `README.md` and
  `CONTRIBUTING.md` are untouched.
- [x] 5.5 Archived as the last commit of the pull request.
