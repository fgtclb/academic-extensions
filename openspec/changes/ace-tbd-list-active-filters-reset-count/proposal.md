## Why

A visitor who filtered the partner, project or program list sees the selects
and nothing else: no summary of what is active, no one-click way back to the
unfiltered list, and no number of results. Three projects build this
themselves, one of them against a demand property that does not exist, so
its tags never render server-side.

## What Changes

- An optional line of active filter tags above the list: one tag per selected
  category, each a link to the same list with only that category removed.
- An optional "reset all filters" link to the list without any filter
  argument.
- An optional result count line with a singular and plural label.
- All three are off by default and switched on per site through
  `settings.filter.showActiveFilters`, `settings.filter.showReset` and
  `settings.filter.showResultCount`, in the `settings.filter` namespace the
  filter configuration change (candidate `listings-08`) introduces.
- The links use the GET URL shape of `ace-tbd-list-filter-get-urls`.
- Affected extensions:
  - `academic_partners` (`packages/fgtclb/academic-partners`), list and map;
  - `academic_projects` (`packages/fgtclb/academic-projects`), both lists;
  - `academic_programs` (`packages/fgtclb/academic-programs`), list;
  - `category_types` (`packages/fgtclb/typo3-category-types`), which builds
    the "remove one category" link arguments.
- Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/list-active-filters`: active filter tags, reset link and
  result count for the partner list and map.
- `academic-projects/list-active-filters`: the same for the project lists.
- `academic-programs/list-active-filters`: the same for the program list.

### Modified Capabilities

None.

## Impact

- New partials `<Extension>/ActiveFilters.html` and `<Extension>/ResultCount.html`
  per extension, rendered from `SortingAndFilters.html`; an overridden
  `SortingAndFilters.html` simply does not show them.
- New labels in the three extensions' `locallang.xlf` (English and German).
- New TypoScript and site settings defaults (all `0`).
- No schema or dependency changes.

## Non-goals

- Changing the filter selects themselves (order, visible count, labels are
  `listings-08`).
- JavaScript-driven filtering without a page load.
- A per-content-element switch in the FlexForm; the switches are site-wide,
  and a FlexForm override can be added later without breaking.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-10`). Three of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-list-active-filters-reset-count` when the issue is filed after
implementation.

Relates to ACE-580 and ACE-612.
