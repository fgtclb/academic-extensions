## 1. Event

- [ ] 1.1 Add `ProfileEditingAction` and `BeforeProfileEditingWriteEvent`
  below `Classes/Event/`; unit tests cover refusal, propagation stop and the
  `\LogicException` of `setFields()` on an action without fields.

## 2. Dispatch

- [ ] 2.1 Add a fixture extension with a configurable listener (see
  `docs/testing/fixture-extensions.md`) and a functional test that refuses
  `createDocument` for `vita`: 422 `write_refused` and no row; show it red
  before the dispatch exists (row created).
- [ ] 2.2 Dispatch in all fourteen mutating endpoints after validation; a data
  provider test asserts the action and record reported for each endpoint.
- [ ] 2.3 Re-validate replaced values; functional tests store a changed title
  and answer an emptied required value with a validation error, shown red by
  skipping the second validation.
- [ ] 2.4 Functional test for a foreign profile asserts 403 and no listener
  call.

## 3. Editor

- [ ] 3.1 Show the `write_refused` reason as text in the TypeScript editor,
  rebuild with `buildJs`, and cover it in `testJs`, shown red without the
  handling.

## 4. Documentation

- [ ] 4.1 Describe the event in `docs/architecture/form-data-transformation.md`
  and `docs/architecture/profile-editing-contract.md`.
- [ ] 4.2 Add an event reference with an example listener to
  `academic-persons-edit/Documentation/` and
  `Documentation/Changelog/3.0/Feature-BeforeProfileEditingWriteEvent.rst`.

## 5. File the issue

- [ ] 5.1 After implementation, file a new `[3.x]` ACE issue (Version 3.0.0)
  in YouTrack, linked to ACE-445 as related; leave ACE-445 on branch `2`.
  Rename the change to `ace-<NNN>-editor-before-write-event`, and commit as
  `[FEATURE] ACE-<NNN>: Dispatch an event before editor writes` in TYPO3 Core
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
