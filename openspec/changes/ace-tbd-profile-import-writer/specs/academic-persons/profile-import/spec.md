## Purpose

Defines how `academic_persons` creates, updates and retires the persons of an
external source handed over by import code, on TYPO3 v13 and v14.

## ADDED Requirements

### Requirement: Imported persons are matched by identifier
The system SHALL create a profile, contract or contact for an imported
identifier that no live record carries, and SHALL update the record that
carries it otherwise.

#### Scenario: Writing the same person twice
- **WHEN** import code writes the same person with the identifier `his:4711`
  twice
- **THEN** exactly one profile carries `his:4711`

#### Scenario: New contact on an existing contract
- **WHEN** import code writes a known contract with an e-mail address whose
  identifier is new
- **THEN** the e-mail address is added to that contract

### Requirement: Excluded profiles are not written
The system SHALL leave a profile excluded from the synchronisation and all its
records unchanged and SHALL report the person as skipped.

#### Scenario: Profile excluded from the synchronisation
- **WHEN** import code writes a person whose existing profile is excluded from
  the synchronisation
- **THEN** no record of that profile changes and the result reports it as
  skipped

### Requirement: Existing records receive only managed fields
The system SHALL store every supplied field on a new record, and on an
existing record only the supplied fields declared as managed for its record
type. Without a managed-field declaration an existing record SHALL NOT be
changed.

#### Scenario: Local field survives
- **WHEN** `position` is managed for contracts, an editor changed the room of
  an imported contract, and import code writes the contract with a new
  position and the old room
- **THEN** the contract has the new position and the editor's room

### Requirement: Visibility of existing records is never changed
The system SHALL NOT change whether an existing profile, contract or contact
is hidden.

#### Scenario: Hidden imported e-mail address
- **WHEN** the owner hid an imported e-mail address and import code writes it
  again
- **THEN** the e-mail address stays hidden

### Requirement: Listeners can change or veto a record write
The system SHALL let a registered listener change the values of each record
write or veto it; a vetoed record SHALL NOT be written and SHALL be reported.

#### Scenario: Vetoed contract
- **WHEN** a listener vetoes a contract of an imported person
- **THEN** the other records are written, the contract is not, and the result
  names it

### Requirement: Records no longer supplied can be retired
The system SHALL hide or delete, as the caller chooses, the records of one
source whose identifiers the caller does not keep, and SHALL NOT touch
records without an identifier, records of another source, or records of
profiles excluded from the synchronisation.

#### Scenario: Retiring a vanished contract
- **WHEN** import code retires the source `his` keeping only `his:4711`, and a
  contract carries `his:0815`
- **THEN** that contract is hidden, and a manually created contract of the
  same profile is unchanged

### Requirement: Imported profiles are synchronised afterwards
The system SHALL synchronise the translations and the slug of every profile
the writer created or changed, in the same run and once per person, in CLI
and scheduler runs as well. Listeners of profile updates SHALL learn that the
update came from an import.

#### Scenario: Import from the command line
- **WHEN** import code writes a changed person from a CLI command
- **THEN** the translations of the profile carry the new values of their
  non-translatable fields

#### Scenario: Listeners recognise an import
- **WHEN** import code writes a changed person
- **THEN** listeners of profile updates are called once for that person, with
  the origin "import"
