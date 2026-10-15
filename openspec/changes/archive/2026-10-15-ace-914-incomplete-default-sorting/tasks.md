## 1. Tests first

- [x] 1.1 Copy `academic-partners/Tests/Unit/Factory/DemandFactorySortingTest.php`
  from `main` byte for byte, and prove it red on the unchanged factory on
  TYPO3 v12, then v13: errors for an empty value, a field alone and a value
  that is not a string, failures for extra spaces and three words.
  Red on v12 and on v13: 15 tests, 3 errors, 2 failures.
- [x] 1.2 Copy `academic-programs/Tests/Unit/Factory/DemandFactorySortingTest.php`
  from `main` without the docblock sentence about the functional test, and
  prove it red the same way on v12, then v13. Red on both: 15 tests, 3
  errors, 2 failures.

## 2. Implementation

- [x] 2.1 Replace the settings sorting block of the partners demand factory
  by `applySortingFromSettings()` as on `main`, and verify 1.1 green on v12
  and v13.
- [x] 2.2 Do the same in the programs demand factory, settings path only,
  and verify 1.2 green on v12 and v13.

## 3. Documentation

- [x] 3.1 Copy `Documentation/Changelog/2.4/Important-IncompletePartnerSortingKeepsTheDefault.rst`
  and `Important-IncompleteProgramSortingKeepsTheDefault.rst` from `main`
  byte for byte, verify every statement holds on v12 and v13 here, and
  confirm with `cmp` against the reviewed commits of pull request #985 on
  `main`: identical.
- [x] 3.2 Grep the partners and programs manuals of this branch for the
  default sorting and an invalid value of it, and update what they say.
  No page mentions either, so nothing changes.
- [x] 3.3 `docs/`: verify no page describes the default sorting of a list.
  None does. Measure the unit test classes of `docs/testing/unit-tests.md`
  again with the command the page names: 107, 92 of them in the extensions.

## 4. Definition of done

- [x] 4.1 `-t 12 -p 8.1`: `composerUpdate`, `cgl -n`, `phpstan`, `unit`,
  `functional -j auto` green: cgl 0 of 925, phpstan no errors, unit 1166
  tests, functional 2382 tests in 16 chunks.
- [x] 4.2 `-t 13 -p 8.2`: `composerUpdate`, `cgl -n`, `phpstan`, `unit`,
  `functional -j auto` green: cgl 0 of 925, phpstan no errors, unit 1166
  tests, functional 2510 tests in 16 chunks.
- [x] 4.3 `lintPhp`, `lintMarkdown -n` and `checkRstRenderingSingle` for
  `academic-partners` and `academic-programs` green, and
  `openspec validate --specs --strict` passes after the archive.
  `lintMarkdown -n`: 480 files, 0 problems.
- [x] 4.4 Commit in TYPO3 Core format, `[BUGFIX] ACE-914: …`, naming TYPO3
  v12 and v13, with `Resolves: ACE-914`.
- [x] 4.5 Archive the change with spec sync as the last commit, with
  `Related: ACE-914`.
