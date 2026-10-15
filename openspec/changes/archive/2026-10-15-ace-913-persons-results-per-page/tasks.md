## 1. Tests first, red on the unchanged code

- [x] 1.1 `AcademicPersonsListPaginationSettingsTest`: one scenario of the delta
  spec per test, for the list and the list and details element, a query
  result and a manual selection. Run on TYPO3 v13 and v14 against the
  unchanged controller, TypoScript and constants: 14 of 15 red on v13 and v14,
  9 by exception 1573061766 and 5 by the number of profiles or links. The
  value the editor entered already worked and stays green as a guard. Logs
  `.agent/tmp/ace-913/red-core13-pagination.log` and
  `red-core14-pagination.log`.
- [x] 1.2 `SiteSetDeliveryTest` expects the site setting default of 10, red
  on v13 and v14 until the definition changes (`red-core13-siteset.log`,
  `red-core14-siteset.log`).

## 2. Implementation

- [x] 2.1 `pagination.resultsPerPage` and `pagination.numberOfLinks` in the
  `ignoreFlexFormSettingsIfEmpty` list of the shared TypoScript, verified by
  the tests of the first requirement.
- [x] 2.2 `ProfileController::listAction()` falls back to 10 results and 5
  links for a value below 1, verified by the tests of the second requirement.
- [x] 2.3 Site setting and constant default to 10, verified by the test of the
  third requirement and `SiteSetDeliveryTest`.

## 3. Documentation

- [x] 3.1 Manual of `academic_persons`: `Integrator/PersonList.rst`,
  `Integrator/PersonListAndDetails.rst` and `Integrator/Index.rst`.
- [x] 3.2 A new `Changelog/2.4/Important-*.rst`, written so that branch `2`
  can take it byte for byte, and a section in `Upgrade/From23To24.rst` and
  `Upgrade/From23To30.rst`.
- [x] 3.3 `docs/architecture/typoscript-and-site-sets.md`: a numeric setting an
  element may leave empty.

## 4. Definition of done

- [x] 4.1 `lintPhp`, then per core version after its own `composerUpdate`:
  `cgl -n`, `phpstan`, `unit` and `functional -j auto` on SQLite, green.
- [x] 4.2 `lintMarkdown -n` and the rendering of the `academic_persons`
  manual, green.
- [x] 4.3 Commit message in TYPO3 Core format with `Resolves: ACE-913`, and the
  archive of this change with the spec sync as the last commit.
