## 1. Partials

- [ ] 1.1 Split the template into `StudyPlan/Filter`, `StudyPlan/Semester`,
  `StudyPlan/Module` and `StudyPlan/ModuleDialog`, adding the data attributes;
  extend the functional content element test to assert each attribute, and
  show it fails on the unsplit template.
- [ ] 1.2 Assert in the same test that the visible text and classes of the
  default fixture are unchanged, by comparing against the output recorded
  before the split.

## 2. Module

- [ ] 2.1 Switch every lookup of `academic-study-plan.ts` to the data
  attribute with a per-part class fallback, and export the initialiser.
- [ ] 2.2 Add `Tests/JavaScript/academic-study-plan.test.ts` (jsdom): a
  fixture with data attributes only builds the filter, highlights and opens a
  dialog; a fixture with the 3.0 classes only does the same. Remove the
  fallback and watch the legacy case fail, remove the attribute lookup and
  watch the data case fail, restore both.
- [ ] 2.3 Cover keyboard operation of the filter buttons, the semester
  headers and the dialog close button in the same test file.
- [ ] 2.4 Add a fixture with two modules whose module elements carry
  `data-study-plan-dialog-trigger` themselves; assert that activating the
  second module opens its own dialog and not the first one's. Restrict the
  trigger lookup to descendants once, watch the test fail, restore it.

## 3. Collapsible filter

- [ ] 3.1 Add `plugin.tx_academicstudyplan.filter.collapsible` to the
  settings definitions and constants, and render
  `data-study-plan-filter-collapsible` from the filter partial; a functional
  test shows the attribute appears only with the setting on.
- [ ] 3.2 Insert the toggle in the module; the jsdom test expands and
  collapses it by keyboard and asserts `aria-expanded`, and fails with the
  toggle handler removed.
- [ ] 3.3 Run `buildJs`, commit the output, and verify `checkJsBuildClean`,
  `typecheckJs`, `lintTypescript` and `testJs` are green.

## 4. Documentation

- [ ] 4.1 Document the partials, their arguments and every data attribute
  with its element in a new `academic-study-plan/Documentation/Templates/Index.rst`,
  linked from the manual's index, including that the trigger attribute may
  sit on the module element itself and why that is not the default.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-StudyPlanPartialsAndDataAttributes.rst`
  and `Documentation/Changelog/3.0/Deprecation-StudyPlanClassSelectors.rst`.
- [ ] 4.3 Add the study plan to any per-extension list in
  `docs/testing/javascript-tests.md`; verify with `lintMarkdown -n`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [ ] 5.2 Rename the change to `ace-<NNN>-study-plan-partials-js-contract`.
- [ ] 5.3 Commit as `[FEATURE] ACE-<NNN>: Split the study plan into partials`
  in TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 6.3 `lintMarkdown -n`, `checkRstRenderingAll` and the node suites of
  3.3 green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entries are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 6.5 Archive the change as the last commit of the pull request.
