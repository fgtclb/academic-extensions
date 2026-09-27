## Purpose

Defines how the frontend profile editor of `academic_persons_edit` presents
and protects the fields and rows that the synchronisation owns, and what it
does with a submitted value for any locked field, on TYPO3 v13 and v14.

## ADDED Requirements

### Requirement: Managed fields are read-only in the editor
The system SHALL render a field that is declared as managed read-only in the
frontend editor when the edited profile, contract or contact carries an import
identifier and the profile is not excluded from the synchronisation, and SHALL
mark it as synchronised.

#### Scenario: Synchronised profile website
- **WHEN** a person opens the editor of a synchronised profile and `website`
  is managed for profiles
- **THEN** the website is shown read-only with the synchronised marker

#### Scenario: Synchronised e-mail type
- **WHEN** a person edits a synchronised e-mail address and `type` is managed
  for e-mail addresses
- **THEN** the type selection is shown disabled with the synchronised marker

#### Scenario: Profile excluded from the synchronisation
- **WHEN** a person opens the editor of a profile excluded from the
  synchronisation
- **THEN** no field carries the synchronised marker and all configured fields
  are editable

#### Scenario: Manually added e-mail
- **WHEN** a person edits an e-mail address without an import identifier
- **THEN** no field carries the synchronised marker

#### Scenario: Translated site language
- **WHEN** a person opens the editor of a synchronised profile in a translated
  site language, and the website and the contract position are managed
- **THEN** the website, whose value all languages share, is shown read-only
  with the synchronised marker, and the position, which is translated, is
  editable, as both are in the backend

### Requirement: Submitted values of locked fields are ignored
The system SHALL keep the stored value of a field when a request submits a
different value for it and the field is managed on that record, or locked
with `readonly`, `frontendreadonly` or `disabled`, and SHALL store the other
submitted fields of the same request. A field the editor does not know SHALL
still be refused.

#### Scenario: Crafted update of a synchronised e-mail
- **WHEN** a request changes the address and the type of a synchronised e-mail
  address, and only `email` is managed for e-mail addresses
- **THEN** the stored address is unchanged and the new type is stored

#### Scenario: Manually added e-mail
- **WHEN** a request changes the address of an e-mail address without an
  import identifier
- **THEN** the new address is stored

#### Scenario: Saving a contract with a read-only room
- **WHEN** the room of contracts is locked with `frontendreadonly` and a
  person saves a contract from the editor, which sends every field of it
- **THEN** the save succeeds, the room keeps its stored value and the other
  fields are stored

#### Scenario: Crafted update of a read-only profile field
- **WHEN** the profile title is locked with `frontendreadonly` and a request
  submits a title
- **THEN** the request succeeds and the stored title is unchanged

### Requirement: Managed rows cannot be deleted
The system SHALL NOT offer deletion of a managed contract or contact row, one
with at least one managed field, and SHALL refuse a delete request for it with
a 403 response, leaving the row in place.

#### Scenario: Delete request for a synchronised contact
- **WHEN** a request deletes a synchronised phone number and `type` is
  managed for phone numbers
- **THEN** the response is 403 and the phone number still exists

#### Scenario: Delete of a manual contact
- **WHEN** a request deletes a phone number without an import identifier and
  deletion is configured for contacts
- **THEN** the phone number is deleted

#### Scenario: Delete from a translated site language
- **WHEN** a request from the editor of a translated site language deletes a
  synchronised contract
- **THEN** the response is 403 and the contract still exists in every language

#### Scenario: Nothing is declared as managed
- **WHEN** an installation declares no managed field for phone numbers and a
  request deletes a synchronised phone number
- **THEN** the phone number is deleted as before

### Requirement: Fully managed rows offer no edit action
The system SHALL NOT offer the edit action on a managed row whose every
editable field is managed, and SHALL refuse an edit request for it with a 403
response.

#### Scenario: All fields of a synchronised e-mail are managed
- **WHEN** `email` and `type` are managed for e-mail addresses and a person
  views a synchronised e-mail address
- **THEN** the row offers no edit action and an edit request is answered
  with 403

#### Scenario: One field of a synchronised e-mail is managed
- **WHEN** only `email` is managed for e-mail addresses and a person views a
  synchronised e-mail address
- **THEN** the row offers the edit action and no delete action

### Requirement: Visibility and order of managed rows stay editable
The system SHALL keep the configured hide, show and sort actions available on
managed rows.

#### Scenario: Hiding a synchronised contact
- **WHEN** a person hides a synchronised e-mail address
- **THEN** the address is hidden and a later synchronisation run leaves it
  hidden
