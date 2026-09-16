## Why

The program list of `academic_programs` (`packages/fgtclb/academic-programs`)
lets a visitor pick a sort field and a sort direction independently, and offers
both directions for every field. The manual order has only an ascending
variant, so a visitor who picks "Page sorting" and "Descending" gets the
ascending list back, and the direction select snaps back to "Ascending"
without a word.

This is the backport of ACE-625, merged on `main` for 3.0.0. It is re-derived
against this branch rather than cherry-picked: `ProgramDemand` and the sorting
ViewHelper are byte-identical here, but the enumeration extends the core
`Enumeration` base class, the plugin FlexForm is split per core version, and
the language files are indented differently.

## What Changes

- The program list accepts the reversed manual order, so every combination the
  two selects can produce is a valid ordering.
- The list content element offers "Page sorting, reversed" as a default
  ordering, like the other fields, on TYPO3 v12 and v13 alike.
- Nothing changes for stored content elements or existing orderings.

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
- The FlexForm item is added **twice**, to
  `Configuration/FlexForms/Core12/ProgramListSettings.xml` and
  `Core13/ProgramListSettings.xml`, because this branch has not flattened that
  split.
- No database schema change, no template change, no dependency change, and no
  frontend label: the two keys the sorting ViewHelper derives for the new value
  already exist.
- Documentation: a `2.4` changelog entry, and a correction to the route
  enhancer page, which documented `/sorting/desc` as a path that resolves to an
  option the plugin never offers.
- Two unit test files pinned the previous behaviour and are updated with it;
  the route enhancer functional test reproduces the defect end to end.

## Non-goals

- Validating the ordering in the repository and dropping invalid input, which
  is the behaviour ACE-439 discusses for `academic_persons`.
- Hiding the direction select for a field that has only one direction.
- Adding a plugin test class for `academic_programs`, which this branch does
  not have. That is new test infrastructure, not a backport.
- Restoring the combined sorting labels, which are missing for every option on
  both branches.

## Source

Backport of ACE-625 (`main`, 3.0.0), re-derived from the backport analysis as
`docs/workflow/backporting.md` requires. The archived change on `main` is
`ace-625-program-sorting-direction-discarded`; specs are branch scoped, so this
is a change of its own.

Implements ACE-625, which carries the `[3.x][2.x]` prefix and Version 2.4.0 and
therefore covers both lines. Relates to ACE-439.
