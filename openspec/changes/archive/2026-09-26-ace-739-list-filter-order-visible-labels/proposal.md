## Why

The backport of the `main` change of the same name, ACE-739, archived there as
`openspec/changes/archive/2026-09-26-ace-739-list-filter-order-visible-labels`
(pull request #755).

The partner, project and program lists offer one category filter per category
type: every type with categories, in registry order, all at once and with one
generic "all" label. Four analysed projects rewrite the filter partials of all
three extensions to choose the types, fix their order, show only the first few
with "more filters" behind a toggle, and label each "all" option per type.

## What Changes

- Integrators choose which category types a list filter offers, and in which
  order, through a setting; empty keeps today's behaviour.
- A setting limits how many filters are visible; the rest are behind a native
  "more filters" disclosure that opens by itself while one of them is active.
- A setting switches the filters to leave out options without results.
- The "all" option of each filter reads a per-type label when one exists and
  falls back to today's generic label.
- The same three settings, with the same names, in all three extensions, as
  TypoScript constants and, on TYPO3 v13, site settings.
- On TYPO3 v12 and v13, a TypoScript label override of the filter form is
  read from `plugin.tx_academic<group>` and `plugin.tx_academic<group>_<plugin>`
  and no longer from `plugin.tx_academic_<group>`: the filter templates pass
  the extension name in UpperCamelCase. An `Important-` changelog entry per
  extension says what to copy.
- Without configuration, and apart from that label path, the filter output is
  unchanged.

The behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-partners/list-filter`: the category filters of the partner list
  and map.
- `academic-projects/list-filter`: the same for the two project lists.
- `academic-programs/list-filter`: the same for the program list. Unlike
  `main`, this branch has no program filter types capability to modify: the
  program list gets the filter types here for the first time, site-wide only.

### Modified Capabilities

None.

## Impact

- `category_types` (`packages/fgtclb/typo3-category-types`):
  `FilterTypeResolver` and `FilterTypes`, which `main` got with ACE-736, arrive
  here with the settings reader of this change.
- `academic_partners` (`packages/fgtclb/academic-partners`),
  `academic_projects` (`packages/fgtclb/academic-projects`),
  `academic_programs` (`packages/fgtclb/academic-programs`): list controllers,
  `DemandCategories` partials, TypoScript constants and setup, site settings
  of the aggregate sets, labels.
- `packages-dev/testing-helper`: `CategoryFilterFormAssertionTrait`.
- Tests, `Feature-` and `Important-` entries in `Changelog/2.4/`, identical
  to `main`; the configuration chapters of the three extensions.

## Non-goals

- The FlexForm field for the program filter types and the program finder:
  both are `main` only.
- The templates outside the filter form that still pass the underscored
  extension name: ACE-740.
