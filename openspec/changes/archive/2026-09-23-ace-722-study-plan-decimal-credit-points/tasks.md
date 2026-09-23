## 1. Tests first, on TYPO3 v13

- [x] 1.1 Extend the functional content element test: a module with 2.5 and
  a semester with 30 credit points render "2.5" and "30"; run it before the
  change and record that 2.5 renders as "2" or is rejected.
- [x] 1.2 Add a DataHandler test writing 2.5 (and 30, "2,5", 2.555) to both
  tables and asserting what the database stores; the output of a stored 2.50
  is the rendering test of 1.1.

## 2. Implementation

- [x] 2.1 Add `format => 'decimal'` to both TCA columns and remove the two
  `credit_points` lines from `ext_tables.sql`; a functional test gives the
  tables the former integer column and proves that the change the database
  compare offers keeps existing values, on every DBMS (instead of checking
  the SQLite-only `core-13` and `core-14` instances by hand).
- [x] 2.2 Cast `credit_points` to `float` in `StudyPlanService` for semesters
  and modules; extend `StudyPlanServiceTest` to assert the type.
- [x] 2.3 Run group 1 green on v13, then on v14, each on SQLite and on
  PostgreSQL.

## 3. Documentation

- [x] 3.1 Add the changelog entry
  `Documentation/Changelog/3.0/Important-StudyPlanCreditPointsAreDecimal.rst`
  with the mandatory database compare and the note for projects that
  redefined the columns.
- [x] 3.2 Document the credit points handed to templates in
  `academic-study-plan/Documentation/Templates/Index.rst` (the extension's
  documentation has no editor section; the changelog entry tells editors).
- [x] 3.3 `docs/testing/functional-tests.md`: the schema update test as a
  worked example (the first test that runs the database compare against a
  former column).

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [x] 4.2 Rename the change to `ace-722-study-plan-decimal-credit-points`.
- [x] 4.3 Commit as `[FEATURE] ACE-722: Allow decimal study plan credits` in
  TYPO3 Core format (the planned `… credit points` exceeded 52 characters).

## 5. Definition of done

- [x] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.3 `functional` green on PostgreSQL (`-d postgres`) for both versions,
  since the change alters columns and writes them.
- [x] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.5 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [x] 5.6 Archive the change as the last commit of the pull request.
