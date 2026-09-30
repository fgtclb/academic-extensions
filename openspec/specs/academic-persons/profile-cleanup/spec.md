# academic-persons/profile-cleanup Specification

## Purpose

Defines how `academic_persons` hides or deletes the profiles the frontend user
synchronisation manages once their frontend users are no longer active, and
which profiles it leaves alone.

## Requirements

### Requirement: Profiles of inactive frontend users are hidden
The system SHALL hide a profile with `academic:cleanupprofiles` when every
frontend user linked to it is disabled or past its end time, unless
`--disabled=keep` is given. This applies to TYPO3 v12 and v13.

#### Scenario: Only linked user is disabled
- **WHEN** the only frontend user linked to a profile is disabled and the
  command runs with default options
- **THEN** the profile is hidden, in every language

#### Scenario: A second linked user is still active
- **WHEN** a profile is linked to a disabled frontend user and to an active
  one
- **THEN** the profile stays visible

### Requirement: Profiles of deleted frontend users are deleted
The system SHALL delete a profile together with its contracts and contact
records when every frontend user linked to it is deleted. With
`--deleted=hide` it SHALL hide the profile instead, and with `--deleted=keep`
it SHALL leave it. Deleted profiles SHALL remain restorable from the record
history.

#### Scenario: Only linked user was deleted
- **WHEN** the only frontend user linked to a profile is deleted and the
  command runs with default options
- **THEN** the profile and its contracts are deleted and appear in the
  record history

#### Scenario: The linked frontend user record is gone
- **WHEN** the frontend user record a profile is linked to does not exist any
  more
- **THEN** the profile is deleted as if the frontend user were deleted

#### Scenario: A hidden profile of deleted users
- **WHEN** a hidden profile's frontend users are all deleted and the command
  runs with default options
- **THEN** the profile is deleted

#### Scenario: One linked user is deleted, another one disabled
- **WHEN** a profile is linked to a deleted and to a disabled frontend user
- **THEN** the profile is hidden and not deleted

### Requirement: Maintained profiles are never touched
The system MUST NOT change a profile excluded from synchronisation, a profile
that has no linked frontend user, or a profile the synchronisation does not
manage: one without an import identifier whose frontend users are all logins
the synchronisation does not read.

#### Scenario: Profile excluded from synchronisation
- **WHEN** a profile marked as excluded from synchronisation belongs to a
  disabled frontend user
- **THEN** the cleanup leaves it unchanged

#### Scenario: A login the synchronisation does not read
- **WHEN** an editor linked a profile to a disabled login of another record
  type, and the profile has no import identifier
- **THEN** the cleanup leaves it unchanged

### Requirement: The page options narrow the profiles looked at
The system SHALL, with `--include-pids`, look only at profiles with a
frontend user stored on one of those pages, and SHALL, with `--exclude-pids`,
leave every profile with a frontend user stored on one of those pages.
Whether a profile it looks at is hidden or deleted SHALL still depend on all
its frontend users.

#### Scenario: A frontend user in an excluded folder
- **WHEN** a profile's frontend users are all disabled and one of them is
  stored on a page given to `--exclude-pids`
- **THEN** the profile stays visible

#### Scenario: Only one folder is cleaned up
- **WHEN** the command runs with `--include-pids` naming one page
- **THEN** only profiles with a frontend user on that page are hidden or
  deleted

### Requirement: Profiles of all languages are cleaned up
The system SHALL treat a profile set to all languages like a profile of the
default language.

#### Scenario: A profile for all languages
- **WHEN** a profile set to all languages belongs to a disabled frontend user
- **THEN** the cleanup hides it

### Requirement: A malformed page list stops the command
The system SHALL refuse a page option with anything but page uids, before it
reads or writes anything.

#### Scenario: A mistyped separator
- **WHEN** an integrator runs the command with `--exclude-pids=200;300`
- **THEN** the command exits with an error and changes nothing

### Requirement: A deleted profile leaves the cached pages
The system SHALL clear the cached list and detail pages of a profile that is
deleted or restored, by the cleanup or in the backend.

#### Scenario: Profile deleted by the cleanup
- **WHEN** the cleanup deletes a profile whose list and detail pages are
  cached
- **THEN** those pages are rendered again on the next request, without the
  profile

#### Scenario: Translation deleted or profile restored in the backend
- **WHEN** an editor deletes a translation of a profile, or restores a
  deleted profile
- **THEN** the cached list and the detail page of the profile are rendered
  again on the next request

### Requirement: A dry run changes nothing
The system SHALL list, with `--dry-run`, every profile it would hide or
delete, and SHALL write nothing.

#### Scenario: Dry run before the first real run
- **WHEN** an integrator runs the command with `--dry-run`
- **THEN** the output names each affected profile and its action, and the
  database is unchanged

### Requirement: The cleanup never shows a profile again
The system MUST NOT make a hidden profile visible again, even when its
frontend user is active again.

#### Scenario: Frontend user re-enabled
- **WHEN** the cleanup hid a profile and its frontend user is enabled again
- **THEN** the profile stays hidden until an editor shows it

#### Scenario: A profile hidden already
- **WHEN** a profile is hidden already and its frontend users are all
  disabled
- **THEN** the cleanup neither lists nor writes it
