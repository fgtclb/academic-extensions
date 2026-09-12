## 1. Loader

- [ ] 1.1 Add two fixture packages declaring the same identifier in different
  groups and a loader unit test expecting the exception with both extension
  keys in its message; run it on main and record the failure.
- [ ] 1.2 Implement the cross-group check after all packages are read; the
  test turns green. Add a test where a third package removes one of the two
  types and loading succeeds, shown red by running the check before removals
  are applied.

## 2. Projects type

- [ ] 2.1 Functional TCA test with programs and projects loaded, asserting the
  `sys_category.type` items carry unique values; record the failure on main.
- [ ] 2.2 Rename the projects type to `project_department` in
  `academic-projects/Configuration/CategoryTypes.yaml`, keeping label keys and
  icon; the test from 2.1 turns green.

## 3. Migration command

- [ ] 3.1 Add the `final` `academic:projects:department:migrate` command with
  `#[AsCommand]` to `academic-projects/Classes/Command/`, following
  `docs/architecture/database-queries.md`; no class in `Classes/Upgrades/`
  and no new `Install\Updates` reference.
- [ ] 3.2 Functional command test with a CSV fixture: categories used on
  project pages only (with a translation and a workspace version), on program
  pages only, on both, and on no page; assert the result CSV and the listed
  uids. Shown red by dropping the doktype condition, which moves the mixed
  category.
- [ ] 3.3 Separate functional test class without `academic_programs` loaded,
  asserting every `department` category moves; shown red by ignoring the
  installed-extension check.
- [ ] 3.4 Test a second run: nothing changes, the ambiguous categories are
  listed again and the exit status is zero; shown red by moving the
  ambiguous categories on the second run.
- [ ] 3.5 Run the command tests with `-d sqlite` and `-d postgres` on v13 and
  v14.

## 4. Seed

- [ ] 4.1 Change category 21 in `Scenario.yaml` to `project_department`,
  regenerate `ScenarioLegacy.yaml` and confirm with the generator's `--check`.
- [ ] 4.2 Run `seedManifest` with `-t 13` and `-t 14`, each after its own
  `composerUpdate`, and commit both manifests; rebuild the instance database
  templates as `docs/testing/seed-verification.md` describes.

## 5. Documentation

- [ ] 5.1 Add `typo3-category-types/Documentation/Changelog/3.0/Breaking-CategoryTypeIdentifiersUniqueAcrossGroups.rst`
  and `academic-projects/Documentation/Changelog/3.0/Breaking-ProjectDepartmentCategoryType.rst`
  (rename, the migration command as the upgrade step, ambiguous categories,
  rollback) from the templates in `Build/Documentation/Templates/`.
- [ ] 5.2 State the uniqueness rule on the `CategoryTypes.yaml` page of
  `category_types` (from `ace-tbd-category-types-yaml-docs`, or its TCA page
  if that has not landed).
- [ ] 5.3 `docs/`: add the rule where `docs/` describes category type
  registration; if no page does, say in the pull request why `docs/` is
  unchanged.

## 6. Track the issue

- [ ] 6.1 Verify ACE-64 in YouTrack, rename the change to
  `ace-64-category-type-identifier-collision`, and commit in TYPO3 Core
  format as `[!!!][BUGFIX] ACE-64: Rename the project department`.

## 7. Definition of done

- [ ] 7.1 `lintPhp` green.
- [ ] 7.2 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, and `functional -d postgres` green, since
  the command writes.
- [ ] 7.3 After `-t 14 -s composerUpdate`: the same suites green with
  `-t 14`, including `functional -d postgres`.
- [ ] 7.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 7.5 `docs/` and the `Documentation/Changelog/3.0/` entries are part of
  the change; `README.md` and `CONTRIBUTING.md` still only summarize and
  link.
- [ ] 7.6 Archive the change as the last commit of the pull request.
