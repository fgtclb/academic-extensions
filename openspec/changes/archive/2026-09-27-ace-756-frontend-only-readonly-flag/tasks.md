## 1. Backport analysis

- [x] 1.1 Diff every file of the `main` change against this branch. The flags
  are read in `AcademicPersonsSettingsFactory` of `academic_persons`, with the
  same computation the normaliser on `main` has. There is no validation
  normaliser in `academic_base`, no expanded document map form and no legacy
  settings migrator. The six TCA files and the domain factories of the editor
  read the `Validation` as on `main`. The form partials put `readonly` on a
  select and a checkbox, which browsers ignore, so those stay operable (filed
  as ACE-757).

## 2. Settings factory and tests

- [x] 2.1 Add a unit test of the settings factory with a fixture settings
  file: the flag alone, in mixed case, with `readonly`, with `disabled`, with
  `required` and with `email`, and a plain `required` as a control.
- [x] 2.2 Add `FrontendReadOnlyFlagTest` in `academic_persons` with the
  fixture extension `test_frontend_readonly`, which restates the shipped
  `validations` map and locks the profile website, the contract position and
  room and the address street for the frontend only, and the middle name
  together with `readonly`.
- [x] 2.3 Add `AcademicPersonsEditFrontendReadOnlyFieldTest` in
  `academic_persons_edit`: the website renders `readonly`, and a submitted
  website is ignored while the website title of the same request is stored.
- [x] 2.4 Recognise the flag in the settings factory as on `main`.

## 3. Documentation

- [x] 3.1 Add the flag to the flag list of `Settings.yaml`, to the
  Validations page of the `academic_persons` manual and to the general
  configuration page of the `academic_persons_edit` manual.
- [x] 3.2 Add the changelog file to
  `academic-persons/Documentation/Changelog/2.4/`, byte-identical to the one
  on `main`.
- [x] 3.3 Update `docs/architecture/validation-settings.md`,
  `docs/architecture/form-data-transformation.md` and the fixture table in
  `docs/testing/fixture-extensions.md`.

## 4. Definition of done

- [x] 4.1 `lintPhp` green.
- [x] 4.2 After `-t 12 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 12`.
- [x] 4.3 After `-t 13 -s composerUpdate`: `cgl -n`, `phpstan`, `unit` and
  `functional` green with `-t 13`, and `functional` on PostgreSQL for the
  editor test, because it writes.
- [x] 4.4 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 4.5 Commit message in TYPO3 Core format with the verified ACE-756
  reference.
- [x] 4.6 Archive the change as the last commit of the pull request.
