## Why

The partner, project and program lists offer one filter per category type,
always all of them, in registry order, with one generic "all" label. Four
projects rewrite the same filter partials in all three extensions to choose
the types, fix their order, show only the first few with "more filters"
behind a toggle, and label each "all" option per type.

## What Changes

- Integrators choose which category types a list filter offers, and in which
  order, through a setting; empty keeps today's behaviour.
- A setting limits how many filters are visible; the rest are behind a
  native "more filters" disclosure that opens by itself while one of them is
  active. 0 keeps all visible.
- A setting switches the filters to leave out options without results.
- The "all" option of each filter reads a per-type label when one exists and
  falls back to today's generic label.
- The same three settings, with the same names, in all three extensions,
  settable through TypoScript and site settings.
- Without configuration the filter output is unchanged.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/list-filter`: which category filters the partner list
  and map offer, in which order and how they are labelled.
- `academic-projects/list-filter`: the same for the project list.
- `academic-programs/list-filter`: the same for the program list.

### Modified Capabilities

None.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`),
  `academic_projects` (`packages/fgtclb/academic-projects`),
  `academic_programs` (`packages/fgtclb/academic-programs`): list
  controllers, the `DemandCategories` filter partials, TypoScript constants
  and setup, site settings definitions, labels.
- `category_types` (`packages/fgtclb/typo3-category-types`): a stateless
  helper that orders and splits the filter types. It has no visitor-facing
  behaviour of its own, so it carries no spec.
- Depends on the change that lets the filter form field hide options without
  results (`ace-tbd-filter-select-hide-disabled`).
- Functional tests of the three list plugins, a unit test of the helper,
  `Feature-` changelog entries.

## Non-goals

- A per-content-element setting in the FlexForm. The per-element override of
  the program filter types under the same key `settings.filter.categoryTypes`
  is `ace-tbd-program-list-filter-types`, which builds on this change.
- Removing the hard-coded grid column classes of the filter cells.
- Bookmarkable filter URLs, active filter tags, reset links or result counts.
- One shared filter partial for all extensions.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-08`). Four of the six analysed projects carry their own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-list-filter-order-visible-labels` when the issue is filed after
implementation.
