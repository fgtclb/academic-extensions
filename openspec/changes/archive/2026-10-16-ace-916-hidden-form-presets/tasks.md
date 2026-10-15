## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicPartnersHiddenFormPartsTest`: one test per scenario of the
  partners delta specs, the list rendered in the answer to the post. Run on
  TYPO3 v12, then v13, against the unchanged factory, by the rendered
  partners or their order: 6 of 6 red on v12 before the guard of 1.5 was
  added, 6 of 7 on v13 with it. Logs
  `.agent/tmp/ace-916-2/logs/red2-core12-partners.log` and
  `red3-core13-partners.log`.
- [x] 1.2 `AcademicProjectsHiddenFormPartsTest`: the same for the projects
  delta spec, the active state and the selected projects element included:
  7 of 7 red on v12 before the guard of 1.5, 7 of 8 on v13 with it
  (`red2-core12-projects.log`, `red3-core13-projects.log`).
- [x] 1.3 `AcademicProgramsHiddenFormPartsTest`: the same for the programs
  delta specs: 5 of 5 red on v12 before the guard of 1.5, 5 of 6 on v13
  with it (`red2-core12-programs.log`, `red3-core13-programs.log`).
- [x] 1.4 The two URL tests of the hidden category filter per extension are
  also red against a factory that ignores requested categories, the first
  design of this change: 2 of 6 partners, 2 of 7 projects, 3 of 5 programs on
  v12 (`settingsonly-core12-*.log`).
- [x] 1.5 A list that hides its filter without default categories is filtered
  by the categories of a link, one test per extension. A guard of what held
  before: green on the unchanged branch, red against the first design for
  partners and projects on v13 (`settingsonly2-core13-*.log`). The program
  factory of that design did not change the categories yet.
- [x] 1.6 A list that shows its filter replaces the default categories by the
  selection of the visitor, one test per extension. A guard of what held
  before, red on v12 and v13 against a factory that adds the default
  categories for a shown filter too: 1 of 8 partners, 1 of 9 projects, 1 of
  7 programs (`mutant-core12-*.log`, `mutant-core13-*.log`).

## 2. Implementation

- [x] 2.1 Partners factory: the sorting of a hidden part from the settings,
  and the default categories of a hidden filter with requested categories
  added, verified by the tests of 1.1 on v12 and v13.
- [x] 2.2 Projects factory: the same, and the active state, verified by the
  tests of 1.2 on v12 and v13.
- [x] 2.3 Programs factory: the same for the sorting and the categories,
  verified by the tests of 1.3 on v12 and v13.

## 3. Documentation

- [x] 3.1 Copy the three `Changelog/2.4/Important-HiddenFormPartsKeepTheirPresets.rst`
  and the three `Upgrade/From23To24.rst` from the final commit on `main` byte
  for byte, verify every statement holds on v12 and v13 here and that every
  `:ref:` resolves, and confirm with `cmp`. Copied from the reviewed commits
  of pull request #991 on `main`, `cmp` identical for all six, every backend
  label they quote is the label of this branch, and every rule they state is
  covered by a test of 1.1 to 1.6.
- [x] 3.2 Grep the manuals of the three extensions on this branch for the
  hide settings and update what they say. No page of this branch describes
  them, the pages of `main` that do are not here, so nothing changes.
- [x] 3.3 `docs/`: verify whether a page describes the presets of a list and
  update it. `docs/architecture/list-filter-types.md` said that a submitted
  filter replaces the presets, it now names the hidden parts as the
  exception, and its test table lists the new tests.

## 4. Definition of done

- [x] 4.1 `-t 12 -p 8.1`: `composerUpdate`, `cgl -n`, `phpstan`, `unit`,
  `functional -j auto` green: cgl 0 of 929, phpstan no errors, unit 1166
  tests, functional 2433 tests in 16 chunks, before the tests of 1.6.
- [x] 4.2 `-t 13 -p 8.2`: `composerUpdate`, `cgl -n`, `phpstan`, `unit`,
  `functional -j auto` green: cgl 0 of 929, phpstan no errors, unit 1166
  tests, functional 2561 tests in 16 chunks, before the tests of 1.6.
- [x] 4.3 `lintPhp`, `lintMarkdown -n` and the rendering of the three
  manuals green, and `openspec validate --specs --strict` passes after the
  archive. `lintMarkdown -n`: 493 files, 0 problems,
  `checkRstRenderingSingle` green for `academic-partners`,
  `academic-programs` and `academic-projects`.
- [x] 4.4 Commit in TYPO3 Core format, `[BUGFIX] ACE-916: …`, naming TYPO3
  v12 and v13, with `Resolves: ACE-916`.
- [x] 4.5 Archive the change with spec sync as the last commit, with
  `Related: ACE-916`.
