## Context

Verified on `main` in `packages/fgtclb/academic-programs`:

- `Classes/Enumeration/SortingOptions.php` knows `title asc|desc`,
  `lastUpdated asc|desc` and only `sorting asc`. `sorting desc` was removed on
  purpose in `a1aef04ed` (2024-10-16, "[TASK] Remove sorting option by sorting
  desc"), without a reason in the message.
- `Classes/ViewHelpers/Form/SortingSelectViewHelper.php` derives the field
  and the direction options from every constant, so the direction select
  offers `asc` and `desc` whatever the field.
- `Resources/Private/Partials/Program/DemandSorting.html` renders the two
  selects on `sortingField` and `sortingDirection`.
- `Classes/Factory/DemandFactory.php` applies the field, then the direction.
  `ProgramDemand::setSorting()` accepts only a value that is a constant and
  otherwise keeps the previous pair. `sorting` plus `desc` therefore keeps
  whatever was set before, `sorting asc` in the common case.
- `Configuration/FlexForms/ProgramListSettings.xml` lists five items for
  `settings.sorting`, none of them `sorting desc`.
- `academic_partners` has `SORT_BY_SORTING_DESC = 'sorting desc'`.
- `ProgramRepository` appends a `uid` tiebreaker to every ordering (ACE-491).
- There is no unit test for `ProgramDemand`; the plugin test covers the title
  orderings only.

## Goals / Non-Goals

**Goals:**

- Every pair the two selects can post is a valid ordering.
- The programs and partners lists offer the same set of orderings.

**Non-Goals:**

- Changing how invalid input is treated. A value no select can produce keeps
  being ignored, as today.

## Decisions

### Restore `SORT_BY_SORTING_DESC`

Add `SortingOptions::SORT_BY_SORTING_DESC = 'sorting desc'`, the FlexForm item
`sorting desc` with the label `flexform.sorting.sorting.desc` (English and
German `locallang_be.xlf`), and nothing in the templates: the ViewHelper
already derives the options from the constants, and the `uid` tiebreaker
already applies.

Rejected: switching `DemandSorting.html` to one select with `type="combined"`
on `property="sorting"`. The ViewHelper and `DemandFactory` support it, and it
only offers valid pairs, but it changes the markup every project styled, and
it keeps the lists of programs and partners different.

Rejected: hiding the direction select for the `sorting` field. It needs
JavaScript or a reload, and the form without JavaScript still posts the pair.

Rejected: validating in the repository as ACE-439 discusses for persons. It
drops the ordering on bad input instead of making the offered input valid.

### Decided: restore the reversed manual order (ACE-625)

`sorting desc` comes back as `SORT_BY_SORTING_DESC` with the FlexForm item
and the label `flexform.sorting.sorting.desc`; the templates stay as they are.
The sorting ViewHelper already offers `sorting` with `desc`, which the demand
silently turns back into `sorting asc`, and academic_partners already ships
the constant, whereas one combined select would change markup that projects
styled and keep the two lists different. Because `a1aef04ed` records no
reason for the removal, its author confirms that there was none before the
change is applied.

## Risks / Trade-offs

- [The removal in `a1aef04ed` had a reason nobody recorded] → Task 1.1 asks
  its author before implementation; the combined select is the prepared
  alternative.
- [Combined-select labels miss the new value] → A project using
  `type="combined"` derives the label key `sorting.desc`; the task checks the
  combined labels for every constant.

## Migration Plan

None. Stored content elements keep their values; the reversed manual order is
one more reachable ordering. ace-demo can drop its demand XCLASS afterwards.

## Open Questions

None.
