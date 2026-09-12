## Context

See `proposal.md` for the motivation. State on `main`:

- `ProfileController::initializeListAction()` allows the keys of
  `settings.demand` plus `currentPage` and `alphabetFilter` from the request.
  It then overlays `settings.demand` with `array_replace_recursive()`, so the
  editor keys (`groupBy`, `sortBy`, `sortByDirection`, `profileList`) are
  effectively never visitor-settable.
- `listAction()` switches pagination off while `alphabetFilter` is set.
- `Pagination.html` links with `{demand: {currentPage: ...}}` five times, and
  `AlphabetPagination.html` with `{demand: {alphabetFilter: ...}}` twice.

## Goals / Non-Goals

**Goals:**

- One list of visitor-settable demand properties that drives both the
  property mapping allow-list and the link arguments, so the two cannot
  drift.
- Partials that need no edit when a later change adds a visitor value.

**Non-Goals:**

- Changing which values are visitor-settable today.
- Lifting the "no pagination under a letter" rule; that is a follow-up
  change.

## Decisions

### Arguments come from the mapped demand, not from the request

The controller assigns `activeListArguments`, built from the `ProfileDemand`
after mapping and after `adoptSettings()`. Only properties on the
visitor-settable list are included, and only when they differ from their
default. A value the controller rejected therefore never reaches a link.

Rejected: `addQueryString` on the links. It carries foreign parameters into
cached URLs and multiplies cache entries. Also rejected: copying the raw
`demand` request argument, which would forward unvalidated values.

### One constant drives allow-list and link arguments

A private constant of the controller lists the visitor-settable properties.
Today that is `currentPage` and `alphabetFilter`. `initializeListAction()`
allows exactly those, plus the `settings.demand` keys as today, and
`activeListArguments` is built from the same list. `ace-tbd-list-view-modes`
and `ace-tbd-visitor-filter-demand-query` add their keys to that constant.

### Merging in a ViewHelper, not in Fluid literals

Fluid cannot merge arrays, and listing every key in an array literal is what
forced a project to copy both partials. A small stateless ViewHelper in
`academic_persons` returns `activeListArguments` with the given overrides
applied (`currentPage`, or `alphabetFilter` with `currentPage` removed). Its
`overrides` argument is optional with an empty default: Fluid 5 rejects a
required argument that has a default.

Rejected: precomputing every page URL in the controller. The page numbers
depend on the pagination class (`NumberedPagination` or `SimplePagination`),
and overrides of `Pagination.html` could no longer choose their links.

### A letter change starts at page one

The letter links drop `currentPage`, as they do today.

### Decided: pagination under a letter comes in a follow-up change

Pagination while a letter is active will be allowed, but not in this change,
which stays a pure refactoring of the links. A separate follow-up change lifts
the rule in `listAction()` and adds the letter and page combinations to the
route set of `ace-tbd-visitor-filter-ui-routes`.

Once links keep the letter, the switch-off has no technical reason left.
Lifting it here would mix a behaviour change into the refactoring and change
the route set the filter routes change is built on, which assumes pagination
stays off under a letter.

## Risks / Trade-offs

- [More URL variants per list page] → Each variant is a distinct visible
  list, and the values are covered by cHash.
- [The defect is not observable on `main` alone] → The red test needs a
  second visitor value, so this change lands together with, or directly
  before, the first of the two changes that add one. The foreign-parameter
  test is green today and guards the rejected `addQueryString` approach.
- [Values without a route enhancer entry show up as query parameters] →
  Accepted; `ace-tbd-visitor-filter-ui-routes` adds routes for the filters.

## Open Questions

None.
