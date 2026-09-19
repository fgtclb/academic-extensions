## 1. Analysis

- [x] 1.1 Diff every file `4089c1c57` and `b371ad759` touch against this
      branch, each against the commit's parent on `main`: all identical except
      `ProfileRepository`, `AGENTS.md` and `docs/architecture/database-queries.md`
      — recorded in design.md.
- [x] 1.2 Sweep every ordering statement of `Classes/` against `main`: the
      remaining differences are the ACE-681 selection query, the ACE-505
      address and e-mail tiebreakers, and `main`-only features — recorded in
      design.md and the proposal's non-goals.

## 2. Backport ACE-482 and ACE-491

- [x] 2.1 Cherry-pick `4089c1c57`; resolve the `ProfileRepository` constant
      conflict by keeping both constants; rewrite every text that claims the
      ACE-477 index reordered results on this branch.
- [x] 2.2 Correct `ProfileRepositoryFindAllTest`, whose docblock says
      `findAll()` carries no `ORDER BY`, and compare its uids in result order.
- [x] 2.3 Cherry-pick `b371ad759`; move every changelog entry to
      `Documentation/Changelog/2.4/`; merge rule 3 into this branch's
      `database-queries.md` next to its v12 notes and its ACE-681 section.
- [x] 2.4 Mutation: drop the `sorting` ordering of `CategoryRepository` (four
      of four ordering tests red) and of `PartnershipRepository::findByPid()`
      (one red) on SQLite, TYPO3 v12; green again once restored.

## 3. Order the selection query (ACE-681)

- [x] 3.1 Add the `uid` fallback to the selection branch, the repository test
      from `main`, and replace the paragraphs of the ACE-681 changelog entry and
      of `database-queries.md` that deferred it.
- [x] 3.2 Mutation: drop the new ordering; the selection test stays green on
      SQLite and PostgreSQL alike, since both return `uid IN (3, 1)` in uid
      order without it. The test pins the contract, as its docblock says; it
      cannot prove the ordering on this branch.

## 4. Address and e-mail tiebreakers (ACE-505 on `main`)

- [x] 4.1 Append `uid` to both, take over the three `equalSorting` tests and
      write the changelog entry.
- [x] 4.2 Mutation: drop the tiebreaker; the tests stay green on SQLite
      **and** on PostgreSQL. `main`'s docblocks say they are red on PostgreSQL,
      which holds there because the tables are workspace aware and carry a
      `t3ver_oid` index; here they are not. The three docblocks are rewritten
      to say so.
- [x] 4.3 Mutation for the one delta scenario: drop the `uid` tiebreaker of
      the program list; `ProgramRepositoryOrderingTest` is red on PostgreSQL
      (the programs are pages, which are workspace aware) and green on SQLite.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v13 and v12, each after its own `composerUpdate`.
- [x] 5.2 The functional tests of the six touched extensions green on
      PostgreSQL for both core versions.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and `AGENTS.md` carry rule 3; one `Documentation/Changelog/2.4/`
      entry per affected behaviour.
- [x] 5.5 Commit messages in TYPO3 Core format with verified issue
      references and no attribution.
