## 1. Tests first

- [x] 1.1 Add `AcademicProgramsFinderRenamedTypesTest` with the fixture
  extension `test_programs_renamed_category_types`, which removes the degree
  and the topic and adds a qualification and a subject, and show its default
  case red on the unchanged code on TYPO3 v13 and v14 (no finder form). The
  fixture declares the subject before the qualification, so the asserted
  group order is not the alphabetical one. Its case of site-wide types none
  of which has a category asserts no form, red when the fallback also
  replaces configured types
- [x] 1.2 Add the cases "without a topic category" and "without degree and
  topic categories" to `AcademicProgramsFinderTest`, the latter red on the
  unchanged code on TYPO3 v13 and v14, the former green as a pin of the
  behaviour that stays

## 2. Implementation

- [x] 2.1 Resolve the finder selects with the default first and with an empty
  value when the default offers nothing, and verify the tests of group 1 are
  green on TYPO3 v13 and v14

## 3. Definition of done

- [x] 3.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional` green on TYPO3 v13 and v14
- [x] 3.2 `docs/architecture/list-filter-types.md` and
  `docs/testing/fixture-extensions.md` updated, `lintMarkdown -n` green
- [x] 3.3 The English and German field description, the site setting, the
  constant, the manual pages `ProgramFinder.rst` and `Configuration/Index.rst`
  and the unreleased 3.0 entry `Feature-ProgramFinderContentElement.rst`
  updated, rst rendering green
- [x] 3.4 Commit message in TYPO3 Core format with `Resolves: ACE-923`, and the
  archive of this change as the last commit with `Related: ACE-923`
