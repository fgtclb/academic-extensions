## 1. Tests first

- [x] 1.1 Re-check the premises of `design.md`, *Context*, on `main`: the
      relations, their `foreign_sortby`, and that `writeForeignField()` still
      writes the sort field of the relation or the table. Report any that
      moved before changing code.
- [x] 1.2 Functional test through the DataHandler (v13 and v14): two partner
      pages with arranged partnerships, one role listing partnerships of both
      in a different order; save the role and assert each page's partnership
      order is unchanged. Run it on the unchanged TCA and record that it fails.
- [x] 1.3 The same for contracts: two profiles, one organisational unit, save
      the unit, assert each profile's contract order. Record that it fails.
- [x] 1.4 The same for page contacts: two pages with arranged contacts, one
      contract and one contacts role listing contacts of both; save each and
      assert each page's contact order. Record that both fail.
- [x] 1.5 Tests asserting that each secondary parent keeps its own arrangement
      after a save and a reload.

## 2. Implementation

- [x] 2.1 Add `role_sorting` (partnership and page contact),
      `organisational_unit_sorting` (contract) and `contract_sorting` (page
      contact), `int unsigned DEFAULT 0 NOT NULL`, in the three
      `ext_tables.sql` files, after checking
      whether `DefaultTcaSchema` derives a `foreign_sortby` column on each core
      version.
- [x] 2.2 Declare them as `foreign_sortby` of the four secondary relations.
      Verify groups 1.2 to 1.5 pass.
- [x] 2.3 Mutation: remove each new `foreign_sortby` again and watch the
      matching test of 1.2 to 1.4 go red; restore.
- [x] 2.4 Re-check that no other table of the extensions has a second inline
      parent writing a shared sort column; record the search.

## 3. Upgrade wizards

- [x] 3.1 One wizard per extension numbering the children of every secondary
      parent in (`sorting`, `uid`) order into its new column, with
      functional tests: seeded order, idempotence, nothing to do afterwards,
      several parents.
- [x] 3.2 Run them on PostgreSQL too; they write.
- [x] 3.3 Append rather than renumber, so a second run does not reset an
      arrangement made in a secondary form, and group the writes by rank
      instead of issuing one statement per record.

## 3a. The write path

- [x] 3a.1 A DataHandler hook per extension appending a child to the list of a
      secondary parent as it joins one: `processDatamap_postProcessFieldArray()`
      for the data map, `processCmdmap_afterFinish()` over
      `copyMappingArray_merged` for copies and localizations, cascaded inline
      children included.
- [x] 3a.2 Tests for each of them - created with the parent selected in the
      child's own form, two in a row not tying, changing the parent, clearing
      it, copying, localizing - shown to fail with the hook unregistered.
- [x] 3a.3 Workspace coverage: editing a record in a workspace keeps its
      position, a copy made in a workspace is still appended.
- [x] 3a.4 A test per extension for the cascaded copy - the owning parent is
      copied - shown to fail against the first shape of the cmdmap half.
- [x] 3a.5 The Extbase write path of `academic_persons`: the profile editing
      frontend writes contracts through the repository, which no DataHandler
      hook sees. A listener on `EntityAddedToPersistenceEvent` and
      `EntityUpdatedInPersistenceEvent` applies the same rule, with tests shown
      to fail without it. Re-checked that no Extbase writer exists for
      partnerships or page contacts.

## 4. Definition of done

- [x] 4.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green on
      TYPO3 v13 and v14, each after its own `composerUpdate`; the new tests on
      PostgreSQL as well.
- [x] 4.2 `Documentation/Changelog/3.0/` entries in the three extensions (what an
      editor saw before, what the wizard does); `docs/architecture/database-queries.md`
      rule 3 gains a sentence on children with two inline parents.
- [ ] 4.3 Commit messages in TYPO3 Core format with the issue filed after
      implementation; the change archived as the last commit.

## Notes recorded while implementing

- **Premises re-measured on `main` at `f04f74dd8`** and all confirmed: the four
  relations and their `foreign_sortby`, `RelationHandler::writeForeignField()`
  writing `$updateValues[$sortby] = ++$c` (v13.4.35 `RelationHandler.php:1034`ff,
  v14.3.7 `:961`ff), and `DefaultTcaSchema` deriving only the `ctrl.sortby`
  column.
- **2.4, the sweep**: `grep -rn "'type' => 'inline'"` over
  `packages/fgtclb/*/Configuration/TCA/` finds thirteen inline relations. Nine
  are the only parent of their child (`contract` → address / email /
  phone_number, `profile` → profile_information, `tt_content` → semester,
  `semester` → module, and the three owning relations). The remaining four are
  the ones this change touches, on exactly the three tables the proposal names.
- **2.3, the mutation**: with the four new `foreign_sortby` entries removed
  again, all ten tests of groups 1.2 to 1.5 fail; restored from a copy under
  `.agent/tmp/`, not with `git checkout --`.
- **The review of the first implementation found the write path missing.** The
  new columns are written by `writeForeignField()` alone, which runs only when
  the secondary parent is saved - and the parent is normally assigned from the
  child's own form instead. Measured: a partnership created on a partner page
  with a role selected is stored with `sorting = 3` and `role_sorting = 0`.
  Group 3a is the answer; `design.md` records the mechanism and the rejected
  alternative.
- **The second review found the two copy paths inverted.** A `copy` or
  `localize` command on the child runs `copyRecord()`, which submits a nested
  data map - so the data map half ranks it, and the four direct-copy tests never
  exercised the cmdmap half. Copying the *owning* parent takes
  `copyRecord_raw()` -> `insertDB()` with the full row, which carries the new
  column over verbatim: the copy tied with the record it came from, in all three
  extensions (`{"2":1,"6":1,"4":2,"1":3,"5":3,"3":4}` for a copied partner page).
  The cmdmap half now recognises an inherited position through
  `copyMappingArray_merged`'s `source uid => new uid`, and a test per extension
  copies the owning parent and asserts that no two children of one secondary
  parent share a position.
- **The third review found two more, both confirmed by reproduction.** A
  workspace version is created through the same `copyRecord_raw()` path as a
  copy and registered in `copyMappingArray_merged` the same way, so editing a
  contract in a workspace moved its version to the end of the unit's list
  (position 3 became 5) - the cmdmap half now skips a record whose `t3ver_oid`
  is the uid it came from. And a record saved between the database analyzer and
  the wizard run took position 1 in a list that was still entirely unseeded,
  after which the wizard appended the rest behind it and inverted the order; all
  three mechanisms now leave such a list to the wizard. Both have a test, in the
  extensions whose table is workspace aware on this branch.
- **The appended order no longer depends on `copyMappingArray_merged`.** Copies
  of one list are collected and appended together, ordered by the position their
  originals hold in it - which also reproduces the dense 1..n the core assigns
  when the secondary parent itself is copied.
- **An SQLite trap worth knowing**: SQLite resolves a double-quoted identifier
  that matches no column as a string literal, so `ORDER BY "role_sorting"`
  against a table without that column silently sorts by a constant instead of
  raising. That is why the wizards ask the schema manager for the column rather
  than catching a database exception: on SQLite there is none to catch.
