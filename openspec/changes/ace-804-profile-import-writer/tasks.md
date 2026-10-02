## 1. Prerequisites

- [x] 1.1 Confirm `ace-795-import-identifier-lookup`,
  `ace-758-managed-fields-backend` and
  `ace-725-backend-save-announces-profile-update` are merged. Stop
  otherwise. The writer lives in `academic_persons`. `academic_persons_sync`
  is left untouched (see design).

## 2. Writer

- [x] 2.1 Add the DTOs, `ImportResult` and `ProfileImportWriter::write()`.
  Functional test writing the same person twice yields one profile, shown red
  by skipping the lookup.
- [x] 2.2 Skip excluded profiles. Functional test asserts no change and the
  skipped result, shown red with the check removed.
- [x] 2.3 Restrict updates to managed fields and never write `hidden` on
  existing rows. Functional tests keep a local room and a hidden e-mail
  address, shown red by writing all supplied fields.
- [x] 2.4 Add `BeforeImportedRecordWriteEvent`. Functional test vetoes a
  contract and changes a value, shown red without the dispatch.
- [x] 2.5 Functional test that a CLI write synchronises the translation of a
  non-translatable field, shown red with the announcement disabled, and that
  a counting listener receives exactly one announcement for the person with
  origin `Import`, shown red without the import correlation mark.

## 3. Retire

- [x] 3.1 Add `retire()` with `RetirePolicy`. Functional tests hide a vanished
  contract, delete with the delete policy, and leave manual records, other
  sources and excluded profiles alone. Shown red by dropping the source
  filter.

## 4. Documentation

- [x] 4.1 Add a page on the import writer to `docs/architecture/` and link it
  from `docs/architecture/Index.md`.
- [x] 4.2 Document the writer with an adapter example in
  `academic-persons/Documentation/` and add
  `Documentation/Changelog/3.0/Feature-ProfileImportWriter.rst`.

## 5. File the issue

- [x] 5.1 After implementation, file the ACE issue in YouTrack (not ACE-360,
  ACE-644 decides the future of `academic_persons_sync`), rename the change
  to `ace-<NNN>-profile-import-writer`, and commit
  as `[FEATURE] ACE-<NNN>: Add an import writer for profiles` in TYPO3 Core
  format.

## 6. Definition of done

- [x] 6.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14. The writer tests also with `-d postgres`.
- [x] 6.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 6.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 6.4 Archive the change as the last commit of the pull request.
