## Why

A project page whose end date column holds no value at all is in neither list
filter of `academic_projects`: a visitor who filters by "Active" does not find
it, and neither does a content element preset to active projects. These are
the open ended projects, the ones that belong in "Active", and the project card
of the same page already says "Active", so the two disagree in front of the
visitor.

The trigger is common. A project page created in the page tree, a page switched
to the project page type, a page from before the extension was installed and
an imported page all keep the column empty, and saving such a page in the
backend without entering an end date does not fill it.

## What Changes

- The "Active" filter of the project list lists a project without any stored
  end date, exactly as it lists a project whose end date was saved empty.
- The "Completed" filter is unchanged: a project without an end date never
  appears there.
- The manual and the 3.0 changelog entry of the active state badge stop
  describing the gap, and an `Important` changelog entry tells integrators that
  such projects now appear in "Active" lists.

The behaviour is the same on TYPO3 v13 and v14, which this branch supports.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-projects/project-active-state`: the requirement that the state
  agrees with the list filter becomes two-sided, so every project in the state
  active is listed by the "Active" filter, including one without any stored
  end date.

## Impact

- `academic_projects` (`packages/fgtclb/academic-projects`): the list filter
  query, the functional tests that pin the gap today, the manual chapter on the
  state of a project, the 3.0 feature entry of the badge, a new 3.0 `Important`
  entry.
- No schema change, no upgrade wizard, no stored value changes.
- Visitors see open ended projects in lists filtered to "Active" that were
  missing so far. Paginated "Active" lists can shift by those entries.

## Non-goals

- Making the end date column `NOT NULL DEFAULT 0`. Existing rows without a
  value would have to be rewritten before the schema comparison, which
  PostgreSQL and strict MySQL otherwise refuse, see `design.md`.
- Rewriting stored values of existing pages.
- Changing the rule of the project state or the badge.
- The start date column, which is declared the same way but feeds no filter.

## Backport

ACE-433 is a `[3.x][2.x]` issue. The fix for branch `2` is a change of its own
on that branch, derived from a backport analysis after this one is merged. It
covers the same filter on TYPO3 v12 and v13. Branch `2` has no project state on
the model, no badge and no manual paragraph about the gap, so there it is the
filter fix, the inverted test assertions and an entry under
`Documentation/Changelog/2.4/` only.
