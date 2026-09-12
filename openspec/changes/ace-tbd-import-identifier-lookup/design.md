## Context

See `proposal.md`. Verified on `main`:

- `import_identifier varchar(170)` exists on profile, contract, e-mail, phone
  number, address, location, organisational unit and function type. All but
  the address table have `key idx_import_identifier`.
- Its TCA is `type => passthrough` on all eight tables, so no backend form
  shows it. That answers the open point of the analysis.
- `academic-persons/Classes/Profile/ProfileFactory.php` is the only writer:
  profile and contract `fe_users:<uid>`, address and e-mail by source field,
  phone numbers `telephone:fe_users:<uid>` / `fax:fe_users:<uid>` (ACE-365).
  Location, organisational unit and function type are never written.
- `skip_sync` on the profile already has
  `displayCond => FIELD:import_identifier:REQ:true`.
- The TCA files switch `ctrl.searchFields` on for v13 only (Breaking #106972
  removed it in v14, which makes input fields searchable by default).
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
`displayCond => FIELD:import_identifier:REQ:true`, in a palette `import`; on
the profile the palette holds `import_identifier` and `skip_sync`. v13 appends
`import_identifier` to the existing `searchFields` switch of each file; v14
needs nothing.

Rejected: keeping `passthrough` and rendering the value through a custom
element. More code for the same result, and still not searchable.

### A public, stateless lookup

`FGTCLB\AcademicPersons\Import\ImportedRecordFinder` (`final readonly`, public
API) with `findUid(string $table, string $identifier): ?int`. `QueryBuilder`
with all restrictions removed, then `DeletedRestriction`; `t3ver_wsid = 0` and
`t3ver_oid = 0` where the table is workspace aware (via `TcaSchemaFactory`);
the language column `IN (0, -1)`; `ORDER BY uid`; `setMaxResults(1)`. An
unknown table or one without the column throws `\InvalidArgumentException`.

Rejected: per-source id columns (one project today). Every new source would
add a column to seven tables.

## Risks / Trade-offs

- [The identifier field appears on forms of synchronised records] → It is
  read-only and in its own palette; hiding it stays a TCEFORM decision.
- [Duplicates return the oldest record silently] → Documented; a cleanup
  report is a separate concern.

## Migration Plan

Database compare adds the address index. No data migration.

## Open Questions

- None.
