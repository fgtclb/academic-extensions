## 1. Settings

- [ ] 1.1 Accept `custom: true` profile fields in the settings graph, check the
  column against the profile TCA, and refuse system and mapped columns; unit
  and functional tests for each refusal, shown red with the check removed.
- [ ] 1.2 Rewrite the header comment of
  `academic-persons/Configuration/AcademicPersons/Settings.yaml` that says the
  file creates no columns or properties.

## 2. Editor

- [ ] 2.1 Add a fixture extension with `tx_test_prefix` (SQL and TCA) and a
  functional test posting it through `update`; it answers
  `invalid_profile_data` today, which is the red run.
- [ ] 2.2 Carry project values in the form data object and validate them with
  the regular pass; functional test that an invalid project value stores
  nothing.
- [ ] 2.3 Write the values through DataHandler after the Extbase save; assert
  the column on the default row and, for a language-1 edit, on the
  translation row, and a `sys_history` entry; shown red without the write.
- [ ] 2.4 Render project fields with the configured renderer and show the
  stored value; rendering test on v13 and v14.

## 3. Documentation

- [ ] 3.1 Extend `docs/architecture/form-data-transformation.md` and
  `docs/architecture/validation-settings.md` with project fields.
- [ ] 3.2 Document project fields in `academic-persons-edit/Documentation/` and
  add `Documentation/Changelog/3.0/Feature-ProjectProfileFields.rst`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-editor-custom-profile-fields`, and commit as
  `[FEATURE] ACE-<NNN>: Edit project profile columns` in TYPO3 Core format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14; the write tests also with `-d postgres`.
- [ ] 5.2 `checkJsBuildClean`, `testJs`, `lintMarkdown -n` and
  `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
