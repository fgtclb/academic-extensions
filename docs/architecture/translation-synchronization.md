# Translation synchronization

`academic_persons` keeps the translations of a profile — and of everything
hanging below it — in sync with the default-language record. The mechanism
spans three extensions: `academic_persons` owns the event and the synchronizer
service, `academic_persons_edit` owns the listener that wires them together and
the configuration that switches the whole thing on, and
`academic_contacts4pages` adds a policy on top of the cascade. Until version
3.0 the synchronizer wrote raw SQL; the ACE-480/483/484/485 round rerouted
every write through the TYPO3 `DataHandler`. This page records why, and how the
pieces fit.

## Why the DataHandler, not queries

Three facts about the core decide the design. All three were verified against
the installed vendor trees — TYPO3 v13.4.34 in `.Build/vendor/`, v14.3.6 in
`core-14/vendor/` — not recalled.

**Nothing on the read path honours `l10n_mode=exclude`.** The persons TCA uses
it heavily: an excluded column is hidden in translation forms and is *supposed*
to carry the default record's value. But the only code in the v13.4.34 core
that consumes the setting is write-side or FormEngine: `DataHandler`,
`DataMapProcessor` and the schema classes (`FieldTranslationBehaviour`,
`VisibleSchemaFieldsCollector`) in `cms-core/Classes/`, and
`SingleFieldContainer` in `cms-backend/Classes/`. `cms-extbase/Classes/` and
`cms-frontend/Classes/` contain **zero** occurrences — neither the page
repository overlay nor Extbase's query parsing special-cases it. Whatever
value sits in the translation row is what the frontend renders; a stale
excluded value is not repaired at render time, so the rows themselves must be
correct.

**Core's own synchroniser only runs inside the DataHandler.**
`DataMapProcessor` (`cms-core/Classes/DataHandling/Localization/DataMapProcessor.php`)
is the code that propagates `l10n_mode=exclude` and
`allowLanguageSynchronization` values from a default record into its
dependents, including relations and inline children. It is invoked from
exactly one place: `DataHandler::process_datamap()` (v13.4.34,
`DataHandler.php:683`).

**Extbase frontend saves bypass it entirely.** Extbase persistence writes
through the DBAL connection — `Typo3DbBackend` calls `$connection->insert()`
and `$connection->update()` directly (v13.4.34, `Typo3DbBackend.php:87` and
`:121`), never the DataHandler. A profile edited through the frontend plugins
therefore changes the default record without any of core's translation
machinery noticing. **That bypass is the reason `RecordSynchronizer` exists**:
something has to carry the change into the translation rows, and doing it by
mimicking the DataHandler in hand-written SQL is how the pre-3.0 defects
happened (dead recursion, live-row writes from workspaces, missing file
references, empty `l10n_diffsource`). Routing the writes through the real
DataHandler buys all of that — inline child cascade, file references, MM
relations, diff source, reference index, history, hooks, workspace
correctness — instead of reimplementing it.

## The chain end to end

```text
dispatch site ──▶ AfterProfileUpdateEvent ──▶ SyncChangesToTranslations ──▶ RecordSynchronizer ──▶ DataHandler
 (persons /                                     (persons_edit, gates)         (persons, @internal)
  persons_edit /
  project hooks)
```

### The event and its dispatch sites

`FGTCLB\AcademicPersons\Event\AfterProfileUpdateEvent`
(`packages/fgtclb/academic-persons/Classes/Event/AfterProfileUpdateEvent.php`)
carries one thing: the **persisted default-language profile**. Listeners read
the database, not the object, so a dispatch is only meaningful after
`persistAll()`, with a real uid, and never for a translation overlay.

| Dispatch site                                                                | Context                                                                                                 | Notes                                                               |
|------------------------------------------------------------------------------|---------------------------------------------------------------------------------------------------------|---------------------------------------------------------------------|
| `AbstractProfileFactory::createProfileForUser()` (persons)                   | Profile auto-creation via the `academic:createprofiles` CLI command (login-time wiring is project-side) | The only in-repo dispatch between 2.0 and 3.0                       |
| `AbstractProfileFactory::updateProfileForUser()` (persons)                   | Profile updates from fe_users data via the `academic:updateprofiles` CLI command                        | Dispatches per profile the update ran through (ACE-490)             |
| `AbstractActionController::persistAndDispatchProfileUpdate()` (persons_edit) | Every frontend editing action that persists a change to the profile aggregate, in all six controllers   | Restored with ACE-485 — the 2.x restructuring had lost the dispatch |
| Project-side `DataHandler` hooks                                             | Backend edits, in installations that wire it up themselves                                              | Outside this repository; the main production path                   |

The helper in `AbstractActionController`
(`packages/fgtclb/academic-persons-edit/Classes/Controller/AbstractActionController.php`)
enforces the contract in one place: it calls `persistAll()` first, then skips
the dispatch for a `null` profile, an unpersisted one, or a translation
overlay. `GenerateSlugForProfile` listens to the same event and has no
translation guard of its own, so dispatching an overlay would regenerate the
default record's slug from the wrong context — the guards sit at the dispatch
for that reason.

The `skip_sync` flag cuts across the two paths differently: in the frontend
editing flow it has nothing to do with the dispatch — it gates only the
fe_users→profile data synchronisation. In `updateProfileForUser()` it gates
**both** since ACE-490: a `skip_sync` profile is neither data-updated nor
announced, even when its frontend user is selected through a second,
synchronisable profile.

### The listener and its gates

`SyncChangesToTranslations`
(`packages/fgtclb/academic-persons-edit/Classes/EventListener/SyncChangesToTranslations.php`,
registered in that extension's `Services.yaml`) turns the event into a
`SynchronizerContext`:

- **Language gate**: the target languages come from the extension
  configuration `academic_persons_edit` → `profile.allowedLanguages`, read on
  every call by `ProfileTranslator::getAllowedLanguageIds()` (deliberately
  uncached). `SynchronizerContext::create()` then drops every id that is not
  positive or that the site does not define. An empty result makes
  `synchronize()` return before doing anything — an installation that leaves
  the setting empty has no translation sync, full stop.
- **Site resolution**: the site is taken from the `site` attribute of
  `$GLOBALS['TYPO3_REQUEST']` when present (a `NullSite` counts as absent),
  falling back to `SiteFinder::getSiteByPageId()` on the profile's pid. No
  site, no sync. The class's own docblock flags this as a design debt — the
  event is dispatched from FE, BE and CLI contexts, so the site should travel
  in the event instead of being reconstructed from globals; that stands as a
  `@todo`, not as a resolved decision.

### The synchronizer

`RecordSynchronizer`
(`packages/fgtclb/academic-persons/Classes/Service/RecordSynchronizer.php`,
`@internal`, published behind `RecordSynchronizerInterface`) walks the allowed
languages of the context and issues DataHandler work per language:

- **Missing translation → `localize` command.** One command translates the
  record *and everything below it*: the inline child tree (contracts and their
  addresses, emails, phone numbers), `sys_file_reference` rows, MM relations,
  `l10n_diffsource` — because that is simply what the DataHandler does.
- **Existing translations → exclude propagation + reference synchronize.** The
  current values of the propagatable `l10n_mode=exclude` columns — of the
  default record *and of every default-language record in its inline child
  tree* (ACE-487) — are re-submitted as **one datamap per run**, and
  `DataMapProcessor` carries them into every translation of every touched
  record at the same time. Then an `inlineLocalizeSynchronize` command
  (`action: synchronize`) per TCA `inline` and synchronized `file` column and language
  carries children added to the default record after the translation was
  created — including their own children — and removes translated file
  references whose default-language parent was removed. The explicit `file`
  pass is required: exclude propagation adds a late reference, but does not
  remove the localized reference after the source relation disappears.

  The profile `image` field is intentionally localizable and is therefore not
  included in either pass. Each profile translation owns and manages its image
  reference independently. Core's `localize` command initially creates the
  localized file reference together with a new profile translation. It may
  initially point at the same physical file; the first language-specific upload
  replaces it with an independent reference and file through DataHandler.

Two implementation choices worth their comments:

- **Propagatable columns are filtered by TCA type.** The types `inline`,
  `file`, `group`, `category`, `folder`, `passthrough` and anything with `MM`
  are excluded from the datamap: their database values are counters or uid
  CSVs that a datamap re-submission would reinterpret as relation writes (the
  profile's `frontend_users` counter `1` would become "relate to fe_user 1").
  That filter costs nothing on the sync side: `DataMapProcessor` synchronizes
  **all** `l10n_mode=exclude` columns of a record the datamap touches, reading
  their values from the database row (`populateTranslationItem()` — file and
  inline references via `synchronizeReferences()`, MM via
  `synchronizeDirectRelations()`). A file reference or MM relation added to
  the default record after its translation exists is therefore carried over
  by the update path too when its TCA column uses `l10n_mode=exclude`. Removal
  is asymmetric and needs the explicit `inlineLocalizeSynchronize` pass
  described above.
- **One DataHandler instance per command.** A cmdmap is keyed
  `[table][uid][command]`, so it can hold only *one* command per record uid —
  `localize` per language and `inlineLocalizeSynchronize` per inline column
  and language cannot be combined into one map. Every pass gets a fresh
  instance, and its `errorLog` is checked and logged after each pass, because
  DataHandler failures are otherwise silent.

The translation existence check is the one core-version switch in the chain:
v14 deprecated `BackendUtility::getRecordLocalization()` in favour of
`LocalizationRepository::getRecordTranslation()`, which does not exist on v13,
so `hasTranslation()` selects the API with a `method_exists()` gate — the
pattern is discussed in [Core version aware code](core-version-aware-code.md).

## Execution contexts and the backend user

The DataHandler wants a backend user, and the chain runs from three contexts
that differ in whether one exists:

| Context               | Reached via                                                    | Backend user present?                 |
|-----------------------|----------------------------------------------------------------|---------------------------------------|
| Frontend Extbase save | Profile auto-create on login; the frontend editing controllers | No (unless a backend preview session) |
| Backend               | Project-side DataHandler hooks dispatching the event           | Yes, with a real workspace            |
| CLI                   | `academic:createprofiles` via a frontend-like bootstrap        | No                                    |

`DataHandlerExecutionContext`
(`packages/fgtclb/academic-persons/Classes/Service/DataHandlerExecutionContext.php`)
answers that once, for all of them. `DataHandler::start()` accepts an explicit
user object (v13.4.34, `DataHandler.php:483`), so no backend *session* is
needed — but passing the object is not enough: parts of the localization path
go through `BackendUtility` helpers that read `$GLOBALS['BE_USER']` directly
and ignore the injected object. `runAsBackendUser()` therefore swaps the
global in for the duration of the callback and restores the previous state in
a `finally` — also when the callback throws. `$GLOBALS['LANG']` is set
defensively the same way, because DataHandler error paths render backend
labels through it.

When no backend user exists, a **synthetic in-memory admin** is used: uid `0`
(no `be_users` row backs it), `admin` flag set (bypasses permission and
workspace access checks), username `_record_synchronizer_`. One trap made it
into a comment and belongs here too: the `workspace` property of
`BackendUserAuthentication` defaults to **-99** ("offline"), not to live — a
freshly instantiated user object that never gets a workspace assigned would
make the DataHandler act in a workspace that does not exist. The synthetic
user's workspace is therefore always set explicitly, from the `Context`
workspace aspect.

One consequence is accepted rather than solved: DataHandler runs under the
synthetic user write `sys_log` rows with `userid=0`. `enableLogging` stays on
**by decision** (ACE-487): the rows are the audit trail of what the
synchronisation wrote, and that trail outweighs the noise of a bulk CLI run.

## Workspace semantics

The synchronisation acts **in the acting workspace** — that is the ACE-480
fix. A backend user working inside a workspace produces versioned rows only
(`t3ver_wsid` set, `t3ver_state=1`); the live rows stay byte-identical until
the workspace is published. This is not implemented, it is inherited: the
DataHandler overlays the source record for the acting workspace and writes new
records as workspace placeholders on its own.

Around that, three guards:

- **Frontend refusal.** A frontend request acting in a non-live workspace
  (workspace preview) is refused before anything runs:
  `DataHandlerExecutionContext::isFrontendRequestInWorkspace()` checks the
  request's application type and the context's workspace aspect, and
  `synchronize()` logs a notice and returns. The policy is **hardcoded** for
  now; making it configurable is a named follow-up of ACE-480 (no issue filed
  yet).
- **Version-uid refusal.** A uid addressing a workspace version row
  (`t3ver_oid > 0`) is refused with a logged notice. The DataHandler addresses
  versioned records through their **live** uid and overlays them itself;
  accepting the version uid was the shape of the ACE-480 defect — draft values
  published as live translations.
- **Foreign-workspace no-op.** A record whose `t3ver_wsid` is neither `0` nor
  the acting workspace is silently skipped: a workspace-only record is
  invisible outside its own workspace. From *inside* its workspace it is
  synchronisable, and the created translations are versioned rows of that
  workspace.

A detail for whoever touches the guards: in a live context the
foreign-workspace check catches a version uid before the `t3ver_oid` check
does, so the two guards were proven load-bearing **as a pair** (disabling only
the version-uid guard keeps its test green). They are not redundant — each is
the one that fires in the context the other cannot see.

## The contact4pages policy — extending the mechanism

`academic_contacts4pages` is the worked example of building policy on top of
the cascade instead of into it.

The problem: a contact is an inline child of a contract, and its `page` column
is a plain `group` relation storing the **default-language page uid** — TYPO3
models page references that way, and the column carries no `l10n_mode` (every
other content column of the contact is `exclude`). A `localize` of a contract
or profile cascades into the contacts, and the DataHandler copies the `page`
value verbatim — whether that page is translated into the target language or
not. The resulting translated contact carries no content of its own and made
the contact render twice (the ACE-103 duplicates). The policy is: **a contact
whose page is not translated into the target language yields no translated
contact at all** (ACE-484).

**Why a `processCmdmap_afterFinish` hook.** Cascaded children are the crux:
the DataHandler localizes inline children through internal
`copyRecord`/`localize` calls, never through cmdmap entries of their own — so
the per-record hooks (`processCmdmap_preProcess`, `processCmdmap_postProcess`)
fire for the contract command only and **never see the contact**. There is
nothing to veto at the level where the contact appears. What does see every
record a run created is `DataHandler::$copyMappingArray_merged`, and
`processCmdmap_afterFinish` fires after `remapListedDBRecords()`, when the
created rows are fully wired. The hook
(`packages/fgtclb/academic-contact4pages/Classes/Hook/DataHandlerHooks.php`)
scans that map, keeps only connected translations (`sys_language_uid > 0` and
`l10n_parent > 0`) with a page reference, checks `pages` for a translation —
workspace-aware, and a *hidden* page translation counts, because visibility is
not existence — and removes an offending row through a **nested DataHandler
`delete` command** under the outer run's user: a soft delete for a live row,
and a discard for a workspace-created row, so workspace semantics stay in
core's hands. Free-mode copies (`l10n_parent = 0`) are exempt — they are
independent records, not duplicates.

**The read side converges on `OVERLAYS_MIXED`.**
`ContactRepository::findByPid()`
(`packages/fgtclb/academic-contact4pages/Classes/Domain/Repository/ContactRepository.php`)
needs a row set no Extbase language setting expresses: records of the
requested language — connected translations *and* language-only records — plus
default-language records without a translation. A raw pre-query resolves
exactly one uid per contact per language, and the Extbase query then fetches
those uids with the language aspect pinned to `OVERLAYS_MIXED`. The pin is
what makes both cores behave identically: v13's `Typo3DbBackend` hardcodes
`OVERLAYS_MIXED` internally (v13.4.34, `Typo3DbBackend.php:585`), while v14
respects the aspect's own overlay type (v14.3.6, `core-14/vendor/…/Typo3DbBackend.php:597`).
A side effect does real work here: because a translation row represents its
default record, the duplicated translations that pre-3.0 installations already
carry collapse to one rendered contact **without any database cleanup** — the
legacy rows stay, they just stop being selected alongside their originals.

## Named gaps

Stated so they are decisions, not surprises:

| Gap                                                                                                                                                                                                                                                                                                  | Tracked as |
|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------|
| ~~Update-path gaps~~ — resolved by ACE-487: children's exclude columns are re-propagated by the inline-tree datamap now, and the late-file/MM half turned out never to be a gap (core's `DataMapProcessor` carries both; probed, pinned by tests). `enableLogging` stays on by decision — see above. | ACE-487    |
| Branch `2` still carries the contract relation select defect the rework surfaced: the "please select" items wrote `''` into nullable integer columns, which PostgreSQL rejects (`main` fixed it as ACE-489 — the columns are `NOT NULL DEFAULT 0` there now).                                        | ACE-488    |
| The frontend workspace refusal is hardcoded **by decision** (ACE-492, closed as won't implement for now): a frontend-triggered synchronisation never writes workspace content, and no installation has asked for the opt-in. Reopen ACE-492 if one does — the candidate shapes are recorded there.   | ACE-492    |

## See also

- [Database queries](database-queries.md) — the ACE-488 defect is exactly the
  class of DBMS-divergent failure that page tells you to run on PostgreSQL
  first.
- [Class design](class-design.md) — `SynchronizerContext` is one of the
  `final readonly` data objects, and the services in this chain are stateless.
- [Core version aware code](core-version-aware-code.md) — the
  `method_exists()` gate in `hasTranslation()` is one of the version switches
  counted there.
- `packages/fgtclb/academic-persons/Documentation/Developers/Index.rst` — the
  same surface documented for extension users rather than maintainers.
- The changelog entries of the round:
  `academic-persons/Documentation/Changelog/3.0/Important-TranslationSyncRoutedThroughDataHandler.rst`,
  `academic-contact4pages/Documentation/Changelog/3.0/Important-ContactsOfUntranslatedPagesAreNotLocalized.rst`
  and
  `academic-persons-edit/Documentation/Changelog/3.0/Feature-FrontendEditsSynchronizeTranslations.rst`.
