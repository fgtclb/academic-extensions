## Context

Measured on `main` `e75f86b95` by reading, against the installed cores,
14.3.7 in `.Build/vendor` and 13.4.35 in `core-13/vendor`. Nothing was run.

The fields:

- `academic-contact4pages/Configuration/TCA/tx_academiccontacts4pages_domain_model_contact.php`:
  `email_address` (line 137), `phone_number` (150) and `physical_address`
  (163) are `select`/`selectSingle` with the static items `0` ("display all",
  `Contact::DISPLAY_ALL`, also the `default`) and `-1` ("display none",
  `Contact::DISPLAY_NONE`), an `itemsProcFunc` of `AddressRecordItems`, a
  `displayCond` `FIELD:contract:>:0`, `l10n_mode` `exclude` and no
  `foreign_table`. `ext_tables.sql` stores them as signed `int(11)`.
- `contract` (line 97) is a select with `ContractItems` (Extbase repository,
  live uids) and is also the `foreign_field` of the inline relation of the
  contract to its contacts. It stays out of this change.
- `Classes/Backend/FormEngine/AddressRecordItems.php` appends one item per row
  of the contract (`'value' => (int)$addressRecord['uid']`, line 109). Its
  query (lines 143 to 166) has a `DeletedRestriction` only, filters
  `sys_language_uid IN (-1, 0)` and orders by `sorting`, `uid`. It resolves
  the contract from the row, or from the inline parent when the row has none
  (lines 122 to 138), labels with the type and a hidden marker, and dispatches
  `ModifyTcaSelectFieldItemsEvent`.
- The address tables of `academic_persons` are `versioningWS` on `main` since
  ACE-477, have `origUid`, and their TCA labels are the bare value (`email`,
  `phone_number`, `type` plus `label_alt` for the address).
- The frontend compares the selection with `getUid()` of the contract's
  address objects (`Classes/Domain/Model/Contact.php:158-177`), which is the
  live uid in a workspace overlay, and deliberately falls back to nothing.

What core does, identical on both versions unless stated:

- Publishing: a record created in a workspace keeps its uid
  (`DataHandlerHook::publishNewRecord()`), a changed record is written into its
  live uid and the version row is deleted. A plain `select` is never rewritten
  on publish, with or without `foreign_table`. References have to hold live
  uids, which is what a selection of the version row breaks.
- `DataHandler::isReferenceField()` treats a `select` as a relation only with
  `foreign_table`. Only then does a copy register the value for
  `remapListedDBRecords()` (`copyRecord_procBasedOnFieldType()`, then
  `remapListedDBRecords_procDBRefs()`, which rewrites only ids found in the
  copy mapping), and only then does `ReferenceIndex` write rows for it.
- Saving with `foreign_table`: `checkValueForGroupFolderSelect()` runs the
  value through `RelationHandler`. With a single table, `readList()` keeps any
  non-zero integer, `-1` included, as an id of that table, drops `0`, and
  `castReferenceValue()` turns the empty result into the TCA `default`, `0`.
  So `0`, `-1` and a uid are stored as before. `ReferenceIndex` writes a row
  with `ref_uid` `-1` for "display none", exactly as it does for core's own
  `fe_group` items `-1` and `-2`, which use the same pattern
  (`TcaEnrichment::enrichFeGroupField()`).
- Translating: `DataMapProcessor::synchronizeDirectRelations()` copies a
  non-MM relation value verbatim for `l10n_mode` `exclude`, as for a plain
  value. No translated uid is substituted.
- FormEngine: `TcaSelectItems::addData()` adds the `foreign_table` items
  (`AbstractItemProvider::addItemsFromForeignTable()`, with
  `DeletedRestriction`, `WorkspaceRestriction`, `workspaceOL()` keeping the
  live uid, no delete placeholders, and the page permission clause
  `PAGE_SHOW` on the record's page), then runs the `itemsProcFunc`.
  `processSelectFieldValue()` keeps a stored value without `foreign_table`
  only when it is among the items, and with `foreign_table` also when it is a
  relation id, so a selection matching no item stays in the row and
  `addInvalidItemsFromDatabase()` shows it as an invalid value. Without
  `foreign_table` the invalid item is shown but the row value is dropped and
  the next save writes the selection away
  (`docs/architecture/backend-select-items.md`). Both methods are identical on
  v13 and v14.
- `###REC_FIELD_contract###` in `foreign_table_where` takes the processed row
  value, the first element when it is an array (`processForeignTableClause()`).
  `contract` has the `default` `0`, so a new contact gives `'0'`, not `''`.
- v14 only (14.2, `Feature-109366`): `TcaColumnsRemoveEmptyRelations` removes a
  select with `foreign_table` from the form when no item has a positive value,
  unless the field sets `showIfEmpty`. v13 has no such provider and ignores the
  key.
- Extbase maps the three columns to `int` properties (`DataMapper` casts
  `int` on both versions), so the relation declaration does not change the
  frontend model.

Releases: the dedicated selection (ACE-236, `4264cfe4b`) is in no tag, the
latest is 2.3.4. The address tables are versionable on `main` only, since
3.0.0-dev.

## Goals / Non-Goals

**Goals:**

- Every stored selection is a uid that stays valid after publishing.
- Copy remapping and the reference index from core, not from a hook.
- The same form on v13 and v14.

**Non-Goals:**

- Changing what the frontend does with a selection that does not resolve.
- The contract select.

## Decisions

### 1. Declare the relation: `foreign_table` and a scoped `foreign_table_where`

The three columns get `foreign_table` (the address table of `academic_persons`)
and a `foreign_table_where` limited to `{#contract} = ###REC_FIELD_contract###`
and `{#sys_language_uid} IN (-1, 0)`, ordered by `sorting`, then `uid`. That
gives copy remapping, the reference index and the relation aware value handling
of FormEngine in one declaration, with no schema change.

Every column of the clause and of its `ORDER BY` is qualified with the address
table (`{#tx_academicpersons_domain_model_email}.{#sys_language_uid}`), as the
`role` select of the same TCA does. The address tables are not `rootLevel`, so
`AbstractItemProvider::buildForeignTableQueryBuilder()` adds `pages` to the
`FROM` for the `PAGE_SHOW` clause on both cores, and `pages` has
`sys_language_uid`, `sorting` and `uid` as well. An unqualified name is an
ambiguous column on every DBMS, under option A as well, because core still
runs its own item query before the handler.

Rejected: an `MM` relation or `type` `group`, a schema change and a data
migration for a single value. Rejected: remapping in a `DataHandler` hook,
which repeats what core does once the field is declared and still leaves the
reference index empty.

### 2. Who builds the items, decision for the maintainer

**Option A, recommended: the items handler stays the source.** With
`foreign_table` set, core adds its own items first. The handler removes the
items with a positive value that core added and appends its own list as
today, now queried with `WorkspaceRestriction` for the editor's workspace,
overlaid with `workspaceOL()`, without delete placeholders, value the live uid
(a record new in the workspace keeps its own). Labels, the hidden marker, the
inline parent fallback and the event stay as they are, and the editor's page
permissions do not narrow the list, consistent with the contract select, which
does not check them either. The cost is a second small query per select.

**Option B: core builds the items.** The handler only rewrites the labels of
core's items (type prefix, hidden marker) and keeps the inline parent fallback
for a row without a contract. Less code. But core applies `PAGE_SHOW` on the
page of each address record: an editor who maintains page contacts without
access to the folder of the person records gets no address items and sees
every existing selection as invalid, while the contract select still offers
that editor the contract. With option B the
scenario "Editor without access to the folder of the person records" of the
delta spec is removed.

Rejected for both: a `foreign_table_where` that matches nothing (`AND 1 = 0`)
to keep core from adding items. It declares a relation with a clause that
lies about it.

### 3. `showIfEmpty` on all three fields, decision for the maintainer

Set `'showIfEmpty' => true`. Without it v14 removes a select whose contract
has no address of that kind, so "display none" cannot be chosen in advance and
the form differs from v13. v13 ignores the key. Rejected: accepting the v14
removal, which makes the two cores show different forms for one record.

### 4. No upgrade wizard and no schema change

Stored values keep their meaning (Context, "Saving"). A version row uid can
only have been stored on a 3.0.0-dev installation, since no release has
versionable address tables, and a copy pointing at the source's addresses only
on an installation running a development branch, since no release has the
dedicated selection. Such a value is shown as invalid after this change and
the editor picks again. Existing contacts get reference index rows on their
next save, or at once with `referenceindex:update`, which the `Important-`
entry says.

Rejected: a wizard remapping copies through `t3_origuid` of the address
tables. It repairs data no release can have produced. It becomes worth
reconsidering only if 2.4.0 is released with the dedicated selection and
without a backport of this fix.

### 5. The ACE-477 note is corrected in place

`Important-ContactInlineParentIsWorkspaceAware.rst` is a 3.0 entry of an
unreleased version, so its note is rewritten rather than contradicted by a
second entry: no remapping is needed on publish, what was wrong were the item
lists, the copy and the reference index, fixed by this change, which it links.

### 6. Tests compile the form

The item list and the row value are tested by compiling the contact form with
`FormDataCompiler` and `TcaDatabaseRecord`, as
`docs/architecture/backend-select-items.md` asks, not by calling the handler
with a hand-built parameter array. The existing handler tests stay, they cover
labels and the event.

## Risks / Trade-offs

- [`-1` gets a reference index row pointing at no record] → the same as
  core's `fe_group`. The lowlevel missing relations check may list it. The
  test of the reference index asserts what is written for `-1` so the
  behaviour is pinned, not assumed.
- [Option A filters core's items by value] → a project listener of
  `ModifyTcaSelectFieldItemsEvent` still runs last and sees the final list.
- [The `RelationHandler` keeps a dangling uid as a relation id] → intended:
  the selection stays visible as invalid instead of being lost on save.
- [PostgreSQL and the `###REC_FIELD_contract###` marker] → `contract` has a
  default, and the form test of a new contact runs on PostgreSQL as well.
- [A seed or fixture writes these columns through `DataHandler`] → the seed
  stores `0`, `-1` and uids, which keep their values. `seedManifest` is run
  for both cores and must not change.

## Migration Plan

Nothing to run for correctness. `referenceindex:update` once, for the
reference index rows of existing contacts. Rollback is reverting the TCA and
the handler. Stored values are the same either way.

## Open Questions

- Decision 2, which is confirmed before the first code change.
- Versioning uses the copy mapping, found in review by reading both cores,
  not run. The first workspace edit of a contract, or of a profile with its
  contracts, versions the inline children in the same command
  (`copyRecord_processRelation()` calls `versionizeRecord()` for each
  workspace aware child, `copyRecord_raw()` records live uid to version uid
  in `copyMappingArray`). With `foreign_table` set, the contact version's
  selection is registered like a copy and `remapListedDBRecords()` at the
  end of that command rewrites it from the live address uid to the address
  version uid. Publishing then writes the version uid into the live contact
  and deletes that row (`version_swap_processFields()` handles only inline
  relations), which is the defect this change fixes, now produced by core.
  Without `foreign_table` nothing is remapped, which is why it does not
  happen today. `dontRemapTablesOnCopy` with the address tables would stop
  it, and copy remapping with it. The task 2.10 test settles the premise
  before decision 1 is implemented, and a red result goes to the maintainer
  as a change of decision 1, not as a fix inside it.
