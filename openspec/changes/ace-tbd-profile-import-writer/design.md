## Context

See `proposal.md`. On `main` there is no import API. `academic_persons_sync`
contains an empty entity and an `fe_users` record type that
`FrontendUserProvider` does not even select. `DataHandlerExecutionContext`
provides a backend user for DataHandler runs outside the backend and has
`runAsLiveBackendUser()` for installation-wide runs. DataHandler does not read
TCA `readOnly`, so read-only identifier columns are writable.

The change needs three others first: the identifier lookup
(`ace-tbd-import-identifier-lookup`), the managed-field resolver
(`ace-tbd-managed-fields-backend`) and the announcement of DataHandler writes
(`ace-tbd-backend-save-announces-profile-update`).

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
`ProfileImportWriter::write(ImportedProfile): ImportResult` reports created,
updated, skipped and vetoed records and DataHandler errors.

### One DataHandler datamap per person

Matching uses `ImportedRecordFinder`; new records get `NEW` ids, relations to
units and function types are resolved by identifier and left empty with a
result message when missing. The whole tree is one datamap, run inside
`runAsLiveBackendUser()` with the import correlation scope of
`ace-tbd-backend-save-announces-profile-update` set. Existing rows get only
the columns `ManagedFieldResolver` names; `hidden` is never in a datamap for
an existing row.

Rejected: Extbase persistence. No history, no DataHandler hooks, so no
`DataMapProcessor` and no announcement. Rejected: a framework with source
adapters and a mapping UI (the ACE-277 scope); it can be built on this later.

### A mutable, vetoable event per record

`BeforeImportedRecordWriteEvent(table, identifier, row, isNew)` with
`setRow()` and `veto(string $reason)`, dispatched before a record enters the
datamap.

### Retire separately

`retire(string $source, array $keepIdentifiers, RetirePolicy $policy)` with
`RetirePolicy::Hide` or `::Delete`, as a cmdmap or datamap per record; it
selects `<source>:%` with a `LIKE` on the indexed column and compares exactly
in PHP.

### Decided: the writer lives in academic_persons

The writer, its data objects and its event live in `academic_persons`. The
future of `academic_persons_sync` is not decided by this change: it is kept
as it is, as a possible home for other code later, and ACE-644 decides the
future of `academic_persons_sync`.

The writer's building blocks (`DataHandlerExecutionContext`,
`ManagedFieldResolver`, `ImportedRecordFinder`) are all in `academic_persons`.
Rejected: `academic_persons_sync` as the home. It ships only an Extbase model
and an `fe_users` record type that `FrontendUserProvider` never selects, so
putting the writer there would add a dependency for every importer. Also
rejected: deprecating `academic_persons_sync` in a separate change now.

### Decided: synchronous per person, origin `Import`, no batch

Each person is announced once, in the same request, with the origin
`Import`, because the writer runs its datamap under the import correlation
scope. This is the decision of `ace-tbd-backend-save-announces-profile-update`
on the same question, which this change depends on. One datamap per person
means one announcement per person, the same cost as
`academic:updateprofiles`.

## Risks / Trade-offs

- [Large surface] → Built only after its three prerequisites; each part has
  a functional test.
- [Announcement per person during a bulk import] → The decided cost model;
  listeners that want to defer work recognise imports by their origin.

## Open Questions

None.
