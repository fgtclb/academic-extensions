## 1. Tests first

- [ ] 1.1 In `packages/fgtclb/academic-projects/Tests/Functional/Domain/Repository/ProjectRepositoryTest.php`
  expect `[10, 11, 13]` from
  `theActiveStateSelectsOpenEndedAndStillRunningProjects()`, and turn
  `aProjectWithoutAnyEndDateValueIsNeitherActiveNorCompleted()` into
  `aProjectWithoutAnyEndDateValueIsActiveAndNotCompleted()`. Correct the
  docblocks. Run the class on v12 and record that both go red on the unchanged
  query.
- [ ] 1.2 Add a case to
  `Tests/Functional/Plugins/AcademicProjectsProjectListPluginTest.php` with a
  fixture of one project page with a `\NULL` end date, one with `0` and one
  that has ended, in a content element preset to `active`. Assert that the
  first two are rendered and the ended one is not. Record that it fails on the
  unchanged query.

## 2. Implementation

- [ ] 2.1 Add `$query->equals('txAcademicprojectsEndDate', null)` as a third
  arm of the "Active" condition in `ProjectRepository::findByDemand()`, with a
  comment why the column holds `NULL`. Leave "Completed" untouched. Verify that
  the tests of group 1 pass on v12 and v13.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/2.4/Important-ProjectsWithoutEndDateAreActive.rst`:
  which pages are affected, that they now appear in "Active" lists and in
  content elements preset to active projects, that no data changes. Render it
  and check that it produces no definition list.
- [ ] 3.2 Add a section to `docs/architecture/database-queries.md` on a column
  that holds `NULL` although its TCA field is not `nullable`, with the v12 and
  v13 lines.

## 4. Definition of done

- [ ] 4.1 `Build/Scripts/runTests.sh -t 12 -p 8.1 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green, functional on
  SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`).
- [ ] 4.2 The same for `-t 13 -p 8.2` after its own `composerUpdate`.
- [ ] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 `docs/` and the `Documentation/Changelog/2.4/` entry are part of the
  change, `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [ ] 4.5 Commit as `[BUGFIX] ACE-433: Treat a NULL end date as active`, body
  wrapped at 72, footer `Resolves: ACE-433`, no attribution of any kind.
- [ ] 4.6 Archive the change as the last commit of the same pull request and
  verify the added requirement landed in
  `openspec/specs/academic-projects/list-filter/spec.md`.
