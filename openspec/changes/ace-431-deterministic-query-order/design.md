## Context

The change on `main` is two commits, ACE-482 (`4089c1c57`) and ACE-491
(`b371ad759`), plus two later pieces of the same rule: the selection query
ordering of ACE-681 (`7bc2fb85f`) and the address and e-mail tiebreakers
inside the ACE-505 feature (`3c9b3ec88`).

Measured against this branch with each commit's parent on `main`:

| Files                                                | State                                                                          |
|------------------------------------------------------|--------------------------------------------------------------------------------|
| every repository, collection and test the two touch  | **byte-identical**, except `ProfileRepository`                                 |
| `ProfileRepository`                                  | differs by a constant, a method and its call site; only the constant conflicts |
| `docs/architecture/database-queries.md`, `AGENTS.md` | differ throughout — v12 notes, the ACE-681 section, a different rule count     |
| changelog entries                                    | new; `Documentation/Changelog/2.4/` exists in all six extensions and globs     |

So the production code ports unchanged. Nothing in it is version specific:
`setOrderings()`, `$defaultOrderings` and `orderBy()` behave the same on v12
and v13, and no PHP 8.2 syntax arrives with it.

What does **not** hold here is the history both commit messages and their
texts tell. ACE-482 was triggered by ACE-477, which made the person tables
workspace aware and gave PostgreSQL an index that reversed the unordered
results; ACE-477 is `main`-only. ACE-491 was triggered by a CI flake on
`main`. Every text that tells that story is rewritten to say it happened on
the 3.x line and why the same queries are unordered here all the same.

## Goals / Non-Goals

**Goals:**

- The branch follows rule 3 of `docs/architecture/database-queries.md` to the
  extent `main` does, with the tests `main` pins it with.
- Every text that deferred the ordering to ACE-431 is corrected where it is
  live, and left alone where it is an archived record.

**Non-Goals:**

- Going beyond `main` — see the proposal's non-goals.

## Decisions

### Four commits, not one

The two `main` commits keep their subject and issue, as the backporting page
asks; the ACE-681 selection ordering and the ACE-505 tiebreakers get commits of
their own, because neither exists on `main` as a commit that could be named.
Folding them into the ACE-491 backport would make that commit claim something
`b371ad759` never did.

### The selection query is ordered here too

The ACE-681 backport left it unordered on purpose: one ordered branch in a
repository with none would have read as an accident. With the fallback
ordering in place that reason is gone, and leaving it out would recreate the
accident the other way round.

### The page module category summary keeps its assertions

The ACE-687 tests here assert presence and counts, as on `main`, and stay
valid with the ordering. They are not tightened to an order: `main` does not
pin one either, and the category order is the subject of
`ace-tbd-category-type-priority-order`.

### Specs follow `main`

`main` specifies the program list tie only. The category, partnership and
media orders have no capability on either branch; inventing one here would
make the branches' specs disagree about behaviour that is identical.

## Risks / Trade-offs

- [Visible reorder] Categories and partnerships an editor reordered in the
  backend now render in that order. → Stated in both changelog entries; it is
  the order the editor expressed.
- [Several tests cannot fail here] A uid-only ordering cannot fail on SQLite,
  and on this branch the address and e-mail tiebreakers cannot fail on
  PostgreSQL either — those tables have no workspace index. → The tests pin the
  order and their docblocks say which databases can break them; the sorting
  tests of categories and partnerships are proven red on SQLite, the program
  tie on PostgreSQL.
