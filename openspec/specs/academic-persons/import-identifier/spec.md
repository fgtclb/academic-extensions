# academic-persons/import-identifier Specification

## Purpose
Defines the external key a person record of `academic_persons` carries, how an
editor sees and searches it in the backend, and how import code finds a record
by it, on TYPO3 v13 and v14.

## Requirements

### Requirement: The import identifier is shown read-only
The system SHALL show the import identifier read-only in the backend form of a
profile, contract, e-mail address, phone number, physical address, location,
organisational unit or function type that carries one, and SHALL NOT show the
field on a record without one.

#### Scenario: Synchronised contract
- **WHEN** an editor opens a contract with the identifier `fe_users:12`
- **THEN** the form shows `fe_users:12` in a field that cannot be edited

#### Scenario: Manually created contract
- **WHEN** an editor opens a contract without an identifier
- **THEN** the form shows no identifier field

#### Scenario: Synchronised profile
- **WHEN** an editor opens a profile with the identifier `fe_users:12`
- **THEN** the form shows the identifier read-only next to the switch that
  disables the synchronisation of the profile

### Requirement: Editors can search by import identifier
The system SHALL find a person record by its import identifier, or a part of
it, in the search of the backend list module and in the backend search on
TYPO3 v13 and v14, for every person table either of them shows. The backend
search never lists contracts, e-mail addresses, phone numbers and physical
addresses. Page TSconfig shows them in the list module, whose search then finds
them. On SQLite a term that contains `_` finds nothing, a limitation of the
core search.

#### Scenario: Searching an identifier
- **WHEN** an editor searches the list module for `campus:12`
- **THEN** the location carrying that identifier is listed

#### Scenario: Searching in the toolbar
- **WHEN** an editor uses the backend search of the toolbar for `hr:4711`
- **THEN** the profile carrying that identifier is found

#### Scenario: Contracts shown by page TSconfig
- **WHEN** page TSconfig shows contracts in the list module and an editor
  searches there for the identifier of a contract
- **THEN** the contract is listed

### Requirement: Import code can find a record by its identifier
The system SHALL let import code look up the uid of the record of a person
table that carries a given import identifier. The lookup SHALL include hidden
and time-restricted records, and SHALL exclude deleted records, workspace
versions and translations. When several records carry the identifier, it
SHALL return the one with the lowest uid. A table without an import
identifier SHALL be refused.

#### Scenario: Hidden synchronised profile
- **WHEN** import code looks up `fe_users:12` in the profile table and that
  profile is hidden
- **THEN** the lookup returns its uid

#### Scenario: Only a workspace version carries the identifier
- **WHEN** the identifier exists only on a record created in a workspace
- **THEN** the lookup returns no record

#### Scenario: Unknown identifier
- **WHEN** no live record carries the identifier
- **THEN** the lookup returns no record

#### Scenario: Only a translation carries the identifier
- **WHEN** the identifier exists only on a translated record
- **THEN** the lookup returns no record

#### Scenario: Identifiers that differ in case
- **WHEN** one record carries `HR:4711` and a newer one `hr:4711`, and import
  code looks up `hr:4711`
- **THEN** the lookup returns the newer record, on every database

#### Scenario: Two records carry the identifier
- **WHEN** two live records carry the same identifier
- **THEN** the lookup returns the one with the lower uid

#### Scenario: Empty identifier
- **WHEN** import code looks up the empty identifier
- **THEN** the lookup returns no record, although every record an editor
  created carries it

#### Scenario: Table without an import identifier
- **WHEN** import code looks up an identifier in a table without the column
- **THEN** the lookup is refused with an error
