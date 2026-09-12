## Context

The defect has three parts:

- `ProfileRepository::applyDemandForQuery()` handles a non-empty
  `profileList` first and returns before `setOrderings()`
  (`Classes/Domain/Repository/ProfileRepository.php:211-216` against `:222`).
- `ProfileController::listAction()` builds a `QueryResultPaginator` from that
  unordered result (`Classes/Controller/ProfileController.php:88-103`). Only
  after that does it restore the selection order, and only on `$profiles`
  (`:105-111`).
- `Templates/Profile/List.html:7-9` renders `paginator.paginatedItems` whenever
  pagination is on, so the restored order never reaches the view. The paginated
  query runs `LIMIT/OFFSET` without `ORDER BY`, the defect class of ACE-482.

`TYPO3\CMS\Core\Pagination\ArrayPaginator` exists on both supported versions
(checked in `core-13/vendor` and `core-14/vendor`). `SimplePagination` and the
optional `NumberedPagination` take any `PaginatorInterface`.

Branch `2` is identical (`ProfileController.php:103-121`, `List.html:7-9`).

## Goals / Non-Goals

**Goals:**

- One ordering source for a selection: the editor's order, applied before
  pagination.
- A deterministic query result for listeners of `ModifyListProfilesEvent`,
  which still receive the query result.

**Non-Goals:**

- Paginating a selection in the database.

## Decisions

### Sort first, then paginate the array

When `$demand->getProfileList()` is not empty, `listAction()` sorts the
(event-modified) result with `sortBySelectionOrder()` first. It then builds an
`ArrayPaginator` over that list, where it would otherwise build the
`QueryResultPaginator`. A list without a selection keeps the
`QueryResultPaginator`. The template stays as it is, because `paginatedItems`
means the same thing on both paginators.

Rejected:

- `ORDER BY FIELD(uid, …)` is MySQL only.
- A `CASE` expression over the uids works everywhere, but it has to be built
  outside the Extbase query object model.
- Forbidding pagination together with a selection breaks existing content
  elements.

### Add the fallback ordering to the selection branch

The `profileList` branch gets `setOrderings(self::FALLBACK_ORDERINGS)` as
well. The visible order is produced in PHP, but the query result handed to
`ModifyListProfilesEvent` is rendered by some listeners (one project's listener
did) and must be the same list twice (`docs/architecture/database-queries.md`).

Rejected: leaving the branch unordered because the controller sorts anyway.
That relies on every consumer of the result sorting it again.

## Risks / Trade-offs

- The whole selection is loaded before paginating → it was loaded already for
  the sort, and a manual selection is small by nature.
- A listener that replaces the result with a differently filtered one → it is
  still sorted and paginated after the event, as today.

## Open Questions

None.
