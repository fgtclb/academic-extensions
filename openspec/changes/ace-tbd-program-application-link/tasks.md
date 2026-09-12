## 1. Tests first, on TYPO3 v13

- [ ] 1.1 Add a functional page test: a program page with
  `application_link = t3://page?uid=5` and a label renders a link to page 5
  with that label, an empty label renders "Apply now", an empty link renders
  nothing. Run it before the change and record that it fails for lack of the
  columns.
- [ ] 1.2 Add a TCA test that the two fields are in the program tab of page
  type 20 and absent from page type 1.

## 2. Implementation

- [ ] 2.1 Add both columns to `Configuration/TCA/Overrides/pages.php` and the
  program tab, with English and German labels on one line per `source`/`target`
  and two-space indentation.
- [ ] 2.2 Verify the derived schema: a database compare in the `core-13` and
  `core-14` instances adds both columns without an `ext_tables.sql` line.
- [ ] 2.3 Add the getters to `Program` and `ProgramData`, map both in
  `ProgramDataFactory`, and extend `ProgramDataFactoryTest` to assert them.
- [ ] 2.4 Add `Program/Page/CallToAction` and render it from the page
  template; run group 1 green on v13, then on v14.

## 3. Documentation

- [ ] 3.1 Document the fields for editors and the template variables in
  `academic-programs/Documentation/`.
- [ ] 3.2 Add `Documentation/Changelog/3.0/Feature-ProgramApplicationLink.rst`,
  including the database compare and the note for projects with their own
  `application_link` column.
- [ ] 3.3 Confirm `docs/` needs no change and state it in the pull request.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [ ] 4.2 Rename the change to `ace-<NNN>-program-application-link`.
- [ ] 4.3 Commit as `[FEATURE] ACE-<NNN>: Add an application link to program
  pages` in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [ ] 5.3 `functional` green on PostgreSQL (`-d postgres`) for both versions,
  since the change adds columns that the page test writes.
- [ ] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.5 `docs/` and the `Documentation/` changelog entry are part of the
  change; `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.6 Archive the change as the last commit of the pull request.
