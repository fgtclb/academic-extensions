# Frontend-user profile sync Specification

## Purpose

Defines how `academic_persons` transfers telephone and fax data from TYPO3 frontend users into profile contracts without producing unselectable types or duplicate imported records.

## Requirements

### Requirement: Integrators can configure imported telephone and fax types independently
The system SHALL provide separate extension configuration options for the type assigned to telephone and fax numbers imported from frontend users. Both options SHALL default to `business` on TYPO3 v13 and v14.

#### Scenario: Default import configuration
- **WHEN** an integrator does not customize either imported phone-number type
- **THEN** newly imported telephone and fax records carry the type `business`

#### Scenario: Independent import configuration
- **WHEN** an integrator configures different available types for telephone and fax numbers
- **THEN** newly imported records carry the corresponding type for their source field

### Requirement: Imported types are selectable
The system MUST validate each configured import type against the installation's available phone-number types before storing it. An unavailable configured value SHALL result in the valid undefined type `''`.

#### Scenario: Configured type is available
- **WHEN** the configured imported type exists in the installation's phone-number type list
- **THEN** the imported record carries that configured type

#### Scenario: Configured type is unavailable
- **WHEN** the configured imported type does not exist in the installation's phone-number type list
- **THEN** the imported record carries the undefined type `''`

### Requirement: Imported records have stable source identifiers
The system SHALL identify imported telephone records as `telephone:fe_users:<uid>` and imported fax records as `fax:fe_users:<uid>`. A change of configured type MUST NOT change these identifiers or create another record.

#### Scenario: Telephone is imported
- **WHEN** a frontend user's telephone value is imported
- **THEN** its phone-number record is identified as `telephone:fe_users:<uid>`

#### Scenario: Fax is imported
- **WHEN** a frontend user's fax value is imported
- **THEN** its phone-number record is identified as `fax:fe_users:<uid>`

#### Scenario: Existing valid type differs from configuration
- **WHEN** an imported phone-number record already carries a selectable type
- **THEN** synchronisation preserves that type and does not create another record

### Requirement: Legacy telephone records remain synchronisable
The system SHALL reuse a legacy `phone:fe_users:<uid>` record when no canonical telephone record exists in the same contract. It SHALL normalize the reused identifier and SHALL replace only the legacy invalid `phone` type.

#### Scenario: Only a legacy telephone record exists
- **WHEN** synchronisation encounters only a legacy telephone record for the frontend user and contract
- **THEN** it reuses the record, changes its identifier to `telephone:fe_users:<uid>`, and creates no duplicate

#### Scenario: Canonical and legacy records coexist
- **WHEN** both canonical and legacy telephone records already exist in the same contract
- **THEN** synchronisation uses the canonical record and neither deletes nor merges the legacy record

### Requirement: Telephone-only data retains a contract
The system SHALL treat `fe_users.telephone` as contract data during profile creation and update.

#### Scenario: Telephone is the only data for a new contract
- **WHEN** a synchronised profile has no contract and its frontend user contains only a telephone value
- **THEN** the system creates a contract containing that telephone record

#### Scenario: Telephone is the only remaining contract data
- **WHEN** an existing contract's frontend user contains a telephone value but no other contract data
- **THEN** the system retains the contract and synchronises the telephone record

### Requirement: Fax remains part of the profile contract
The system SHALL continue to store imported fax numbers as phone-number records attached to the corresponding profile contract.

#### Scenario: Frontend user has a fax number
- **WHEN** the frontend user's fax value is synchronised
- **THEN** the corresponding contract contains an imported fax phone-number record

### Requirement: Synchronisation ignores the visibility window of profiles
The system SHALL update a profile linked to a frontend user during
`academic:updateprofiles` even when the profile is hidden, outside its start
or end time, or restricted to a frontend user group. Deleted profiles SHALL
stay excluded. This applies to TYPO3 v12 and v13.

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

### Requirement: A profile created from a frontend user is translated
The system SHALL treat a profile that is created from a frontend user as a
default-language profile. When `academic_persons_edit` is installed and
languages are configured in its option `profile.allowedLanguages`, the
translations of the new profile SHALL be created in the same run, on TYPO3
v12 and v13.

#### Scenario: New profile with one allowed language
- **WHEN** a profile is created for a frontend user and
  `profile.allowedLanguages` lists a language the site offers
- **THEN** a translation of that profile exists in that language when the
  creation has finished

#### Scenario: New profile without allowed languages
- **WHEN** a profile is created and `profile.allowedLanguages` is empty
- **THEN** only the default-language profile exists

### Requirement: Profiles loaded from the database keep their language status
The system MUST continue to report a profile loaded in a translation as a
translation, and a profile loaded in the default language as a
default-language profile. A profile kept in all languages MUST NOT be
reported as a translation.

#### Scenario: Translated profile is updated in the frontend editor
- **WHEN** a frontend user saves a profile in a translated language
- **THEN** the translation synchronisation is not started from that
  translation, exactly as before

### Requirement: The page options take page uids only
The system SHALL read `--include-pids` and `--exclude-pids` of
`academic:createprofiles` and `academic:updateprofiles` as a comma-separated
list of page uids, the way `academic:cleanupprofiles` reads them. Spaces around
the commas and empty parts SHALL be accepted. A list with any other part SHALL
be refused with an error message and the exit code for invalid input, before
anything is created or updated. This applies to TYPO3 v12 and v13.

#### Scenario: A mistyped separator
- **WHEN** an integrator runs `academic:updateprofiles` with
  `--exclude-pids=110;1100`
- **THEN** the command prints that the page options take a comma-separated
  list of page uids, exits with the code for invalid input and updates no
  profile

#### Scenario: A page list that is no number
- **WHEN** an integrator runs `academic:createprofiles` with
  `--include-pids=abc`
- **THEN** the command exits with the code for invalid input and creates no
  profile

### Requirement: The commands report what they did
The system SHALL print the number of profiles `academic:createprofiles`
created and the number of frontend users whose profiles
`academic:updateprofiles` updated, a run that changed nothing included. When
`academic:createprofiles` created no profile and the automatic profile
creation is disabled in the extension configuration, it SHALL name that
option.

#### Scenario: Profiles are created
- **WHEN** `academic:createprofiles` creates profiles for two frontend users
- **THEN** it prints `2 profile(s) created.`

#### Scenario: Automatic creation is disabled
- **WHEN** `academic:createprofiles` runs while the automatic profile creation
  is disabled
- **THEN** it prints `0 profile(s) created.` and names the option
  `profile.autoCreateProfiles` that enables it

#### Scenario: Profiles are updated
- **WHEN** `academic:updateprofiles` updates the profiles of two frontend users
- **THEN** it prints `Profiles of 2 frontend user(s) updated.`
