## 1. Schema and TCA

- [ ] 1.1 Add `key idx_import_identifier` to the address table and verify the
  database compare of the functional test setup creates it.
- [ ] 1.2 Turn `import_identifier` into a read-only input in its own palette on
  the eight tables, move `skip_sync` into the profile palette, add
  `import_identifier` to the v13 `searchFields` switch; functional test
  compiling a contract form asserts the read-only field with an identifier
  and no field without, shown red on the `passthrough` configuration.
- [ ] 1.3 Functional test of the list module search (or the search query it
  builds) for `fe_users:12` on v13 and v14, shown red without the v13
  `searchFields` entry.

## 2. Lookup

- [ ] 2.1 Add `ImportedRecordFinder`; functional tests return hidden and
  time-restricted rows, the lowest uid of duplicates, no workspace version, no
  translation, and refuse a table without the column; shown red by keeping
  the default restrictions.

## 3. Documentation

- [ ] 3.1 Document the convention and the lookup in
  `docs/architecture/frontend-user-contact-import.md` (or a new page linked
  from `docs/architecture/Index.md`).
- [ ] 3.2 Document the identifier in `academic-persons/Documentation/` and add
  `Documentation/Changelog/3.0/Feature-ImportIdentifierLookup.rst`.

## 4. File the issue

- [ ] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-<NNN>-import-identifier-lookup`, and commit as
  `[TASK] ACE-<NNN>: Document and look up import identifiers` in TYPO3 Core
  format.

## 5. Definition of done

- [ ] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14; the lookup tests also with `-d postgres`.
- [ ] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [ ] 5.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [ ] 5.4 Archive the change as the last commit of the pull request.
