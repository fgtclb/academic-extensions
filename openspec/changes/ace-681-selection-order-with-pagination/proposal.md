## Why

Backport of ACE-681 to branch `2`. A list or list-and-detail plugin of
`academic_persons` (`packages/fgtclb/academic-persons`) with a manual profile
selection and pagination switched on ignores the order the editor chose. The
selection order is restored only on the unpaginated result, while the template
renders the paginated one. The paginated query also has no `ORDER BY`, so on
PostgreSQL two pages can overlap or skip a profile.

The defect is on this branch in the same shape as on `main`: the `listAction()`
block and `Templates/Profile/List.html` are byte-identical between the two.

## What Changes

- A list or list-and-detail plugin with a manual selection and pagination
  renders the selected profiles in the editor's order, page by page, with
  every selected profile on exactly one page.
- Lists without a manual selection are unchanged.

The behaviour is identical on TYPO3 v12 and v13.

## Capabilities

### New Capabilities

- `academic-persons/profile-list-pagination`: how the list and
  list-and-detail plugins split their profiles into pages, including a manual
  selection.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the list action of the profile controller.
- New functional list test fixture, run on SQLite and PostgreSQL.
- No template, setting, schema or API change. Listeners of the list event
  still receive the query result, before pagination.

## Non-goals

- Ordering the selection query itself. On `main` the same change appends the
  deterministic `uid` fallback to the selection branch of the profile
  repository, because `main` has that fallback on every other branch already
  (ACE-482, ACE-491). This branch has no such fallback anywhere, and giving one
  branch of one repository an ordering the rest of the branch does not have
  reads as an accident. Ordering the branch `2` queries is ACE-431.
- Honouring the letter navigation or the editor filters for a manual selection
  (a selection deliberately overrides both).
- Changing the card, selected-profiles or selected-contracts plugins, which
  already sort a selection and do not paginate.

## Source

Backport of ACE-681, re-derived from the file-level backport analysis rather
than cherry-picked, as `docs/workflow/backporting.md` requires.
