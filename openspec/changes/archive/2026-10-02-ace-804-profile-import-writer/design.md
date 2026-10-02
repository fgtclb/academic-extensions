## Context

See `proposal.md`. On `main` there is no import API. `academic_persons_sync`
contains an empty entity and an `fe_users` record type that
`FrontendUserProvider` does not even select. `DataHandlerExecutionContext`
provides a backend user for DataHandler runs outside the backend and has
`runAsLiveBackendUser()` for installation-wide runs. DataHandler does not read
TCA `readOnly`, so read-only identifier columns are writable.

The change needs three others first: the identifier lookup
(`ace-795-import-identifier-lookup`), the managed-field resolver
(`ace-758-managed-fields-backend`) and the announcement of DataHandler writes
(`ace-725-backend-save-announces-profile-update`).

## Goals / Non-Goals

**Goals:**

- A small writer a project adapter calls, not a framework.
- History, reference index and translation sync as for a backend save.

**Non-Goals:**

- Batching the announcement across persons (decided against, below).

## Decisions

### Plain data in, a result out

`final readonly` DTOs: `ImportedProfile{identifier, pid, fields, contracts}`,
`ImportedContract{identifier, fields, organisationalUnitIdentifier,
functionTypeIdentifier, emailAddresses, phoneNumbers, physicalAddresses}`,
`ImportedContact{identifier, fields}`. `fields` are database column names.
`ProfileImportWriter::write(ImportedProfile): ImportResult` reports one
`ImportedRecordResult{tableName, identifier, outcome, uid, reason}` per
record, with an `ImportedRecordOutcome` (created, updated, unchanged, skipped,
vetoed, failed, and hidden or deleted for a retirement), the messages of the
write and the DataHandler errors. The data objects carry `#[Exclude]`, as the
settings value objects do. Fields that name a column the writer owns, and
identifiers that are empty or repeat within a table, throw before anything is
written.

### One DataHandler datamap per person

Matching uses `ImportedRecordFinder`. New records get `NEW` ids, relations to
units and function types are resolved by identifier and left empty with a
result message when missing. The whole tree is one datamap, run inside
`runAsLiveBackendUser()` with the import correlation mark of
`ace-725-backend-save-announces-profile-update` set. Existing rows get only
the columns `ManagedFieldResolver` names. `hidden` is never in a datamap for
an existing row.

New children enter through the inline column of their parent, which lists the
live default-language children the parent has, in their order, and then the
new ones, the shape the backend form submits.

`DataMapProcessor` purges an empty row, and the hook announces a profile only
when the profile table is in the datamap. An existing profile with no managed
field supplied is therefore submitted with the identifier it has, a value that
changes nothing and keeps the row in the run.

Rejected: Extbase persistence. No history, no DataHandler hooks, so no
`DataMapProcessor` and no announcement. Rejected: a framework with source
adapters and a mapping UI (the ACE-277 scope). It can be built on this later.

### A mutable, vetoable event per record

`BeforeImportedRecordWriteEvent(tableName, identifier, uid, row,
suppliedFields)` with `isNew()`, `getSuppliedFields()`, `setRow()` and
`veto(string $reason)`, dispatched before a record enters the datamap. The uid
replaces the `isNew` flag of the analysis, since a listener may want to read
the stored record. The rules of the writer apply to the replaced row again,
and a veto stops the propagation, as in `BeforeProfileEditingWriteEvent`.

### Retire separately

`retire(string $source, array $keepIdentifiers, RetirePolicy $policy)` with
`RetirePolicy::Hide` or `::Delete`, as a datamap or a cmdmap. It selects
`<source>:%` with a `LIKE` on the indexed column and compares exactly in PHP.
Hiding skips records that are hidden already. Deleting relies on the
DataHandler deleting inline children, which it does unless a column sets
`behaviour.enableCascadingDelete` to false. The children of a deleted record
are left out of the cmdmap, and a deleted profile takes the contracts an
editor added with it. The profiles of retired contracts and contact records
are announced, for the delete policy in a second run after the commands,
because a run announces its datamap before it processes its commands.

### Decided: the writer lives in academic_persons

The writer, its data objects and its event live in `academic_persons`. The
future of `academic_persons_sync` is not decided by this change: it is kept as
it is, as a possible home for other code later, and ACE-644 decides the future
of `academic_persons_sync`.

The writer's building blocks (`DataHandlerExecutionContext`,
`ManagedFieldResolver`, `ImportedRecordFinder`) are all in `academic_persons`.
Rejected: `academic_persons_sync` as the home. It ships only an Extbase model
and an `fe_users` record type that `FrontendUserProvider` never selects, so
putting the writer there would add a dependency for every importer. Also
rejected: deprecating `academic_persons_sync` in a separate change now.

### Decided: synchronous per person, origin `Import`, no batch

Each person is announced once, in the same request, with the origin `Import`,
because the writer runs its datamap under the import correlation mark. This is
the decision of `ace-725-backend-save-announces-profile-update` on the same
question, which this change depends on. One datamap per person means one
announcement per person, the same cost as `academic:updateprofiles`.

## Risks / Trade-offs

- [Large surface] → Built only after its three prerequisites. Each part has
  a functional test.
- [Announcement per person during a bulk import] → The decided cost model.
  Listeners that want to defer work recognise imports by their origin.

## Open Questions

None.
