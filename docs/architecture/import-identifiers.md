# Import identifiers

Eight tables of `academic_persons` carry an `import_identifier` column: the
profile, the contract, the e-mail address, the phone number, the physical
address, the location, the organisational unit and the function type. It holds
the key a record has in the source it was imported from, so the next run of an
import finds the record it wrote before instead of creating a second one.

## The convention is `<source>:<key>`

The source comes first, the key of the record in that source second, separated
by a colon. Where one source row yields several records, the part that tells
them apart comes first:

| Record                                  | Identifier                      |
|-----------------------------------------|---------------------------------|
| profile and contract of a frontend user | `fe_users:<uid>`                |
| first address and e-mail address        | `fe_users:<uid>`                |
| further address and e-mail address      | `<first column>:fe_users:<uid>` |
| phone number                            | `<column>:fe_users:<uid>`       |

These are the identifiers the frontend user synchronisation writes, see
[Frontend-user contact import](frontend-user-contact-import.md#identity-and-presentation-are-separate).
Location, organisational unit and function type are never written by it. The
column is there for imports of a project. An editor never writes it, and a
record an editor creates carries the empty string.

Rejected: a column per source. One project added its own source columns to
seven tables, and every further source would have added seven more.

## In the backend

The column is a read-only input in a palette `import` on the last tab of each
form, "Extended", which the contract form gains for it. On the profile the
palette also holds `skip_sync`, which moved there from the `hidden` palette on
the Access tab: the switch that stops the synchronisation sits next to the key
it synchronises by.
Both fields have the display condition `FIELD:import_identifier:REQ:true`, so a
record without an identifier shows neither.

`readOnly` is a FormEngine option only. The input renders `disabled`, so the
form never submits the value, and the DataHandler does not read the option on
either core version: the synchronisation, an import and a script keep writing
the column. Hiding the field from an editor is a TCEFORM decision of the
project.

The search of the list module and the backend search find a record by its
identifier. TYPO3 v14 searches every input field. On v13 the field is added to
`ctrl.searchFields`, which seven of the eight TCA files set in their version
switch, see
[Core version aware code](core-version-aware-code.md#a-switch-inside-a-configuration-file).
The contract sets none, and v13 then searches every searchable field.

Contracts, addresses, e-mail addresses and phone numbers are `hideTable`. Both
searches leave such a table out, so by default only profiles, locations,
organisational units and function types are found by their identifier. A
profile shares its identifier with its synchronised contract, so the profile
found by it holds that contract. The backend search never lists the other four.
Page TSconfig `mod.web_list.table.<table>.hideTable = 0` shows one of them in
the list module, whose search then finds it. `LiveSearchTest` pins that the
backend search leaves the contract out.

In the list module, a number as search term is also compared with the text of
every searchable field, so searching a folder of person records for the uid
`12` lists the records whose identifier contains `12` as well. The backend
search compares a number with numeric fields only.

The core search escapes `_` for `LIKE` without naming an escape character.
MariaDB, MySQL and PostgreSQL take the backslash by default, SQLite has none, so
on SQLite a search for `fe_users:12` finds nothing and one for `users:12` finds
the record. `RecordListSearchTest` searches without the `_` for that reason.
`LiveSearchTest` uses identifiers of a source without one.

## Looking a record up

`FGTCLB\AcademicPersons\Import\ImportedRecordFinder::findUid($table,
$identifier)` returns the uid of the record that carries an identifier, or
`null`. It is public API, listed on the extension points page of
`academic_base`, and stateless.

- **Hidden and time-restricted records are found.** The query removes every
  restriction and adds the deleted one back. An import updates what it wrote,
  even after an editor hid it, and a hidden profile is still synchronised (see
  [Visibility does not stop the synchronisation](frontend-user-contact-import.md#visibility-does-not-stop-the-synchronisation)).
- **Only live records.** `t3ver_wsid = 0` and `t3ver_oid = 0` on a workspace
  aware table, read from `TcaSchema`. A record created in a workspace and a
  workspace version of a live record are never found, so an import does not
  write into a draft.
- **Only the default language and all languages**, `sys_language_uid IN (0,
  -1)`. The synchronisation writes default-language rows only, and a
  translation found by the identifier would be updated in place of its parent.
- **The lowest uid** when several records carry the identifier. Nothing keeps
  them unique: a copy made in the backend keeps the identifier of its original
  (ACE-759). The query orders by `uid`.
- **An exact comparison.** `utf8mb4_unicode_ci`, the collation TYPO3 creates
  its MySQL and MariaDB tables with, ignores case and trailing spaces, so the
  database would find `HR:4711` for `hr:4711` there and not on PostgreSQL or
  SQLite. The query selects the candidates and PHP takes the first one whose
  identifier is identical, as `ContractRelationResolver` does, see
  [Frontend-user contact import](frontend-user-contact-import.md#contract-relations-are-looked-up-never-imported).
- **The empty identifier finds nothing**, since every record an editor created
  carries it.
- **A table without the column is refused** with an
  `\InvalidArgumentException` (1790866812): a typo in a table name must not
  look like a record that is not there.

`ImportedRecordFinderTest` covers each rule. Each was shown to fail by breaking
the rule it covers, the order by reversing it, since SQLite returns rows in uid
order without one, and the exact comparison on MariaDB, the only kind of
database where it makes a difference. The checks for a table without workspace
or language support are read from the schema, but every person table has both,
so no test runs them.

Every person table except the address table had an index on the column. The
address table has one now, `ImportIdentifierIndexTest` checks all eight.

## See also

- [Frontend-user contact import](frontend-user-contact-import.md) — the
  synchronisation that writes the identifiers of frontend users.
- [Validation settings](validation-settings.md#managed-fields-a-lock-per-record)
  — the managed fields, which a record with an identifier locks.
- [Database queries](database-queries.md) — the ordering and quoting rules the
  lookup follows.
