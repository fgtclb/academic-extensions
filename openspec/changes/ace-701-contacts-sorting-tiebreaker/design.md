## Context

Measured on `2` at `a8c21f6e1`, the base of this change:

- `findByPid()` has no uid pre-query - that one is ACE-484 and lives on `main`
  only. The Extbase query orders by `sorting` alone (`:42`), matches
  `equals('page', $pid)` and lifts `respectSysLanguage` and
  `respectStoragePage`.
- The contact table has `ctrl.sortby = sorting` and `versioningWS = true`.
- `PageContactsProvider` is the only caller, and both the contacts content
  element (`ContactsController`) and the page contacts data processor
  (`ContactsProcessor`) go through it.
- `ace-699-inline-sort-column-per-parent` has landed here as well: the
  contract and the contacts role relations declare `contract_sorting` and
  `role_sorting`, so saving one of them no longer renumbers `sorting`.
- The test class and its fixtures are identical to `main` except for the
  language tests, which pin the language-blind behaviour of this branch as a
  defect - every translation is returned next to its default record - and the
  core versions the tie docblock names.

## Decisions

### Append `uid`, keep `sorting` first

The arrangement of the page stays the primary order; `uid` only settles equal
values, as for every other manually sortable table since ACE-491.

### Where contacts share a value

The page form renumbers a page's contacts on every save, so a page an editor
saved has distinct values. Ties come from records that did not pass through
that form - the page relation declares `foreign_sortby` `sorting`, so
`RelationHandler::writeForeignField()` renumbers a whole page 1..n on every
save that submits its contacts, folders or not. What is left after ACE-699:

- contacts of one page kept in different storage folders.
  `DataHandler::getSortNumber()` numbers a new record within its pid, so the
  first contact of each folder is given the same default interval, and
  `findByPid()` lifts `respectStoragePage`;
- contacts copied along with a contract or a contacts role.
  `copyRecord_raw()` writes the stored row unchanged, unlike `copyRecord()`,
  which recalculates the sort number;
- imports, which write whatever they carry;
- data written before ACE-699.

The test builds the tie directly in its fixture and spreads it over two
storage folders, so the fixture matches the first of them.

### The tiebreaker is the fetched row's uid

This branch lifts `respectSysLanguage` without a pre-query, so a translation is
selected next to its default record and the query carries no language
constraint at all. It therefore fetches the same rows in every language and
orders them by the raw `uid` column, which resolves a tie identically in every
language context. That column is not what the overlaid object reports - a
default row overlaid by its translation carries the translation's uid as
`_localizedUid`. The language handling itself stays the defect the test class
already pins.

## Risks / Trade-offs

- [The test cannot fail on SQLite] → uid is the rowid there. Measured: dropping
  the tiebreaker keeps the test green on SQLite, MySQL 8.0 and MariaDB 10.6,
  and turns it red on PostgreSQL, which hands the tied rows back in the order
  they were written, on TYPO3 v12 and v13 alike. The fixture writes them in
  descending uid order for that reason, and the docblock records the result so
  a green default run is not read as coverage.

## Backport

This is the backport. The `main` change is
`ace-701-contacts-sorting-tiebreaker` there; no further branch is a target.
