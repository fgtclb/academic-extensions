## Purpose

Defines how `academic_persons` hides or deletes profiles whose frontend users
are no longer active, without touching profiles an editor maintains on their
own.

## ADDED Requirements

### Requirement: Profiles of inactive frontend users are hidden
The system SHALL hide a profile with `academic:cleanupprofiles` when every
frontend user linked to it is disabled or past its end time, unless
`--disabled=keep` is given. This applies to TYPO3 v13 and v14.

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

### Requirement: Maintained profiles are never touched
The system MUST NOT change a profile excluded from synchronisation, or a
profile that has no linked frontend user.

#### Scenario: Profile excluded from synchronisation
- **WHEN** a profile marked as excluded from synchronisation belongs to a
  disabled frontend user
- **THEN** the cleanup leaves it unchanged

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
