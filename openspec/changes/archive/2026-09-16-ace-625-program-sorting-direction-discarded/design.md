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
  selects on `sortingField` and `sortingDirection`, inside a form that submits
  by POST.
- `Classes/Factory/DemandFactory.php` applies the field, then the direction.
  `ProgramDemand::setSorting()` accepts only a value that is a constant and
  otherwise keeps the previous pair. `sorting` plus `desc` therefore keeps
  whatever was set before, `sorting asc` in the common case.
- `Configuration/FlexForms/ProgramListSettings.xml` lists five items for
  `settings.sorting`, none of them `sorting desc`.
- `academic_partners` has `SORT_BY_SORTING_DESC = 'sorting desc'`, and so does
  `academic_projects`. Programs is the only one of the three list extensions
  without it.
- `ProgramRepository` appends a `uid` tiebreaker to every ordering (ACE-491).
- `Configuration/Yaml/Routes.yaml` maps both directions for all three fields,
  so `/sorting/desc` already resolves — into an ordering that was then
  discarded. `Documentation/Configuration/RouteEnhancers/Index.rst` documents
  that mismatch as a caveat, and stops being true with this change.

**Labels.** The ViewHelper derives `sorting.field.<field>` for `type="fields"`
and `sorting.direction.<direction>` for `type="directions"`. Both keys the
reversed manual order needs — `sorting.field.sorting` and
`sorting.direction.desc` — already exist in `locallang.xlf` and
`de.locallang.xlf`, so **no frontend label is added by this change**. The
`combined` type derives `sorting.<field>.<direction>`; those labels were added
in `63aa6a508` and removed again in `8e27dd444` (2025-06-02, "[!!!][TASK]
Improve templating of academic programs"). No option has a combined label
today, so that type is unlabelled for all five equally — pre-existing, and out
of scope here. Only the backend FlexForm item needs a new label.

**Three test files pin the current behaviour and have to change with it.** An
earlier revision of this design claimed there was no unit test for
`ProgramDemand`; that was wrong, and it changes the shape of the work from
"add a test" to "update a pinned assertion", which is the case ACE-439
describes:

- `Tests/Unit/Domain/Model/Dto/ProgramDemandTest.php`,
  `aFieldOfferedInOneDirectionOnlyRejectsTheOther()`, asserts that `sorting`
  plus `desc` keeps `sorting asc`. Its docblock names this very situation as
  "what a list plugin offering 'backend order, reversed' would silently run
  into". Its `knownSortingOptions()` provider lists five options.
- `Tests/Unit/Enumeration/SortingOptionsTest.php`,
  `everySortingOptionIsOffered()`, asserts the constant map as a whole, so a
  sixth constant turns it red, and its docblock describes the asymmetry as
  intended.
- `Tests/Functional/Routing/ProgramListRouteEnhancerTest.php` holds a
  hardcoded `SORTING_OPTIONS` list of five pairs with a comment stating there
  is deliberately no `sorting desc`. Adding the pair **does** turn it red:
  `everyGeneratedSortingUriResolvesBackIntoItsArguments()` renders the page for
  every pair and `assertSelectedSorting()` checks the direction select shows
  the demanded value, which is the defect seen from the outside. The list is
  still spelled out rather than derived, so a future option has to be added by
  hand — nothing reports a missing one.

The plugin test covers the title orderings only.

## Goals / Non-Goals

**Goals:**

- Every pair the two selects can post is a valid ordering.
- The programs, partners and projects lists offer the same manual orderings.

**Non-Goals:**

- Changing how invalid input is treated. A value no select can produce keeps
  being ignored, as today.
- Restoring the combined labels `8e27dd444` removed. They are missing for
  every option, which is a separate defect of the `combined` type.

## Decisions

### Restore `SORT_BY_SORTING_DESC`

Add `SortingOptions::SORT_BY_SORTING_DESC = 'sorting desc'`, the FlexForm item
`sorting desc` with the backend label `flexform.sorting.sorting.desc` (English
and German `locallang_be.xlf`), and nothing in the templates or the frontend
labels: the ViewHelper already derives the options from the constants, both
derived frontend keys already resolve, and the `uid` tiebreaker already
applies.

Rejected: switching `DemandSorting.html` to one select with `type="combined"`
on `property="sorting"`. The ViewHelper and `DemandFactory` support it, and it
only offers valid pairs, but it changes the markup every project styled, it
keeps the lists of programs and partners different, and the combined labels do
not exist any more.

Rejected: hiding the direction select for the `sorting` field. It needs
JavaScript or a reload, and the form without JavaScript still posts the pair.

Rejected: validating in the repository as ACE-439 discusses for persons. It
drops the ordering on bad input instead of making the offered input valid.

### Decided: restore the reversed manual order (ACE-625)

`sorting desc` comes back as `SORT_BY_SORTING_DESC` with the FlexForm item and
its backend label; the templates stay as they are. The sorting ViewHelper
already offers `sorting` with `desc`, which the demand silently turns back into
`sorting asc`, and both sibling extensions already ship the constant, whereas
one combined select would change markup that projects styled and keep the
three lists different.

### Decided: the removal in `a1aef04ed` is not re-litigated

The maintainer waived the question on 2026-09-16 and decided to restore the
constant. What the record shows: the removal was deliberate — `63aa6a508`
shaped the combined label set without a `desc` counterpart nineteen seconds
earlier, by the same author — but no reason for it is written down anywhere,
the author's own label for the ascending variant read "Page sorting (Result
may be unexpected)", and both sibling extensions kept the constant. ACE-625
itself proposes restoring it.

## Risks / Trade-offs

- [The removal had an unrecorded reason] → Accepted by the maintainer. The
  combined select stays the prepared alternative if one surfaces.
- [A hardcoded test list stops covering a future option] →
  `ProgramListRouteEnhancerTest::SORTING_OPTIONS` is spelled out, so a seventh
  option would go uncovered without failing. Named in its own comment.
- [The `combined` ViewHelper type renders raw label keys] → Pre-existing since
  `8e27dd444` and equal for all options; named in the pull request, not fixed
  here.

## Migration Plan

None. Stored content elements keep their values; the reversed manual order is
one more reachable ordering. ace-demo can drop its demand XCLASS afterwards.

## Open Questions

None.
