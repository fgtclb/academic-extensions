## Why

The category filter of the partner, project and program lists marks every
category that no entry of the current result carries as a disabled option.
A site that wants those options gone instead of greyed out has to give up the
shipped option rendering and write its own option loop, losing the grouping
by parent category; one project does this three times.

## What Changes

- The category filter form field gets an opt-in switch that leaves out
  options without results instead of rendering them disabled.
- A selected option is always kept, even without results, so an active
  filter stays visible and removable.
- With grouping by parent category, a parent without results is kept while
  one of its children is shown, so the hierarchy stays intact.
- Default off: without the switch the output is unchanged.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `typo3-category-types/filter-select`: which options the category filter
  form field renders.

### Modified Capabilities

None.

## Impact

- `category_types` (`packages/fgtclb/typo3-category-types`): the filter select
  form field used by the filter partials of `academic_partners`,
  `academic_projects` and `academic_programs`.
- The shipped filter partials are not changed here; they gain the switch
  through a setting in a separate change. Integrators can use it in their own
  partials right away.
- Functional tests of the form field; a `Feature-` changelog entry.

## Non-goals

- Changing which categories count as "without results".
- Wiring the switch into the shipped filter partials and settings.
- Hiding the "all" option or a whole filter whose options are all without
  results.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`listings-07`). One of the six analysed projects carries its own code for this
today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-filter-select-hide-disabled` when the issue is filed after
implementation.
