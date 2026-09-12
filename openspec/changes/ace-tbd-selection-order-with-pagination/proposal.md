## Why

A list plugin of `academic_persons` (`packages/fgtclb/academic-persons`) with
a manual profile selection and pagination switched on ignores the order the
editor chose. The selection order is restored only on the unpaginated result,
while the template renders the paginated one. The paginated query also has no
`ORDER BY`, so on PostgreSQL two pages can overlap or skip a profile. No
project reported it; it came up while the analysis verified the letter
navigation.

## What Changes

- A list or list-and-detail plugin with a manual selection and pagination
  renders the selected profiles in the editor's order, page by page, with
  every selected profile on exactly one page.
- The selection query gets the same deterministic fallback ordering every
  other profile query already has.
- Lists without a manual selection are unchanged.

The behaviour is identical on TYPO3 v13 and v14 and on every supported DBMS.

## Capabilities

### New Capabilities

- `academic-persons/profile-list-pagination`: how the list and
  list-and-detail plugins split their profiles into pages, including a manual
  selection.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the list action of the profile controller and the
  selection branch of the profile repository.
- New functional list test fixture, run on SQLite and PostgreSQL.
- No template, setting, schema or API change. Listeners of the list event
  still receive the query result, before pagination.

## Non-goals

- Honouring the letter navigation or the editor filters for a manual selection
  (a selection deliberately overrides both).
- Reproducing the selection order in database queries.
- Changing the card, selected-profiles or selected-contracts plugins, which
  already sort a selection and do not paginate.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-display-03`). Found while verifying the analysis on main. No YouTrack
issue is filed yet; the change is renamed to
`ace-<NNN>-selection-order-with-pagination` when the issue is filed after
implementation.
