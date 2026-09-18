## 1. Tests first

- [x] 1.1 Add `Resources/Private/Partials/List/Pagination.html` to
  `EXT:test_plugin_templates` and the fixture
  `Tests/Functional/Plugins/Fixtures/AcademicPersonsListPlugin/defaultLanguageOnly_selectedProfilesPaginated.csv`:
  profiles 1 to 3, a list plugin with `profileList` `3,1,2`,
  `paginationEnabled` 1 and `resultsPerPage` 2, plus its list-and-detail twin.
- [x] 1.2 Add tests to `AcademicPersonsListPluginTest` in this branch's request
  style (`InternalRequest` + `InternalRequestContext`, `writeSiteConfiguration()`).
  Page 1 renders profile 3 before profile 1 and does not render profile 2;
  page 2 renders only profile 2. Record the failure against the unchanged
  controller.
- [x] 1.3 Add a list-and-detail variant of the page 1 test and record the
  same failure.

## 2. Implementation

- [x] 2.1 Sort a selection before paginating and paginate it with
  `ArrayPaginator` in `ProfileController::listAction()`. Verify the tests of
  group 1 pass and the existing list and list-and-detail tests stay green.
- [x] 2.2 Correct the two `currentPage` docblocks of `ProfileDemand`.
- [x] 2.3 Revert 2.1 on purpose and watch 1.2 go red again; restore.

## 3. Documentation

- [x] 3.1 Add a short section on ordering a manual selection before pagination
  to this branch's `docs/architecture/database-queries.md`; verify with
  `lintMarkdown -n`.
- [x] 3.2 Add
  `Documentation/Changelog/2.4/Important-PaginatedSelectionKeepsItsOrder.rst`.

## 4. Commit

- [x] 4.1 Commit as `[BUGFIX] ACE-681: Sort a selection before paginating` in
  TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 12`, and the affected tests also with
  `-d postgres`.
- [x] 5.2 `composerUpdate`, then the same suites green with `-t 13`, and the
  affected tests also with `-d postgres`.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` updated as in group 3; `README.md` and `CONTRIBUTING.md`
  still only summarise.
- [x] 5.5 Anything left out is named in the pull request, with the reason.
- [ ] 5.6 Archive the change as the last commit of the pull request.
