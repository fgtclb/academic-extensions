## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicPersonsListPaginationSettingsTest` with its fixture, ported
  from `main` to the test case of this branch and the shared
  `FrontendPluginRenderingTrait`: one scenario of the delta spec per data set,
  for the list and the list and details element, a query result and a manual
  selection. Run on TYPO3 v12 first, then v13, against the unchanged
  controller, TypoScript and constants: 14 of 15 red on both, 9 by exception
  1573061766 and 5 by the number of profiles or links. The value the editor
  entered already worked and stays green as a guard. Logs
  `.agent/tmp/ace-913-2/red-12.log` and `red-13.log`.
- [x] 1.2 The site set test of TYPO3 v13 expects the definition default of 10,
  red on v13 until the definition changes (`red-13-siteset.log`).

## 2. Implementation

- [x] 2.1 `pagination.resultsPerPage` and `pagination.numberOfLinks` appended
  to the `ignoreFlexFormSettingsIfEmpty` list of the shared TypoScript,
  verified by the tests of the first requirement on v12 and v13.
- [x] 2.2 `ProfileController::listAction()` falls back to 10 results and 5
  links for a value below 1, verified by the tests of the second requirement.
- [x] 2.3 Constant and site set definition default to 10, verified by the
  tests of the third requirement and the site set test of v13.

## 3. Documentation

- [x] 3.1 `Changelog/2.4/Important-ProfileListPaginationFallsBack.rst` and
  `Upgrade/From23To24.rst` of `academic_persons`, copied byte for byte from
  `main` and checked with `cmp`. Every statement checked against this branch:
  the defaults of 2.3.4, the exception on v12, the 10 links of
  `georgringer/numbered-pagination` 2.2.
- [x] 3.2 No `docs/` change, see design.md, decision 4.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green on
  TYPO3 v12 (PHP 8.1) and v13 (PHP 8.2).
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_persons`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-913`, and the
  archive of this change with the spec sync as the last commit.
