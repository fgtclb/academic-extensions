## Context

Measured on `main` at `f7c4c5bf1` and `origin/2` at `6af098433`:

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
that form: contacts created from a contract or a contacts role (until
`ace-699-inline-sort-column-per-parent` lands they even renumber `sorting`
across pages), copies and imports. The test builds the tie directly in its
fixture rather than depending on one of those paths.

## Risks / Trade-offs

- [The test cannot fail on SQLite] → uid is the rowid there. The table is
  workspace aware on both branches, so PostgreSQL is expected to reorder ties
  without the tiebreaker; the implementation proves it there and records the
  result either way.

## Backport

Recommended. Branch `2` orders by `sorting` alone as well; its query differs
by ACE-484, so the one line goes into its own query shape.
