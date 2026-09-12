## Why

The program list matches a selected category by exact identity. A visitor who
filters by "Bachelor" does not find a program that carries only "Bachelor of
Science", a child of "Bachelor". Editors therefore assign both levels, and
three projects then hide the parent again in the program output: by a uid
blacklist, by comparing titles with literal strings, or by a whitelist. The
same applies to the categories an editor preselects in the list plugin.

## What Changes

- A new list plugin option "Include subcategories", off by default.
- With the option on, a selected category matches every program that carries
  the category itself or any of its descendants. This applies to the
  categories preselected in the plugin and to the categories a visitor
  selects in the filter form.
- Several selections still all have to match: the option widens each
  selection by its subtree, it does not change how selections combine.
- The filter form is unchanged: the options and the selected value render as
  today.
- `category_types` gains a way to resolve the descendants of a set of
  categories, restricted to the types of one group.

Affected extensions: `academic_programs` (`packages/fgtclb/academic-programs`)
and `category_types` (`packages/fgtclb/typo3-category-types`).

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-list-subcategory-filter`: how the program list
  matches programs assigned to a subcategory of a selected category.

### Modified Capabilities

None.

## Impact

- The FlexForm of the program list plugin and its labels.
- The program list query, which gains one lookup per request for the
  descendants of the selected categories when the option is on.
- The category repository of `category_types`, used by other extensions only
  if they opt in.
- No database schema change; existing plugins keep today's result.

## Non-goals

- Matching any of several values of one type ("or within a type"). Upstream
  does not implement it; a project adds it through a listener on the demand
  events, which composes with this change (see the design).
- Hiding the parent category in the program output, which is the separate
  candidate `programs-studyplan-07`.
- The frontend order of the degree hierarchy (ACE-620).
- The same option for academic partners and projects.
- A backport to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-08`). Three of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-filter-match-subcategories` when the issue is filed after
implementation.

Relates to ACE-620.
