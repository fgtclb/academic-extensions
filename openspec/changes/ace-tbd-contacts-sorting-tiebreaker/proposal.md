## Why

`ContactRepository::findByPid()` of `academic_contacts4pages` orders the
contacts of a page by `sorting` and nothing else. Contacts that share a
`sorting` value are returned in whatever order the database yields, which on
PostgreSQL is not the same list twice; the contact table is workspace aware,
so PostgreSQL has an index to choose another access path with. Rule 3 of
`docs/architecture/database-queries.md` orders a manually sortable table by
`sorting` with `uid` breaking ties. This repository is the one list query left
that does not, on both branches; ACE-491 did not cover it and ACE-431 named it
as left out.

## What Changes

- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`):
  `ContactRepository::findByPid()` appends `uid` ascending to its `sorting`
  ordering. Both the contacts content element and the page contacts data
  processor use it.
- A functional test with contacts sharing a `sorting` value, and an
  `Important-` changelog entry.
- TYPO3 v13 and v14 alike.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-contact4pages/page-contacts`: contacts equal in the editor's
  arrangement are shown in the same relative order on every request.

## Impact

One query, one test, one changelog entry. No schema, TCA or template change.
Contacts with distinct `sorting` values keep their order.

## Non-goals

- The shared `sorting` column of page contacts with three inline parents,
  which is `ace-699-inline-sort-column-per-parent`. That change keeps the
  editor's arrangement intact; this one settles ties. They are independent,
  and either can land first.
- The language handling of the query (ACE-484 on `main`).

## Source

Left out of ACE-431 on 2026-09-19 and named in its closing comment. Not from
the project differences analysis; adopted into the same pipeline. No YouTrack
issue is filed yet; the change is renamed to
`ace-<NNN>-contacts-sorting-tiebreaker` when the issue is filed after
implementation. Relates to ACE-431 and ACE-491.
