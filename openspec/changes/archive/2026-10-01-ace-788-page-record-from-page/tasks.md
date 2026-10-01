## 1. Tests

- [x] 1.1 Programs: in `AcademicProgramPageTemplateTest`, a v13 only
  (`not-core-12`) data provider test with a `PAGEVIEW` site package that
  assigns a text (`SitePackageDataVariable.typoscript`) or the records of a
  query (`SitePackageDataRecords.typoscript`) as `data`, asserting the program
  title, and a test on both versions with a `FLUIDTEMPLATE` site package that
  assigns a text as `page` (`SitePackagePageVariable.typoscript`). Verify on
  v13 that both `PAGEVIEW` data sets fail on the current processor, and that
  the records data set also fails when `data` is read first whenever it is an
  array.
- [x] 1.2 Partners: the same three cases in `AcademicPartnerPageTemplateTest`,
  with a `PAGEVIEW` site package fixture of its own. Same red proofs.
- [x] 1.3 Projects: the same three cases in `AcademicProjectPageTemplateTest`,
  and a v13 only test that a project without a project title shows the page
  title as heading on `PAGEVIEW`. Same red proofs, and the heading test red on
  the current template.
- [x] 1.4 Show the `FLUIDTEMPLATE` `page` test red on v12 and v13 against a
  processor that uses `page` whenever it is set.

## 2. Fix

- [x] 2.1 The three processors read the record from `page` when it is an
  object with `getPageRecord()`, from `data` otherwise, and add nothing when
  the value is not a non-empty array. Verify the new tests pass and the page
  template tests stay green on v12 and v13.
- [x] 2.2 The project page template resolves the page record from
  `{page.pageRecord}`, then `{data}`, for the heading fallback. Verify the
  heading test.

## 3. Documentation

- [x] 3.1 `docs/architecture/page-type-rendering.md`, linked from
  `docs/architecture/Index.md`: the two page object types, the order, and why
  this branch checks for the method. Verify with `lintMarkdown -n`.
- [x] 3.2 `Important-<Type>PageReadsThePageRecordFromPage.rst` in
  `Documentation/Changelog/2.4/` of `academic_programs`, `academic_partners`
  and `academic_projects`. Verify the over and underline lengths and
  `checkRstRenderingAll`.

## 4. Definition of done

- [x] 4.1 `Build/Scripts/runTests.sh -t 12 -s composerUpdate`, then
  `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` (SQLite and
  PostgreSQL) with `-t 12` green.
- [x] 4.2 The same for `-t 13` after its own `composerUpdate`.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.4 `docs/` and the `Documentation/Changelog/2.4/` entries of tasks 3.1
  and 3.2 are part of the change, and `README.md` and `CONTRIBUTING.md` still
  only summarize and link.
- [x] 4.5 Commit as `[BUGFIX] ACE-788: <subject>` with the verified key and no
  attribution, subject at most 52 characters, body wrapped at 72. The commit
  and the pull request name what is left out: the rest of ACE-785.
- [x] 4.6 Archive the change as the last commit of the pull request and verify
  the delta specs landed in `openspec/specs/`.
