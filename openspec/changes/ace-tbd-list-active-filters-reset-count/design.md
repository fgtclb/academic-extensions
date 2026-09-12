## Context

See `proposal.md` for the motivation. The data for tags is already in the
view: `demand.filterCollection.filterCategories` holds the resolved filter
categories keyed by type and is read today by `Templates/Partner/Map.html`
for its empty state. The result is a `QueryResultInterface`, whose `count()`
is the total, not a page, so a later pagination does not change the count.

This change depends on `ace-tbd-list-filter-get-urls` (the GET argument shape
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
`ace-tbd-list-filter-get-urls`), which is what an editor who preselected a
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
`ace-tbd-list-filter-order-visible-labels`. It is the same question on the
same `settings.filter` namespace, and a FlexForm override can be added later
without breaking anything.

### Pluralised count label

`list.resultCount.singular` / `list.resultCount.plural` with `%d`, chosen in
the partial on `count == 1`. Two plain keys are readable in a project's label
override and need no plural support from the label parser; whether
`f:translate` offers plural selection on both core versions is therefore not
a dependency of this change.

## Risks / Trade-offs

- [`listings-08` lands later or with another namespace] → this change adopts
  whatever namespace it settles on; the three keys are named here only.
- [Tag titles come from the default language in a strict-fallback site] →
  the titles are those of the resolved filter categories, which the factory
  loads in the current language; covered by a test in a translated fixture.

## Open Questions

None.
