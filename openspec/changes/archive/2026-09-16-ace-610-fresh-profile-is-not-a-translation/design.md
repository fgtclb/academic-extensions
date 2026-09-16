## Context

Backport analysis of the `main` change (ACE-610, #619) against this branch:

- `Profile::getIsTranslation()` is byte-identical here (`Profile.php:538-540`),
  and so is `getLanguageUid()` right above it. The patch carries over
  unchanged.
- `SyncChangesToTranslations::__invoke()` gates on the flag in the same place
  and with the same comment, so the defect has the same effect.
- `AbstractProfileFactory::createProfileForUser()` persists the profile and
  dispatches `AfterProfileUpdateEvent` with the object it just built, exactly
  as on `main`. The per-profile event of the update run (ACE-490) does not
  exist here and is not involved.
- The unit tests of the flag exist here in their pre-ACE-610 shape and are
  ported rather than rewritten.
- `sbuerk/typo3-site-based-test-trait` is a dependency of this branch too, and
  the persons-edit functional test case extends the same SBUERK
  `FunctionalTestCase`, so the new listener test takes the shape it has on
  `main`.
- `docs/` has no translation synchronisation page on this branch — that page
  is `main`-only. There is nothing to extend, so this change touches no
  `docs/` page.

## Decisions

### The same answer as on `main`

```php
if ($this->_isNew()) {
    return false;
}
return $this->getLanguageUid() > 0;
```

`_isNew()` and the Extbase internals the answer relies on are the same on
TYPO3 v12 as on v13: only `DataMapper::thawProperties()` writes
`_localizedUid` and `_languageUid` from a row, and `Backend::insertObject()`
assigns `uid` and — when the object carries no language — `_languageUid` as
`0`. Until that insert, the language on the object is not the language of any
row, which is why the `_isNew()` guard sits in front of the language check
rather than being replaced by it.

### No core version split

Nothing in the fix or in its tests is version dependent, so no
`Core12/`/`Core13` folder and no `not-core-*` group is involved.

## Risks / Trade-offs

- [A custom factory relied on the wrong answer to suppress the translation
  sync] → Unlikely, and the behaviour it would have relied on is the defect.
  The `Important-` changelog entry names it.
- [Installations with `allowedLanguages` set get more translation rows on the
  next creation run] → This is the configured intent. The changelog says so.

## Migration Plan

Nothing to migrate. Profiles created earlier without translations get them on
their next `academic:updateprofiles` run or on their next edit.

## Open Questions

None.
