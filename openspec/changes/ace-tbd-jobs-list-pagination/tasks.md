## 1. Configuration

- [ ] 1.1 `settings.paginationEnabled` and `settings.pagination.resultsPerPage`
  in `PluginList.xml`, `plugin.tx_academicjobs.pagination.numberOfLinks` in
  the site set settings and constants, English and German labels, and the
  `georgringer/numbered-pagination` suggest in `composer.json`.

## 2. Pagination in the list

- [ ] 2.1 `listAction(int $currentPage = 1)` with paginator and pagination;
  `Job/Pagination.html`; `List.html` switches to the paginated items.
  Functional rendering test in `AcademicJobsListAndDetailPluginTest`: five
  jobs, two per page, page three shows exactly job five. Show it fails on the
  unchanged controller (all five render).
- [ ] 2.2 Regression: pagination off renders all five jobs and no
  navigation; ten per page with four jobs renders no navigation. Show the
  second fails when the partial is rendered unconditionally.
- [ ] 2.3 Page zero and page nine: first and last page. Show the page zero
  test fails without the lower bound.
- [ ] 2.4 Job type: a paged thesis list contains theses only.
- [ ] 2.5 Both pagination classes: with and without `numbered_pagination`
  loaded.

## 3. Documentation

- [ ] 3.1 `docs/`: mention the job list in the pagination notes of
  `docs/architecture/` (new section if none exists), linked from its
  `Index.md`.
- [ ] 3.2 `academic-jobs` `Documentation/Configuration/` and
  `Documentation/Changelog/3.0/Feature-JobListPagination.rst`, including the
  note on overridden `List.html` templates.

## 4. File the issue

- [ ] 4.1 After implementation, verify ACE-256 in YouTrack as the issue this
  implements (or file a new ACE issue), rename the change to
  `ace-<NNN>-jobs-list-pagination`, and commit as
  `[FEATURE] ACE-<NNN>: Paginate the job list` in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13; record the results.
- [ ] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14; record
  the results.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the `academic-jobs` `Documentation/` changelog updated;
  `README.md` and `CONTRIBUTING.md` still only link.
- [ ] 5.5 Archive the change as the last commit of the pull request.
