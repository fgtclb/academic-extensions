## 1. Prerequisite

- [x] 1.1 Verify `ace-610-fresh-profile-is-not-a-translation` is merged. A
  backend-created profile must not report itself as a translation.

## 2. Event and origin

- [x] 2.1 Add the origin enum, with the `Import` case from the start, and the
  optional site and origin arguments to the profile update event.
  Unit-test the defaults and the getters. The getters test fails before the
  change (the method does not exist).
- [x] 2.2 Pass the origin, and the site where one is known, at the five
  existing dispatch sites. Extend
  `academic-persons-edit/Tests/Functional/EventListener/SyncChangesToTranslationsSiteTest.php`
  so that the event's site wins over the global request. Show it fails
  before the listener change.

## 3. Backend dispatch and guard

- [x] 3.1 Add a functional test,
  `academic-persons-edit/Tests/Functional/Hook/BackendSaveAnnouncementTest.php`,
  with `academic_persons_edit` loaded and one allowed language: a DataHandler
  update of `last_name` updates the translation and keeps the slug. Show it
  fails on the unchanged hook.
- [x] 3.2 Add a counting listener fixture. A backend save dispatches exactly
  once per default-language profile, a save of only a translation dispatches
  nothing, and a three-profile datamap dispatches three times.
- [x] 3.3 Implement the after-all-operations dispatch. Verify 3.1 and 3.2 on
  v13 and v14.
- [x] 3.4 Mark the runs of the synchronizer and the image writer with the
  internal correlation aspect (the image repair wizard writes through the
  writer), and skip such runs in the hook. Prove
  the guard: temporarily remove it and watch the counting listener report
  more than one dispatch for a backend save and for an editor image upload.
  Then restore it.
- [x] 3.5 Skip the image metadata listener for backend and import origin
  events. Verify with the existing `ProfileImageMetadataServiceTest` that
  metadata is still written once.
- [x] 3.6 Skip the slug listener for backend-origin events, and make the
  regenerated slug unique by the column's `eval` rules. Show that a second
  profile of the same name in the folder gets a unique slug after an import,
  and that a slug set by hand survives a backend save; both fail before.
- [x] 3.7 Add the import correlation aspect next to the internal one. A
  DataHandler run carrying it dispatches once per profile with origin
  `Import`, a run without it with origin `Backend`, and both synchronise the
  translation. Show the `Import` assertion fails before the mark is read.

## 4. Documentation

- [x] 4.1 Add
  `academic-persons/Documentation/Changelog/3.0/Feature-ProfileUpdateEventCarriesSiteAndOrigin.rst`
  and `Important-BackendSavesAnnounceProfileUpdates.rst`. The first one names
  the `Import` origin and how an import marks its run; the second one names
  project hooks that must be removed and the bulk import cost.
- [x] 4.2 Add
  `academic-persons-edit/Documentation/Changelog/3.0/Important-TranslationsFollowBackendSaves.rst`,
  which also names the slug that stays after a backend save and the unique
  slug everywhere else.
- [x] 4.3 Update the dispatch sites, the internal and import marks and the site
  source in
  `docs/architecture/translation-synchronization.md`. Verify with
  `lintMarkdown -n`.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core format as
  `[FEATURE] ACE-<NNN>: <subject>`.

## 6. Definition of done

- [x] 6.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [x] 6.2 `functional` also on PostgreSQL for the hook and listener tests,
  because they write.
- [x] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.4 `docs/` and both extensions' `Documentation/` changelogs updated in
  the same change.
- [x] 6.5 Archive the change as the last commit of the pull request.
