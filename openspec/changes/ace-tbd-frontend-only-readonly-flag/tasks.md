## 1. Tests first

- [ ] 1.1 Extend `academic-base/Tests/Unit/Settings/ValidationNormalizerTest.php`:
  - `['frontendreadonly']` gives `readOnly = true` and
    `tcaConfig['readOnly'] = false`;
  - mixed case behaves the same;
  - `['frontendreadonly', 'readonly']` gives `tcaConfig['readOnly'] = true`;
  - `['frontendreadonly', 'required']` keeps the TCA `required` and has no
    `NotEmpty` validator.

  Show the first case fails before the change (the flag has no effect).
- [ ] 1.2 Add a functional test in `academic_persons` with a fixture
  `Settings.yaml` marking a profile field `frontendreadonly`. The loaded TCA
  column has no read-only lock. Show that it passes both before and after the
  change, and that 1.1 is the one that goes red.
- [ ] 1.3 Add a functional test in `academic_persons_edit`: the field renders
  read-only and a submitted value is not persisted. Show it fails before the
  change (the value is saved).

## 2. Implementation

- [ ] 2.1 Recognise the flag in the normaliser as decided in `design.md`.
  Verify 1.1 to 1.3 on v13 and v14.

## 3. Documentation

- [ ] 3.1 Add the flag to the validator list in
  `academic-persons/Configuration/AcademicPersons/Settings.yaml` and to
  `Documentation/Configuration/Validations/Index.rst`.
- [ ] 3.2 Add
  `academic-persons/Documentation/Changelog/3.0/Feature-FrontendReadOnlyValidationFlag.rst`
  and a short `academic-base/Documentation/Changelog/3.0/Feature-FrontendReadOnlyValidationFlag.rst`.
  Verify both render.
- [ ] 3.3 Update the flag list in `docs/architecture/validation-settings.md`.
  Verify `lintMarkdown -n`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-<slug>`, and commit in TYPO3 Core format as
  `[FEATURE] ACE-<NNN>: <subject>`.
- [ ] 4.2 Backport: a separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`). There the flags are evaluated in
  `academic_persons`' settings factory, not in `academic_base`.

## 5. Definition of done

- [ ] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [ ] 5.2 `functional` also on PostgreSQL for the editor test, because it
  writes.
- [ ] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.4 `docs/` and both extensions' `Documentation/` changelogs updated in
  the same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
