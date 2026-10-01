## Context

See proposal.md for the defect. `FLUIDTEMPLATE` assigns the page record as
`data` and no `page` on TYPO3 v12 and v13. `PAGEVIEW`, v13 only, assigns the
page information object as `page` and no `data`, and reserves `page` for
`variables`. The page information object class does not exist on v12.

On `main` the processors check `page` with `instanceof` against that class.
Both supported versions there have it.

## Goals / Non-Goals

**Goals:** the three processors and the project heading resolve the page record
in the order `main` uses, `page` first, on a branch whose older core version
has no page information object.

**Non-Goals:** no `Core12`/`Core13` split and no shared helper. The resolution
is a single condition in each processor.

## Decisions

- **`page` counts when it is an object with `getPageRecord()`**, checked with
  `is_object()` and `method_exists()`, not with `instanceof`. The class does not
  exist on v12, and `phpstan` analyses this branch against v12 too, where an
  `instanceof` against a missing class is an error. `instanceof` itself would
  not fail at runtime, so the reason is the analysis, not PHP. A
  `FLUIDTEMPLATE` site package that assigns a `page` of its own, a text for
  example, is not such an object, and the record comes from `data`.
  - Rejected: a `Core12`/`Core13` split of three processors for one condition.
    The design rules of this branch keep a difference of a line or two inside
    the file.
  - Rejected: a `phpstan` baseline entry for v12. It hides a real finding of the
    analysis instead of avoiding it.
- **Rejected: keep `data` first and only check that it is an array.** The
  records of a query are an array as well, and would reach the factory as if
  they were the page record.
- **The project heading resolves the record in the template**, as on `main`:
  `{page.pageRecord}`, and `{data}` when that is empty. On v12 `page` is not
  assigned, so the heading reads `data` as before. Only the heading changes,
  the 3.0 partials are not backported.

## Risks / Trade-offs

- [A site package on v13 that set `data` on purpose to show another record] →
  it now gets the record of the page. The changelog entries name it.
- [An object with `getPageRecord()` assigned as `page` by a `FLUIDTEMPLATE`
  site package] → its record would be used. Such an object is the page
  information object or an object that imitates it, which carries the page
  record.
