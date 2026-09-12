## Purpose

Defines the external key a person record of `academic_persons` carries, how an
editor sees and searches it in the backend, and how import code finds a record
by it, on TYPO3 v13 and v14.

## ADDED Requirements

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

### Requirement: Editors can search by import identifier
The system SHALL find a person record by its import identifier in the search
of the backend list module on TYPO3 v13 and v14.

#### Scenario: Searching an identifier
- **WHEN** an editor searches the list module for `fe_users:12`
- **THEN** the records carrying that identifier are listed

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
