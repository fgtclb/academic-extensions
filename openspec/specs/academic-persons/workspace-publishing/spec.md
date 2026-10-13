# academic-persons/workspace-publishing Specification

## Purpose
Defines that the contracts of a profile or an organisational unit, the
addresses of a contract and the profile information records of a profile stay
with their parent when the parent is published from a workspace, copied or
translated.

## Requirements

### Requirement: Children keep their parent when it is published
When a profile, a contract or an organisational unit is edited in a workspace
and the workspace is published, from the workspace module or on its publish
date, its contracts, e-mail addresses, phone numbers, postal addresses and
profile information records SHALL still belong to it, on TYPO3 v13 and v14,
and the frontend SHALL show them with it.

#### Scenario: Profile edited in a workspace
- **WHEN** an editor changes a profile in a workspace and the workspace is
  published on its publish date
- **THEN** the profile still has its contracts and its profile information
  records, and its detail page shows them

#### Scenario: Contract edited in a workspace
- **WHEN** an editor changes a contract in a workspace and publishes the
  workspace
- **THEN** the contract still has its e-mail addresses, phone numbers and
  postal addresses, and the profile's detail page shows them

#### Scenario: Organisational unit edited in a workspace
- **WHEN** an editor changes an organisational unit in a workspace and
  publishes the workspace
- **THEN** the contracts assigned to it are still assigned to it

### Requirement: Copies and translations get their own children
Copying a profile, a contract or an organisational unit, and translating a
profile or a contract, SHALL give the copied or translated children the copied
or translated parent, and SHALL leave the children of the original with the
original, on TYPO3 v13 and v14.

#### Scenario: Profile copied
- **WHEN** an editor copies a profile
- **THEN** the copied contracts and profile information records belong to the
  copied profile, and those of the original to the original

#### Scenario: Contract translated
- **WHEN** an editor translates a contract
- **THEN** the translated addresses belong to the translated contract

#### Scenario: Profile translated
- **WHEN** an editor translates a profile
- **THEN** the translated contracts and profile information records belong to
  the translated profile
