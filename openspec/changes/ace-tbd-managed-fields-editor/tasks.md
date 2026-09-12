## 1. Prerequisite

- [ ] 1.1 Confirm `ace-tbd-managed-fields-backend` is merged and its resolver
  is available; stop otherwise.

## 2. Server side

- [ ] 2.1 Pass the managed property names into `mayApplyProperty()` of all six
  factories; functional test posting a changed synchronised e-mail keeps the
  stored address and stores the type, shown red by passing an empty list.
- [ ] 2.2 Refuse delete of a managed contract or contact and edit of a fully
  managed row with the existing 403 codes; functional tests for a synchronised
  and a manual phone number, shown red with the check removed.

## 3. Rendering

- [ ] 3.1 Serialise the managed marker into the field descriptors and drop
  `delete`/`edit` from the row actions; render the marker in the TypeScript
  editor, rebuild with `buildJs` and cover it in `testJs`, shown red without
  the marker.
- [ ] 3.2 Functional rendering test asserts the marker and the missing delete
  button for a synchronised row and their absence for a `skip_sync` profile.

## 4. Documentation

- [ ] 4.1 Extend `docs/architecture/profile-editing-contract.md` with the
  managed marker and the refused actions.
- [ ] 4.2 Document the behaviour in `academic-persons-edit/Documentation/` and
  add `Documentation/Changelog/3.0/Feature-EditorHonoursManagedFields.rst`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-managed-fields-editor`, and commit as
  `[FEATURE] ACE-<NNN>: Honour managed fields in the editor` in TYPO3 Core
  format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14; the write tests also with `-d postgres`.
- [ ] 6.2 `checkJsBuildClean`, `testJs`, `lintMarkdown -n` and
  `checkRstRenderingAll` green.
- [ ] 6.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.4 Archive the change as the last commit of the pull request.
