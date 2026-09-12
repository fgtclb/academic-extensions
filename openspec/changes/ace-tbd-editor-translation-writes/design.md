## Context

See `proposal.md`. Verified by reading `main`, not executed:

- `ProfileUpdateRequestService::findEditableProfile()` resolves the profile
  through `ProfileRepository::findByFrontendUser()` in the request language,
  which yields the overlay object.
- `createDocumentAction()` hands that object to
  `ProfileInformationFactory::createFromFormData()`. Extbase writes the
  relation as `getUid()`, the default-language uid, and the new row with
  `sys_language_uid = 0`.
- `persistAndDispatchProfileUpdate()` returns without dispatching when
  `$profile->getIsTranslation()` is true, for every endpoint.
- Profile field edits on the overlay are written to the translation row.
- `profile_information.profile` only offers default-language profiles
  (`foreign_table_where ... sys_language_uid IN (-1,0)`), so the TCA models
  timeline rows as owned by the default profile.
- `SyncChangesToTranslations` also skips a profile whose `getIsTranslation()`
  is true, and works from `getUid()`; `GenerateSlugForProfile` works from
  `getUid()` alone.
- The existing translation sync tests start from the default profile only
  (`AcademicPersonsEditProfileEditingTranslationSyncTest`).

## Goals / Non-Goals

**Goals:**

- A reproducing test before any fix.
- Step 1 independent of the step 2 decision, so it can land first.

**Non-Goals:**

- Changing `AfterProfileUpdateEvent` or its listeners.

## Decisions

### Announce the default-language profile

`persistAndDispatchProfileUpdate()` keeps dispatching the profile it got for a
default-language write. For an overlay it loads the default-language profile
by `getUid()` with a query that ignores the language aspect and includes hidden
records, and dispatches `AfterProfileUpdateEvent` with that object. The
listeners stay untouched: they already expect a default-language profile.

Rejected: dropping the `getIsTranslation()` guard and dispatching the overlay.
`SyncChangesToTranslations` would still skip it, and changing that guard would
change what the fe_users sync and the backend announce too.

Rejected: one project's XCLASS of `ProfileInformationFactory` attaching a fake
profile reference that carries the translation uid. It breaks the TCA model
above and is fatal on 3.0.

### Decided: structure in the default language, text on the translation

On a translation the editor refuses structural writes to documents and
contacts: the add, delete and sort endpoints reject the request, and the
sections show a hint "change entries in the default language" instead of
those controls. Editing the text of an existing, translated entry keeps
writing the translation row, which is what happens today
(`Backend.php:730-733`). This is **BREAKING** for installations that let
people add entries on translations.

The TCA models these rows as owned by the default profile, and every
synchronisation consumer (`RecordSynchronizer`, `ManagedFieldResolver`, the
import writer) relies on that, so a structural write belongs to the default
language.

Rejected: model A, all document and contact sections read-only on a
translation. It would also take away the translation of existing vita text,
which works today. Rejected: model B, rows created on language L with
`sys_language_uid = L` and `l10n_parent = 0`, attached to the default
profile. Such parentless rows contradict the TCA model and every consumer
above. One project expects model B; it keeps its own code or adapts to the
default-language workflow.

## Risks / Trade-offs

- [The synchronisation after a translation write overwrites a translated
  value] → Only exclude columns are synchronised; the second requirement of
  the spec is covered by a test.
- [People used to adding entries on a translation lose that] → The
  `Breaking-` entry explains it; an entry added in the default language
  reaches the translations through the synchronisation of step 1.

## Migration Plan

Neither step needs a migration. Existing rows stay as they are.

## Open Questions

None.
