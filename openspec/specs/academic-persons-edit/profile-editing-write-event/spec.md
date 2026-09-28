# academic-persons-edit/profile-editing-write-event Specification

## Purpose

Defines the extension point through which an integrator can veto or complete
a write of the frontend profile editor of `academic_persons_edit` before it is
stored, on TYPO3 v13 and v14.

## Requirements

### Requirement: Every editor write is offered to listeners before it is stored
The system SHALL offer every accepted write of the frontend editor to the
registered listeners before anything is stored: the profile form, the
skip-sync toggle, the visibility switch, creating, updating, hiding, deleting
and sorting a document,
creating, updating, hiding, deleting and sorting a contact, and uploading and
removing the profile image. A listener SHALL learn the profile, the action,
the section, record and contract where one applies, and the submitted values
the write stores, without a locked field the browser sent.

#### Scenario: Document creation reaches a listener
- **WHEN** a person adds an entry to the `vita` section
- **THEN** a registered listener is told the profile, the action "create
  document", the section `vita` and the submitted values, before the entry is
  stored

#### Scenario: No listener registered
- **WHEN** no listener is registered
- **THEN** every write behaves as before the change

### Requirement: A listener can refuse a write
The system SHALL answer a write refused by a listener with status 422 and the
error `write_refused` carrying the listener's reason, SHALL store nothing of
it, and the editor SHALL show the reason to the person.

#### Scenario: Refused document creation
- **WHEN** a listener refuses the creation of `vita` entries and a person adds
  one
- **THEN** the response is 422 `write_refused`, no entry is stored, and the
  editor shows the reason

#### Scenario: Refused image upload
- **WHEN** a listener refuses an image upload
- **THEN** the response is 422 `write_refused`, and neither the image
  reference nor the uploaded file is kept

#### Scenario: Markup in a reason
- **WHEN** the reason of a refusal contains markup
- **THEN** the editor shows it as text

### Requirement: A listener can change the submitted values
The system SHALL store the values a listener replaced instead of the submitted
ones, after validating and sanitising them exactly as submitted values. A
replaced value that fails validation SHALL be answered as a validation error
and nothing SHALL be stored.

#### Scenario: Listener completes a value
- **WHEN** a listener replaces the submitted title of a document with a
  changed title
- **THEN** the changed title is stored

#### Scenario: Listener sets an invalid value
- **WHEN** a listener replaces a required value with an empty one
- **THEN** the response reports the validation error and nothing is stored

#### Scenario: Listener sets a locked value
- **WHEN** a listener adds a value for a field the configuration locks
- **THEN** that value is dropped and the other values of the write are stored

#### Scenario: Listener replaces the values of a write without values
- **WHEN** a listener replaces the values of a delete, a visibility change, a
  sort or an image write
- **THEN** the request fails and nothing is stored

### Requirement: Rejected requests never reach a listener
The system SHALL NOT offer a write to listeners when the request is rejected
for missing authentication, for a profile the person may not edit, for an
action that is not configured, or for invalid submitted values.

#### Scenario: Foreign profile
- **WHEN** a person posts a document for a profile they may not edit
- **THEN** the response is 403 and no listener is called
