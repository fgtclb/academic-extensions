## Purpose

Defines which fields of a synchronised person record are owned by the
synchronisation, and how the backend record form of `academic_persons`
presents those fields to an editor on TYPO3 v13 and v14.

## ADDED Requirements

### Requirement: Integrators declare managed fields per record type
The system SHALL let an integrator declare, in the persons settings, a list of
managed fields for each of the record types profile, contract, e-mail address,
phone number and physical address. Without a declaration no field SHALL be
managed, on TYPO3 v13 and v14.

#### Scenario: No declaration
- **WHEN** an installation does not declare any managed fields
- **THEN** every backend form of a person record behaves as before the change

#### Scenario: Declaration for one record type
- **WHEN** an integrator declares `position` as managed for contracts only
- **THEN** only the position field of contracts can become read-only, and no
  field of a profile, e-mail address, phone number or physical address does

### Requirement: A managed field is read-only on a synchronised record
The system SHALL render a declared managed field read-only in the backend form
of a record that carries an import identifier and whose profile is not
excluded from the synchronisation. The field SHALL show a hint that it is
maintained by the synchronisation, naming the import identifier.

#### Scenario: Synchronised contract
- **WHEN** an editor opens a contract with the import identifier
  `fe_users:12`, its profile takes part in the synchronisation, and `position`
  is managed for contracts
- **THEN** the position field is read-only and shows the hint naming
  `fe_users:12`
- **AND** every field that is not declared as managed stays editable

#### Scenario: Synchronised contact inside the profile form
- **WHEN** an editor opens a profile and expands a synchronised e-mail address
  of one of its contracts, and `email` is managed for e-mail addresses
- **THEN** the e-mail field of that address is read-only

### Requirement: Manually added and excluded records stay editable
The system SHALL NOT restrict a field of a record without an import
identifier, nor any field of a record whose profile is excluded from the
synchronisation.

#### Scenario: Manually added e-mail address
- **WHEN** an editor opens an e-mail address without an import identifier on a
  synchronised contract, and `email` is managed for e-mail addresses
- **THEN** the e-mail field is editable

#### Scenario: Profile excluded from the synchronisation
- **WHEN** an editor opens a synchronised contract of a profile that is
  excluded from the synchronisation
- **THEN** every field of the contract is editable

### Requirement: Translations are not affected
The system SHALL apply the managed-field lock to default-language records only.
A translation record SHALL keep the field behaviour it had before the change.

#### Scenario: Translated contract
- **WHEN** an editor opens the translation of a synchronised contract whose
  `position` is managed
- **THEN** the position field of the translation behaves as it did before the
  change
