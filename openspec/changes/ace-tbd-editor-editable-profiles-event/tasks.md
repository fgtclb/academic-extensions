## 1. Event and service

- [ ] 1.1 Add `ModifyEditableProfilesEvent` and
  `ProfileUpdateRequestService::getEditableProfiles()`; unit tests cover
  de-duplication, order and the missing dispatch without login, shown red by
  dispatching unconditionally.
- [ ] 1.2 Route `listAction()`, `indexAction()` and `validate()` through the
  new method.

## 2. Authorisation tests

- [ ] 2.1 Fixture extension with a listener granting a foreign profile; the
  edit page answers 200 and a write is accepted. Today it answers 403
  (`AcademicPersonsEditProfileEditingAuthorizationTest`), which is the red
  run.
- [ ] 2.2 Listener removing a linked profile: the list omits it, the edit page
  is denied and every endpoint family answers 403; shown red by bypassing the
  event in `listAction()`.
- [ ] 2.3 A visitor without login gets 401 and the listener is not called.

## 3. Documentation

- [ ] 3.1 Extend `docs/architecture/form-data-transformation.md` where it
  describes the ownership check.
- [ ] 3.2 Document the event with its security note in
  `academic-persons-edit/Documentation/` and add
  `Documentation/Changelog/3.0/Feature-ModifyEditableProfilesEvent.rst`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-editor-editable-profiles-event`, and commit as
  `[FEATURE] ACE-<NNN>: Let listeners decide editable profiles` in TYPO3 Core
  format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
