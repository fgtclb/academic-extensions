## Context

See `proposal.md`. Verified on `main`:

- `import_identifier varchar(170)` exists on profile, contract, e-mail, phone
  number, address, location, organisational unit and function type. All but
  the address table have `key idx_import_identifier`.
- Its TCA is `type => passthrough` on all eight tables, so no backend form
  shows it. That answers the open point of the analysis.
- Two classes write it. `Profile/ProfileFactory.php` writes profile and
  contract, `fe_users:<uid>`. `Profile/FrontendUserProfileMapper.php` (since
  ACE-720) writes the contact records: the first address and e-mail
  `fe_users:<uid>`, further ones `<first column>:fe_users:<uid>`, phone
  numbers `<column>:fe_users:<uid>` (ACE-365). Location, organisational unit
  and function type are never written. `Profile/ManagedFieldResolver.php`
  (ACE-758) reads it to lock managed fields.
- The label every column names,
  `generic.columns.import_identifier.label`, does not exist in
  `locallang_tca.xlf`.
- `skip_sync` on the profile already has
  `displayCond => FIELD:import_identifier:REQ:true`.
- The TCA files switch `ctrl.searchFields` on for v13 only (Breaking #106972
  removed it in v14, which makes input fields searchable by default). The
  contract sets none, so v13 searches all its searchable fields. The
  organisational unit names `function_name`, a column it does not have, so on
  v13 it was not searchable at all. That is fixed in a commit of its own
  before this change (ACE-794).
- Both core versions decide through `SearchableSchemaFieldsCollector`, which
  the list module and the backend search share. `passthrough` is never
  searchable. Contract, address, e-mail and phone number are `hideTable`, which
  both leave out on both core versions. Page TSconfig
  `mod.web_list.table.<table>.hideTable = 0` shows them in the list module.
  Removing `hideTable` is not part of this change.
- The search of the list module escapes `_` for `LIKE` without an `ESCAPE`
  clause. SQLite has no default escape character, so there a term with `_`
  finds nothing, on both core versions. A core limitation, documented.
- DataHandler does not read TCA `readOnly` on either version, so a read-only
  input column is still written by DataHandler and by Extbase.

## Goals / Non-Goals

**Goals:**

- One lookup that import and cleanup code can share.

**Non-Goals:**

- Uniqueness constraints.

## Decisions

### A read-only input instead of passthrough

`type => input`, `readOnly => true`, `size => 30`, `max => 170`,
`displayCond => FIELD:import_identifier:REQ:true`, in a palette `import` on the
"Extended" tab, which every form has except the contract, which gains it. On
the profile the palette holds `import_identifier` and `skip_sync`, which leaves
the `hidden` palette. v13 appends `import_identifier` to the existing
`searchFields` switch of each file. v14 needs nothing. The input renders
`disabled`, so a form save never submits it.

Rejected: keeping `passthrough` and rendering the value through a custom
element. More code for the same result, and still not searchable.

### A public, stateless lookup

`FGTCLB\AcademicPersons\Import\ImportedRecordFinder` (`final readonly`, public
API) with `findUid(string $table, string $identifier): ?int`. The
`QueryBuilder` removes all restrictions and adds `DeletedRestriction` back. It
adds `t3ver_wsid = 0` and `t3ver_oid = 0` where the table is workspace aware
and the language column `IN (0, -1)` where it is language aware, both read
from `TcaSchemaFactory`, and orders by `uid`. PHP then takes the first row
whose identifier is identical, because `utf8mb4_unicode_ci` on MySQL and
MariaDB ignores case and trailing spaces, as `ContractRelationResolver` does
for the same reason. An unknown table or one without the column throws
`\InvalidArgumentException` (1790866812). Any TCA table with the column is
accepted, so a project table that adds one can use it too. The branches for a
table without workspace or language support are not covered by a test: every
person table has both. The empty identifier returns `null` without a query,
since every record an editor created carries it.

Rejected: per-source id columns (one project today). Every new source would
add a column to seven tables.

## Risks / Trade-offs

- [The identifier field appears on forms of synchronised records] → It is
  read-only and in its own palette. Hiding it stays a TCEFORM decision.
- [Duplicates return the oldest record silently] → Documented. A cleanup
  report is a separate concern.

## Migration Plan

Database compare adds the address index. No data migration.

## Open Questions

- None.
