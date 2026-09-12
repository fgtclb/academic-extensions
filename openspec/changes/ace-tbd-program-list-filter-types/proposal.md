## Why

The program list renders one filter select for every category type of the
programs group that has categories among the listed programs, in the order of
the type registry. There is no way to offer fewer filters, or the same
filters in another order. Four projects hard-code their own ordered list of
four to seven types in a template override, and a fifth asks for a different
set of filters.

## What Changes

- A new list plugin field "Filter types": a multiple select of the category
  types of the programs group, in which the editor picks the filters and
  their order. It is stored under the key of the site-wide filter type
  setting that `ace-tbd-list-filter-order-visible-labels` adds for the
  partner, project and program lists, and overrides it for one element.
- An empty field falls back to the site-wide setting. With that empty as
  well, today's behaviour stays: every type with categories is offered, in
  the type order of the group.
- With the field set, the filter form offers exactly the chosen types, in the
  chosen order. A chosen type without categories among the listed programs
  is still left out, as today.
- `category_types` gains a reusable backend select that offers the category
  types of one group, with their titles and icons, in the type order of the
  group. The program field uses it, and partner and project lists can use it
  as well.

Affected extensions: `academic_programs` (`packages/fgtclb/academic-programs`)
and `category_types` (`packages/fgtclb/typo3-category-types`).

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-list-filter-types`: which filter selects the
  program list offers, and in which order.
- `typo3-category-types/category-type-selection`: a backend select that
  offers the category types of one group.

### Modified Capabilities

None.

## Impact

- The FlexForm of the program list plugin and its labels.
- The filter form of the program list.
- A new items provider in `category_types` for TCA and FlexForm select fields.
- Builds on `ace-tbd-list-filter-order-visible-labels`, which owns the
  site-wide setting and the resolver of the filter types; this change owns
  the items provider the finder element reuses as well.
- No database schema change; existing plugins keep today's filters.

## Non-goals

- Generic filter behaviour: visible count, reset, active filter chips,
  hiding options without results, "or within a type". These are shared by
  partners, projects and programs and belong to the listings analysis.
- Restricting which submitted filter values the list accepts. A value of a
  type that is not offered still filters, so existing links keep working.
- The FlexForm fields for partner and project lists. They get the site-wide
  selection through the listings change in the same release; a per-element
  field can follow later under the same key.
- The program finder element (`programs-studyplan-10`), which uses the same
  key.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-09`). Five of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is
renamed to `ace-<NNN>-program-list-filter-types` when the issue is filed
after implementation.

The default order of the types follows `ace-tbd-category-type-priority-order`
once that change lands; this change does not depend on it.
