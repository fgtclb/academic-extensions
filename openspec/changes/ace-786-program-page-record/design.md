## Context

See proposal.md for the defect. `FLUIDTEMPLATE` assigns the page record as
`data` and does not assign `page`. `PAGEVIEW` assigns the page information
object as `page`, with the record at `getPageRecord()`, and never assigns
`data`. A `PAGEVIEW` site package can add any variable not reserved by the
content object, and `data` is not reserved.

## Goals / Non-Goals

**Goals:** the program processor resolves the page record in the same order as
the partner and project processors.

**Non-Goals:** no shared helper for the three processors. The resolution is
two lines in each, and a helper in `academic_base` would add an API that
nothing outside these three classes needs.

## Decisions

- **`page` first, checked by type.** When `page` is a page information object,
  its record is used. Otherwise `data` is used, as a `FLUIDTEMPLATE` page object
  assigns it. The type check keeps a `FLUIDTEMPLATE` site package that assigns
  a `page` variable of its own working as well.
- **Rejected: keep `data` first and only check that it is an array.** That
  hides the text case but not the array case: a `PAGEVIEW` site package that
  assigns the result of a record query as `data` would hand that array to the
  program factory, and the page would show the wrong program or fail further
  down. `page` is the one variable a `PAGEVIEW` site package cannot assign
  through `variables`.
- **No program when the value is not a non-empty array.** The processor adds
  no `program` and no `facts`.

## Risks / Trade-offs

- [A `PAGEVIEW` site package that set `data` on purpose to feed another page
  record to the program page] → it now gets the record of the page it renders.
  This is the order documented since ACE-785, and the changelog entry names
  the change.
