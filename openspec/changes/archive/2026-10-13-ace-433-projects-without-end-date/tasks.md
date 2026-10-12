## 1. Re-check the premises

- [x] 1.1 Re-measure every statement of `design.md`, *Context*, on `main` HEAD
  for TYPO3 v13 and v14: the column declaration, the missing `nullable` and
  `default`, the two filter conditions, `ActiveState::fromEndDate()`, the
  `IS NULL` rendering of `equals(…, null)` in both installed cores, and the
  pinned tests. Verify by reading the files. If one is false, stop and update
  this change first.

## 2. Tests first

- [x] 2.1 In `packages/fgtclb/academic-projects/Tests/Functional/Domain/Repository/ProjectRepositoryTest.php`
  change the expected result of
  `theActiveStateSelectsOpenEndedAndStillRunningProjects()` to `[10, 11, 13]`,
  turn `aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted()` into a
  test that uid 13 is in the "Active" result and not in the "Completed" one
  (renamed accordingly), and add `13 => 'active'` to the `active` case of
  `filteredStates()`. Run the class on v13 and record that these three go red
  on the unchanged query.
  Recorded on TYPO3 13.4.36, SQLite, before the fix: 11 tests, 3 failures,
  `theActiveStateSelectsOpenEndedAndStillRunningProjects()` (uid 13 missing),
  `aProjectWithoutAnyEndDateValueIsActiveAndNotCompleted()` and the `active`
  case of `everyProjectAFilterListsIsInTheStateOfThatFilter()`.
- [x] 2.2 Rewrite the docblocks of the tests from 2.1 and of
  `aProjectWithoutAnyEndDateValueIsActive()` so that they describe the fixed
  behaviour and no longer announce ACE-433 as pending. Verify with
  `grep -n "ACE-433" Tests/` that only an explanation of the past defect is
  left, if any.
- [x] 2.3 Add a case to
  `Tests/Functional/Plugins/AcademicProjectsProjectListPluginTest.php`: a
  project list content element preset to active projects (`settings.activeState`
  `active`) on a fixture with one project page whose end date is `\NULL`, one
  with `0` and one that has ended. Assert that the `\NULL` and the `0` project
  are rendered and the ended one is not. Record that it fails on the unchanged
  query because the `\NULL` project is missing.
  Recorded on TYPO3 13.4.36, SQLite, before the fix:
  `projectListPluginPresetToActiveProjectsListsProjectsWithoutAnEndDate()`
  failed, the card "Project without an end date value" was missing.

## 3. Implementation

- [x] 3.1 Add `$query->equals('txAcademicprojectsEndDate', null)` as a third
  arm of the "Active" condition in `ProjectRepository::findByDemand()`, with a
  comment that names why the column can hold `NULL`. Leave the "Completed"
  condition untouched. Verify that the tests of group 2 pass on v13 and v14.
- [x] 3.2 Run `ProjectRepositoryTest` and the plugin test of 2.3 on PostgreSQL
  (`-b docker`) and MariaDB for v13 and v14, and record the result.
  Recorded: the full functional suite passed on 13.4.36 and 14.3.8, on SQLite
  (16 chunks), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`), 5593 tests
  listed and executed on v13, 5613 on v14.

## 4. Documentation

- [x] 4.1 Replace the gap paragraph in
  `packages/fgtclb/academic-projects/Documentation/Configuration/Index.rst`
  (section "The state of a project", today `:541-545`) by a statement that a
  project without a stored end date is active and listed by the "Active"
  filter.
- [x] 4.2 Remove the gap sentence from
  `Documentation/Changelog/3.0/Feature-ProjectActiveStateBadge.rst`
  (today `:38-40`), keeping the reference to the configuration chapter only
  where it still helps.
- [x] 4.3 Add `Documentation/Changelog/3.0/Important-ProjectsWithoutEndDateAreActive.rst`
  from the template in `Build/Documentation/Templates/`: which pages are
  affected, that they now appear in "Active" lists and in content elements
  preset to active projects, that no data changes. Render it and check that
  it produces no definition list.
- [x] 4.4 Add a short section to `docs/architecture/database-queries.md` on a
  column that holds `NULL` although its TCA field is not `nullable`: why
  DataHandler keeps it, and that a filter on such a column names `IS NULL`
  explicitly. Link it from the page's own table of contents if it has one.

## 5. Definition of done

- [x] 5.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then `lintPhp`,
  `cgl -n`, `phpstan`, `unit` and `functional` with `-t 13` green, functional
  on SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`).
- [x] 5.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `Documentation/` changelog entry are part of the
  change, and `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 5.5 Every test of group 2 was shown red on the unchanged query, as
  recorded there.
- [x] 5.6 Commit in TYPO3 Core format as
  `[BUGFIX] ACE-433: Treat a NULL end date as active`, body wrapped at 72,
  footer `Resolves: ACE-433`, no attribution of any kind.
- [x] 5.7 Archive the change as the last commit of the same pull request and
  verify the modified requirement landed in
  `openspec/specs/academic-projects/project-active-state/spec.md`.
