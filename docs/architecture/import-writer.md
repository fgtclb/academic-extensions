# Import writer

`FGTCLB\AcademicPersons\Import\ProfileImportWriter` writes the persons of an
external source into `academic_persons`, one person per call, and retires the
records the source no longer supplies. A project keeps the part that reads its
source, an HR system or a campus management system, and maps each person to
plain data objects. Everything after that is the writer's: matching, the
managed fields, the DataHandler run and the announcement.

It is public API, listed on the extension points page of `academic_base`, and
stateless. The developer chapter of `academic_persons` documents it with an
adapter example.

## What import code hands over

`ImportedProfile`, `ImportedContract` and `ImportedContact` are
`final readonly` data objects. A profile holds its contracts, a contract its
e-mail addresses, phone numbers and physical addresses. Each record carries its
import identifier, `<source>:<key>` as described in
[Import identifiers](import-identifiers.md), and its field values keyed by
database column.

A contract names its organisational unit and its function type by their import
identifiers. The writer looks them up and never creates one: an unknown
identifier is not set and is reported as a message in the result.

The writer owns some columns and refuses fields that name them, with an
`\InvalidArgumentException` before anything is written: `uid`, `pid`,
`import_identifier`, the language and workspace columns, the relation to the
parent record, the inline columns, and a contract's unit and function type. An
identifier that is empty, or used twice for one table within a person, is
refused the same way.

Rejected: Extbase persistence. It writes no history, runs no DataHandler hook,
so neither `DataMapProcessor` nor the announcement of a profile update would
happen. Rejected as well: a framework with source adapters and a mapping UI.
That is the scope of the HIS connector issues, and it can be built on this.

## One DataHandler run per person

Every record is looked up with `ImportedRecordFinder`, so the same person
written twice is one profile. A record that is found gets an existing uid in
the datamap, a new one a `NEW` id. The whole tree is one datamap, run as a
synthetic admin in the live workspace (`runAsLiveBackendUser()`), and marked
`ProfileWriteCorrelation::Import` after `start()`.

- **A new record** gets every supplied field, the page and its identifier. Its
  page is the one the person names, or the page of the profile when the
  profile exists. An existing profile is never moved.
- **An existing record** gets only the supplied fields that
  `ManagedFieldResolver` names for it, see
  [Validation settings](validation-settings.md#managed-fields-a-lock-per-record).
  Without a managed field declaration none of its fields changes. `hidden`
  is never written on it: the shipped settings declare no field on `hidden`,
  and the writer drops one a site package declares on that column, so an
  import never shows a record an editor or the owner hid.
- **A profile excluded with `skip_sync`** is not written at all, nor is any of
  its records, and there is no DataHandler run.
- **A record that belongs to another parent**, a contract the identifier finds
  on another profile, is skipped and reported. The writer does not move
  records between people.

New children are added the way the backend form adds them: through the inline
column of the parent, which lists the live default-language children it has,
in their order, and then the new ones. The DataHandler writes the parent and
the sorting of every child listed and leaves the others alone, so a contract an
editor added keeps its place.

## Keeping the profile in the run

The DataHandler hook announces a profile only when the profile table is in the
datamap of the run. `DataMapProcessor` purges a row that is empty, which an
existing profile with no managed field supplied is. The writer therefore always
adds the identifier the profile has, a value that changes nothing and keeps the
row. `aWriteIsAnnouncedOnceAsAnImport` failed without it.

The announcement is the one of
[Translation synchronization](translation-synchronization.md): once per person,
synchronously, with the origin `Import`. The listeners of
`academic_persons_edit` synchronise the translations from it, including
contracts the import added, which only the synchronisation localizes, and
regenerate the slug. With `academic_persons` alone, only the image metadata
follows. A listener that wants to defer its work recognises an import by the
origin. Every profile is loaded through Extbase for its announcement and stays
in the persistence session for the rest of the request, so the memory of a
long import grows with the number of persons.

A result reports what the writer handed to the DataHandler. An updated, hidden
or deleted record is one it submitted, and whether the DataHandler stored it is
in the errors of the result.

A writer called from inside another DataHandler run, a hook or a listener of
the announcement, runs nested and is not announced, like every nested run. It
passes its own `ReferenceIndexUpdater` and flushes it, because the DataHandler
flushes the one of the outermost run only.

## The event

`BeforeImportedRecordWriteEvent` is dispatched for every record before it enters
the datamap, with the row the writer would store and the fields the import code
supplied. On an existing record the row holds only the managed fields, so the
supplied fields are what a listener decides on. A listener replaces the row with
`setRow()` or vetoes the record with `veto()`. The writer applies the same rules
to the replaced row as to the supplied one, so a listener cannot write a field
that is not managed on an existing record, nor `hidden`, nor a column the writer
owns. A vetoed profile writes nothing of the person, a vetoed contract none of
its contact records. Vetoing stops the propagation, as refusing a write of the
profile editing does.

## Retiring

`retire($source, $keepIdentifiers, $policy)` hides or deletes the records of a
source whose identifiers are not in the list. It selects the candidates with a
`LIKE` on the indexed column and compares the prefix exactly in PHP, for the
collation reason [Import identifiers](import-identifiers.md) gives. Records
without an identifier, records of other sources and records of a profile
excluded from the synchronisation are never retired.

- `RetirePolicy::Hide` writes `hidden = 1` through a datamap and leaves a
  record that is hidden already alone, so a second run reports nothing.
- `RetirePolicy::Delete` deletes through a cmdmap. The DataHandler deletes
  the inline children of a deleted record unless the column sets
  `behaviour.enableCascadingDelete` to false, which none of the person tables
  does. A deleted profile takes its contracts with it, and a deleted contract
  its contact records, the manual ones included. The writer leaves the
  children of a record it deletes out of the cmdmap.

The profiles whose contracts or contact records were retired are announced, so
their translations follow where `academic_persons_edit` synchronises them. A
run announces its datamap before it processes its
commands, so for the delete policy the announcement is a second run after the
deletion.

The list of identifiers to keep is one list for every table, and a record whose
identifier is in it stays, whatever the last write reported for it. A source
whose keys repeat across record kinds needs identifiers that tell them apart,
which the convention of [Import identifiers](import-identifiers.md) asks for
anyway.

## Tests

`ProfileImportWriterTest` and `ProfileImportWriterWithoutManagedFieldsTest` in
`academic_persons` cover matching, the excluded profile, the managed fields,
`hidden`, records below another parent, the event on a new and an existing
record and a vetoed profile, the lookup of unit and function type, the
refused input, the announcement, and retiring with both policies, the cascade
included. `ProfileImportWriterTranslationTest` in `academic_persons_edit` runs
the writer without a request and a backend user, as a command does, and checks
the translations of a changed and of a new person.

Each was shown to fail by removing what it covers: the lookup, the `skip_sync`
check, the managed field filter, the event dispatch, the second restriction of
a listener's row, the import mark, the profile row that keeps the
announcement, and the source filter. The source filter needed both halves
removed, the `LIKE` and the comparison in PHP, since either alone still
filters.

The changed person in the translation test adds a contract on purpose. The core
carries an `l10n_mode` `exclude` column such as the website into an existing
translation within the write itself, so a test of the website alone stayed
green with the announcement switched off.

Not covered here:

- The slug. The DataHandler generates the slug of a new profile itself, so a
  test of a new person proves nothing about the announcement. The slug an
  import regenerates for a changed name is covered by
  `BackendSaveAnnouncementTest` of `academic_persons_edit`, which marks a
  DataHandler run as an import the way the writer does.
- `hidden` on an existing record through the map. The shipped settings
  declare no field on it, so the drop is a guard without a test. It would
  matter for a field a site package declares on that column.
- The order of deletion and announcement. The DataHandler deletes the
  translations of a deleted record itself, so a translation shows no
  difference between the two orders. The writer announces after the deletion
  all the same, so a listener sees the profile as it is afterwards.
- A write in a workspace. The writer always writes live.

## See also

- [Import identifiers](import-identifiers.md): the key every record is matched
  by, and the lookup.
- [Validation settings](validation-settings.md#managed-fields-a-lock-per-record):
  the managed fields an import owns on an existing record.
- [Translation synchronization](translation-synchronization.md): what the
  announcement of an import starts.
- [Database queries](database-queries.md): the ordering and quoting rules the
  queries follow.
