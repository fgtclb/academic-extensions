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

#### Scenario: Upgrade wizard was not run
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

### Requirement: Existing imported records can be migrated explicitly
The system SHALL provide an idempotent upgrade wizard that normalizes unambiguous imported phone-number records. It SHALL preserve selectable types and records not identified as frontend-user imports.

#### Scenario: Legacy imported records need migration
- **WHEN** the installation contains `phone:fe_users:<uid>` records or imported fax records with the invalid type `fax`
- **THEN** the wizard reports an update as necessary and migrates the actionable values

#### Scenario: Existing type was corrected manually
- **WHEN** an imported record already carries a selectable type
- **THEN** the wizard preserves that type

#### Scenario: Record was created manually
- **WHEN** a phone-number record does not have a recognized frontend-user import identifier
- **THEN** the wizard leaves the record unchanged

#### Scenario: Wizard is repeated
- **WHEN** the wizard has completed successfully and is run again
- **THEN** it reports no update as necessary and changes no records

### Requirement: Fax remains part of the profile contract
The system SHALL continue to store imported fax numbers as phone-number records attached to the corresponding profile contract.

#### Scenario: Frontend user has a fax number
- **WHEN** the frontend user's fax value is synchronised
- **THEN** the corresponding contract contains an imported fax phone-number record
