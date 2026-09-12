## Context

See `proposal.md` for the motivation. `JobController` is `final`;
`listAction()` takes no argument and assigns `findByJobType()` or
`findAllJobs()` unpaginated, plus `data` and `record`.
`Configuration/FlexForms/PluginList.xml` has only `settings.job.type` and
`settings.showHiddenRecords` and is registered for `academicjobs_list` only.
The `List` plugin is non-cacheable (`ext_localconf.php`). Job lists break
`starttime` ties by `uid` since 3.0 (changelog
`Important-JobListsBreakStarttimeTiesByUid.rst`), so the order a paginator
slices is deterministic on every DBMS.

`academic_persons` paginates with `QueryResultPaginator`, `NumberedPagination`
when `numbered_pagination` is loaded and the class exists, `SimplePagination`
otherwise.

## Goals / Non-Goals

**Goals:**

- The persons pagination behaviour in the job list, with the smallest API
  surface.

**Non-Goals:**

- A shared pagination helper.

## Decisions

### An action argument, not a demand

`listAction(int $currentPage = 1)`; a value below 1 is treated as 1, a value
beyond the last page is clamped by the paginator. The pagination partial
links with `arguments="{currentPage: page}"`.

Rejected: a demand object only for the page number. The list's only other
parameter, the job type, comes from settings, so a demand would carry one
property.

### Paginate exactly like academic_persons

`settings.paginationEnabled === '1'`, `settings.pagination.resultsPerPage`
(fallback 10), `settings.pagination.numberOfLinks` (fallback 5); `paginator`
and `pagination` assigned only when enabled. `List.html` iterates
`paginator.paginatedItems` when a paginator exists and `jobs` otherwise, and
renders `Job/Pagination.html` when `paginator.numberOfPages > 1`.

Rejected: a shared pagination trait in `academic_base`. Persons and partners
read the page from a demand, jobs from an action argument; one trait would
carry that switch.

### Site setting for the number of links

`plugin.tx_academicjobs.pagination.numberOfLinks` in the jobs site set
settings definitions and constants, named like the persons setting; results
per page stays a FlexForm field because it differs per content element.

## Risks / Trade-offs

- [An overridden `List.html` iterates `jobs`] → it renders every job and no
  navigation; named in the changelog.
- [A cached registration later] → every page becomes a cache entry; the
  plugin is non-cacheable today.
