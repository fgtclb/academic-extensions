## Context

The defect has two parts on this branch:

- `ProfileController::listAction()` builds a `QueryResultPaginator` from the
  unordered query result and only afterwards restores the selection order, onto
  `$profiles` (`Classes/Controller/ProfileController.php:103-121`).
- `Templates/Profile/List.html:7-13` renders `paginator.paginatedItems` whenever
  pagination is on, so the restored order never reaches the view. The paginated
  query runs `LIMIT/OFFSET` without an `ORDER BY`.

`TYPO3\CMS\Core\Pagination\ArrayPaginator` exists on TYPO3 v12 as well, with the
same constructor - verified in `.Build/vendor/` after `composerUpdate -t 12`,
which installed v12.4.45. `SimplePagination` and the optional
`NumberedPagination` take any `PaginatorInterface`.

## Goals / Non-Goals

**Goals:**

- One ordering source for a selection: the editor's order, applied before
  pagination.

**Non-Goals:**

- Paginating a selection in the database.
- A deterministic ordering for the selection query. See the non-goals of the
  proposal: this branch has no fallback ordering at all, and ACE-431 covers it.

## Decisions

### Sort first, then paginate the array

When `$demand->getProfileList()` is not empty, `listAction()` sorts the
(event-modified) result with `sortBySelectionOrder()` first. It then builds an
`ArrayPaginator` over that list, where it would otherwise build the
`QueryResultPaginator`. A list without a selection keeps the
`QueryResultPaginator`. The template stays as it is, because `paginatedItems`
means the same thing on both paginators.

Rejected:

- `ORDER BY FIELD(uid, ...)` is MySQL only.
- A `CASE` expression over the uids works everywhere, but it has to be built
  outside the Extbase query object model.
- Forbidding pagination together with a selection breaks existing content
  elements.

### The fixture extension needs a pagination partial first

`EXT:test_plugin_templates`, which the plugin rendering tests render with,
renders `List/Pagination` whenever `settings.paginationEnabled` is set, and no
such partial exists - neither in the fixture extension nor under a resolvable
name in `academic_persons`. No test on this branch could switch pagination on,
which is why the defect had no coverage. The change adds a minimal partial that
prints the current page and the target of every page link, so a test follows the
link the plugin built instead of assembling a URL and a cHash of its own. The
same partial is added on `main` by the same change.

### The `currentPage` docblocks

`ProfileDemand::getCurrentPage()` and `::setCurrentPage()` carry their
neighbours' blanket "Does not have any effect when `getProfileList()` is not
empty", which was already false - the paginator was built from
`getCurrentPage()` with a selection too - and is definitively false afterwards.
Both are corrected, as on `main`.

Named and **not** changed: `listAction()` switches pagination off whenever the
alphabet filter is set, regardless of a selection, so the same blanket docblock
is imprecise for `getAlphabetFilter()` too. That belongs to the letter
navigation.

## Risks / Trade-offs

- The whole selection is loaded before paginating - it was loaded already for
  the sort, and a manual selection is small by nature.
- A listener that replaces the result with a differently filtered one - it is
  still sorted and paginated after the event, as today.

## Open Questions

None.
