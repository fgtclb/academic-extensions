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

### Answer the flag from the language of the record

```php
if ($this->_isNew()) {
    return false;
}
return $this->getLanguageUid() > 0;
```

The flag asks about language, so the model reads the language rather than
deriving it from two uids. A record that was never persisted is a translation
of nothing; a persisted one is a translation when the language it was read in
is a translation language.

`_isNew()` rather than a bare language check is deliberate: until the object
is inserted, its `_languageUid` is not the language of any row.
`Backend::insertObject()` writes `0` onto an object that carries no language,
and leaves one that does although the row it writes does not get it — so an
unpersisted object is answered from the fact that it has no record yet, not
from a language no record has.

Rejected: re-fetching the profile from the repository before the dispatch in
`createProfileForUser()`. That fixes one caller, and the model keeps
answering wrong for every other object built in PHP. Also rejected: setting
`_localizedUid` in the factory, which every other creator of a profile would
have to repeat. Also rejected: the null guard on the localized uid
(`$this->_localizedUid !== null && $this->_localizedUid !== $this->uid`),
which one project carries as a composer patch — see the decision below.

### No core version split

The persistence internals quoted above are the same on v13 and v14. The fix
is one line in a shared class.

### Decided during the implementation: the language, not the null guard

The plan was the null guard on the localized uid, and the implementation
replaced it. The guard repairs the comparison for one input and leaves the
method answering a question about *language* from two uids, which is what
makes `sys_language_uid = -1` wrong as well: a record kept in all languages is
not a translation either, and `-1` passes a "differs from the default
language" test.

The concern that ruled `_isNew()` out while planning — it is already `false`
after `persistAll()`, so on its own it does not cover the failing path — still
holds and is why it is a guard in front of the language check rather than the
answer. After `persistAll()` the created profile falls through to
`getLanguageUid()`, which is `0`, so the path that failed is covered.

| Record shape                         | before | null guard | implemented |
|--------------------------------------|--------|------------|-------------|
| never persisted                      | false  | false      | false       |
| persisted in PHP, never mapped       | true   | false      | false       |
| mapped, `sys_language_uid = 0`       | false  | false      | false       |
| mapped language overlay              | true   | true       | true        |
| mapped, `sys_language_uid = -1`      | true   | true       | false       |

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
