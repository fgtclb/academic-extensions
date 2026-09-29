## Context

See `proposal.md` for the motivation. `JobController` is `final`.
`listAction()` takes no argument and assigns `findByJobType()` or
`findAllJobs()` unpaginated, plus `data` and `record`.
`Configuration/FlexForms/PluginList.xml` has only `settings.job.type` and
`settings.showHiddenRecords` and is registered for `academicjobs_list` only.
The `List` plugin is non-cacheable (`ext_localconf.php`). Job lists break
`starttime` ties by `uid` since 3.0 (changelog
`Important-JobListsBreakStarttimeTiesByUid.rst`), so the order a paginator
slices is deterministic on every DBMS.

Two lists paginate already. `academic_persons` does it with
`QueryResultPaginator`, `NumberedPagination` when `numbered_pagination` is
loaded and the class exists, `SimplePagination` otherwise. `academic_partners`
got the same in 3.0 (ACE-727), with a FlexForm sheet "Pagination", fallbacks
for an unusable results per page or number of links, a site setting and a
`Partner/Pagination.html` partial. The partner list is the model here: it is
the newer of the two and was reviewed against the persons one.

## Goals / Non-Goals

**Goals:**

- The partner list pagination behaviour in the job list, with the smallest API
  surface.

**Non-Goals:**

- A shared pagination helper.

## Decisions

### A plugin argument read by the action, not a demand

The list action reads `currentPage` from the request itself. A value that is
no integer, or one below 1, is the first page, and the paginator clamps a
value beyond the last page. The pagination partial links with
`arguments="{currentPage: page}"`.

Rejected: a demand object only for the page number. The list's only other
parameter, the job type, comes from settings, so a demand would carry one
property.

Rejected: `listAction(int $currentPage = 1)`, as planned first. A
value that is no integer fails the argument validation, and the list answers
with an error instead of the jobs. A POST reaches every list without a cache
hash, so that was open to anyone on every job list, with the pagination on or
off. The partner list guards its page with the same integer check in its
demand factory.

### Paginate exactly like academic_partners

`settings.paginationEnabled` switches it on, `settings.pagination.resultsPerPage`
sets the page size and falls back to 10 below one, and
`settings.pagination.numberOfLinks` falls back to 5 below one. `paginator`
and `pagination` are assigned only when the switch is on. `List.html`
iterates `paginator.paginatedItems` when a paginator exists and `jobs`
otherwise, and renders `Job/Pagination.html` when there is more than one page.

The two fields go into a sheet "Pagination" of their own, as in the partner
list. The existing fields stay in the first sheet, whose stored values keep
their sheet index `sDEF`.

Rejected: a shared pagination trait in `academic_base`. Persons and partners
read the page from a demand, jobs from a plugin argument. One trait would
carry that switch.

### Site setting for the number of links

`plugin.tx_academicjobs.pagination.numberOfLinks`, named like the persons and
partners settings, in the constants and in the settings definitions of the
aggregate set `fgtclb/academic-jobs`, which declares every other setting of
the shared `plugin.tx_academicjobs` block. Results per page stays a FlexForm
field because it differs per content element.

Rejected: declaring it on the list set, as `academic_partners` does. The
settings of this extension belong to the block all three plugins share, and
the aggregate set is where an integrator finds them.

## Risks / Trade-offs

- [An overridden `List.html` iterates `jobs`] → it renders every job and no
  navigation. Named in the changelog.
- [Every page is a page cache entry] → the page is part of the cache hash,
  so each page of a list is an entry of its own around the placeholder of the
  non-cacheable action. Only the list builds a valid hash, one per page it
  has, so a visitor cannot add entries.
- [A listener replaces `jobs` in the plugin view event] → a paginated list
  ignores it, because the paginator is built before the event. Named in the
  changelog.
