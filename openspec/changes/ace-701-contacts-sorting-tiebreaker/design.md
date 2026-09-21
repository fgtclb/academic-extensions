## Context

Measured on `main` at `f7c4c5bf1` and `origin/2` at `6af098433`, and re-measured
at `main` `b4d797398` before implementing - all four still hold:

- `main`: `ContactRepository::findByPid()` resolves the contact uids per
  language with a raw pre-query ordered by `uid` (ACE-484), then runs the
  Extbase query with `setOrderings(['sorting' => ASC])` (`:73`).
- `origin/2`: the method has no pre-query; the Extbase query orders by
  `sorting` alone (`:42`). The file differs by 113 lines, all of them ACE-484.
- The contact table has `ctrl.sortby = sorting` and `versioningWS = true` on
  both branches.
- Callers: `PageContactsProvider` is the only one, and both the contacts
  content element (`ContactsController`) and the page contacts data processor
  (`ContactsProcessor`) go through it.

## Decisions

### Append `uid`, keep `sorting` first

The editor's arrangement stays the primary order; `uid` only settles equal
values, as for every other manually sortable table since ACE-491.

### Where contacts share a value

The page form renumbers a page's contacts on every save, so a page an editor
saved has distinct values. Ties come from records that did not pass through
that form. `ace-699-inline-sort-column-per-parent` has landed since this
change was written and removed the largest source - saving a contract or a
contacts role no longer renumbers `sorting` across pages - so what remains is:

- contacts of one page kept in different storage folders.
  `DataHandler::getSortNumber()` numbers a new record within its pid, so the
  first contact of each folder is given the same default interval, and
  `findByPid()` lifts `respectStoragePage`;
- contacts copied along with a contract or a contacts role.
  `copyRecord_raw()` writes the stored row unchanged, unlike `copyRecord()`,
  which recalculates the sort number;
- imports, which write whatever they carry;
- data written before ACE-699.

The test builds the tie directly in its fixture rather than depending on one
of those paths, and spreads it over two storage folders so the fixture matches
the first of them.

## Risks / Trade-offs

- [The test cannot fail on SQLite] → uid is the rowid there. PostgreSQL
  promises no order without an `ORDER BY` at all, so it is the database the
  implementation has to measure on; the result is recorded either way.

Measured: dropping the tiebreaker keeps the test green on SQLite, MySQL 8.0
and MariaDB 10.6, and turns it red on PostgreSQL 10, which hands the tied rows
back in the order they were written, on TYPO3 v13 and v14 alike. That is a
sequential scan and a sort, not the workspace index this change was first
expected to blame. The fixture writes the tied contacts in descending uid
order for that reason, and the test docblock records the result so a green
default run is not read as coverage.

## Backport

Recommended. Branch `2` orders by `sorting` alone as well; its query differs
by ACE-484, so the one line goes into its own query shape.
