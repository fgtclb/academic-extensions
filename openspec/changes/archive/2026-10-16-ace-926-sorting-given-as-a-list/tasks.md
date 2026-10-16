## 1. Tests first

- [x] 1.1 Add `academic-projects/Tests/Unit/Factory/DemandFactorySortingTest.php`
  with the settings cases (a sorting and an active state that are not a
  string) and the request cases, and show it red on the unchanged code on
  TYPO3 v13 and v14 (`TypeError` of the setters and of
  `ActiveState::tryFromDefault()`, the "Array to string conversion" warning)
- [x] 1.2 Add the request cases "not a string" to the unit
  `DemandFactorySortingTest` of `academic-partners` and `academic-programs`,
  red on the unchanged code on both core versions
- [x] 1.3 Add a GET and a POST case per malformed demand key to the
  `FilterUrlTest` of all three list extensions, red on the unchanged code on
  both core versions with a `TypeError` from the list action and from the
  redirect handler
- [x] 1.4 Add `AcademicProjectsSettingsNotAStringTest` with TypoScript that
  gives the sorting and the active state a child, for an element with and one
  without stored settings, red on TYPO3 v13 and v14 with the sorting guard
  reverted (`TypeError`) and with the active state guard reverted ("Array to
  string conversion")

## 2. Implementation

- [x] 2.1 Guard the values of the request in the three demand factories and
  the two settings of the project factory, and verify the tests of group 1 are
  green on TYPO3 v13 and v14

## 3. Definition of done

- [x] 3.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` green on TYPO3 v13 and
  v14
- [x] 3.2 `docs/architecture/list-filter-urls.md` and the unit test count of
  `docs/testing/unit-tests.md` updated, `lintMarkdown -n` green
- [x] 3.3 An `Important` entry in `Documentation/Changelog/2.4/` of each of the
  three extensions, true on TYPO3 v12 and v13 as well, and the integrator
  manuals of the three list elements updated, rst rendering green
- [x] 3.4 Commit message in TYPO3 Core format with `Resolves: ACE-926`, and the
  archive of this change as the last commit with `Related: ACE-926`
