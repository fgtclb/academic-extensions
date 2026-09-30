## 1. Test

- [x] 1.1 Add `SitePackageDataVariable.typoscript` (a text) and
  `SitePackageDataRecords.typoscript` (the records of a query as `data`) to the
  program page test fixtures, and a data provider test in
  `AcademicProgramPageLayoutTest` that renders a program page with a `PAGEVIEW`
  site package at key 100 assigning each, asserting the heading and the
  subtitle. Verify on v13 and v14 that both data sets fail on the current
  processor, and that the records data set also fails when `data` is read
  first whenever it is an array.

## 2. Fix

- [x] 2.1 `ProgramDataProcessor` of `academic_programs` reads the record from
  `page` when it is a page information object, from `data` otherwise, and adds
  nothing when the value is not a non-empty array. Verify the new test passes
  and the existing program page tests stay green.

## 3. Documentation

- [x] 3.1 `docs/architecture/page-type-rendering.md`: the three processors
  share the order, and the tests section names the program page. Verify with
  `lintMarkdown -n`.
- [x] 3.2 `Important-ProgramPageReadsThePageRecordFromPage.rst` in
  `packages/fgtclb/academic-programs/Documentation/Changelog/3.0/`, from the
  template in `Build/Documentation/Templates/`. Verify the over and underline
  lengths and `checkRstRenderingAll`.

## 4. Definition of done

- [x] 4.1 `Build/Scripts/runTests.sh -t 13 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` (SQLite and
  PostgreSQL) with `-t 13` green.
- [x] 4.2 The same for `-t 14` after its own `composerUpdate`.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 `README.md` and `CONTRIBUTING.md` still only summarize and link.
- [x] 4.5 Commit as `[BUGFIX] ACE-786: <subject>`, subject at most 52
  characters, body wrapped at 72.
- [ ] 4.6 Archive the change as the last commit of the pull request and verify
  the delta spec landed in `openspec/specs/`.
