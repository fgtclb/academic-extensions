## Why

Every person table of `academic_persons` has an `import_identifier` column,
and the fe_users synchronisation fills it, but nothing documents what goes in
it, no form shows it, and there is no way to find a record by it. So one
project added its own source-key columns to seven tables instead, and import
and cleanup code in two more projects queries the column by hand.

## What Changes

- The convention `<source>:<key>` is documented for `import_identifier`
  (`fe_users:12` for the fe_users sync, `telephone:fe_users:12` for its phone
  numbers).
- The backend form of `academic_persons` (`packages/fgtclb/academic-persons`)
  shows the identifier read-only on the eight tables that have it, when a
  record carries one. On the profile it sits next to the existing
  `skip_sync` toggle.
- Editors can search for an identifier in the list module on TYPO3 v13 and
  v14.
- Import code can look up the record of a table for an identifier: live,
  default-language, not deleted, hidden and time-restricted rows included.
- The address table gets the index on the column that the other tables
  already have.

The visible behaviour is the same on TYPO3 v13 and v14; the search is wired
differently per version (v13 `searchFields`, v14 searchable by default).

## Capabilities

### New Capabilities

- `academic-persons/import-identifier`: the external key of a person record,
  how it is shown and searched in the backend, and how import code finds a
  record by it.

### Modified Capabilities

None.

## Impact

- `academic_persons`: the TCA of eight tables (`passthrough` becomes a
  read-only input), one index in `ext_tables.sql`, one lookup service for
  integrators, labels.
- Database compare adds one index; no data change.
- Integrator documentation and a 3.0 feature changelog.

## Non-goals

- Migrating one project's own columns; that is project work.
- An import writer (`ace-tbd-profile-import-writer`) or a cleanup command.
- Enforcing unique identifiers; the lookup returns the oldest record.
- Backporting to branch `2`.

## Source

Derived from the project differences analysis of 2026-09-12 (candidate
`persons-data-16`). Three of the six analysed projects carry their own code for
this today. No YouTrack issue is filed yet; the change is renamed to
`ace-<NNN>-import-identifier-lookup` when the issue is filed after
implementation.
