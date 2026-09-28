## 1. Settings

- [x] 1.0 Start after `ace-763-settings-tca-after-overrides` is merged.
- [x] 1.1 Accept `custom: true` profile fields in the settings graph. The TCA
  listener merges an allowed project column and raises an `E_USER_DEPRECATED`
  notice for a missing, system, model or non-scalar column. The editor fails
  with a message naming it. Unit tests for each refusal, functional tests for
  a missing and a system column, shown red with the check removed.
- [x] 1.2 Rewrite the header comment of
  `academic-persons/Configuration/AcademicPersons/Settings.yaml` that says the
  file creates no columns or properties.

## 2. Editor

- [x] 2.1 Add a fixture extension with `tx_test_prefix` (SQL and TCA) and a
  functional test posting it through `update`; it answers
  `invalid_profile_data` today, which is the red run.
- [x] 2.2 Carry project values in the form data object and validate them with
  the regular pass; functional test that an invalid project value stores
  nothing.
- [x] 2.3 Write the values through DataHandler after the Extbase save. Assert
  the column on the default row and, for a language-1 edit, on the
  translation row, and a `sys_history` entry, shown red without the write.
- [x] 2.4 Render project fields with the configured renderer and show the
  stored value; rendering test on v13 and v14.

## 3. Documentation

- [x] 3.1 Extend `docs/architecture/form-data-transformation.md` and
  `docs/architecture/validation-settings.md` with project fields.
- [x] 3.2 Document project fields in `academic-persons-edit/Documentation/` and
  add `Documentation/Changelog/3.0/Feature-ProjectProfileFields.rst`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-editor-custom-profile-fields`, and commit as
  `[FEATURE] ACE-<NNN>: Edit project profile columns` in TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14; the write tests also with `-d postgres`.
- [x] 5.2 `checkJsBuildClean`, `testJs`, `lintMarkdown -n` and
  `checkRstRenderingAll` green.
- [x] 5.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [x] 5.4 Archive the change as the last commit of the pull request.
