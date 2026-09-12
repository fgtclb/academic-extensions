## Context

See `proposal.md` for the motivation. Verified on `main`:

- `CategoryRepository::findAllApplicable()` marks every category that none of
  the given entities carries as disabled (`CategoryRepository.php:136-139`).
  All three list controllers feed the filter from it.
- `FilterSelectViewHelper::getOptions()` copies that into `isDisabled` (:42)
  and writes `disabled="disabled"` into the option attributes (:69-71). With
  `groupByParent`, children are only placed below a root they can reach; a
  child whose root is missing is dropped (covered by
  `groupedOptionWithoutItsRootIsDropped` in `FilterSelectViewHelperTest`).
- `renderOptions=false` hands the prepared options to the template as
  `options` (`AbstractSelectViewHelper::render()`, :113-116); that is the
  workaround projects use today.
- A disabled option can already be the selected one
  (`disabledCategoryCanBeTheSelectedOne`).

## Goals / Non-Goals

**Goals:**

- One opt-in argument, default off, no change to existing output.
- The same filtered list for both rendering paths (`renderOptions` true or
  false).

**Non-Goals:**

- A per-type or per-option setting.

## Decisions

### A `hideDisabledOptions` argument, applied after grouping

`FilterSelectViewHelper` registers `hideDisabledOptions` (bool, default
`false`). At the end of `getOptions()`, after the options were grouped and
linearised, a pass removes every option that is disabled, not selected, and
has no kept descendant. Filtering after grouping keeps the parent of a shown
child; the result feeds both the rendered options and the `options` variable.

Rejected: filtering before grouping. The tree building would then drop every
child of a disabled parent silently, because it only places children below a
root it can find.

Rejected: filtering in each extension's filter partial. That repeats the loop
in three extensions and loses the grouping the form field does.

### Selected options always stay

An option whose value is selected is never removed, so the visitor can see the
active filter and change it. The prepended "all" option is not an option of
this list and is unaffected.

### Decided: backport to branch `2` as a change of its own

The switch is backported to branch `2` in a separate change, after diffing
the form field between the two lines. A project runs 2.3.4, and the form
field there lacks the later fixes of `main`, so the backport is not a plain
cherry-pick.

## Risks / Trade-offs

- [After a selection, the alternatives of the same type have no result and
  disappear] → That is already the case in effect today: they are disabled
  and cannot be chosen. The "all" option stays the way back.
- [A filter can end up with only the "all" option] → Accepted; hiding a whole
  filter is a non-goal and belongs to the filter settings change.

## Migration Plan

None. Opt-in.

## Open Questions

None.
