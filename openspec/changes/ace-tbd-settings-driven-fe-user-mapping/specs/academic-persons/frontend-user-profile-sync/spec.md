## ADDED Requirements

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
