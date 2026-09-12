## Why

Editors assign both the parent degree ("Bachelor") and the child ("Bachelor
of Science") to a program so that a filter on the parent finds it. The facts
then print both. Four projects hide the parent, each in its own fragile way:
a list of category uids, a comparison with the literal titles "Bachelor" and
"Master", or a whitelist, none of which survives another instance or
language.

## What Changes

- A site setting makes the program facts show only the most specific
  category of each type: a category is left out when a category assigned to
  the same program is its descendant within the same type.
- It applies to the program page, the program details content element and
  the program card.
- Off by default; nothing changes without configuration.

Behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-facts-most-specific-category`: the facts of a
  program can hide a category whose descendant is also assigned.

### Modified Capabilities

None.

## Impact

- `category_types` (`packages/fgtclb/typo3-category-types`): the category
  collection can return its categories per type with ancestors removed; no
  query, no schema change.
- `academic_programs` (`packages/fgtclb/academic-programs`): one site
  setting and its constant; the facts rendering introduced by
  `ace-tbd-program-facts-field-list` uses the reduced list when it is on.
- List filtering is unaffected.

## Non-goals

- Matching a parent filter against programs that carry only the child
  (`ace-tbd-filter-match-subcategories`).
- The frontend order of a degree hierarchy (ACE-620).
- A "hide in frontend" column on `sys_category`.
- The same switch for the partner and project listings; it follows when an
  installation asks for it (see the design).
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-07`). Four of the six analysed projects carry their own
code for this today. No YouTrack issue is filed yet; the change is renamed
to `ace-<NNN>-most-specific-categories-only` when the issue is filed after
implementation.

Relates to ACE-620.
