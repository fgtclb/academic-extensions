## Context

See `proposal.md` for the motivation. State on `main`:

- `ProfileController::listAction()` sets `settings.paginationEnabled` to `0`
  while `alphabetFilter` is not empty (lines 84-86), after the list event and
  before the paginator is built.
- The paginator counts the query result, which already carries the letter
  constraint. The core `AbstractPaginator` clamps a page number above the last
  page to the last page, on v13 and v14 alike.
- `Configuration/Routes/List.yaml` and `ListAndDetail.yaml` route
  `{localized_page}-{page}` and `/{letter}` as alternatives. The route
  enhancer documentation names the consequence: a link with both values is
  generated as `/persons/page-2?...[alphabetFilter]=m`.
- The core `RouteSorter` tries routes that have all their variables filled
  first, and among those the one with more variables first. Its logic is the
  same on v13 and v14; the differences between the two files are type
  declarations and redundant conditions.
- `ace-tbd-list-links-keep-state` keeps the letter in page links and drops the
  page from letter links. `ace-tbd-visitor-filter-ui-routes` adds the filter
  routes with explicit `requirements` and states that this change extends its
  route set.

## Goals / Non-Goals

**Goals:**

- A letter page is a paginated list like any other: same partial, same
  settings, same links.
- A letter and page combination has one generated URL on both cores.

**Non-Goals:**

- Changing the letter predicate or the letter availability; both belong to
  `ace-tbd-letter-navigation-availability`.

## Decisions

### Remove the switch-off, add no option

The three lines in `listAction()` go. Nothing replaces them: pagination
follows the editor's `paginationEnabled` for every list.

Rejected: an option that keeps the old behaviour. It would need a field in
both `Core13/List.xml` and `Core14/List.xml`, and no analysed project asked
for a letter on a single page. An editor who wants that disables pagination.

### A letter change resets the page

Letter links and the link back to all letters carry no page number, as
`ace-tbd-list-links-keep-state` already builds them. This change adds only
the tests that pin it for a list that is now paginated under a letter.

Rejected: keeping the page number across a letter change. Pages of two
letters are unrelated. The clamping would show the last page of the new
letter when it has fewer pages, which looks like a random jump.

### One explicit route per enhancer: `/{letter}/{localized_page}-{page}`

Both enhancers get the route with the aspects they already have. As in
`ace-tbd-visitor-filter-ui-routes`, every variable gets `requirements` of
`[^/]+`. That keeps the two-segment route apart from `/{profile_name}` in
ListAndDetail. No existing route changes, so `/persons/m` and
`/persons/page-2` stay as they are.

No generation order has to be configured. When a link carries both values,
the `RouteSorter` prefers the new route over the letter route and the page
route, because all three of its variables are filled.

Rejected:

- Making the page optional on the letter route. Symfony omits only trailing
  defaults, and `localized_page` sits in front of `page`, which is why the
  filter routes use one explicit route per combination as well.
- `/{localized_page}-{page}/{letter}`. The letter selects the list, the page
  moves within it, and the filter routes put the page last too.

### No cHash and cache configuration

The letter uses a `StaticRangeMapper` from `a` to `z`, the page one from 1 to
1000, and the page word a `LocaleModifier`. All three are static, so a routed
letter page carries no `cHash`. Each letter and page combination is one page
cache entry, bounded by the mapper ranges. A value outside the ranges falls
back to query parameters with a `cHash`, as it does today.

Rejected: excluding `currentPage` or `alphabetFilter` from the cHash
calculation. The cache entry would then be shared by different result pages.

## Risks / Trade-offs

- [Existing content elements show fewer profiles per letter] → The changelog
  entry says so; disabling pagination restores the single letter page.
- [One more route per enhancer that is declared in two files] → The overlap
  tables in `Documentation/Configuration/RouteEnhancers/Index.rst` and
  `docs/architecture/typoscript-and-site-sets.md` get the row, and the
  existing `limitToPages` advice covers it.
- [Letter, page and a filter in one link] → The link is generated with the
  filter and page route, or with this one, and the value not in the path stays
  a query argument with a cHash. It resolves to the same list. Routes for
  letter plus filter follow the open question of
  `ace-tbd-visitor-filter-ui-routes`.
- [Two URLs for the first page of a letter] → `/persons/m` and
  `/persons/m/page-1`, just as `/persons` and `/persons/page-1` exist today.
  Accepted here; it is a matter of the pagination links, with or without a
  letter.
- [The letter filter is case sensitive on PostgreSQL] → The persons query is
  built by Extbase, which renders the filter as a plain `LIKE` on both cores,
  so `m` does not match `Miller` there. The difference exists today and is
  named in `ace-tbd-letter-navigation-availability`. The fixtures of this
  change use last names that match the letter on every DBMS, so the tests
  assert pagination and not case handling.

## Open Questions

None.
