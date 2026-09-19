## Context

`ContractRepository::findAll()` has ordered by `uid` since ACE-491, for the
reason recorded in its comment: the table's `sorting` is scoped per parent
profile. The four remaining methods are in the same position. Address, e-mail
and phone number records are inline children of a contract, and profile
information records are inline children of a profile.

## Decisions

### `uid`, not `sorting` with a `uid` tiebreaker

Rule 3 orders a manually sortable table by `sorting`, except when `sorting`
is scoped per parent, because a global `ORDER BY sorting` interleaves records
of different parents by values that were only ever compared within one parent.
All four tables are that exception. The per-parent methods of the same
repositories keep ordering by `sorting` and `uid`.

### Pin the order in the existing tests

The three contact repository tests and the profile information `findAll()`
test compared sorted uid sets because the order was not promised. The order
is promised now, so they compare the result as it comes. Dropping the ordering
turns all four red on PostgreSQL, where the workspace aware tables give the
planner another path, and none on SQLite, where uid is the rowid. The three
contact fixtures run `sorting` against uid order, so ordering them by
`sorting` instead is red on SQLite too; the profile information fixture does
not, which is recorded rather than changed.

## Risks / Trade-offs

- [SQLite cannot prove the ordering] → PostgreSQL does, on TYPO3 v13 and v14,
  and CI runs it.
