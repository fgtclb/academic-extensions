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
is promised now, so they compare the result as it comes. On this branch none
of the four tables is workspace aware, so dropping the ordering fails on no
database of the suite: SQLite returns uid order because uid is its rowid, and
PostgreSQL has no index to prefer over a scan in insertion order. On `main`,
where the tables are workspace aware, the same tests are red on PostgreSQL
without it. What the tests do prove here is the choice of `uid` over
`sorting`: the three contact fixtures run `sorting` against uid order, so a
`sorting` ordering is red on SQLite. The profile information fixture does
not, which is recorded rather than changed.

## Risks / Trade-offs

- [No database of the suite can prove the ordering on this branch] → The tests
  pin the contract and say so; `main` proves it on PostgreSQL.
