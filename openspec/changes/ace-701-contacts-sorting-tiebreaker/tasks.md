## 1. Tests first

- [x] 1.1 Re-check the premises of `design.md`, *Context*, on `main`.
- [x] 1.2 Functional test of `ContactRepository::findByPid()` with a fixture
      whose contacts share a `sorting` value in an order that contradicts uid
      order, asserting uid order within the tie; one more contact with a
      lower `sorting` to prove `sorting` still wins.
- [x] 1.3 Run it without the tiebreaker on SQLite and on PostgreSQL, for v13
      and v14, and record the result per database in the test docblock —
      SQLite cannot fail it.

## 2. Implementation

- [x] 2.1 Append `'uid' => QueryInterface::ORDER_ASCENDING` to the orderings
      of `findByPid()`, with a comment naming rule 3. Verify 1.2 passes.
- [x] 2.2 Mutation: drop it, watch 1.2 go red where 1.3 said it would; restore.

## 3. Definition of done

- [x] 3.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v13 and v14, each after its own `composerUpdate`; the new test on
      PostgreSQL as well.
- [x] 3.2 `Documentation/Changelog/3.0/Important-*.rst` in
      `academic_contacts4pages`, and the tie recipe added to the "Testing an
      ordering" section of `docs/architecture/database-queries.md` - rule 3
      covers the case, but said nothing about how to make a tie assertion fail
      anywhere.
- [ ] 3.3 Commit message in TYPO3 Core format with the issue filed after
      implementation; the change archived as the last commit.
