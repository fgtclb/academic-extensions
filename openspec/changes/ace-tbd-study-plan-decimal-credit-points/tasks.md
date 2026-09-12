## 1. Tests first, on TYPO3 v13

- [ ] 1.1 Extend the functional content element test: a module with 2.5 and
  a semester with 30 credit points render "2.5" and "30"; run it before the
  change and record that 2.5 renders as "2" or is rejected.
- [ ] 1.2 Add a DataHandler test writing 2.5 to a module, asserting 2.50 in
  the database and, through the element, "2.5" in the output.

## 2. Implementation

- [ ] 2.1 Add `format => 'decimal'` to both TCA columns and remove the two
  `credit_points` lines from `ext_tables.sql`; verify the database compare in
  the `core-13` and `core-14` instances changes the column to a decimal
  column and keeps existing values.
- [ ] 2.2 Cast `credit_points` to `float` in `StudyPlanService` for semesters
  and modules; extend `StudyPlanServiceTest` to assert the type.
- [ ] 2.3 Run group 1 green on v13, then on v14, each on SQLite and on
  PostgreSQL.

## 3. Documentation

- [ ] 3.1 Add `Documentation/Changelog/3.0/Important-StudyPlanCreditPointsAreDecimal.rst`
  with the mandatory database compare and the note for projects that
  redefined the columns.
- [ ] 3.2 Mention decimal credit points in the editor section of
  `academic-study-plan/Documentation/`.
- [ ] 3.3 Confirm `docs/` needs no change and state it in the pull request.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [ ] 4.2 Rename the change to `ace-<NNN>-study-plan-decimal-credit-points`.
- [ ] 4.3 Commit as `[FEATURE] ACE-<NNN>: Allow decimal study plan credit
  points` in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.3 `functional` green on PostgreSQL (`-d postgres`) for both versions,
  since the change alters columns and writes them.
- [ ] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.5 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.6 Archive the change as the last commit of the pull request.
