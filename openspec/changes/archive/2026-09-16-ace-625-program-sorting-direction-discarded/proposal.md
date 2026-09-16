## Why

The program list of `academic_programs` (`packages/fgtclb/academic-programs`)
lets a visitor pick a sort field and a sort direction independently, and offers
both directions for every field. The manual order has only an ascending
variant, so a visitor who picks "manual order" and "descending" gets the
ascending list back, and the direction select snaps back to "ascending"
without a word. `academic_partners` (`packages/fgtclb/academic-partners`) and
`academic_projects` (`packages/fgtclb/academic-projects`) both offer the
reversed manual order, so programs is the one list of the three that disagrees.

## What Changes

- The program list accepts the reversed manual order, so every combination the
  two selects can produce is a valid ordering.
- The list content element offers "manual order, descending" as a default
  ordering, like the other fields.
- Nothing changes for stored content elements or existing orderings.

The behaviour is identical on TYPO3 v13 and v14.

## Capabilities

### New Capabilities

- `academic-programs/program-list-sorting`: which orderings the program list
  offers to editors and visitors, and that every offered combination is
  honoured.

### Modified Capabilities

None.

## Impact

- `academic_programs`: the list of valid orderings, the list content element
  settings and their backend labels.
- No database schema change, no template change, no dependency change, and no
  frontend label: the two keys the sorting ViewHelper derives for the new
  value already exist.
- Documentation: a `3.0` changelog entry, and a correction to the route
  enhancer page, which documented `/sorting/desc` as a path that resolves to
  an option the plugin never offers. That stops being true here.
- Three existing test files pinned the previous behaviour and are updated with
  it; two of them reproduce the defect, as does the route enhancer test.
- `academic_partners` and `academic_projects` are unchanged; both already
  offer the reversed manual order.

## Non-goals

- Validating the ordering in the repository and dropping invalid input, which
  is the behaviour ACE-439 discusses for `academic_persons`.
- Hiding the direction select for a field that has only one direction.
- A fallback on the modification date for "last updated" (ACE-624).
- Generic sorting UI changes shared by partners, projects and programs.
- Restoring the combined sorting labels removed in `8e27dd444`. They are
  missing for every option, not only for the new one.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-01`). One of the six analysed projects carries its own
code for this today.

The issue already exists: **ACE-625** covers exactly this question, so no new
one is filed and the change is renamed to
`ace-625-program-sorting-direction-discarded` once the implementation is green.

Implements ACE-625. Relates to ACE-439, which discusses the opposite treatment
of invalid sorting input in `academic_persons`.
