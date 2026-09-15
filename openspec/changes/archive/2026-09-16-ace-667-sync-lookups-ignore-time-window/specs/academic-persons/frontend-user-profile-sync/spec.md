## ADDED Requirements

### Requirement: Synchronisation ignores the visibility window of profiles
The system SHALL update a profile linked to a frontend user during
`academic:updateprofiles` even when the profile is hidden, outside its start
or end time, or restricted to a frontend user group. Deleted profiles SHALL
stay excluded. This applies to TYPO3 v13 and v14.

#### Scenario: Profile end time has passed
- **WHEN** a profile's end time lies in the past and the last name of its
  frontend user changed
- **THEN** `academic:updateprofiles` writes the new last name to the profile

#### Scenario: Profile start time lies in the future
- **WHEN** a profile's start time lies in the future and its frontend user's
  e-mail address changed
- **THEN** `academic:updateprofiles` updates the profile's imported e-mail
  address

#### Scenario: Profile restricted to a frontend user group
- **WHEN** a profile is restricted to a frontend user group and the last name
  of its frontend user changed
- **THEN** `academic:updateprofiles` writes the new last name to the profile

### Requirement: Only visibility fields the profile table declares are ignored
The system SHALL ignore, of the hidden flag, start time, end time and
frontend user group, only those the profile table declares as visibility
fields in the installation. Any other visibility field SHALL stay active for
the synchronisation lookup.

#### Scenario: Installation without a frontend user group on profiles
- **WHEN** an installation removes the frontend user group from the
  visibility fields of the profile table, and a profile's end time has
  passed
- **THEN** `academic:updateprofiles` still updates that profile

### Requirement: Synchronisation ignores the visibility window of frontend users
The system SHALL select frontend users outside their start or end time for
both `academic:createprofiles` and `academic:updateprofiles`. Deleted
frontend users SHALL stay excluded.

#### Scenario: Frontend user end time has passed
- **WHEN** a frontend user without a profile has an end time in the past and
  profiles are created for its group
- **THEN** `academic:createprofiles` creates a profile for that frontend user

#### Scenario: Deleted frontend user
- **WHEN** a frontend user is deleted
- **THEN** neither command selects it

### Requirement: Synchronisation never changes the visibility window
The system MUST NOT change the hidden flag, start time, end time or frontend
user group of a profile while synchronising it.

#### Scenario: Time-windowed profile is updated
- **WHEN** `academic:updateprofiles` updates a profile whose end time has
  passed
- **THEN** the profile keeps its end time and stays invisible in the
  frontend
