## 1. Schema and TCA

- [x] 1.1 Add `key idx_import_identifier` to the address table and verify the
  database compare of the functional test setup creates it
  (`ImportIdentifierIndexTest`, all eight tables, red for the address table
  without the key). Rebuild both `sqlite-databases/core-*.sqlite` templates,
  since a new index is a rebuild and not a migration.
- [x] 1.2 Turn `import_identifier` into a read-only input in its own palette on
  the eight tables, move `skip_sync` into the profile palette, and add
  `import_identifier` to the v13 `searchFields` switch and the missing labels.
  A functional test compiling the form of each table asserts the read-only
  field with an identifier and no field without, shown red on the
  `passthrough` configuration (`ImportIdentifierFieldTest`).
- [x] 1.3 Functional test of the list module search query for the identifier
  of each table on v13 and v14 (`RecordListSearchTest`), shown red without the
  v13 `searchFields` entry and on v14 with `passthrough`. The query is built
  per table, so for the four `hideTable` tables it stands for the list module
  with page TSconfig `hideTable = 0`, which is core behaviour and not tested
  here. The backend search of the toolbar finds the four tables it shows and
  not the contract (`LiveSearchTest`), shown red on v14 with `passthrough`.

## 2. Lookup

- [x] 2.1 Add `ImportedRecordFinder`. Functional tests return hidden and
  time-restricted rows, the lowest uid of duplicates, an exact match only,
  no workspace version, no translation and nothing for the empty identifier,
  and refuse a table without the column. Each rule was shown red by breaking
  it: the default restrictions, no workspace or language condition, the order
  reversed, no empty guard, and on MariaDB no exact comparison.

## 3. Documentation

- [x] 3.1 Document the convention, the field and the lookup in a new page
  `docs/architecture/import-identifiers.md`, linked from
  `docs/architecture/Index.md` and from
  `docs/architecture/frontend-user-contact-import.md`.
- [x] 3.2 Document the identifier in `academic-persons/Documentation/`
  (Developers, frontend user synchronisation), list the finder on the
  extension points page of `academic_base`, and add
  `Documentation/Changelog/3.0/Feature-ImportIdentifierLookup.rst`.

## 4. File the issue

- [x] 4.1 After implementation, file the ACE issue in YouTrack, rename the
  change to `ace-795-import-identifier-lookup`, and commit as
  `[FEATURE] ACE-795: Show and find import identifiers` in TYPO3 Core format.

## 5. Definition of done

- [x] 5.1 `composerUpdate`, then `lintPhp`, `cgl -n`, `phpstan`, `unit` and
  `functional` for TYPO3 v13, and the same after its own `composerUpdate` for
  TYPO3 v14, with `-d postgres` as well, and the new tests on MariaDB and
  MySQL.
- [x] 5.2 `lintMarkdown -n` and `checkRstRenderingAll` green.
- [x] 5.3 `docs/` and the extension's `Documentation/` changelog updated in the
  same change.
- [x] 5.4 Archive the change as the last commit of the pull request.
