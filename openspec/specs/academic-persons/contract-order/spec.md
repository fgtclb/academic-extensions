# academic-persons/contract-order Specification

## Purpose
TBD - created by archiving change ace-699-inline-sort-column-per-parent. Update Purpose after archive.

## Requirements

### Requirement: A profile keeps the contract order arranged on it

The contracts of a profile SHALL render in the order an editor arranged on
that profile. Saving any other record, an organisational unit that lists the
same contracts included, MUST NOT change that order. This applies to TYPO3 v13
and v14 alike.

#### Scenario: Saving an organisational unit

- **WHEN** an editor arranges the contracts of a profile, and then saves an
  organisational unit that lists some of them in a different order
- **THEN** the profile still renders its contracts in the order arranged on
  the profile

#### Scenario: An organisational unit keeps its own arrangement

- **WHEN** an editor rearranges the contracts listed in an organisational unit
  and saves it
- **THEN** the unit form shows them in that order the next time it is opened,
  and every later save of one of those contracts leaves that order alone

#### Scenario: A contract joins an organisational unit from somewhere else

- **WHEN** a contract is given an organisational unit in its own form on a
  profile, in the profile editing frontend, or is copied or localized
- **THEN** it appears at the end of that unit's list rather than in front of
  the contracts the editor arranged

#### Scenario: A contract changes its organisational unit

- **WHEN** an editor gives a contract a different organisational unit
- **THEN** it appears at the end of the new unit's list and no longer in the
  old one

#### Scenario: Editing a contract in a workspace

- **WHEN** an editor changes a contract in a workspace and the workspace is
  published
- **THEN** the contract sits where it sat in its organisational unit's list
  before the edit

#### Scenario: Updating an existing installation

- **WHEN** an installation updates and runs the upgrade wizards
- **THEN** profiles and organisational unit forms show their contracts in the
  order they showed before the update
