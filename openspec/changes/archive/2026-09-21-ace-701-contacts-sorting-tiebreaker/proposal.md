## Why

`ContactRepository::findByPid()` of `academic_contacts4pages` orders the
contacts of a page by `sorting` and nothing else. Contacts that share a
`sorting` value are returned in whatever order the database yields, and
PostgreSQL promises no order without an `ORDER BY`. Rule 3 of
`docs/architecture/database-queries.md` orders a manually sortable table by
`sorting` with `uid` breaking ties. `ContactRepository::findByPid()` is the one
list query left that does not; ACE-491 did not cover it and ACE-431 named it as
left out.

This is the backport of the `main` change. The defect is the same here, and
the query the fix touches differs only in the language handling `main` gained
with ACE-484 - branch `2` has no such pre-query, so the tiebreaker goes into
the orderings of the plain Extbase query.

## What Changes

- `academic_contacts4pages` (`packages/fgtclb/academic-contact4pages`):
  `ContactRepository::findByPid()` appends `uid` ascending to its `sorting`
  ordering. Both the contacts content element and the page contacts data
  processor use it.
- A functional test with contacts sharing a `sorting` value, spread over two
  storage folders, and an `Important-` changelog entry.
- The tie recipe in the "Testing an ordering" section of
  `docs/architecture/database-queries.md`, which the existing reference
  fixture does not follow.
- TYPO3 v12 and v13 alike.

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
  which was `ace-699-inline-sort-column-per-parent` and has landed on this
  branch as well. That change kept the arrangement of a page intact by giving
  the contract and the contacts role relations sort columns of their own; this
  one settles the ties that remain.
- The language handling of the query, which is pinned as a defect in
  `ContactRepositoryFindByPidTest` on this branch and is not this change's to
  answer.
- The tie fixture of `PartnershipRepositoryFindByPidTest` in
  `academic_partners`, which writes its tied rows in ascending uid order and
  therefore cannot fail on any database. The documentation paragraph names it
  as the counter-example; turning it around belongs to that extension and to a
  follow-up, not here.

## Source

Backport of the `main` change `ace-701-contacts-sorting-tiebreaker`, archived
there as `openspec/changes/archive/2026-09-21-ace-701-contacts-sorting-tiebreaker/`.
Filed as ACE-701 for both branches. Relates to ACE-431 and ACE-491.
