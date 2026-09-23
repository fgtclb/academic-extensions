# Frontend-user profile sync Specification

## Purpose

Defines how `academic_persons` transfers telephone and fax data from TYPO3 frontend users into profile contracts without producing unselectable types or duplicate imported records.

## Requirements

### Requirement: Integrators can configure imported telephone and fax types independently
The system SHALL provide separate extension configuration options for the type assigned to telephone and fax numbers imported from frontend users. Both options SHALL default to `business` on TYPO3 v13 and v14. A phone number entry of the mapping that names its own type SHALL use that type instead.

#### Scenario: Default import configuration
- **WHEN** an integrator does not customize either imported phone-number type
- **THEN** newly imported telephone and fax records carry the type `business`

#### Scenario: Independent import configuration
- **WHEN** an integrator configures different available types for telephone and fax numbers
- **THEN** newly imported records carry the corresponding type for their source field

#### Scenario: Entry with its own type
- **WHEN** the mapping gives the telephone entry the type `mobile`
- **THEN** newly imported telephone records carry the type `mobile`, whatever the extension configuration says

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
The system SHALL treat `fe_users.telephone` as contract data during profile creation and update, as long as the mapping lists the column `telephone`.

#### Scenario: Telephone is the only data for a new contract
- **WHEN** a synchronised profile has no contract and its frontend user contains only a telephone value
- **THEN** the system creates a contract containing that telephone record

#### Scenario: Telephone is the only remaining contract data
- **WHEN** an existing contract's frontend user contains a telephone value but no other contract data
- **THEN** the system retains the contract and synchronises the telephone record

### Requirement: Fax remains part of the profile contract
The system SHALL continue to store imported fax numbers as phone-number records attached to the corresponding profile contract, as long as the mapping lists the column `fax`.

#### Scenario: Frontend user has a fax number
- **WHEN** the frontend user's fax value is synchronised
- **THEN** the corresponding contract contains an imported fax phone-number record

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

### Requirement: A profile created from a frontend user is translated
The system SHALL treat a profile that `academic:createprofiles` creates from
a frontend user as a default-language profile. When `academic_persons_edit`
is installed and languages are configured in its option
`profile.allowedLanguages`, the translations of the new profile SHALL be
created in the same run, on TYPO3 v13 and v14.

#### Scenario: New profile with one allowed language
- **WHEN** `academic:createprofiles` creates a profile for a frontend user
  and `profile.allowedLanguages` lists a language the site offers
- **THEN** a translation of that profile exists in that language when the
  command has finished

#### Scenario: New profile without allowed languages
- **WHEN** `academic:createprofiles` creates a profile and
  `profile.allowedLanguages` is empty
- **THEN** only the default-language profile exists

### Requirement: Profiles loaded from the database keep their language status
The system MUST continue to report a profile loaded in a translation as a
translation, and a profile loaded in the default language as a
default-language profile.

#### Scenario: Translated profile is updated in the frontend editor
- **WHEN** a frontend user saves a profile in a translated language
- **THEN** the translation synchronisation is not started from that
  translation, exactly as before

### Requirement: The default mapping reproduces the previous synchronisation
The system SHALL ship a frontend-user mapping whose result is identical to
the synchronisation before this change: the same profile fields, the same
contact records, the same import identifiers and the phone-number types from
the extension configuration. This applies to TYPO3 v13 and v14.

#### Scenario: Installation without its own mapping
- **WHEN** no installation package ships a `frontendUserSync` map and
  `academic:createprofiles` runs
- **THEN** profiles, contracts and contact records are written exactly as
  before the change

### Requirement: Integrators map frontend-user columns to profile and contract fields
The system SHALL let an integrator assign an `fe_users` column to each
supported profile field and to the contract position and room. A field
mapped to an empty value MUST NOT be synchronised.

#### Scenario: Position is mapped
- **WHEN** the mapping assigns the column `tx_project_position` to the
  contract position and a frontend user carries "Professor" in it
- **THEN** the synchronised contract shows the position "Professor"

#### Scenario: Website is not synchronised
- **WHEN** the mapping assigns an empty value to the profile website
- **THEN** synchronisation neither writes nor clears the website an editor
  entered

### Requirement: Integrators map several contact records per contract
The system SHALL let an integrator list several physical addresses, e-mail
addresses and phone numbers per contract, each from its own columns. A phone
number entry SHALL carry a type, and an empty type SHALL mean the type from
the extension configuration.

#### Scenario: Mobile number as a second phone
- **WHEN** the mapping lists `telephone` without a type and `mobile` with the
  type `mobile`, and a frontend user carries both
- **THEN** the contract carries two imported phone numbers, the second with
  the type `mobile`

#### Scenario: Repeated synchronisation
- **WHEN** `academic:updateprofiles` runs twice without changes to the
  frontend user
- **THEN** no contact record is duplicated

### Requirement: An empty source removes the imported contact record
The system SHALL remove an imported contact record when all columns of its
mapping entry are empty. Records an editor added manually SHALL stay.

#### Scenario: Mobile number was removed in the source
- **WHEN** a frontend user's `mobile` column becomes empty
- **THEN** the imported mobile phone number is removed from the contract
  and the manually added numbers remain

### Requirement: A mistake in the mapping stops the synchronisation, not the site
The system SHALL refuse to synchronise with a mapping that names an unknown
property or key, a value that is not a string, an entry that maps no column,
two entries of one list identified by the same column, or a phone number read
from the column `phone`, whose identifier is the one of the telephone records
before ACE-365. The default
synchronisation SHALL also refuse a mapped column the frontend user record
does not have. It SHALL fail before writing anything, with a message naming
every mistake. The rest of the installation SHALL keep working. This applies
to TYPO3 v13 and v14.

#### Scenario: Typo in a property name
- **WHEN** an installation package maps `firstname` instead of `firstName`
  and `academic:updateprofiles` runs
- **THEN** the command fails with a message naming
  `frontendUserSync.profile.firstname`, and no profile is changed

#### Scenario: Typo in a column name
- **WHEN** an installation package maps the e-mail address to `e_mail`, a
  column `fe_users` does not have, and `academic:updateprofiles` runs
- **THEN** the command fails with a message naming `e_mail`, and the imported
  e-mail addresses are not removed

#### Scenario: Backend with a mistaken mapping
- **WHEN** the mapping contains a mistake and an editor opens a profile in the
  backend
- **THEN** the record opens as without the mistake

### Requirement: A mapping without contract sources leaves the contract alone
The system SHALL neither create, nor write, nor remove the imported contract
when the mapping names no contract property and no contact record. This
applies to TYPO3 v13 and v14.

#### Scenario: Only the names are synchronised
- **WHEN** an installation package sets the three contact lists to `[]` and
  maps no contract property, and `academic:updateprofiles` runs
- **THEN** the imported contract keeps its position, room and contact records,
  the ones an editor added included
