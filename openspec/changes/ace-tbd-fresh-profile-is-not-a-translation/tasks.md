## 1. Tests first

- [ ] 1.1 Add a unit test in `academic-persons/Tests/Unit/Domain/Model/`: a
  new `Profile` whose uid is set to 42 through `_setProperty()` reports
  `false` for the translation flag. Run it against the unchanged model and
  record that it fails.
- [ ] 1.2 Add a functional test next to
  `Tests/Functional/Service/ProfileCreateCommandService/`: with
  `academic_persons_edit` loaded and `profile.allowedLanguages` set to one
  site language, `academic:createprofiles` leaves a translated profile row
  behind. Show that it fails on the unchanged model (no row for the
  language).
- [ ] 1.3 Keep a hydrated translation reporting `true`. Extend an existing
  translation test, or add one, and verify it passes both before and after
  the change.

## 2. Implementation

- [ ] 2.1 Add the null guard to the translation flag of the profile model and
  verify 1.1 and 1.2 turn green on TYPO3 v13 and v14.

## 3. Documentation

- [ ] 3.1 Add
  `academic-persons/Documentation/Changelog/3.0/Important-NewProfilesAreTranslatedOnCreation.rst`
  from `Build/Documentation/Templates/`. It covers the new rows for
  installations with allowed languages and how existing profiles catch up.
  Verify the changelog index includes it.
- [ ] 3.2 Add the creation path to the dispatch sites in
  `docs/architecture/translation-synchronization.md` and verify the page
  still passes `lintMarkdown -n`.

## 4. File the issue

- [ ] 4.1 After implementation, file or confirm the ACE issue in YouTrack
  (ACE-610 exists; rewrite its draft fix from `_isNew()` to the null guard),
  rename the change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core format
  as `[BUGFIX] ACE-<NNN>: <subject>`.
- [ ] 4.2 Backport: a separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`). The model is identical there.

## 5. Definition of done

- [ ] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 5.2 `functional` also on PostgreSQL for the creation test, because it
  writes.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and the extension's `Documentation/` changelog updated in
  the same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
