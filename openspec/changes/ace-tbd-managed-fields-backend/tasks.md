## 1. Settings

- [ ] 1.1 Add the `managedFields` map to the settings graph of
  `academic_persons` with an empty default, resolve identifiers through the existing field
  descriptors and fail the build on an unknown identifier; cover defaults,
  resolution and the failure in unit tests and show the failure test red by
  removing the check.
- [ ] 1.2 Document the key in the header comment of
  `Configuration/AcademicPersons/Settings.yaml`.

## 2. Resolver

- [ ] 2.1 Add the stateless `ManagedFieldResolver`; functional tests cover a
  synchronised contract, a contract without identifier, a `skip_sync` profile,
  a hidden profile, a translation row and an e-mail two levels below the
  profile; show each red by returning all fields unconditionally.

## 3. Backend form

- [ ] 3.1 Register the FormEngine data provider for `tcaDatabaseRecord` after
  reading the provider order in both vendor trees, and add the hint label in
  English and German (XLF on one line, two-space indent).
- [ ] 3.2 Functional test compiling a contract form with import identifier
  `fe_users:1` asserts `position` read-only with the hint, and without the
  identifier editable; show it red with the provider unregistered, on v13 and
  v14.

## 4. Documentation

- [ ] 4.1 Add a section on managed fields to
  `docs/architecture/validation-settings.md` (or a new page linked from
  `docs/architecture/Index.md`) that separates the managed lock from the
  global `readonly` flag.
- [ ] 4.2 Document `managedFields` in the configuration chapter of
  `academic-persons/Documentation/` and add
  `Documentation/Changelog/3.0/Feature-ManagedFieldsReadOnlyInTheBackend.rst`.

## 5. File the issue

- [ ] 5.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-managed-fields-backend`, and commit as
  `[FEATURE] ACE-<NNN>: Lock managed fields in the backend` in TYPO3 Core
  format.

## 6. Definition of done

- [ ] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14.
- [ ] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 6.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.4 Archive the change as the last commit of the pull request.
