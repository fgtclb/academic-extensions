## 1. Verify before writing

- [ ] 1.1 In the `core-13` instance, remove `special.skipSync` from the
  settings file of the site and send a `skipSync` update to the endpoint;
  expect 422 `invalid_profile_data`. Repeat with `readonly`. If either writes,
  stop and file a bugfix instead of documenting the setting.
- [ ] 1.2 Prove the include order: in the `core-13` and `core-14` instances,
  include the Bootstrap Package by its static template on a site that has the
  set of `academic_programs`, and check that a program page resolves
  `Academicprogram.html`. Then include it by its site set and check that it
  resolves `AcademicProgram.html`. On branch `2`, check the order of two
  static templates on TYPO3 v12. Record the results in the pull request.
- [ ] 1.3 Check every row of the persons-edit table in design.md against
  `academic-persons/Configuration/AcademicPersons/Settings.yaml` and the
  manuals of `academic_persons` and `academic_persons_edit`, and note the
  label each row links.
- [ ] 1.4 Check that the label the other manuals will link exists in
  `https://docs.typo3.org/p/fgtclb/academic-base/main/en-us/objects.inv.json`
  before task 3.2 starts.

## 2. Profile editor

- [ ] 2.1 Add the section "From an override to a setting" below "The replaced
  view" of `academic-persons-edit/Documentation/Upgrade/From24To30.rst`, with
  the table of design.md and what stays a template override. Verify with
  `checkRstRenderingAll`.
- [ ] 2.2 Add the same section to `From23To30.rst` and compare the two with
  `diff`, which may only differ in the labels.

## 3. Installations outside the documented paths

- [ ] 3.1 Add the section "Installations outside these paths" with its label
  to "Before you start" of `academic-base/Documentation/Upgrade/Index.rst`.
  Verify the 2.1 and 2.2 entries it names against
  `Documentation/Changelog/2.1/` and `2.2/` of persons, programs and category
  types, and the branch aliases against both `composer.json` files. Commit and
  merge this before task 3.2.
- [ ] 3.2 After `main` is rendered with the label, link it from "Before you
  start" of the other eleven `Upgrade/Index.rst` as an interlink to
  `fgtclb/academic-base`. Verify with `checkRstRenderingAll`, which fails on a
  label the inventory lacks.

## 4. Include order

- [ ] 4.1 Extend "Affected Installations" of
  `Changelog/2.4/Important-PageTemplateNameIsSetExplicitly.rst` in
  `academic-programs`, `academic-partners` and `academic-projects` with the
  result of task 1.2. Verify that the three files differ only in the page type
  and the doktype, as they do now.

## 5. Issue

- [ ] 5.1 File the ACE issue in YouTrack, relate it to ACE-367, ACE-450,
  ACE-601 and ACE-910, and verify the key.
- [ ] 5.2 Rename the change to `ace-<NNN>-integrator-migration-guide`.
- [ ] 5.3 Commit as `[DOCS] ACE-<NNN>: <subject>` in TYPO3 Core format, one
  commit per pull request of tasks 3.1 and 3.2.

## 6. Branch 2

- [ ] 6.1 After the backport analysis of `docs/workflow/backporting.md`, a
  change of its own on branch `2`: the task 4.1 text, byte-identical to `main`
  (verify with `cmp` against `origin/main`), and the section of task 3.1
  without the 3.0 step.

## 7. Definition of done

- [ ] 7.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan` and `unit` green. No `functional` run, the change has no runtime
  behaviour.
- [ ] 7.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan` and `unit` green.
- [ ] 7.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.4 `docs/` needs no change, the rules for upgrade chapters and
  interlinks exist. State that, and that no changelog entry is added because
  the change is documentation only, in the pull request.
- [ ] 7.5 Archive the change as the last commit of the pull request of task
  3.2.
