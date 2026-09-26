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
- On TYPO3 v13, a TypoScript label override of the filter form is read from
  `plugin.tx_academic<group>` and `plugin.tx_academic<group>_<plugin>`, as on
  v14, and no longer from `plugin.tx_academic_<group>`. An `Important-`
  changelog entry per extension says what to copy.
- Without configuration, and apart from that label path on v13, the filter
  output is unchanged.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-partners/list-filter`: which category filters the partner list
  and map offer, in which order and how they are labelled.
- `academic-projects/list-filter`: the same for the two project lists.

### Modified Capabilities

- `academic-programs/program-list-filter-types`: the program list, which
  already offers configured filter types in order, gains the visible count,
  the per-type "all" label and the hidden options without results.
- `academic-programs/program-finder`: the finder follows the list in the
  hidden options and the per-type "all" label.

## Impact

- `academic_partners` (`packages/fgtclb/academic-partners`),
  `academic_projects` (`packages/fgtclb/academic-projects`),
  `academic_programs` (`packages/fgtclb/academic-programs`): list
  controllers, the `DemandCategories` filter partials, TypoScript constants
  and setup, site settings definitions, labels.
- `category_types` (`packages/fgtclb/typo3-category-types`): the stateless
  helper that orders and splits the filter types exists since
  `ace-736-program-list-filter-types`; it gains a method that reads the plugin
  settings. It has no visitor-facing behaviour of its own, so it carries no
  spec.
- `packages-dev/testing-helper`: a trait that reads the filters of a list's
  filter form, shared by the tests of the three extensions.
- Depends on the change that lets the filter form field hide options without
  results (`ace-738-filter-select-hide-disabled`).
- Functional tests of the three list plugins and the finder, a unit test of
  the helper, `Feature-` and `Important-` changelog entries in
  `Changelog/2.4/` - the change is backported to branch `2`.

## Non-goals

- A per-content-element setting in the FlexForm. The per-element override of
  the program filter types under the same key `settings.filter.categoryTypes`
  is `ace-736-program-list-filter-types`, merged before this change; this
  change builds on it.
- Removing the hard-coded grid column classes of the filter cells.
- Bookmarkable filter URLs, active filter tags, reset links or result counts.
- One shared filter partial for all extensions.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-08`). Four of the six analysed projects carry their own code for this
today. Implemented as ACE-739.
