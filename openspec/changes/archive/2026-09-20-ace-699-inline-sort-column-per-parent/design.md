## Context

Measured on `main` at `f7c4c5bf1` and on `origin/2` at `6af098433`:

- `tx_academicpartners_domain_model_partnership` has `ctrl.sortby = sorting`.
  The partner page relation (`Configuration/TCA/Overrides/pages.php`) declares
  `foreign_sortby = sorting`. The role relation
  (`tx_academicpartners_domain_model_role.php`, field `partnerships`, shown in
  the role form) declares none.
- `tx_academicpersons_domain_model_contract` has `ctrl.sortby = sorting`. The
  profile relation and the organisational unit relation (field `contracts`,
  shown in the unit form) both declare `foreign_sortby = sorting`.
- `RelationHandler::writeForeignField()` (v14, `:961`ff; the same logic on v12
  and v13) writes `$updateValues[$sortby] = ++$c` for every child of the saved
  parent, with `$sortby` = `foreign_sortby` or the table's sort field.
- `tx_academiccontacts4pages_domain_model_contact` has `ctrl.sortby = sorting`.
  The page relation (`Overrides/pages.php`) and the contract relation
  (`Overrides/tx_academicpersons_domain_model_contract.php`, a tab of the
  contract form, drag and drop enabled) declare `foreign_sortby = sorting`; the
  contacts role relation (`contacts`, in the role form) declares none.
- Nothing reads `Role::$partnerships` or `OrganisationalUnit::$contracts` in a
  template; the frontend renders `profile.contracts`, the partnerships of a
  page and the contacts of a page. Whether a contract's or a contacts role's
  contacts are rendered anywhere is re-checked by the implementation.
- A sweep of every `type => inline` relation in the extensions' TCA finds
  exactly these three tables with more than one parent.
- The relevant TCA is identical on both branches apart from labels.

## Decisions

### A sort column per second relation

Each second relation gets its own `foreign_sortby`, the pattern core uses for
`sys_file_reference` (`sorting_foreign` per parent field). The owning relation
keeps `sorting`, so nothing that renders today changes. Rejected:

- **Read-only second relations**: fixes the defect by removing a capability
  editors have today (creating and rearranging from the role or unit form).
- **Dropping the second relations from the forms**: the same, more so.
- **Ordering the frontend by something other than `sorting`**: undoes the
  editor's arrangement, which ACE-491 made visible deliberately.

### Fill the column on the write path, not only from the parent

`writeForeignField()` numbers the children of the record being saved, so it
fills a secondary sort column only when that secondary parent is saved. The
usual editing path is the other one: `partnership.role`,
`contract.organisational_unit` and `contact.contract`/`contact.role` are
`select` fields of the **child's** form, reached through the inline list of the
owning parent. Measured on `main`: a partnership created on a partner page with
a role selected is stored with `sorting = 3` and `role_sorting = 0`. Without a
write-path fix the secondary lists would fill up with rows at `0` that sort
above everything the wizard seeded, tied with each other.

A DataHandler hook per extension therefore appends a child to the list of a
secondary parent as it joins one, in the two places a child is created or moved:

- `processDatamap_postProcessFieldArray()` for every data map write, which is
  the editor path. It adds the rank to the field array, so it costs no
  statement of its own, and it leaves a child that already has a rank in the
  parent it is being saved with alone - that is what makes an arrangement made
  in the secondary form survive.
- `processCmdmap_afterFinish()` for the copies no data map reaches. A `copy` or
  `localize` command on the child itself runs `copyRecord()`, which submits a
  nested data map, so the half above ranks it. Copying the **owning** parent
  takes the other branch: `copyRecord_raw()` -> `insertDB()` with the full
  database row, which carries a column without a TCA `columns` entry over
  verbatim - the copy would tie with the record it was copied from, which is the
  arbitrary order the column exists to remove.
  `DataHandler::$copyMappingArray_merged` holds every record the run created as
  `source uid => new uid`, so an inherited position is recognisable as such and
  a position the data map half already assigned is not rewritten. In a
  workspace the same relation takes the `copyRecord()` branch, so the child is
  ranked by the data map half there; both are covered by a test.

Rejected: a `passthrough` TCA column, which would make `copyRecord()` carry the
source's rank over. It covers the copy path only, and it would put a copy at the
same rank as its source rather than behind it.

**A DataHandler hook does not see an Extbase write**, and one write path is
Extbase: the profile editing frontend of `academic_persons_edit` creates and
updates contracts through `ContractRepository`, with the organisational unit
taken from its own form. Measured: a contract added that way keeps
`organisational_unit_sorting = 0`, and one whose unit is changed keeps the rank
of the unit it left. `academic_persons` therefore also listens to
`EntityAddedToPersistenceEvent` and `EntityUpdatedInPersistenceEvent` and
applies the same rule. The events rather than a decorated repository, because
they are what every Extbase write passes through - `PersistenceManager::update()`
called without a repository included, which is how the editor reorders a
profile's records. Whether the unit changed is read off the entity's clean
property, which is still the previous value when the event fires:
`_memorizeCleanState()` runs after `updateObject()` returns
(`Extbase\Persistence\Generic\Backend::persistObject()`).

**A workspace version is not a copy**, although the core creates it the same way.
Editing a record in a workspace runs a nested DataHandler with a `version`
command, which takes the `copyRecord_raw()` branch and registers the pair in
`copyMappingArray_merged` exactly as a copy does. Its position is the one it
versions, so the cmdmap half skips a created record whose `t3ver_oid` is the uid
it was created from. A genuine copy made inside a workspace carries `t3ver_oid`
`0` and is still appended.

**Nothing is numbered while the upgrade wizard is still pending.** Between the
database analyzer and the wizard run the column exists and every row sits at
`0`. Numbering one of them then - because it happened to be saved first - would
make the wizard read it as an arrangement and append the rest of the list behind
it, reversing the order the wizard exists to preserve. So all three mechanisms
leave a record alone while every other row of its list sits at `0`; the only
record that may take position 1 is one with no siblings, which is a list the
wizard has nothing to say about.

The other two tables need no such listener. Their repositories have no write
methods and nothing in the repository adds or updates a partnership or a page
contact outside the backend; addresses, e-mail addresses and phone numbers,
which the editor does write, have a single inline parent.

### Seed the new columns

Without seeding, every existing child has `0` in the new column and the role
and unit forms would show ties in whatever order the database returns. A wizard
numbers the children of each parent in their current (`sorting`, `uid`) order,
which is what those forms show today. It runs once and reports nothing to do
afterwards.

It **appends** rather than renumbers: it gives a rank to the rows that have
none, behind the rows that have one. On an installation that has just updated
nothing has a rank, so that is the same 1..n numbering - but a second run, which
`upgrade:run --force` is one flag away from, then leaves an arrangement an
editor has since made in the secondary form untouched instead of silently
resetting it to the owning parent's order.

The writes are grouped by rank rather than issued per record: the ranks of a
table run 1..n per parent, so their number is the size of the largest list while
the number of records is the size of the table.

### One wizard per extension, not a shared seeder

The three wizards run the same query twice over different columns, and all three
extensions depend on `academic-base`, so the routine could live there once.
Rejected: the change would then touch a fourth extension and put an `@internal`
service into the shared package that nothing but three upgrade wizards ever
calls, for some fifty lines of query code each. The wizards are self-contained
and are deleted together with the columns they seed.

### Number every row of a parent, not only the live ones

The wizard groups by the parent uid a row carries and numbers **all** of them -
deleted rows and workspace rows included - rather than restricting to live,
undeleted records. That is the same grouping `RelationHandler` uses when it
writes the column, so the relative order of any set of rows it later lists is
the seeded one, in the live workspace and in a draft alike. Restricting the run
would leave the excluded rows at `0`, which sorts them above every numbered
sibling the moment they are restored or published.

### Name the columns after the parent

`role_sorting`, `organisational_unit_sorting` and `contract_sorting` say which
relation they belong to; `role_sorting` exists on two tables, for two
different role tables. They are declared in `ext_tables.sql`, because a `foreign_sortby`
column is not derived from TCA the way `ctrl.sortby` is: `DefaultTcaSchema`
derives the `ctrl.sortby` column and nothing else, on v13.4.35
(`DefaultTcaSchema.php:218`) as on v14.3.7 (`:250`, through
`TcaSchemaCapability::SortByField`).

## Risks / Trade-offs

- [A project renders a role's partnerships or a unit's contracts itself] → Its
  order follows the new column, which the wizard seeds from today's order, so
  it does not change on update.
- [Upgrade wizards are among the v15 blockers] → A wizard is still the
  documented way on v13 and v14; it uses the same API as the twelve existing
  ones.

## Backport

Recommended. The TCA and the relation handling are the same on branch `2` for
all three tables, and partnership lists there render in `sorting` order since
#670.
