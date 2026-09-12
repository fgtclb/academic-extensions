## 1. Tests first

- [ ] 1.1 Add the fixture
  `Tests/Functional/Plugins/Fixtures/AcademicPersonsListPlugin/defaultLanguageOnly_selectedProfilesPaginated.csv`:
  profiles 1 to 3, a list plugin with `profileList` `3,1,2`,
  `paginationEnabled` 1 and `resultsPerPage` 2.
- [ ] 1.2 Add tests to `AcademicPersonsListPluginTest`. Page 1 renders
  profile 3 before profile 1 and does not render profile 2; page 2
  (`demand[currentPage]=2`) renders only profile 2. Run them against the
  unchanged controller and record that page 1 fails (it renders 1 and 2 in uid
  order).
- [ ] 1.3 Add a list-and-detail variant of the page 1 test and record the
  same failure.

## 2. Implementation

- [ ] 2.1 Sort a selection before paginating and paginate it with
  `ArrayPaginator` in `ProfileController::listAction()`. Verify the tests of
  group 1 pass and the existing list and list-and-detail tests stay green.
- [ ] 2.2 Add `FALLBACK_ORDERINGS` to the `profileList` branch of
  `ProfileRepository::applyDemandForQuery()` and adjust its comment. Verify
  with a repository test that the selection result is in uid order.
- [ ] 2.3 Revert 2.1 on purpose and watch 1.2 go red again; restore.

## 3. Documentation

- [ ] 3.1 Add a short paragraph on ordering a manual selection before
  pagination to `docs/architecture/database-queries.md`; verify with
  `lintMarkdown -n`.
- [ ] 3.2 No changelog entry: a bugfix without integrator action. Record the
  decision in the pull request text.

## 4. Backport

- [ ] 4.1 Backport: separate change on branch 2 after a backport analysis
  (`docs/workflow/backporting.md`). `ArrayPaginator` exists on v12 as well;
  diff `listAction()` between the branches first.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, verify its key
  and rename the change to `ace-<NNN>-selection-order-with-pagination`.
- [ ] 5.2 Commit as `[BUGFIX] ACE-<NNN>: Keep selection order when paginating`
  in TYPO3 Core format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, and the new tests also with `-d postgres`.
- [ ] 6.2 `composerUpdate`, then the same suites green with `-t 14`, and the
  new tests also with `-d postgres`.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` updated as in group 3; `README.md` and `CONTRIBUTING.md`
  still only summarise.
- [ ] 6.5 Anything left out is named in the pull request, with the reason.
- [ ] 6.6 Archive the change as the last commit of the pull request.
