## 1. Before the first fix

- [x] 1.1 Re-measure the premises of `design.md`, *Context*, on `2` HEAD for
  v12 and v13. Done when each is confirmed or the change is updated first.
  Confirmed on `be2cd1678` against 12.4.45 and 13.4.36.
- [x] 1.2 Work in a git worktree below `.agent/tmp/wt/`, on a branch from `2`.

## 2. ACE-438: tests first

- [x] 2.1 `AbstractDemandValuesTest` and `AbstractTypesTest`: the cases of
  `main` (no empty item, empty label, empty value, `=`, `a==b`), and the guard
  tests for the two exceptions and an array result.
- [x] 2.2 `ProfileRepositoryOrderingTest`: an empty allowed sort and group
  value with "none", and "none" grouped but sorted by last name, under a
  frontend request built the v12 way.
- [x] 2.3 `ConfiguredValueItemsTest`: the five selects compiled through
  FormEngine with trailing commas, `TcaDatabaseRecord` through
  `GeneralUtility::makeInstance()`.
- [x] 2.4 Run 2.1 to 2.3 on unchanged code for v12 and v13, 2.2 on SQLite,
  PostgreSQL (`-b docker`) and MariaDB, and record each result and database
  error here.
  Red on both cores.
  Functional, 7 tests, 2 errors and 5 failures on every DBMS and both cores,
  with the errors of `main`: SQLite "no such column:
  tx_academicpersons_domain_model_profile.", MariaDB "Unknown column
  'tx_academicpersons_domain_model_profile.' in 'order clause'", PostgreSQL
  "zero-length delimited identifier". Unit on 12.4.45: 17 failures, 2 errors
  and 5 warnings, the array case raising "Array to string conversion". On
  13.4.36: 16 failures, 3 errors and 4 warnings, the array case raising a
  `TypeError`.

## 3. ACE-438: implementation, documentation and commit

- [x] 3.1 The parsers and the guard of `main`. 2.1 to 2.3 pass.
- [x] 3.2 `Documentation/Changelog/2.4/Important-EmptyConfiguredValuesAreIgnored.rst`,
  the manual correction, the section of `backend-select-items.md` and the line
  references of `class-design.md`.
- [x] 3.3 `lintPhp`, `cgl -n`, `phpstan`, `unit` and the functional tests of
  `academic-persons` on SQLite for v12 and v13 on this commit alone.
  Green on both: 1099 unit tests, and the functional tests of
  `academic-persons` on SQLite, 591 on 12.4.45 and 617 on 13.4.36.
- [x] 3.4 Commit `[BUGFIX] ACE-438: Drop empty configured list values`,
  footers `Resolves: ACE-438` and `Related: ACE-434`.

## 4. ACE-439: tests first

- [x] 4.1 `ProfileDemandTest`: the direction cases of `main`, each starting
  from the other direction, and the unknown direction cases.
- [x] 4.2 `ProfileRepositoryOrderingTest`: `DESC` by last name.
- [x] 4.3 `AcademicPersonsListPluginTest`: the constant `DESC` on an element
  without its own direction.
- [x] 4.4 Run 4.1 to 4.3 on unchanged code for v12 and v13 and record the
  results here.
  Red on 12.4.45 and on 13.4.36: 5 unit failures, `[1, 2, 3]` instead of
  `[3, 1, 2]`, and the list rendered Meyer, Becker, Schulz.

## 5. ACE-439: implementation, documentation and commit

- [x] 5.1 `ProfileDemand::setSortByDirection()` as on `main`. 4.1 to 4.3 pass.
- [x] 5.2 `Documentation/Changelog/2.4/Important-SortDirectionIsNormalised.rst`.
  No manual page and no `docs/` page documents the setting, check again.
  Checked, neither does.
- [x] 5.3 Commit `[BUGFIX] ACE-439: Normalise persons sort direction`, footer
  `Resolves: ACE-439`.

## 6. Definition of done

- [x] 6.1 For `-t 12 -p 8.1` and `-t 13 -p 8.2`, each after its own
  `composerUpdate`: `lintPhp`, `cgl -n`, `phpstan`, `unit`, and `functional`
  on SQLite (`-j auto`), PostgreSQL (`-b docker -j 8`) and MariaDB (`-j 8`).
  Record the counts here.
  12.4.45 and 13.4.36: 1106 unit tests each. Functional on SQLite,
  PostgreSQL and MariaDB: 2325 tests on 12.4.45 and 2453 on 13.4.36, every
  one green.
- [x] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green, and no rendered
  changelog entry contains a definition list.
  Green, and both new entries render without one.
- [x] 6.3 A dedicated review of the branch before the push, until the
  reviewer has no open finding.
  Approved in the second round. Round 1 found the red run of 4.4 recorded
  for v12 only (run on 13.4.36 since, red as on v12) and the functional
  counts missing in 3.3. The stale line reference of
  `AbstractActionController` in `class-design.md` was wrong before this change
  and stays out of it.
- [x] 6.4 Archive the change as the last commit of the same pull request,
  `[TASK] ACE-438: Archive the persons ordering fix`, footers
  `Related: ACE-438` and `Related: ACE-439`.
