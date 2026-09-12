## Why

The program list of `academic_programs` (`packages/fgtclb/academic-programs`)
lets a visitor pick a sort field and a sort direction independently, and offers
both directions for every field. The manual order has only an ascending
variant, so a visitor who picks "manual order" and "descending" gets the
ascending list back, and the direction select snaps back to "ascending"
without a word. `academic_partners` (`packages/fgtclb/academic-partners`)
offers the reversed manual order, so the two lists disagree.

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
- No database schema change, no template change, no dependency change.
- `academic_partners` is unchanged; it already offers the reversed manual
  order.

## Non-goals

- Validating the ordering in the repository and dropping invalid input, which
  is the behaviour ACE-439 discusses for `academic_persons`.
- Hiding the direction select for a field that has only one direction.
- A fallback on the modification date for "last updated" (ACE-624).
- Generic sorting UI changes shared by partners, projects and programs.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`programs-studyplan-01`). One of the six analysed projects carries its own
code for this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-program-sorting-direction-discarded` when the issue is filed after
implementation.

Implements ACE-625. Relates to ACE-439.
