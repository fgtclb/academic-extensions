## Why

`ContactRepository::findByPid()` of `academic_contacts4pages` orders the
contacts of a page by `sorting` and nothing else. Contacts that share a
`sorting` value are returned in whatever order the database yields, and
PostgreSQL promises no order without an `ORDER BY`. Rule 3 of
`docs/architecture/database-queries.md` orders a manually sortable table by
`sorting` with `uid` breaking ties. `ContactRepository::findByPid()` is the one
list query left that does not, on both branches; ACE-491 did not cover it and
ACE-431 named it as left out.

## What Changes

- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`):
  `ContactRepository::findByPid()` appends `uid` ascending to its `sorting`
  ordering. Both the contacts content element and the page contacts data
  processor use it.
- A functional test with contacts sharing a `sorting` value, spread over two
  storage folders, and an `Important-` changelog entry.
- The tie recipe in the "Testing an ordering" section of
  `docs/architecture/database-queries.md`, which says how to build a tie
  fixture that can fail at all.
- TYPO3 v13 and v14 alike.

## Capabilities

### New Capabilities

None.

### Modified Capabilities

- `academic-contact4pages/page-contact-order`: contacts equal in the
  arrangement of a page are shown in the same relative order on every request.

## Impact

One query, one test, one changelog entry, one documentation paragraph. No
schema, TCA or template change. Contacts with distinct `sorting` values keep
their order.

## Non-goals

- The shared `sorting` column of page contacts with three inline parents,
  which was `ace-699-inline-sort-column-per-parent` and has landed. That
  change kept the arrangement of a page intact by giving the contract and the
  contacts role relations sort columns of their own; this one settles the ties
  that remain. They are independent.
- The language handling of the query (ACE-484 on `main`).
- The tie fixture of `PartnershipRepositoryFindByPidTest` in
  `academic_partners`, which writes its tied rows in ascending uid order and
  therefore cannot fail on any database. The documentation paragraph names it
  as the counter-example; turning it around belongs to that extension and to a
  follow-up, not here.

## Source

Left out of ACE-431 on 2026-09-19 and named in its closing comment. Not from
the project differences analysis; adopted into the same pipeline. Filed as
ACE-701 once the implementation was green, which is what the change is now
named after. Relates to ACE-431 and ACE-491.
