## 1. Tests first

- [ ] 1.1 Re-check the premises of `design.md`, *Context*, on `main`: the
      relations, their `foreign_sortby`, and that `writeForeignField()` still
      writes the sort field of the relation or the table. Report any that
      moved before changing code.
- [ ] 1.2 Functional test through the DataHandler (v13 and v14): two partner
      pages with arranged partnerships, one role listing partnerships of both
      in a different order; save the role and assert each page's partnership
      order is unchanged. Run it on the unchanged TCA and record that it fails.
- [ ] 1.3 The same for contracts: two profiles, one organisational unit, save
      the unit, assert each profile's contract order. Record that it fails.
- [ ] 1.4 The same for page contacts: two pages with arranged contacts, one
      contract and one contacts role listing contacts of both; save each and
      assert each page's contact order. Record that both fail.
- [ ] 1.5 Tests asserting that each secondary parent keeps its own arrangement
      after a save and a reload.

## 2. Implementation

- [ ] 2.1 Add `role_sorting` (partnership and page contact),
      `organisational_unit_sorting` (contract) and `contract_sorting` (page
      contact), `int unsigned DEFAULT 0 NOT NULL`, in the three
      `ext_tables.sql` files, after checking
      whether `DefaultTcaSchema` derives a `foreign_sortby` column on each core
      version.
- [ ] 2.2 Declare them as `foreign_sortby` of the four secondary relations.
      Verify groups 1.2 to 1.5 pass.
- [ ] 2.3 Mutation: remove each new `foreign_sortby` again and watch the
      matching test of 1.2 to 1.4 go red; restore.
- [ ] 2.4 Re-check that no other table of the extensions has a second inline
      parent writing a shared sort column; record the search.

## 3. Upgrade wizards

- [ ] 3.1 One wizard per extension numbering the children of every secondary
      parent in (`sorting`, `uid`) order into its new column, with
      functional tests: seeded order, idempotence, nothing to do afterwards,
      several parents.
- [ ] 3.2 Run them on PostgreSQL too; they write.

## 4. Definition of done

- [ ] 4.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v13 and v14, each after its own `composerUpdate`; the new tests on
      PostgreSQL as well.
- [ ] 4.2 `Documentation/Changelog/3.0/` entries in the three extensions (what an
      editor saw before, what the wizard does); `docs/architecture/database-queries.md`
      rule 3 gains a sentence on children with two inline parents.
- [ ] 4.3 Commit messages in TYPO3 Core format with the issue filed after
      implementation; the change archived as the last commit.
