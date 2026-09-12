## Purpose

Defines when `academic_persons` announces that a profile changed, so that
translations, slugs and the listeners of installations follow every save,
whether frontend or backend, exactly once.

## ADDED Requirements

### Requirement: Backend saves of default-language profiles are announced
The system SHALL announce a profile update after a live backend save that
creates or changes a default-language profile, on TYPO3 v13 and v14. When
`academic_persons_edit` is installed, the translations in the allowed
languages and the slug SHALL follow that save.

#### Scenario: Editor changes the last name in the backend
- **WHEN** a backend user changes the last name of a default-language
  profile and saves it, with one allowed translation language configured
- **THEN** the translation carries the new last name and the profile slug is
  regenerated

#### Scenario: Import writes through the DataHandler
- **WHEN** an import running with a backend user writes three
  default-language profiles in one DataHandler run
- **THEN** each of the three profiles is announced once and synchronised

### Requirement: Saves of translations alone are not announced
The system MUST NOT announce a profile update when a backend save touches
only translated profile rows.

#### Scenario: Editor corrects a translation
- **WHEN** a backend user saves only the English translation of a profile
- **THEN** no synchronisation starts and the default-language profile is not
  rewritten

### Requirement: Every save is announced exactly once
The system SHALL announce each changed profile once per save. The writes the
synchronisation performs on its own, and the image writes of the frontend
editor, MUST NOT cause a further announcement.

#### Scenario: Synchronisation writes back to the default-language profile
- **WHEN** a backend save starts the translation synchronisation, and the
  synchronisation writes to the default-language profile
- **THEN** listeners of profile updates are called exactly once for that
  save

#### Scenario: Frontend user uploads a profile image
- **WHEN** a frontend user replaces the profile image in the editor
- **THEN** listeners of profile updates are called exactly once for that
  upload

### Requirement: Announcements carry the site and the origin
The system SHALL provide every listener of a profile update with the site the
profile belongs to, when one can be resolved from its page. It SHALL also
provide the origin: creation from a frontend user, frontend-user
synchronisation, frontend editing, backend save, import, or unknown.

#### Scenario: Backend save of a profile on a site page
- **WHEN** a profile stored on a page of site "main" is saved in the backend
- **THEN** listeners receive the site "main" and the origin "backend save"

#### Scenario: Import marks its run as an import
- **WHEN** import code marks its DataHandler run as an import and writes a
  default-language profile
- **THEN** listeners receive the origin "import" once for that profile, and
  its translations and slug follow in the same run

#### Scenario: Listener written for an earlier version
- **WHEN** an installation's own code announces a profile update without
  giving a site or an origin
- **THEN** the translation synchronisation still resolves the site from the
  profile's page and listeners receive the origin "unknown"
