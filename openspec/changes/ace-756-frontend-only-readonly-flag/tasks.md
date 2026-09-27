## 1. Tests first

- [x] 1.1 Extend `academic-base/Tests/Unit/Settings/ValidationNormalizerTest.php`:
  - `['frontendreadonly']` gives `readOnly = true` and
    `tcaConfig['readOnly'] = false`
  - mixed case behaves the same
  - `['frontendreadonly', 'readonly']` and `['disabled', 'frontendreadonly']`
    give `tcaConfig['readOnly'] = true`
  - `['frontendreadonly', 'required']` keeps the TCA `required` and
    `minitems` and has no `NotEmpty` validator
  - `['email', 'frontendreadonly']` keeps the email validator and TCA type.

  Shown to fail before the change: the four data sets without `readonly` or
  `disabled` go red when the flag is not recognised.
- [x] 1.2 Add `academic-persons/Tests/Functional/Tca/FrontendReadOnlyFlagTest.php`
  with the fixture extension `test_frontend_readonly`, which marks the profile
  title, the contract position and room and the address street, and the
  middle name together with `readonly`. The loaded TCA columns have no
  read-only lock and keep `required`, the middle name stays locked, and the
  settings report the fields read-only and not required. The TCA assertions
  pass with and without the change, the two settings tests go red without it.
- [x] 1.3 Add `academic-persons-edit/Tests/Functional/Plugins/AcademicPersonsEditFrontendReadOnlyFieldTest.php`:
  the title renders `readonly`, a submitted title and a submitted contract
  room are refused and not stored, and an unlocked field of the same contract
  is still stored. Shown to fail before the change: three tests go red, the
  values are saved.
- [x] 1.4 Extend the settings factory and legacy migrator unit tests: the
  expanded map form `frontendreadonly: true` of a document field, and a
  legacy `validations` map that lists the flag for one field while a section
  map lists it for a field the legacy map leaves out. Both shown to fail
  without their part of 2.2.

## 2. Implementation

- [x] 2.1 Recognise the flag in the normaliser as decided in `design.md`.
  Verified 1.1 to 1.3 on v13 and v14.
- [x] 2.2 Add the flag to the document flags of the expanded map form and to
  the flags the legacy migrator decides, as decided in `design.md`.

## 3. Documentation

- [x] 3.1 Add the flag to the validator list in
  `academic-persons/Configuration/AcademicPersons/Settings.yaml`, to
  `Documentation/Configuration/Validations/Index.rst` (flag table, note,
  backend and frontend effects, an override example, the legacy mapping) and
  to the two flag lists of the `academic-persons-edit` manual.
- [x] 3.2 Add
  `academic-persons/Documentation/Changelog/2.4/Feature-FrontendReadOnlyValidationFlag.rst`,
  the same entry the backport adds on branch `2`, and a short
  `academic-base/Documentation/Changelog/3.0/Feature-FrontendReadOnlyValidationFlag.rst`.
  Verify both render.
- [x] 3.3 Update the flag list, the normalisation section and the legacy
  overlay in `docs/architecture/validation-settings.md`, rule 1 in
  `docs/architecture/form-data-transformation.md`, and the fixture table in
  `docs/testing/fixture-extensions.md`, which also gets the four fixture
  extensions ACE-50 and ACE-754 did not list. Verify `lintMarkdown -n`.

## 4. File the issue

- [x] 4.1 File the ACE issue in YouTrack (ACE-756), rename the change to
  `ace-756-frontend-only-readonly-flag`, and commit in TYPO3 Core format as
  `[FEATURE] ACE-756: <subject>`.
- [x] 4.2 Backport: a separate change on branch `2` after a backport analysis
  (`docs/workflow/backporting.md`). There the flags are evaluated in
  `academic_persons`' settings factory, not in `academic_base`. The select and
  checkbox controls of that branch ignore `readonly` in the browser, filed as
  ACE-757.

## 5. Definition of done

- [x] 5.1 `lintPhp`, `cgl -n`, `phpstan`, `unit` and `functional` green for
  TYPO3 v13 and v14, each after its own `composerUpdate`.
- [x] 5.2 `functional` also on PostgreSQL for the editor test, because it
  writes.
- [x] 5.3 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.4 `docs/` and both extensions' `Documentation/` changelogs updated in
  the same change.
- [ ] 5.5 Archive the change as the last commit of the pull request.
