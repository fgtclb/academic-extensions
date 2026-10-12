## Why

A project page whose end date column holds no value at all is in neither list
filter of `academic_projects` (`packages/fgtclb/academic-projects`): a visitor
who filters by "Active" does not find it, and neither does a content element
preset to active projects. These are the open ended projects, the ones that
belong in "Active".

The trigger is common. A project page created in the page tree, a page switched
to the project page type, a page from before the extension was installed and
an imported page all keep the column empty, and saving such a page in the
backend without entering an end date does not fill it.

The same defect is fixed on `main` for 3.0. This is its own change for 2.4,
derived from a comparison of the affected files on both branches.

## What Changes

- The "Active" filter of the project list lists a project without any stored
  end date, exactly as it lists a project whose end date was saved empty.
- The "Completed" filter is unchanged: a project without an end date never
  appears there.
- An `Important` changelog entry for 2.4 tells integrators that such projects
  now appear in "Active" lists.

The behaviour is the same on TYPO3 v12 and v13, which this branch supports.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-projects/list-filter`: a new requirement on which projects the
  "Active" and "Completed" filters list, including a project without any
  stored end date.

## Impact

- `academic_projects`: the list filter query, the functional tests that pin the
  gap today, a new rendering test, a 2.4 `Important` entry.
- `docs/architecture/database-queries.md`: a section on filtering a column that
  holds `NULL` although its field is not `nullable`.
- No schema change, no upgrade wizard, no stored value changes.
- Visitors see open ended projects in lists filtered to "Active" that were
  missing so far. Paginated "Active" lists can shift by those entries.

## Non-goals

- Making the end date column `NOT NULL DEFAULT 0`, see `design.md`.
- Rewriting stored values of existing pages.
- A project state on the model or a badge on the card. They are 3.0 features
  and do not exist on this branch.
- The start date column, which is declared the same way but feeds no filter.
