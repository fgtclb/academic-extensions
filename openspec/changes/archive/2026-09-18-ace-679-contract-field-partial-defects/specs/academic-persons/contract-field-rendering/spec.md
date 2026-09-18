## Purpose

Defines what each contract field an editor selects for display renders in the
profile list, card and selection plugins of `academic_persons`, and how the
link target of a phone number is built in every persons output.

## ADDED Requirements

### Requirement: A selected organisational unit renders its name

When an editor selects the organisational unit as a displayed contract field,
the system SHALL render a translated label followed by the unit's display text.
When the display text is empty the system SHALL render the unit name instead.
A contract without an organisational unit SHALL render no row for the field.
This applies on TYPO3 v12 and v13.

#### Scenario: Unit with a display text

- **WHEN** a visitor views a selected contracts plugin that displays the
  organisational unit and the contract belongs to a unit with a display text
- **THEN** the contract row shows the label "Organisational Unit" and the
  unit's display text

#### Scenario: Unit without a display text

- **WHEN** the contract's unit has an empty display text and a unit name
- **THEN** the contract row shows the unit name

#### Scenario: Contract without a unit

- **WHEN** the contract has no organisational unit
- **THEN** no organisational unit row is rendered for that contract

### Requirement: Phone links carry a dialable target

The system MUST render the link target of a phone number without the spaces of
the stored number, in the list, card and selection output and in the profile
detail view alike, and SHALL keep the stored spelling as the visible link text.
This applies on TYPO3 v12 and v13.

#### Scenario: Stored number contains spaces in a selection plugin

- **WHEN** a contract phone number is stored as `+49 6241 509 123` and phone
  numbers are displayed
- **THEN** the link target is `tel:+496241509123`
- **AND** the link text reads `+49 6241 509 123`

#### Scenario: Stored number contains spaces in the detail view

- **WHEN** a visitor views the profile detail of a profile whose contract
  carries that phone number
- **THEN** the link target is `tel:+496241509123`
