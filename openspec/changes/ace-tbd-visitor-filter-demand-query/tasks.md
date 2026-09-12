## 1. Prerequisites

- [ ] 1.1 Confirm `ace-tbd-list-links-keep-state` is merged or lands in the
  same pull request, and verify its constant of visitor-settable properties
  exists.

## 2. Configuration

- [ ] 2.1 Add `settings.filter.functionType` and
  `settings.filter.organisationalUnit` to `Core13/List.xml` and
  `Core14/List.xml` with labels in English and German (one line per
  `source`/`target`); verify both data structures parse and carry the fields
  in a functional test run on v13 and v14.
- [ ] 2.2 Hide both fields in the card page TSconfig and assert that in a
  functional test, added or extended, that reads the card's page TSconfig.

## 3. Demand and query

- [ ] 3.1 Add `functionTypeFilter` and `organisationalUnitFilter` to
  `ProfileDemand`, allow them only with the flag on, and validate them
  against the filter options in `adoptSettings()`; add functional list tests
  for flag off, a value outside the restriction and a non-integer value, and
  show the flag-off test fails with the allow-list check removed.
- [ ] 3.2 AND the equality constraints in `ProfileRepository::setFilters()`;
  add functional tests for each filter, for both filters on different
  contracts (not listed) and for a profile with two matching contracts
  (listed once), and show the filter test fails without the constraint.
- [ ] 3.3 Add a functional test that a manual selection ignores the filter
  value.
- [ ] 3.4 Add the ordered option methods with a `uid` tiebreaker and assign
  `filterOptions`; add a functional test with two records of the same name
  that asserts the order, and show it fails when ordering by `uid` only.

## 4. Documentation

- [ ] 4.1 Document the two plugin options and the accepted values in
  `Documentation/Configuration/General/Index.rst`.
- [ ] 4.2 Add `Documentation/Changelog/3.0/Feature-VisitorListFilter.rst`.
- [ ] 4.3 Record the same-contract join behaviour in
  `docs/architecture/database-queries.md`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack (relating it to
  ACE-18) and verify the key with a GET request.
- [ ] 5.2 Rename the change to `ace-<NNN>-visitor-filter-demand-query` and
  verify `openspec validate` passes under the new name.
- [ ] 5.3 Commit as `[FEATURE] ACE-<NNN>: Filter the profile list by visitors`
  in TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v13, and `functional -d postgres` for the list
  tests.
- [ ] 6.2 `composerUpdate -t 14`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` green for v14, and `functional -d postgres` for the list
  tests.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and the `Documentation/` changelog entry are part of the
  commit.
- [ ] 6.5 Archive the change as the last commit of the pull request.
