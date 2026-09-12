## Context

See `proposal.md` for the motivation. `PartnerController::listAction()`
assigns the full `QueryResult`; `PartnerDemand` has no page property; the
FlexForm `Configuration/FlexForms/ListSettings.xml` is registered for **both**
the list and the map content element (`Configuration/TCA/Overrides/tt_content.php`,
two `addContentElementPluginFlexForm()` calls naming the same file).

`academic_persons` paginates in `ProfileController::listAction()`:
`QueryResultPaginator` over the result, `NumberedPagination` when
`numbered_pagination` is loaded and the class exists, `SimplePagination`
otherwise, both assigned as `paginator` and `pagination`; its partial
`Profile/List/Pagination.html` links with `arguments="{demand: {currentPage: …}}"`.
Its FlexForm is version-split (`Core13/Core14/List.xml`, ACE-560), but only
because of a value picker; the pagination fields themselves are plain.

This change depends on `ace-tbd-list-filter-get-urls` for the GET shape of
filter and sorting.

## Goals / Non-Goals

**Goals:**

- The persons pagination behaviour, in the partner list only.
- Links that carry filter, sorting and page.

**Non-Goals:**

- A shared pagination abstraction.
- A route enhancer for the page argument (`ace-tbd-list-route-enhancers`).

## Decisions

### Page number on the demand

`PartnerDemand` gains `currentPage` (int, minimum 1); `DemandFactory` reads
`demand[currentPage]` from the request. The filter redirect of
`ace-tbd-list-filter-get-urls` never forwards `currentPage`, so a new filter
submission starts at page one without extra code.

Rejected: an action argument `int $currentPage`. The pagination links then
have to merge two argument namespaces, and the route enhancer file would need
a second `_arguments` source for the same list.

### Paginate exactly like academic_persons

The controller copies the persons block: `settings.paginationEnabled === '1'`,
`settings.pagination.resultsPerPage` (fallback 10),
`settings.pagination.numberOfLinks` (fallback 5), `QueryResultPaginator`,
`NumberedPagination` or `SimplePagination`. `QueryResultPaginator` clamps a
page beyond the last one to the last page.

Rejected: a shared pagination trait in `academic_base` for persons, partners
and jobs. Persons and partners read the page from the demand, jobs from an
action argument; one trait would carry that switch and serve three callers
with different inputs.

### A FlexForm file of its own for the list

The pagination fields must not appear on the map element. `ListSettings.xml`
stays the list's file and gains a "Pagination" sheet; the map gets a new
`MapSettings.xml` with today's content, and `tt_content.php` points the map
at it. Existing map content elements keep their stored values because the
field names do not change.

Rejected: one shared file with fields the map silently ignores; an editor
would enable pagination on a map and see nothing happen.

### Links carry the full GET demand

`Partner/Pagination.html` builds its `f:link.action` arguments from
`toFilterArgument()` of the demand's filter collection (via the ViewHelper of
`ace-tbd-list-active-filters-reset-count` if it landed first, otherwise a
`filterArgument` variable assigned by the controller), plus `sortingField`,
`sortingDirection` and `currentPage`.

The arguments come from the demand object, not from the request. On a first
visit the demand holds the editor's preselected categories and sorting, so
the link to page two carries them explicitly; `DemandFactory`, which ignores
the preset as soon as any demand argument arrives, needs no change for
paging.

## Risks / Trade-offs

- [ace-demo's controller subclass still overrides `listAction()`] → the
  overlay must be removed in the same upgrade; named in the changelog.
- [Numbered pagination is not installed] → `SimplePagination` renders
  previous/next only; covered by both code paths in tests.
- [`MapSettings.xml` duplicates the filter fields] → the two files can drift;
  a functional test asserts both data structures expose the same filter
  fields.

## Migration Plan

No data migration. The map's FlexForm file is swapped in TCA; stored values
keep their names.
