## 1. Event

- [x] 1.1 Add `ProfileEditingAction` and `BeforeProfileEditingWriteEvent`
  below `Classes/Event/`. Unit tests cover refusal, propagation stop and the
  `\LogicException` of `setFields()` on an action without fields.

## 2. Dispatch

- [x] 2.1 Add a fixture extension with a configurable listener (see
  `docs/testing/fixture-extensions.md`) and a functional test that refuses
  `createDocument` for `vita`: 422 `write_refused` and no row. Show it red
  before the dispatch exists (row created).
- [x] 2.2 Dispatch in all fifteen mutating endpoints after validation. A data
  provider test asserts the action, section, record and fields reported for
  each JSON endpoint, and that a refusal stores nothing. The image removal and
  the upload (TYPO3 v14 only, like every upload test) have tests of their own.
- [x] 2.3 Re-validate replaced values. Functional tests store a changed title,
  answer an emptied required value with a validation error and sanitise a
  replaced rich text, shown red by skipping the second validation. A locked
  field a listener adds is dropped.
- [x] 2.4 Functional tests for a foreign profile, invalid values and an
  incomplete order assert the refusal and no listener call, and the contract
  contact action test asserts it for an action the section does not allow.
  Shown red by dispatching before the validation.

## 3. Editor

- [x] 3.1 The TypeScript already shows the message of a refused request as
  text on every path, so nothing is rebuilt. `write-refused.test.ts` covers
  the profile field, the document and the contact editor, shown red without
  the handling and with the message written as markup.

## 4. Documentation

- [x] 4.1 Describe the event in `docs/architecture/form-data-transformation.md`
  and `docs/architecture/profile-editing-contract.md`, and add `Before…Event`
  to the event rules of `docs/architecture/class-design.md`.
- [x] 4.2 Add an event reference with an example listener to
  `academic-persons-edit/Documentation/Developers/`, the event and its enum to
  the extension points page of `academic_base`, and
  `Documentation/Changelog/3.0/Feature-BeforeProfileEditingWriteEvent.rst`.

## 5. File the issue

- [x] 5.1 After implementation, file a new `[3.x]` ACE issue (Version 3.0.0)
  in YouTrack, linked to ACE-445 as related. ACE-445 stays on branch `2`.
  Filed as ACE-762. Rename the change to `ace-762-editor-before-write-event`,
  and commit as `[FEATURE] ACE-762: Add an event before editor writes` in
  TYPO3 Core format.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14. The write tests also with `-d postgres`.
- [x] 6.2 `checkJsBuildClean`, `testJs`, `lintMarkdown -n` and
  `checkRstRenderingAll` green.
- [x] 6.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.4 Archive the change as the last commit of the pull request.
