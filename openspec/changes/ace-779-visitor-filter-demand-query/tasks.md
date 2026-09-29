## 1. Prerequisites

- [x] 1.1 Confirm `ace-734-list-links-keep-state` is merged or lands in the
  same pull request, and verify its constant of visitor-settable properties
  exists. Merged. `ProfileController::VISITOR_DEMAND_PROPERTIES` holds
  `currentPage`, `alphabetFilter` and `viewMode`.

## 2. Configuration

- [x] 2.1 Add `settings.filter.functionType` and
  `settings.filter.organisationalUnit` to `Core13/List.xml` and
  `Core14/List.xml` with labels in English and German (one line per
  `source`/`target`), and verify both data structures parse and carry the
  fields in a functional test run on v13 and v14 (`VisitorFilterFieldsTest`).
- [x] 2.2 Hide both fields in the card page TSconfig and assert that in a
  functional test, added or extended, that reads the card's page TSconfig
  (`VisitorFilterFieldsTest::theCardOffersNoFilter()`).

## 3. Demand and query

- [x] 3.1 Add `functionTypeFilter` and `organisationalUnitFilter` to
  `ProfileDemand` and to the constant of visitor-settable properties, drop a
  value that is not a whole number in `initializeListAction()`, and reset a
  value that is not one of the filter options in `adoptVisitorFilters()`,
  which covers a filter whose flag is off. Add functional list tests for flag
  off, a value outside the restriction, unknown, hidden and non-integer
  values, and show them failing without the option check and without the
  integer check.
- [x] 3.2 AND the equality constraints in `ProfileRepository::setFilters()`.
  Add functional tests for each filter, for both filters on different
  contracts (not listed) and for a profile with two matching contracts
  (listed once), and show the filter tests fail without the constraint.
- [x] 3.3 Add a functional test that a manual selection ignores the filter
  value and gets no options, and show it failing with options under a manual
  selection. The query ignores the value either way.
- [x] 3.4 Add the ordered option methods with a `uid` tiebreaker and assign
  `filterOptions`. Add functional tests with two tied pairs of names, one
  written in each uid direction, that assert the order, and show them
  failing when ordering by `uid` only and, on PostgreSQL, without the `uid`
  tiebreaker.
- [x] 3.5 Add functional tests that the pagination and the letter navigation
  count the filtered profiles and keep the filter in their links, and that a
  link built as the templates chapter shows it leads to the filtered list,
  and show them failing without the constant entries.

## 4. Documentation

- [x] 4.1 Document the two plugin options and the accepted values in
  `Documentation/Configuration/Index.rst` (section "Filters for visitors"),
  where the other plugin options are. `Configuration/General/Index.rst` covers
  `Settings.yaml` only. The template variable is documented in
  `Templates/Partials/Index.rst`, the demand in `Developers/Index.rst`.
- [x] 4.2 Add `Documentation/Changelog/3.0/Feature-VisitorListFilter.rst`.
- [x] 4.3 Record the same-contract join behaviour in
  `docs/architecture/database-queries.md`.

## 5. File the issue

- [x] 5.1 File the ACE issue in YouTrack (relating it to ACE-18) and verify
  the key with a GET request. ACE-779.
- [x] 5.2 Rename the change to `ace-779-visitor-filter-demand-query` and
  verify `openspec validate` passes under the new name.
- [x] 5.3 Commit as `[FEATURE] ACE-779: Let visitors filter the list`
  in TYPO3 Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres` for the list
  tests.
- [x] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres` for the list
  tests.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
