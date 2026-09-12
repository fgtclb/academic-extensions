## ADDED Requirements

### Requirement: Installations can enrich frontend-user data before mapping
The system SHALL let an installation's extension add or change the
frontend-user values before profile creation and update map them, on TYPO3
v13 and v14. Added values SHALL be available to the configurable mapping
under the key the extension chose.

#### Scenario: Room from a directory service
- **WHEN** an extension adds the value "A 2.14" under the key `ldap.room`,
  and the mapping assigns `ldap.room` to the contract room
- **THEN** the synchronised contract shows the room "A 2.14"

### Requirement: Installations can skip a frontend user
The system SHALL let an installation's extension skip a frontend user during
creation or update. For a skipped user, no profile SHALL be created, changed
or announced.

#### Scenario: User without directory data
- **WHEN** an extension skips a frontend user during `academic:createprofiles`
- **THEN** no profile exists for that user afterwards, and the command
  continues with the next user

#### Scenario: Skip during update
- **WHEN** an extension skips a frontend user during `academic:updateprofiles`
- **THEN** that user's profile keeps its values and is not announced as
  updated

### Requirement: Installations can adjust the mapped profile
The system SHALL let an installation's extension change the mapped profile
after the mapping and before it is saved, with access to the frontend-user
values.

#### Scenario: Gender value map
- **WHEN** an extension maps the source value `w` to the profile gender `ms`
- **THEN** the saved profile carries the gender `ms`

### Requirement: A factory may decline to create a profile
The system SHALL accept that a custom profile factory creates no profile for
a frontend user. In that case no empty profile SHALL be saved.

#### Scenario: Custom factory declines
- **WHEN** a custom factory returns no profile for a frontend user
- **THEN** `academic:createprofiles` saves nothing for that user and reports
  no error
