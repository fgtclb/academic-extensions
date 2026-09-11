## 1. Tests first

- [x] 1.1 Add a unit test in `academic-persons/Tests/Unit/Domain/Model/`: a
  new `Profile` whose uid is set to 42 through `_setProperty()` reports
  `false` for the translation flag. Run it against the unchanged model and
  record that it fails.
- [x] 1.2 Add a functional test for the creation path: with
  `academic_persons_edit` loaded and `profile.allowedLanguages` set to one
  site language, the profile the factory builds, persists and announces
  leaves a translated profile row behind. Show that it fails on the unchanged
  model (no row for the language). It is placed next to the other listener
  tests, `academic-persons-edit/Tests/Functional/EventListener/`, and hands
  the object to the real listener rather than running the command, so it pins
  the gate against that object state and nothing else. A second functional
  test in `academic-persons/Tests/Functional/Domain/Model/ProfileTest.php`
  pins what persisting a model built in PHP leaves behind.
- [x] 1.3 Keep a hydrated translation reporting `true`. The two existing unit
  tests for the mapped shapes were completed with the `_languageUid` the data
  mapper always writes alongside — one of them asserts `true` and would
  otherwise have passed for the wrong reason — and two cases were added for
  the new boundaries (`sys_language_uid = -1`, and an unpersisted object
  carrying a language).

## 2. Implementation

- [x] 2.1 Answer the translation flag of the profile model from the language
  of the record, behind an `_isNew()` guard, and verify 1.1 and 1.2 turn
  green on TYPO3 v13 and v14. The null guard the plan carried was replaced
  during the implementation; `design.md` records why.

## 3. Documentation

- [x] 3.1 Add
  `academic-persons/Documentation/Changelog/3.0/Important-CreatedProfilesAreSynchronizedIntoTranslations.rst`
  from `Build/Documentation/Templates/`. It covers the new rows for
  installations with allowed languages and how existing profiles catch up.
  The `3.0` index picks entries up by glob, so it needs no edit.
- [x] 3.2 `docs/architecture/translation-synchronization.md` already lists the
  creation path among the dispatch sites; document the translation gate of
  the listener there instead, since that is the gate this change moves.
  Verify the page still passes `lintMarkdown -n`.

## 4. File the issue

- [x] 4.1 After implementation, file or confirm the ACE issue in YouTrack
  (ACE-610 exists; its draft fix is superseded by the language answer),
  rename the change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core format
  as `[BUGFIX] ACE-<NNN>: <subject>`.
- [ ] 4.2 Backport: a separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`). The model is identical there.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [x] 5.2 `functional` also on PostgreSQL for the creation test, because it
  writes.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and the extension's `Documentation/` changelog updated in
  the same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
