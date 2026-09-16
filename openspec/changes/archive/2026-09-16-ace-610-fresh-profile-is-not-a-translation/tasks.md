## 1. Tests

- [x] 1.1 Port the unit tests of the translation flag: complete the two
  existing mapped-record tests with the `_languageUid` the data mapper writes
  alongside, and add the persisted-but-never-mapped, the all-languages and
  the unpersisted-with-language cases.
- [x] 1.2 Port the functional test of the creation path: the profile built,
  persisted and announced the way `AbstractProfileFactory` does it leaves a
  translated profile row behind. Show it fails without the change on v12 and
  v13.
- [x] 1.3 Port the functional model test pinning what persisting a model
  built in PHP leaves behind.

## 2. Implementation

- [x] 2.1 Answer the translation flag from the language of the record, behind
  an `_isNew()` guard, and verify the ported tests on v12 and v13. Restoring
  the uid comparison on v12 turns all three new guards red - the unit case
  `aPersistedRecordThatWasNeverMappedIsNotATranslation`, the functional model
  test and the listener test - while the characterising unit tests stay green.

## 3. Documentation

- [x] 3.1 Add
  `academic-persons/Documentation/Changelog/2.4/Important-CreatedProfilesAreSynchronizedIntoTranslations.rst`.
- [x] 3.2 `docs/` on this branch has no translation synchronisation page, so
  there is nothing to extend there. State that rather than inventing one.

## 4. Definition of done

- [x] 4.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v12 (PHP 8.1) and v13 (PHP 8.2), each after its own `composerUpdate`.
- [x] 4.2 `functional` also on PostgreSQL for the touched test classes.
- [x] 4.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 4.4 Archive the change as the last commit of the pull request.
