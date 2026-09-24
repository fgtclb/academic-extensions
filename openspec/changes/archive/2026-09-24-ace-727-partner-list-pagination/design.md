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

This change depends on `ace-723-list-filter-get-urls` for the GET shape of
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
`ace-723-list-filter-get-urls` never forwards `currentPage`, so a new filter
submission starts at page one without extra code.

The factory takes the value only when `MathUtility::canBeInterpretedAsInteger()`
accepts it, and the setter clamps it at 1: a crafted URL - `0`, `-2`, `last`, an
array - shows page one instead of reaching the paginator, which throws below 1.

Rejected: an action argument `int $currentPage`. The pagination links then
have to merge two argument namespaces, and the route enhancer file would need
a second `_arguments` source for the same list.

### Paginate exactly like academic_persons

The controller copies the persons block: `settings.paginationEnabled === '1'`,
`settings.pagination.resultsPerPage` (fallback 10),
`settings.pagination.numberOfLinks` (fallback 5), `QueryResultPaginator`,
`NumberedPagination` or `SimplePagination`. `QueryResultPaginator` clamps a
page beyond the last one to the last page.

Two deviations from the persons block, both about input the persons code
trusts: the switch is read as `(bool)` rather than compared with `'1'`, and a
results per page or number of links below 1 - an emptied FlexForm field, a
constant set to 0 - falls back to 10 and 5. The paginator rejects a results
per page below 1, and the numbered pagination silently takes 10 links for a
number below 1. The paginator pages the result the list event handed back;
the categories of the filter stay those of the whole result. The code sits in
a private `assignPagination()`: protected, it could collide with a method of a
project subclass, the risk `injectFilterRedirectExtensionService()` already
names.

The page and the arguments of the page links are read from the demand the
request asked for, **before** the demand event - the rule the filter redirect
of `ace-723-list-filter-get-urls` already follows for its URL. A listener acts
on every request, the one a page link leads to included, so its changes need
not travel in the URL; and a listener that hands back a demand it built itself
(a documented pattern) would otherwise drop the page and pin the list to page
one. A listener therefore cannot choose the page. Found in review; a
functional test with such a listener covers it.

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

The list's file keeps its fields in sheet `sDEF` - what a single-sheet data
structure is stored under - and adds the sheet `pagination`, so stored values
and page TSconfig addressing `sDEF` keep working. "Results per page" is a
`number` field with a lower bound of 1 and a default of 10. The map's file is
today's content unchanged, including a description label key that does not
exist (`flexform.category.description`, the file names `categories`); fixing
it is left to a change of its own.

### Links carry the full GET demand

`Partner/Pagination.html` builds its `f:link.action` arguments from
`toFilterArgument()` of the demand's filter collection (via the ViewHelper of
`ace-tbd-list-active-filters-reset-count` if it landed first, otherwise a
`filterArgument` variable assigned by the controller), plus `sortingField`,
`sortingDirection` and `currentPage`.

As implemented: the ViewHelper has not landed, and the controller assigns
`demandArguments`, the whole of `DemandFactory::createDemandArguments()` - the
very arguments the filter redirect uses - rather than the filter argument
alone. The partial renders every link through one section that adds
`currentPage`; a list without a filter has no `filterCollection` key, and the
`null` the partial reads for it is left out of the URL (`http_build_query()`).
First, previous, next and last use core's `widget.pagination.*` labels of
`EXT:fluid`, as the persons partial does.

### The item list picks the page

`Partner/ItemList.html` renders `paginator.paginatedItems` when a paginator
exists and `partners` otherwise, and the pagination partial below the items
when the pagination has more than one page. The persons list makes that choice
in its list template instead; here a project that overrides
`Templates/Partner/List.html` - more common than an item list override - keeps
working unchanged.

### The number of links is declared with the list set

`plugin.tx_academicpartners.pagination.numberOfLinks` is declared in
`Configuration/Sets/List/settings.definitions.yaml`, not with the aggregate set
as the settings of persons, jobs and programs are: those are read by several
components, this one by the list alone. A site on the aggregate set gets it
through the dependency. Without the declaration, a flat site setting of that
key does not reach the constant.

### Numbered and core pagination

The proposal and the first version of the spec said "previous and next links"
for a site without `numbered_pagination`. The persons partial, which this
change follows, links every page with the core `SimplePagination`, and so does
the partner partial; the spec now says so. `georgringer/numbered-pagination`
is a `suggest` and a `conflict` outside `^2.1` in `composer.json` and a
`suggests` in `ext_emconf.php`, as in `academic_persons`.

The arguments come from the demand object, not from the request. On a first
visit the demand holds the editor's preselected categories and sorting, so
the link to page two carries them explicitly; `DemandFactory`, which ignores
the preset as soon as any demand argument arrives, needs no change for
paging.

## Risks / Trade-offs

- [ace-demo's controller subclass still overrides `listAction()`] → the
  overlay must be removed in the same upgrade; named in the changelog.
- [Numbered pagination is not installed] → `SimplePagination` links every
  page; covered by both code paths in tests.
- [`MapSettings.xml` duplicates the filter fields] → the two files can drift;
  a functional test asserts both data structures expose the same filter
  fields.

## Migration Plan

No data migration. The map's FlexForm file is swapped in TCA; stored values
keep their names.
