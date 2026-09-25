## Context

See `proposal.md` for the motivation. Verified on `main`:

- `CategoryRepository::findAllApplicable()` marks every category that none of
  the given entities carries as disabled, in the loop over its result. All
  three list controllers feed the filter from it.
- `FilterSelectViewHelper::getOptions()` copies that into `isDisabled` when it
  builds an option, and writes `disabled="disabled"` into the option
  attributes in its last loop. With `groupByParent`, children are only placed
  below a root they can reach; a child whose root is missing is dropped
  (covered by `groupedOptionWithoutItsRootIsDropped` in
  `FilterSelectViewHelperTest`).
- `renderOptions=false` hands the prepared options to the template as
  `options` (`AbstractSelectViewHelper::render()`); that is the workaround
  projects use today.
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

"Selected" is the value the select is bound to, not the `isSelected` flag of
the option: with `selectAllByDefault` and nothing selected every option carries
that flag, and keeping them all would make the switch a no-op for a multiple
select. Such an option is no active filter, and a disabled option is never
submitted either way. Decided during the apply (2026-09-26).

The comparison is the one `isSelected()` makes, shared as `isBoundValue()` in
`AbstractSelectViewHelper`: a loose `in_array()` over the string values. A
strict comparison of its own would hide an option the same markup marks as
selected, for a bound `"02"` and an option `"2"`.

### What the reduced list changes besides the markup

The form token registration of a multiple select counts the options
`getOptions()` returns, so it follows the reduced list by construction. It is
not asserted: the count is only observable through a rendered form and its
token, which these view helper tests do not build.

### Descendants in the linearised list

After grouping, `getOptions()` holds a flat list in tree order with a `level`
per option; the descendants of an option are the options that follow it with a
higher level. The pass walks the list backwards, so every descendant is decided
before its ancestor. Without `groupByParent` every level is 0: no option has a
descendant, and a disabled parent is left out like any other disabled option,
even when a child of it is shown. Grouping is what makes the hierarchy visible,
so only grouping keeps it.

### Decided: backport to branch `2` as a change of its own

The switch is backported to branch `2` in a separate change, after diffing
the form field between the two lines. A project runs 2.3.4. The diff on
2026-09-26 found the form field and its test class identical on both lines,
and the base class different only in how `render()` reaches the rendering
context; the change of branch `2` is still one of its own, because specs are
branch-scoped.

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
