## 1. Verify the premises and the base

- [x] 1.1 Check the state of pull request #850 (ACE-818). It has grown into
  the stack #929 (#850, #924, #926, #927, #928), all open and green on
  2026-10-10. Four layers touch files of this change: #850 the template,
  the partials, the script and the tests, #926 the template,
  `LegacyDeliveryTest` and the seed docs, #927 `frontend-assets.md`, #928 the
  manual. This change therefore branches from the top of the stack,
  `ace-892-class-docs` at `c5498dae1`, and is opened as a further layer of
  #929. Every file of this change is edited in the stack's version, its
  classes and structure kept.
  - Stack only on green heads, and record the base commit in the pull
    request.
  - While the base is a stack branch, never merge this pull request, it would
    land in that branch.
  - Rebase whenever a layer below changes. Once the stack is merged by
    rebase, its commits carry new hashes on `main`:
    `git rebase --onto origin/main <old base head> <branch>`, retarget the
    pull request to `main`, so it carries only its own commits.
  - Regenerate the sqlite templates and the seed manifests (5.4) after the
    last rebase, the binary templates cannot be merged.
- [x] 1.2 On that base, on both core versions, confirm that `RECORDS` and
  `CONTENT` hand their request to the records they render, that the
  `currentContentObject` attribute of that request is the renderer of the
  "Insert records" element, and that `data` and `currentRecord` are public. If
  anything differs, stop and update `design.md`.
- [x] 1.3 Confirm that the study plan script still resolves `data-dialog-id`
  with `document.getElementById()`, and that no content uid the seed is to use
  is taken. Content 65 and 66 are taken on the stack, the seed uses 67 to 70
  (German 567 to 570), page 241, semesters 9 and 10, modules 25 to 28.

## 2. Tests first

- [x] 2.1 JavaScript tests in `Tests/JavaScript/academic-study-plan.test.ts`,
  with the markup of #850:
  - the same plan twice with the previous ids (no prefix), where click, Enter
    and close on each copy act on that copy's dialog
  - the first copy `hidden`, where the trigger of the second opens the dialog
    in the second
  - a foreign `dialog#popup-1` before the plan, where the plan opens its own
  - a dialog rendered outside the container, still found through the document

  Show the first three to fail against today's lookup. The fourth passes
  today and must keep passing.
- [x] 2.2 Functional tests in
  `Tests/Functional/ContentElement/AcademicStudyPlanContentElementTest.php`, on
  both core versions, with a fixture page holding a plan and two "Insert
  records" elements of it: three plan containers, dialog ids `popup-<m>`,
  `popup-c<first>-<m>` and `popup-c<second>-<m>`, no dialog id twice on the
  page, every `data-dialog-id` naming a dialog inside its own container. A
  page with the plan alone renders `popup-<m>` as before. Show the insert
  test to fail by returning an empty prefix from the processor, and the plan
  alone to fail by prefixing a plan whatever renders it.
- [x] 2.3 In `AcademicStudyPlanContentElementLocalizationTest`, a translated
  page with a translated "Insert records" element has no repeated dialog id
  and the same ids as its default language page. Show it to fail by building
  the prefix from the plan's own uid instead of the inserting element, and
  its German half on its own by taking the localized uid of the inserting
  element. Its shortcut header is not rendered by the core template, so the
  test counts the translated plans instead.

## 3. The script

- [x] 3.1 In `Resources/Private/TypeScript/frontend/academic-study-plan.ts`,
  resolve a trigger's dialog among the `dialog[id]` elements of its plan
  first and in the document only when the plan has none, keep the module's
  dialog as fallback. Rebuild with `runTests.sh -s buildJs`, then `testJs`,
  `typecheckJs`, `lintTypescript -n` and `checkJsBuildClean` green, task 2.1
  green.

## 4. The processor and the markup

- [x] 4.1 `Classes/DataProcessing/StudyPlanProcessor.php`: read the
  `currentContentObject` attribute of the content object's request and assign
  `idPrefix` = `c<uid>-` only when the attribute is a `ContentObjectRenderer`
  whose `currentRecord` starts with `tt_content:`, `''` otherwise. One code
  path for both cores, the docblock names the `@internal` access and why it is
  accepted, no new phpstan baseline entry on either core. Tasks 2.2 and 2.3
  green.
- [x] 4.2 Pass `idPrefix` from `Templates/AcademicStudyPlan.html` through
  `Partials/StudyPlan/Semester.html` and `Module.html` to `ModuleDialog.html`,
  render `popup-{idPrefix}{module.uid}` in the trigger and the dialog, and
  update the `f:comment` argument lists. Confirm the single plan page renders
  byte identical to its base.

## 5. The seed

- [x] 5.1 `Build/Scripts/generateLegacyScenario.php`: map the `records` field
  of a content element to the legacy uids, with a test in its existing test
  class shown to fail without the mapping. The test reads the committed
  `ScenarioLegacy.yaml`, so it was shown red on a mirror generated without the
  mapping.
- [x] 5.2 `packages-dev/dev-site/.../Scenario.yaml`: the page
  `/study-plan/several-plans` with its German variant, plan A, plan B and two
  "Insert records" elements of A, then regenerate `ScenarioLegacy.yaml` and
  check it with `--check`.
- [x] 5.3 Extend the attribute uid rule of `LegacyDeliveryTest` to a value with
  two numbers (`popup-c<uid>-<uid>`), with its comment, shown to fail on the
  new page pair without it.
- [x] 5.4 `runTests.sh -s seedManifest` for v13 and v14, regenerate
  `sqlite-databases/core-13.sqlite` and `core-14.sqlite` from a seeded empty
  instance, and keep `SeedManifestTest`, `SnapshotManifestTest` and
  `LegacyDeliveryTest` green, and the page counts of the docs and of the
  generator updated.
- [x] 5.5 Browser check on both development instances, in the `/` and the
  `/legacy/` tree and in German: every trigger opens a dialog inside its own
  copy, also with the first copy hidden, the page usable after closing, no
  dialog id twice, no console error. Record the numbers in the pull request.

## 6. Documentation

- [x] 6.1 `Documentation/Templates/Index.rst` of `academic_study_plan`: the
  `data-dialog-id` row says the dialog is looked up inside the plan first, the
  partial arguments name `idPrefix`, and a plan inside a grid element gets the
  prefix too.
- [x] 6.2 `Documentation/Changelog/3.0/Important-StudyPlanDialogIdsInInsertedRecords.rst`
  from the template in `Build/Documentation/Templates/`: the prefixed ids
  inside "Insert records" and grid elements, the `idPrefix` variable for
  overrides, the two cases that keep a repeated id.
- [x] 6.3 `docs/development/frontend-assets.md`: an id named by markup is
  resolved inside its own plan first. `docs/development/instances.md` and
  `docs/testing/seed-verification.md`: the new page, the counts and the new
  uid rule of `LegacyDeliveryTest`.

## 7. File the issue

- [x] 7.1 File the ACE issue (Bug, Version 3.0.0, subtask of ACE-10), relate
  it to ACE-704, ACE-707 and ACE-818, and rename the change to
  `ace-<NNN>-study-plan-same-element-twice`.

## 8. Definition of done

- [x] 8.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (SQLite, PostgreSQL, MariaDB) green.
- [x] 8.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` (SQLite, PostgreSQL, MariaDB) green.
- [x] 8.3 `testJs`, `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 8.4 `docs/` is updated, and `README.md` and `CONTRIBUTING.md` still
  only summarize.
- [ ] 8.5 Commit as `[BUGFIX] ACE-<NNN>: Open study plan dialogs per copy`
  in TYPO3 Core format, and archive the change as the last commit of the pull
  request. Merge only after #850, never ahead of it.
