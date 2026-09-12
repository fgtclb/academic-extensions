## 1. Tests first, on TYPO3 v13

- [ ] 1.1 Add functional content element tests: with defaults the page
  contains the study plan stylesheet and the module import; with
  `plugin.tx_academicstudyplan.assets.css` off only the module; with
  `plugin.tx_academicstudyplan.assets.js` off only the stylesheet. Run them
  before the change and record that the switched-off asset is still present.
- [ ] 1.2 Add the static-template variant of the stylesheet case through the
  constant, asserting the stylesheet is absent.

## 2. Implementation

- [ ] 2.1 Add `Configuration/Sets/ContentElement/settings.definitions.yaml`
  with both settings, and the same paths and defaults in
  `constants.typoscript`; map them in `setup.typoscript`.
- [ ] 2.2 Wrap both `f:asset` lines of the template in `f:if`; run group 1
  green on v13, then on v14.
- [ ] 2.3 Check in the `core-13` instance whether the filter placeholder item
  is visible with the module switched off; if it is, mark it `hidden` in the
  template, un-hide the clones in the module, rebuild with `buildJs` and
  verify `checkJsBuildClean`.

## 3. Documentation

- [ ] 3.1 Document both switches, the constants and the placeholder behaviour
  in `academic-study-plan/Documentation/Configuration/Index.rst`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-StudyPlanAssetSwitches.rst`.
- [ ] 3.3 Confirm `docs/` needs no change: the settings follow the existing
  pattern documented in `docs/architecture/typoscript-and-site-sets.md`; state
  it in the pull request.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [ ] 4.2 Rename the change to `ace-<NNN>-study-plan-asset-switch`.
- [ ] 4.3 Commit as `[FEATURE] ACE-<NNN>: Allow skipping the study plan
  assets` in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green, and the node
  suites if 2.3 touched the module.
- [ ] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.5 Archive the change as the last commit of the pull request.
