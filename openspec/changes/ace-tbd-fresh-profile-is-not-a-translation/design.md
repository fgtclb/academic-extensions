## Context

`Profile::getIsTranslation()` returns `$this->_localizedUid !== $this->uid`
(`academic-persons/Classes/Domain/Model/Profile.php:536`). Extbase writes
`_localizedUid` only when the `DataMapper` hydrates a row
(`cms-extbase/Classes/Persistence/Generic/Mapper/DataMapper.php:204`), and
`Backend::insertObject()` sets only `uid` (`Backend.php:580`).

`AbstractProfileFactory::createProfileForUser()` persists the new object and
then dispatches `AfterProfileUpdateEvent` with it
(`Classes/Profile/AbstractProfileFactory.php:99-106`). At that point
`uid = N` and `_localizedUid = null`, so the flag answers `true`.
`SyncChangesToTranslations`
(`academic-persons-edit/Classes/EventListener/SyncChangesToTranslations.php:44`)
therefore returns early.

The only caller of `createProfileForUser()` on `main` is
`ProfileCreateCommandService` (`academic:createprofiles`). The candidate
mentioned profile creation at login, but no such path exists on this branch.

The other readers of the flag, `ProfileController.php:3126`, `:3183` and
`:3317` in `academic_persons_edit`, work on hydrated objects and are
unaffected. Branch `2` has the same code (`Profile.php:538-540`).

## Goals / Non-Goals

**Goals:**

- The model answers correctly for an object that was never hydrated, so every
  caller of the flag is right without knowing how the object was created.

**Non-Goals:**

- Touching the event, the listener or the synchronizer.

## Decisions

### Guard against the unset localized uid in the model

`return $this->_localizedUid !== null && $this->_localizedUid !== $this->uid;`
This is the same as one project's composer patch. An object Extbase never
hydrated has no language overlay, so it cannot be a translation.

Rejected: re-fetching the profile from the repository before the dispatch in
`createProfileForUser()`. That fixes one caller, and the model keeps
answering wrong for every other object built in PHP. Also rejected: setting
`_localizedUid` in the factory, which every other creator of a profile would
have to repeat. Also rejected: `_isNew()`, the first idea in the draft of
ACE-610. It is already `false` after `persistAll()`, so it does not cover the
path that fails.

### No core version split

The persistence internals quoted above are the same on v13 and v14. The fix
is one line in a shared class.

### Decided: the null guard, not `_isNew()`

The fix is the null guard on the localized uid in the model, and the draft
fix of ACE-610 is rewritten to match it. `_isNew()` is `uid === null`, and the
uid is already set when the creation path dispatches, so it does not fire on
the failing path. A re-fetch in the factory would fix one caller and leave the
model answering wrong for every object that was never hydrated.

## Risks / Trade-offs

- [A custom factory relied on the wrong answer to suppress the translation
  sync] → Unlikely, and the behaviour it would have relied on is the defect.
  The `Important-` changelog entry names it.
- [Installations with `allowedLanguages` set get more translation rows on the
  next creation run] → This is the configured intent. The changelog says so.

## Migration Plan

Nothing to migrate. Profiles created earlier without translations get them on
their next `academic:updateprofiles` run, which dispatches the event for
hydrated objects since ACE-490, or on their next edit.

## Open Questions

None.
