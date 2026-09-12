## Purpose

Defines which profiles a logged-in frontend user may edit in the frontend
profile editor of `academic_persons_edit`, and how an integrator changes that
set, on TYPO3 v13 and v14.

## ADDED Requirements

### Requirement: A user edits the profiles linked to them by default
The system SHALL let a logged-in frontend user list and edit exactly the
profiles linked to their frontend user when no listener changes the set.

#### Scenario: Linked profile
- **WHEN** a logged-in person opens the editor of a profile linked to them
- **THEN** the editor is shown

#### Scenario: Foreign profile
- **WHEN** a logged-in person opens the editor of a profile not linked to them
- **THEN** access is denied

### Requirement: Integrators can change the editable profiles
The system SHALL let a registered listener add profiles to or remove profiles
from the set a logged-in frontend user may edit, and SHALL apply the resulting
set to the profile list, the edit page and every write request.

#### Scenario: Granted profile
- **WHEN** a listener grants a person a profile not linked to them
- **THEN** the profile appears in the person's list, its edit page is shown,
  and a write for it is accepted

#### Scenario: Removed profile
- **WHEN** a listener removes a profile that is linked to the person
- **THEN** the profile is missing from the list, its edit page is denied, and
  a write for it is answered with 403

### Requirement: Anonymous visitors are never granted a profile
The system SHALL NOT ask listeners for a visitor who is not logged in and
SHALL deny such a visitor every profile.

#### Scenario: Visitor without login
- **WHEN** a visitor who is not logged in posts a write for any profile
- **THEN** the response is 401 and no listener is called
