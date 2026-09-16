## Context

Verified on this branch (`2`, `63fa93b5d`) in
`packages/fgtclb/academic-programs`, and compared against the merged `main`
change file by file:

| File                                                   | `main`                                          | here                                                                 | Consequence                                                                                                                             |
|--------------------------------------------------------|-------------------------------------------------|----------------------------------------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------|
| `Classes/Enumeration/SortingOptions.php`               | plain final class with its own `getConstants()` | `final class SortingOptions extends TYPO3\CMS\Core\Type\Enumeration` | The constant is added the same way. `getConstants()` comes from core and already excludes `__default`, which the existing test asserts. |
| `Classes/Domain/Model/Dto/ProgramDemand.php`           | —                                               | **byte-identical**                                                   | The defect is the same: `setSorting()` accepts only a value that is a constant and otherwise keeps the previous pair.                   |
| `Classes/ViewHelpers/Form/SortingSelectViewHelper.php` | —                                               | **byte-identical**                                                   | Both selects are derived from the constants, so the direction select already offers `desc` for every field.                             |
| `Configuration/FlexForms/ProgramListSettings.xml`      | one file                                        | **`Core12/` and `Core13/`**                                          | The item is added twice. The flatten happened on `main` only.                                                                           |
| `Resources/Private/Language/*locallang_be.xlf`         | two spaces                                      | **tabs**                                                             | Same labels, this branch's indentation.                                                                                                 |

`ProgramRepository` orders by the demanded pair. Unlike `main` it appends no
`uid` tiebreaker here — that is ACE-491, which is not part of this change and
is not introduced by it.

**The tests that pin the current behaviour.** Both exist here and are
substantively identical to the ones on `main`:

- `Tests/Unit/Domain/Model/Dto/ProgramDemandTest.php`,
  `aFieldOfferedInOneDirectionOnlyRejectsTheOther()`, asserts that `sorting`
  plus `desc` keeps `sorting asc`, with a docblock naming this exact case.
- `Tests/Unit/Enumeration/SortingOptionsTest.php`,
  `everySortingOptionIsOffered()`, asserts the constant map as a whole.
- `Tests/Functional/Routing/ProgramListRouteEnhancerTest.php` holds the same
  hardcoded `SORTING_OPTIONS` list. On `main` this turned out to fail on the
  unchanged code, because
  `everyGeneratedSortingUriResolvesBackIntoItsArguments()` renders the page for
  every pair and asserts the selected option — so it is a reproduction of the
  defect, not mere coverage. The same is expected here.

**What is absent here.** `academic_programs` has no `Tests/Functional/Plugins/`
directory on this branch, so the two plugin cases written on `main` — a posted
demand and a FlexForm default — have no home. `FrontendPluginRenderingTrait`
does exist, so a plugin test class could be created, but that is new test
infrastructure for this extension rather than a backport. The route enhancer
test covers the same path end to end and is used instead; the gap is stated in
the pull request.

## Goals / Non-Goals

**Goals:**

- Every pair the two selects can post is a valid ordering, on v12 and v13.
- The programs, partners and projects lists offer the same manual orderings.

**Non-Goals:**

- Changing how invalid input is treated. A value no select can produce keeps
  being ignored.
- Adding the `uid` tiebreaker (ACE-491) to this branch.

## Decisions

### Restore `SORT_BY_SORTING_DESC`, as on `main`

Add `SortingOptions::SORT_BY_SORTING_DESC = 'sorting desc'`, the FlexForm item
in **both** core version files with the backend label
`flexform.sorting.sorting.desc` (English and German, tab indented), and nothing
in the templates or the frontend labels.

The alternatives were weighed and rejected on `main` for reasons that hold here
unchanged: one combined select changes markup that installations have styled,
hiding the direction select needs JavaScript and the form still posts the pair
without it, and validating in the repository drops the ordering instead of
making the offered input valid.

### No frontend label is added

The ViewHelper derives `sorting.field.<field>` and
`sorting.direction.<direction>`, and both keys the reversed manual order needs
— `sorting.field.sorting` and `sorting.direction.desc` — already exist in
`locallang.xlf` and `de.locallang.xlf`. The `combined` type derives
`sorting.<field>.<direction>`, and those labels exist for no option on either
branch, which is a separate defect.

### The removal in `a1aef04ed` is not re-litigated

The maintainer waived that question on 2026-09-16 when the `main` change was
implemented: the removal was deliberate but no reason is recorded anywhere, and
both sibling extensions kept the constant. The same decision applies here.

## Risks / Trade-offs

- [The FlexForm item is added in two files] → A backport that edits only one of
  them fixes one core version and not the other, silently. Both are listed in
  the tasks and both are asserted by the FlexForm test run.
- [No plugin test on this branch] → The reversed ordering is proven by the unit
  tests plus the route enhancer test, not by a rendered list plugin. Stated in
  the pull request.
- [A hardcoded test list stops covering a future option] → Same as on `main`;
  named in the test's own comment.

## Migration Plan

None. Stored content elements keep their values; the reversed manual order is
one more reachable ordering.

## Open Questions

None.
