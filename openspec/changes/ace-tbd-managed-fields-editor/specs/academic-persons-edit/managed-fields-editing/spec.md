## Purpose

Defines how the frontend profile editor of `academic_persons_edit` presents
and protects the fields and rows that the synchronisation owns, on TYPO3 v13
and v14.

## ADDED Requirements

### Requirement: Managed fields are read-only in the editor
The system SHALL render a field that is declared as managed read-only in the
frontend editor when the edited profile, contract or contact carries an import
identifier and the profile is not excluded from the synchronisation, and SHALL
mark it as synchronised.

#### Scenario: Synchronised profile name
- **WHEN** a person opens the editor of a synchronised profile and `lastName`
  is managed for profiles
- **THEN** the last name is shown read-only with the synchronised marker

#### Scenario: Profile excluded from the synchronisation
- **WHEN** a person opens the editor of a profile excluded from the
  synchronisation
- **THEN** no field carries the synchronised marker and all configured fields
  are editable

### Requirement: Submitted values of managed fields are not stored
The system SHALL keep the stored value of a managed field on a managed record
when a request submits a different value for it, and SHALL store the other
submitted fields of the same request.

#### Scenario: Crafted update of a synchronised e-mail
- **WHEN** a request changes the address and the type of a synchronised e-mail
  address, and only `email` is managed for e-mail addresses
- **THEN** the stored address is unchanged and the new type is stored

#### Scenario: Manually added e-mail
- **WHEN** a request changes the address of an e-mail address without an
  import identifier
- **THEN** the new address is stored

### Requirement: Managed rows cannot be deleted
The system SHALL NOT offer deletion of a managed contract or contact row and
SHALL refuse a delete request for it with a 403 response, leaving the row in
place.

#### Scenario: Delete request for a synchronised contact
- **WHEN** a request deletes a synchronised phone number
- **THEN** the response is 403 and the phone number still exists

#### Scenario: Delete of a manual contact
- **WHEN** a request deletes a phone number without an import identifier and
  deletion is configured for contacts
- **THEN** the phone number is deleted

### Requirement: Fully managed rows offer no edit action
The system SHALL NOT offer the edit action on a managed row whose every
editable field is managed, and SHALL refuse an edit request for it with a 403
response.

#### Scenario: All fields of a synchronised e-mail are managed
- **WHEN** `email` and `type` are managed for e-mail addresses and a person
  views a synchronised e-mail address
- **THEN** the row offers no edit action

### Requirement: Visibility and order of managed rows stay editable
The system SHALL keep the configured hide, show and sort actions available on
managed rows.

#### Scenario: Hiding a synchronised contact
- **WHEN** a person hides a synchronised e-mail address
- **THEN** the address is hidden and a later synchronisation run leaves it
  hidden
