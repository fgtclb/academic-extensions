## Context

See `proposal.md` for the motivation. The data for tags is already in the
view: `demand.filterCollection.filterCategories` holds the resolved filter
categories keyed by type and is read today by `Templates/Partner/Map.html`
for its empty state. The result is a `QueryResultInterface`, whose `count()`
is the total, not a page, so a later pagination does not change the count.

This change depends on `ace-723-list-filter-get-urls` (the GET argument shape
and `CategoryFilterNormalizer::toFilterArguments()`) and on the
`settings.filter` namespace introduced by candidate `listings-08`. One
project's override reads `demand.activeFilters`, which exists on no demand
class; that is not reproduced.

## Goals / Non-Goals

**Goals:**

- Tags, reset and count as three independent, opt-in partials per extension.
- Tag links that keep every other selection, including the sorting.

**Non-Goals:**

- A shared partial in `academic_base`; the three filter UIs stay separate.
- Styling beyond neutral class names (`academic-<ext>-active-filters`, …).

## Decisions

### Link arguments come from a ViewHelper in category_types

`FGTCLB\CategoryTypes\ViewHelpers\FilterArgumentViewHelper`
(`ct:filterArgument`) takes the filter collection and an optional category
to leave out, and returns the flat uid list of `toFilterArgument()` without
that category. The partial merges the sorting and, for projects, the active
state, and renders `f:link.action` with `arguments="{demand: …}"`. The
ViewHelper is stateless and delegates to the normaliser, so the URL shape is
defined once.

The reset link carries no demand at all, so it returns to the list as the
content element presets it (see the preselection decision of
`ace-723-list-filter-get-urls`), which is what an editor who preselected a
category expects "reset" to mean.

Rejected: a `getActiveFilters()` method on the three demand classes. The
filter collection already holds the categories; a second representation adds
API on three classes without new information. Also rejected: assembling the
"all but one" array in Fluid, which cannot remove a value from a comma list.

### The project active state is a tag too

A non-default active state is a visible selection like a category, so the
projects partial renders it as a tag that links back to the default state.
Without it, "reset" would be the only way to undo it.

### Three settings, default 0

`settings.filter.showActiveFilters`, `settings.filter.showReset` and
`settings.filter.showResultCount` in each extension's TypoScript setup, fed
by site set settings `plugin.tx_academic<ext>.filter.*` following the
`plugin.tx_academicpersons.pagination.*` convention of `academic_persons`.
The partials are rendered from `SortingAndFilters.html` behind
`f:if`, so a site with an overridden `SortingAndFilters.html` sees no change.

Rejected: a FlexForm field per content element; every project that asked sets
this site-wide.

### Decided: TypoScript and site settings only

The three `settings.filter.show*` switches are TypoScript and site settings
only, the same answer as for the filter settings of
`ace-739-list-filter-order-visible-labels`. It is the same question on the
same `settings.filter` namespace, and a FlexForm override can be added later
without breaking anything.

### Pluralised count label

`list.resultCount.singular` / `list.resultCount.plural` with `%d`, chosen in
the partial on `count == 1`. Two plain keys are readable in a project's label
override and need no plural support from the label parser; whether
`f:translate` offers plural selection on both core versions is therefore not
a dependency of this change.

### Decided during implementation

- **The reset link follows the visitor's selection and what can be reset.** On
  the bare page the list shows the content element's preselection, and the
  reset link would lead to the page shown. After the visitor removed a
  preselected category no filter is active, and the reset link is the one way
  back to it. With nothing selected and nothing preselected it would reset no
  more than the sorting or the page. Each list action assigns
  `visitorSelection` (`$demand !== null`, the factories' own test for applying
  the preselection), and the link is offered while it is true and a category
  (projects: or a state other than `all`) is selected or preset.
  Rejected: comparing the selection with the preselection, which needs the
  preselection loaded a second time for a link.
- **Tags and the reset link follow the filter's visibility.** Where the
  content element hides the filter (projects: the state select), the visitor
  cannot change it, and a tag would let them. The count is shown regardless.
- **The partner map shows the count too.** It renders the same partial as the
  list, and on the map it counts the partners drawn.
- **The tag links of the map name `map`.** Extbase gives a template no access to
  the action it runs in, so `Templates/Partner/Map.html` sets `filterAction`.
  Without it the partial links to `list`, which the map plugin answers with its
  default action under a second URL.
- **The tags loop over `allCategoriesByType`.** `CategoryCollection` is an
  `\Iterator` with a single position, and the ViewHelper walks the same
  collection inside the template's loop, which ended the loop after the first
  tag. The arrays per type also put the tags in the order of the filters.
- **`CategoryFilterNormalizer::toFilterArgument()` takes the category to leave
  out** as an optional second argument. The ViewHelper only adapts its
  arguments to it.
- **Settings in TypoScript and site settings, as decided.** The site settings
  are declared with each aggregate set next to the other filter settings.

## Risks / Trade-offs

- [`listings-08` lands later or with another namespace] → it landed as
  ACE-739 with `settings.filter`, which this change uses.
- [Tag titles come from the default language in a strict-fallback site] →
  the titles are those of the resolved filter categories, which the factory
  loads in the current language; covered by a test in a translated fixture.

## Open Questions

None.
