## 1. Tests first, on TYPO3 v12

- [x] 1.1 Rendering test: a module with 2.50, semesters with 30.00 and 0.00,
  a module with 0.00 render "2.5 CP", "30 CP" and nothing, and the dialog
  trigger labels announce "30 CP" and "0 CP"; record that it fails before the
  change, and without the cast on a DBMS that returns strings.
- [x] 1.2 DataHandler test writing 2.5, 30, "2,5" and 2.555 to both tables.
- [x] 1.3 Service test asserting the floats; schema update test from the
  former integer column.

## 2. Implementation

- [x] 2.1 `format => 'decimal'` on both TCA columns; `ext_tables.sql`
  declares `decimal(10,2) unsigned NOT NULL DEFAULT '0.00'`.
- [x] 2.2 Cast `credit_points` to `float` in `StudyPlanService` for semesters
  and modules.
- [x] 2.3 Run group 1 green on v12, then on v13, on SQLite, MariaDB, MySQL and
  PostgreSQL.

## 3. Documentation

- [x] 3.1 The changelog entry
  `Documentation/Changelog/2.4/Important-StudyPlanCreditPointsAreDecimal.rst`
  with the mandatory database compare and the note for projects that
  redefined the columns.
- [x] 3.2 `docs/testing/functional-tests.md`: the schema update test as a
  worked example.

## 4. Definition of done

- [x] 4.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`,
  `functional` on SQLite and PostgreSQL, and the study plan tests on MariaDB
  and MySQL green with `-t 12`.
- [x] 4.2 The same with `-t 13`.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 Commit in TYPO3 Core format, `[FEATURE] ACE-722: <subject>`.
- [x] 4.5 Archive the change as the last commit of the pull request.
