## Context

- `DataHandlerHooks` (`academic-persons/Classes/Hook/DataHandlerHooks.php`,
  registered as `processDatamapClass` in `ext_localconf.php:94-95`, public in
  `Configuration/Services.yaml`) sets alpha columns before the run. After each
  record it writes image metadata and flushes caches (`:51-92`). It dispatches
  no event.
- `AfterProfileUpdateEvent` is `final` with `__construct(Profile $profile)`
  (`Classes/Event/AfterProfileUpdateEvent.php:16-18`). It is dispatched in
  `AbstractProfileFactory.php:105` (creation) and `:180` (update), and in
  `academic-persons-edit/Classes/Controller/ProfileController.php:3127`,
  `:3184` (image) and `:3320`.
- Three listeners use it:
  - `SyncChangesToTranslations` guesses the site from the global request with
    a `@todo` (`academic-persons-edit/Classes/EventListener/SyncChangesToTranslations.php:52,70-86`);
  - `GenerateSlugForProfile` writes the slug through a plain `Connection`, so
    it does not re-enter the DataHandler;
  - `UpdateProfileImageMetadata` in `academic_persons`.
- **Recursion traps, verified by reading:**
  - `RecordSynchronizer` writes datamaps on default-language rows
    (`Classes/Service/RecordSynchronizer.php:221-239`, through
    `executeDataHandler()` at `:386`).
  - The editor's `ProfileImageRelationWriter::replace()` puts the profile row
    in its datamap (`Classes/Service/ProfileImageRelationWriter.php:79-102`),
    and the controller then dispatches itself (`:3126-3127`).

  The candidate named only the synchronizer. A guard on it alone would
  announce every editor image upload twice.
- `DataHandler::setCorrelationId()` and `getCorrelationId()` exist on v13
  (`DataHandler.php:9655-9660`) and v14 (`:9657-9662`).

## Goals / Non-Goals

**Goals:**

- One announcement per default-language profile and DataHandler run, with no
  service state and no faked request.

**Non-Goals:**

- Announcing `cmdmap` operations (copy, move, localize, delete, publish).

## Decisions

### Extend the existing event instead of adding a backend event

Add promoted, optional constructor arguments to `AfterProfileUpdateEvent`:
`?Site $site = null` and
`ProfileUpdateOrigin $origin = ProfileUpdateOrigin::Unknown`, with getters. The
new backed enum `FGTCLB\AcademicPersons\Event\ProfileUpdateOrigin` has the
cases `Creation`, `Synchronization`, `FrontendEditing`, `Backend`, `Import`
and `Unknown`. Existing `new AfterProfileUpdateEvent($profile)` calls stay
valid.

Rejected: a separate `BackendProfileSavedEvent`. Every listener would have to
subscribe twice, and the translation sync would still miss imports.

### Dispatch once, after all operations

Implement `processDatamap_afterAllOperations(DataHandler $dataHandler)` in
`DataHandlerHooks`:

1. Resolve the profile keys of `$dataHandler->datamap` through
   `substNEWwithIDs`.
2. Keep the uids of live, default-language rows (`sys_language_uid = 0`,
   `BE_USER->workspace === 0`).
3. Load each one ignoring every enable field, like the synchronisation lookup
   of `ace-tbd-sync-lookups-ignore-time-window`. The candidate's
   `findByUidIncludingHidden()` ignores only `disabled`, so it misses a
   profile whose end time has passed. That finder also serves the public
   detail view and must not be widened. Dispatch with `origin: Backend`, or
   `origin: Import` when the run carries the import scope (below), and the
   site from `SiteFinder` for the pid.

A private local array per call holds the uids, and the class stays stateless.

Rejected: dispatching per record in `afterDatabaseOperations`, which is
the approach of one project's own hook. That hook is deferred for records on
the remap stack, it fires once per record, and it needs a faked request for
the site.

### Guard with a correlation id scope, set by every internal writer

`RecordSynchronizer::executeDataHandler()`,
`ProfileImageRelationWriter::executeDataHandler()` and the DataHandler runs of
`RepairLocalizedProfileImagesUpgradeWizard` set
`CorrelationId::forScope('academic-persons-internal')`. The hook skips a run
whose correlation id has that scope. A small `final` class holding the scope
constant keeps the string in one place.

Rejected: a static "currently synchronising" flag, which is service state and
leaks across runs in a long-lived worker.

A second scope, `academic-persons-import`, marks a run as an import. The hook
does not skip it; it dispatches with `origin: Import` instead of `Backend`.
The same class holds both scope constants. The import writer of
`ace-tbd-profile-import-writer` sets it, and a project's own DataHandler
import can set it too.

### The site comes from the dispatcher

`SyncChangesToTranslations` uses `$event->getSite()` first and keeps
`getSite()` as the fallback for third-party dispatchers. The editor passes
the request's site, the commands pass `null`, and the hook passes the
resolved site.

### Image metadata is not written twice

`UpdateProfileImageMetadata` returns early for `origin: Backend`, because the
hook's `afterDatabaseOperations` already covers it for backend saves.

### No core version split

Every API used is the same on v13 and v14.

### Decided: synchronous per profile, with an `Import` origin, no batch

Backend saves and imports announce every profile synchronously, in the same
request. `ProfileUpdateOrigin` gets the case `Import` in this change, derived
from the import correlation scope, so listeners can tell imports apart or
defer them. One synchronisation per profile is already the cost of
`academic:updateprofiles` since ACE-490, and a DataHandler based import gets
no translation or slug sync at all today. A batch would need a queue or
remembered state, which the stateless-services rule excludes. Adding an enum
case later would break listeners that use an exhaustive `match`, so the case
set is fixed now.

## Risks / Trade-offs

- [Bulk imports run one synchronisation per profile in the request] → This is
  the decided cost model and is named in the changelog. Listeners that want
  to defer work recognise imports by their origin.
- [A project's own hook now dispatches a second time] → The `Important-`
  entry tells projects to remove it.
- [A third-party DataHandler writer in a listener re-enters] → The
  correlation scope is documented in `docs/` for such writers.

## Open Questions

None.
