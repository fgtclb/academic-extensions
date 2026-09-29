## 1. Tests first, on TYPO3 v13

- [x] 1.1 Add a functional page test: a program page with
  `application_link = t3://page?uid=5` and a label renders a link to page 5
  with that label, an empty label renders "Apply now", an empty link and a
  hidden target page render nothing. Run it before the change and record that
  it fails for lack of the columns.
- [x] 1.2 Add a TCA test that the two fields are in the program tab of page
  type 20 and absent from page type 1.

## 2. Implementation

- [x] 2.1 Add both columns to `Configuration/TCA/Overrides/pages.php` and the
  program tab, with English and German labels on one line per `source`/`target`
  and two-space indentation.
- [x] 2.2 Verify the derived schema: a database compare in the `core-13` and
  `core-14` instances adds both columns without an `ext_tables.sql` line.
- [x] 2.3 Add the getters to `Program` and `ProgramData`, map both in
  `ProgramDataFactory`, and extend `ProgramDataFactoryTest` to assert them.
- [x] 2.4 Add `Program/Page/CallToAction` and render it from the page
  template. Run group 1 green on v13, then on v14.

## 3. Documentation

- [x] 3.1 Document the fields for editors and the template variables in
  `academic-programs/Documentation/`.
- [x] 3.2 Add `Documentation/Changelog/3.0/Feature-ProgramApplicationLink.rst`,
  including the database compare, the note for projects with their own
  `application_link` column and the statement that copies a link kept in
  another column.
- [x] 3.3 Add the new partial to the list of program page partials in
  `docs/architecture/page-type-rendering.md`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack and verify the
  key.
- [x] 4.2 Rename the change to `ace-777-program-application-link`.
- [x] 4.3 Commit as `[FEATURE] ACE-777: Add a program application link` in
  TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 After `composerUpdate` for TYPO3 v13: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.2 After `composerUpdate` for TYPO3 v14: `lintPhp`, `cgl -n`,
  `phpstan`, `unit` and `functional` green.
- [x] 5.3 `functional` green on PostgreSQL (`-d postgres`) for both versions,
  since the change adds columns that the page test writes.
- [x] 5.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.5 `docs/` and the `Documentation/` changelog entry are part of the
  change. `README.md` and `CONTRIBUTING.md` still only summarize.
- [ ] 5.6 Archive the change as the last commit of the pull request.
