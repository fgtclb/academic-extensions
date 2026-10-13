## 1. Before the first fix

- [ ] 1.1 Re-measure the premises of `design.md`, *Context*, on `2` HEAD for
  v12 and v13. Done when each is confirmed or the change is updated first.
- [ ] 1.2 Work in a git worktree below `.agent/tmp/wt/`, on a branch from `2`.

## 2. ACE-438: tests first

- [ ] 2.1 `AbstractDemandValuesTest` and `AbstractTypesTest`: the cases of
  `main` (no empty item, empty label, empty value, `=`, `a==b`), and the guard
  tests for the two exceptions and an array result.
- [ ] 2.2 `ProfileRepositoryOrderingTest`: an empty allowed sort and group
  value with "none", and "none" grouped but sorted by last name, under a
  frontend request built the v12 way.
- [ ] 2.3 `ConfiguredValueItemsTest`: the five selects compiled through
  FormEngine with trailing commas, `TcaDatabaseRecord` through
  `GeneralUtility::makeInstance()`.
- [ ] 2.4 Run 2.1 to 2.3 on unchanged code for v12 and v13, 2.2 on SQLite,
  PostgreSQL (`-b docker`) and MariaDB, and record each result and database
  error here.

## 3. ACE-438: implementation, documentation and commit

- [ ] 3.1 The parsers and the guard of `main`. 2.1 to 2.3 pass.
- [ ] 3.2 `Documentation/Changelog/2.4/Important-EmptyConfiguredValuesAreIgnored.rst`,
  the manual correction, the section of `backend-select-items.md` and the line
  references of `class-design.md`.
- [ ] 3.3 `lintPhp`, `cgl -n`, `phpstan`, `unit` and the functional tests of
  `academic-persons` on SQLite for v12 and v13 on this commit alone.
- [ ] 3.4 Commit `[BUGFIX] ACE-438: Drop empty configured list values`,
  footers `Resolves: ACE-438` and `Related: ACE-434`.

## 4. ACE-439: tests first

- [ ] 4.1 `ProfileDemandTest`: the direction cases of `main`, each starting
  from the other direction, and the unknown direction cases.
- [ ] 4.2 `ProfileRepositoryOrderingTest`: `DESC` by last name.
- [ ] 4.3 `AcademicPersonsListPluginTest`: the constant `DESC` on an element
  without its own direction.
- [ ] 4.4 Run 4.1 to 4.3 on unchanged code for v12 and v13 and record the
  results here.

## 5. ACE-439: implementation, documentation and commit

- [ ] 5.1 `ProfileDemand::setSortByDirection()` as on `main`. 4.1 to 4.3 pass.
- [ ] 5.2 `Documentation/Changelog/2.4/Important-SortDirectionIsNormalised.rst`.
  No manual page and no `docs/` page documents the setting, check again.
- [ ] 5.3 Commit `[BUGFIX] ACE-439: Normalise persons sort direction`, footer
  `Resolves: ACE-439`.

## 6. Definition of done

- [ ] 6.1 For `-t 12 -p 8.1` and `-t 13 -p 8.2`, each after its own
  `composerUpdate`: `lintPhp`, `cgl -n`, `phpstan`, `unit`, and `functional`
  on SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`).
  Record the counts here.
- [ ] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green, and no rendered
  changelog entry contains a definition list.
- [ ] 6.3 A dedicated review of the branch before the push, until the
  reviewer has no open finding.
- [ ] 6.4 Archive the change as the last commit of the same pull request,
  `[TASK] ACE-438: Archive the persons ordering fix`, footers
  `Related: ACE-438` and `Related: ACE-439`.
