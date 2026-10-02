## 1. Tests first

- [x] 1.1 Add a functional list plugin test with profiles whose matching contract
  has ended, starts next month, is valid today, or has no dates, stored as NULL
  and as 0. A visitor filter and a restriction to a unit list only the profiles
  with a valid matching contract while "only valid" is on, and every one of them
  while it is off. A list without conditions on contracts keeps a profile with
  only an ended contract. The pagination count and the letter navigation of a
  filtered list follow. Show the filter and restriction cases fail on `main`.
- [x] 1.2 Add a functional test that a filtered list with "only valid" caps the
  page cache lifetime at the start of the day after a matching contract ends,
  and at the start date of a matching contract of an unlisted profile, and
  leaves the lifetime alone without the option. Show it fails on `main`.

## 2. Query

- [x] 2.1 Add the "only valid contracts count" property to `ProfileDemand` and
  write it in `ProfileController::adoptSettings()` from
  `settings.contracts.onlyValid`. Verify the request cannot set it (it is not a
  visitor demand property) with a test that posts it.
- [x] 2.2 In `ProfileRepository::setFilters()`, add the validity conditions on
  the joined contract while the demand counts only valid contracts and has a
  condition on contracts. Verify with the tests of 1.1 on SQLite and
  PostgreSQL, and that dropping the condition on an end date of 0 fails them.
- [x] 2.3 Add the boundary query to `ProfileRepository` and restrict the page
  cache lifetime in `listAction()`. Verify with the test of 1.2.
- [x] 2.4 Bound the boundary query by the storage pages of the list, let it read
  the demand after `ModifyProfileDemandEvent` and answer `null` for a manual
  selection. Verify with tests for a condition a listener adds, a contract
  outside the storage pages, and the restriction of the element, each shown to
  fail with its part removed.

## 3. Documentation

- [x] 3.1 Describe the combined behaviour in the configuration chapter of
  `Documentation/` (the visitor filters and the contract display options) and
  in the 3.0 entries `Feature-ContractDisplayPolicy.rst` and
  `Feature-VisitorListFilter.rst`. Both features are new in 3.0, so no entry
  of its own: an installation updating from 2.4 renders nothing different
  because of this change.
- [x] 3.2 Update the `docs/` page that explains the contract display policy or
  the profile list query, if one describes the matching, and its `Index.md`
  link.
- [x] 3.3 Remove the ACE-800 note from the seed comment of the profile list in
  `packages-dev/dev-site` once the list no longer shows the case.

## 4. Definition of done

- [x] 4.1 `composerUpdate -t 13`, then `lintPhp`, `cgl -n`, `phpstan`, `unit`
  and `functional` (SQLite `-j auto`, PostgreSQL and MariaDB `-j 8`) green for
  v13.
- [x] 4.2 The same for v14 after `composerUpdate -t 14`.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 Commit as `[BUGFIX] ACE-800: <subject>` in TYPO3 Core format, with no
  attribution, and archive the change as the last commit of the pull request.
