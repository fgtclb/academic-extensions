## 1. Configuration

- [x] 1.1 A sheet "Pagination" in `PluginList.xml` with
  `settings.paginationEnabled` and `settings.pagination.resultsPerPage`,
  `plugin.tx_academicjobs.pagination.numberOfLinks` in the constants, the
  setup and the settings definitions of the aggregate set, English and German
  labels, and `georgringer/numbered-pagination` as a suggest and conflict in
  `composer.json` and a suggest in `ext_emconf.php`, as in
  `academic_partners`. The FlexForm test covers the new sheet.

## 2. Pagination in the list

- [x] 2.1 The list action reads `currentPage` and assigns paginator and
  pagination, `Job/Pagination.html` renders the navigation, and `List.html`
  switches to the paginated items.
  Functional rendering test `AcademicJobsListPaginationTest`: five jobs, two
  per page, page three shows exactly job five. Show it fails on the unchanged
  controller (all five render).
- [x] 2.2 Regression: pagination off, and a list stored before the sheet
  existed, render all five jobs and no navigation. Ten per page with five jobs
  renders no navigation. Show the last one fails when the partial is rendered
  unconditionally.
- [x] 2.3 Page zero and page nine: first and last page. A results per page of
  zero falls back to ten. A page that is no number, linked or posted, renders
  page one on a list with and without pagination. Show the page zero test
  fails without the lower bound, and the non-number tests fail with the page
  as an `int` argument of the action (a validation error).
- [x] 2.4 Job type and hidden jobs: a paged thesis list contains theses only,
  and a list showing hidden jobs pages them with the others.
- [x] 2.5 Both pagination classes: with and without `numbered_pagination`
  loaded, and the number of links as a site setting of the aggregate set.

## 3. Documentation

- [x] 3.1 `docs/`: mention the job list in the pagination notes of
  `docs/architecture/` (new section if none exists), linked from its
  `Index.md`.
- [x] 3.2 `academic-jobs` `Documentation/Configuration/` and
  `Documentation/Changelog/3.0/Feature-JobListPagination.rst`, including the
  note on overridden `List.html` templates.

## 4. The issue

- [x] 4.1 ACE-256 is the issue this implements: aligned to
  `[3.x] Paginate the job list`, Story, Version 3.0.0, subtask of ACE-40,
  relates to the project issue it came from. The change is named after it and
  committed as `[FEATURE] ACE-256: Paginate the job list` in TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and record the results.
- [x] 5.2 `composerUpdate`, then the same five suites for TYPO3 v14, and
  record the results.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the `academic-jobs` `Documentation/` changelog updated,
  `README.md` and `CONTRIBUTING.md` still only link.
- [x] 5.5 Archive the change as the last commit of the pull request.
