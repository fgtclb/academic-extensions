# academic-persons-edit/contract-visibility-editing Specification

## Purpose
Defines how the owner of a profile shows or hides a contract in the frontend
editor: through the visibility of the contract, with no separate publish
switch in the contract form.

## Requirements

### Requirement: The contract form has no publish switch
The contract form of the profile editor SHALL NOT offer a "Publish" switch.
The show and hide action of each contract in the list SHALL be the one
control for whether a contract appears on the public site. This applies on
TYPO3 v13 and v14.

#### Scenario: Owner edits a contract
- **WHEN** the owner opens a contract in the profile editor
- **THEN** the form shows the contract fields without a "Publish" switch

#### Scenario: Owner hides a contract
- **WHEN** the owner uses the hide action of a contract in the list
- **THEN** the contract is marked as hidden in the editor and is left out of
  the public views

#### Scenario: Request that still names the old field
- **WHEN** a save request of the contract form sends a value for the removed
  publish field
- **THEN** the editor refuses it as an unknown field, as for any other field
  the form does not have
