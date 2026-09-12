## 1. Prerequisite

- [ ] 1.1 Verify `ace-tbd-fresh-profile-is-not-a-translation` is merged. A
  backend-created profile must not report itself as a translation.

## 2. Event and origin

- [ ] 2.1 Add the origin enum, with the `Import` case from the start, and the
  optional site and origin arguments to the profile update event.
  Unit-test the defaults and the getters. The getters test fails before the
  change (the method does not exist).
- [ ] 2.2 Pass the origin, and the site where one is known, at the five
  existing dispatch sites. Extend
  `academic-persons-edit/Tests/Functional/EventListener/SyncChangesToTranslationsSiteTest.php`
  so that the event's site wins over the global request. Show it fails
  before the listener change.

## 3. Backend dispatch and guard

- [ ] 3.1 Add a functional test to
  `academic-persons/Tests/Functional/Hook/DataHandlerHooksTest.php`, with
  `academic_persons_edit` loaded and one allowed language: a DataHandler
  update of `last_name` updates the translation and regenerates the slug.
  Show it fails on the unchanged hook.
- [ ] 3.2 Add a counting listener fixture. A backend save dispatches exactly
  once per default-language profile, a save of only a translation dispatches
  nothing, and a three-profile datamap dispatches three times.
- [ ] 3.3 Implement the after-all-operations dispatch. Verify 3.1 and 3.2 on
  v13 and v14.
- [ ] 3.4 Set the internal correlation scope in the synchronizer, the image
  writer and the image repair wizard, and skip such runs in the hook. Prove
  the guard: temporarily remove it and watch the counting listener report
  more than one dispatch for a backend save and for an editor image upload.
  Then restore it.
- [ ] 3.5 Skip the image metadata listener for backend-origin events. Verify
  with the existing `ProfileImageMetadataServiceTest` that metadata is still
  written once.
- [ ] 3.6 Add the import correlation scope next to the internal one. A
  DataHandler run carrying it dispatches once per profile with origin
  `Import`, a run without it with origin `Backend`, and both synchronise the
  translation. Show the `Import` assertion fails before the scope is read.

## 4. Documentation

- [ ] 4.1 Add
  `academic-persons/Documentation/Changelog/3.0/Feature-ProfileUpdateEventCarriesSiteAndOrigin.rst`
  and `Important-BackendSavesAnnounceProfileUpdates.rst`. The first one names
  the `Import` origin and how an import sets its scope; the second one names
  project hooks that must be removed and the bulk import cost.
- [ ] 4.2 Add
  `academic-persons-edit/Documentation/Changelog/3.0/Important-TranslationsFollowBackendSaves.rst`.
- [ ] 4.3 Update the dispatch sites, the guard and import scopes and the site
  source in
  `docs/architecture/translation-synchronization.md`. Verify with
  `lintMarkdown -n`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core format as
  `[FEATURE] ACE-<NNN>: <subject>`.

## 6. Definition of done

- [ ] 6.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 6.2 `functional` also on PostgreSQL for the hook and listener tests,
  because they write.
- [ ] 6.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.4 `docs/` and both extensions' `Documentation/` changelogs updated in
  the same change.
- [ ] 6.5 Archive the change as the last commit of the pull request.
