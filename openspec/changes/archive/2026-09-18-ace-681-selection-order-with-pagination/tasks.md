## 1. Tests first

- [x] 1.1 Add the fixture
  `Tests/Functional/Plugins/Fixtures/AcademicPersonsListPlugin/defaultLanguageOnly_selectedProfilesPaginated.csv`:
  profiles 1 to 3, a list plugin with `profileList` `3,1,2`,
  `paginationEnabled` 1 and `resultsPerPage` 2. Also add the missing
  `Resources/Private/Partials/List/Pagination.html` to `EXT:test_plugin_templates`:
  its `Profile/List.html` renders that partial whenever pagination is on and no
  copy of it existed anywhere, so no test could switch pagination on at all.
- [x] 1.2 Add tests to `AcademicPersonsListPluginTest`. Page 1 renders
  profile 3 before profile 1 and does not render profile 2; page 2
  (`demand[currentPage]=2`) renders only profile 2. Run them against the
  unchanged controller and record that page 1 fails (it renders 1 and 2 in uid
  order).
- [x] 1.3 Add a list-and-detail variant of the page 1 test and record the
  same failure.

## 2. Implementation

- [x] 2.1 Sort a selection before paginating and paginate it with
  `ArrayPaginator` in `ProfileController::listAction()`. Verify the tests of
  group 1 pass and the existing list and list-and-detail tests stay green.
- [x] 2.2 Add `FALLBACK_ORDERINGS` to the `profileList` branch of
  `ProfileRepository::applyDemandForQuery()` and adjust its comment. Verify
  with a repository test that the selection result is in uid order.
- [x] 2.3 Revert 2.1 on purpose and watch 1.2 go red again; restore.

## 3. Documentation

- [x] 3.1 Add a short paragraph on ordering a manual selection before
  pagination to `docs/architecture/database-queries.md`; verify with
  `lintMarkdown -n`.
- [x] 3.2 Changelog entry **shipped after all**, against the decision this task
  recorded. The pages an existing installation renders change - a selection that
  was arranged to match the database order now renders in the editor's order -
  so the repository definition of done ("user or integrator facing") applies.
  `Documentation/Changelog/3.0/Important-PaginatedSelectionKeepsItsOrder.rst`,
  same reasoning as ACE-596 and ACE-679. The deviation is named in the pull
  request.

## 4. Backport

- [ ] 4.1 Backport: separate change on branch 2 after a backport analysis
  (`docs/workflow/backporting.md`). `ArrayPaginator` exists on v12 as well;
  diff `listAction()` between the branches first.

## 5. File the issue

- [x] 5.1 Filed as ACE-681 (Bug, Version 2.4.0, State Open, assignee s.buerk),
  linked `relates to` ACE-482, ACE-491 and ACE-431; change renamed to
  `ace-681-selection-order-with-pagination`.
- [x] 5.2 Commit as `[BUGFIX] ACE-681: Keep selection order when paginating`
  in TYPO3 Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, and the new tests also with `-d postgres`.
- [x] 6.2 `composerUpdate`, then the same suites green with `-t 14`, and the
  new tests also with `-d postgres`.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` updated as in group 3; `README.md` and `CONTRIBUTING.md`
  still only summarise.
- [x] 6.5 Anything left out is named in the pull request, with the reason.
- [x] 6.6 Archive the change as the last commit of the pull request.
