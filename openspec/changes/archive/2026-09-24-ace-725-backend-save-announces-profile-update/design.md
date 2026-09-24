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
  (`DataHandler.php:9655-9660`) and v14 (`:9657-9662`). `start()` replaces the
  correlation id, so a writer sets its own after `start()`. On v14 the nested
  instances of a run receive the outer id; on v13 each gets a random one. The
  design does not depend on either, nested runs are skipped by call stack.
- The DataHandler flushes the reference index of the outermost run only. A
  run started from a listener of a backend save is nested in the save's run
  and gets an updater of its own, which nobody flushes unless it does.
- `RepairLocalizedProfileImagesUpgradeWizard` runs no DataHandler of its own.
  Every write goes through `ProfileImageRelationWriter`.
- `GenerateSlugForProfile` rewrites the slug with `SlugHelper::generate()` on
  every announcement and applies none of the `eval` uniqueness rules of the
  column (`uniqueInPid`).

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

1. Return for a nested instance (`isOuterMostInstance()`), a run marked
   `Internal` (below) and a run in a workspace (`BE_USER->workspace !== 0`).
   The DataHandler writes a copied or localized record through a nested
   instance, and commands are not announced; the check reads the call stack,
   so a run started from another run's hook is nested as well.
2. Resolve the profile keys of `$dataHandler->datamap` through
   `substNEWwithIDs`, and keep the live default-language rows
   (`sys_language_uid = 0`, `t3ver_wsid = 0`), ordered by uid.
3. Load each one with a new `ProfileRepository::findByUidForSynchronization()`,
   which ignores every enable field like the synchronisation lookup of
   `ace-667-sync-lookups-ignore-time-window`, and pins the language aspect to
   the stored row. The candidate's `findByUidIncludingHidden()` ignores only
   `disabled`, so it misses a profile whose end time has passed. That finder
   also serves the public detail view and must not be widened. Dispatch with
   `origin: Backend`, or `origin: Import` when the run carries the import
   mark (below), and the site from `SiteFinder` for the pid.

A private local array per call holds the uids, and the class stays stateless.

Rejected: dispatching per record in `afterDatabaseOperations`, which is
the approach of one project's own hook. That hook is deferred for records on
the remap stack, it fires once per record, and it needs a faked request for
the site.

### Guard with a correlation id aspect, set by every internal writer

The backed enum `FGTCLB\AcademicPersons\DataHandling\ProfileWriteCorrelation`
has two cases, `Internal` (`academic-persons-internal`) and `Import`
(`academic-persons-import`). `create()` returns a correlation id with a random
scope and the case value as its aspect; `fromCorrelationId()` reads the case
back from a run's correlation id. `RecordSynchronizer::executeDataHandler()`
and `ProfileImageRelationWriter::executeDataHandler()` set
`ProfileWriteCorrelation::Internal->create()` after `start()`. That covers the
repair wizard too, which writes through the relation writer only. The hook
skips a run marked `Internal`.

The aspect is the shape core's own redirects extension uses to recognise its
nested runs (`DataHandlerSlugUpdateHook::isNestedHookInvocation()`). The scope
stays random per run, as the DataHandler makes it: the history store derives
the correlation id of every row it writes from it, and the redirects rollback
groups by that id.

Rejected: a fixed scope such as `CorrelationId::forScope('academic-persons-internal')`.
Every internal write of a record would share one history correlation id
across all runs. Also rejected: a static "currently synchronising" flag, which
is service state and leaks across runs in a long-lived worker.

A run marked `Import` is not skipped. The hook dispatches with
`origin: Import` instead of `Backend`. The import writer of
`ace-tbd-profile-import-writer` sets it, and a project's own DataHandler
import can set it too.

### The internal writers flush their own reference index

Both `executeDataHandler()` methods pass a `ReferenceIndexUpdater` of their own
to `start()` and call `update()` on it after processing. Run from a listener of
a backend save, their instance is nested in the save's run, and the
DataHandler flushes the updater of the outermost instance only: without this,
every translation the synchronisation of a backend save or an import creates
would be missing from `sys_refindex`. For an outermost run the second
`update()` finds nothing left to do.

### The site comes from the dispatcher

`SyncChangesToTranslations` uses `$event->getSite()` first and keeps
`getSite()` as the fallback for third-party dispatchers. The editor passes
the request's site, the commands pass `null`, and the hook passes the
resolved site. An event of origin `Backend` or `Import` without a site is not
synchronised: the hook found no site for the profile's page, and the site of
a backend request belongs to the page selected in the page tree.

### Image metadata is not written twice

`UpdateProfileImageMetadata` returns early for `origin: Backend` and
`origin: Import`. Both are DataHandler runs, and the hook's
`afterDatabaseOperations` already writes the metadata for them when a name or
the image changed.

### Decided: a backend save keeps its slug

`GenerateSlugForProfile` returns early for `origin: Backend`. The backend
form has the slug field with its own regenerate button, and the DataHandler
has already made the slug unique in the folder (`eval: uniqueInPid`).
Regenerating it would overwrite a slug the editor set by hand on the next
save, and would turn the `john-doe-1` of a second "John Doe" back into a
duplicate `john-doe`.

For every other origin the listener still regenerates the slug from the name,
reads the row past the visibility restrictions (a hidden profile is announced
too), and now applies the `eval` rules of the column the way the DataHandler
does: `unique`, `uniqueInSite` and `uniqueInPid`, in that order. A marked
import therefore gets unique slugs without code, and so do the frontend and
the commands.

Rejected: regenerating for every origin, as the analysis proposed. That loses
slugs set by hand in the backend. Also rejected: carrying the changed columns
in the event, so that the listener could regenerate only after a name change.
That widens the event for one listener.

A slug the name still yields is left as it is: the plain one, unique or not,
and a suffixed one while `makeUnique()` returns it unchanged. Otherwise the
first command run after the update would renumber every pair of profiles
that share a slug today, and every run would move a suffixed slug down to the
lowest free suffix. A backend save that submits the slug field resolves a
pair; one without it, for lack of the exclude field permission, does not.

### No core version split

Every API used exists on v13 and v14. The one difference, the propagation of
the correlation id to nested instances on v14 only, does not matter here.

### Decided: synchronous per profile, with an `Import` origin, no batch

Backend saves and imports announce every profile synchronously, in the same
request. `ProfileUpdateOrigin` gets the case `Import` in this change, derived
from the import correlation mark, so listeners can tell imports apart or
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
- [The keep rule reads any trailing `-<digits>` as a suffix] → A profile
  "John 2" renamed to "John" keeps `john-2`. Telling the two apart needs the
  changed columns, which the event does not carry; the case is rare.
- [A third-party DataHandler writer in a listener re-enters] → The
  internal correlation mark is documented in `docs/` and in the developer
  manual for such writers.

## Open Questions

None.
