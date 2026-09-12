## Purpose

Defines the extension point through which an integrator can veto or complete
a write of the frontend profile editor of `academic_persons_edit` before it is
stored, on TYPO3 v13 and v14.

## ADDED Requirements

### Requirement: Every editor write is offered to listeners before it is stored
The system SHALL offer every accepted write of the frontend editor to the
registered listeners before anything is stored: the profile form, the
skip-sync toggle, creating, updating, hiding, deleting and sorting a document,
creating, updating, hiding, deleting and sorting a contact, and uploading and
removing the profile image. A listener SHALL learn the profile, the action,
the section and record where one applies, and the submitted values.

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

### Requirement: Rejected requests never reach a listener
The system SHALL NOT offer a write to listeners when the request is rejected
for missing authentication, for a profile the person may not edit, for an
action that is not configured, or for invalid submitted values.

#### Scenario: Foreign profile
- **WHEN** a person posts a document for a profile they may not edit
- **THEN** the response is 403 and no listener is called
