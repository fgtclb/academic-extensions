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
This applies on TYPO3 v13 and v14.

#### Scenario: Unit with a display text

- **WHEN** a visitor views a card plugin that displays the organisational unit
  and the profile's contract belongs to a unit with a display text
- **THEN** the contract row shows the label "Organisational unit" and the
  unit's display text

#### Scenario: Unit without a display text

- **WHEN** the contract's unit has an empty display text and a unit name
- **THEN** the contract row shows the unit name

#### Scenario: Contract without a unit

- **WHEN** the contract has no organisational unit
- **THEN** no organisational unit row is rendered for that contract

### Requirement: Phone links carry a dialable target

The system MUST render the link target of a phone number in the list, card,
selected-profiles and selected-contracts output without the spaces of the
stored number, and SHALL keep the stored spelling as the visible link text.

#### Scenario: Stored number contains spaces

- **WHEN** a contract phone number is stored as `+49 6241 509 123` and phone
  numbers are displayed
- **THEN** the link target is `tel:+496241509123`
- **AND** the link text reads `+49 6241 509 123`

### Requirement: Integrators can prefix every phone link target

The system SHALL offer a site setting for a prefix of phone link targets,
empty by default. When it is set, the system SHALL prepend it to the link
target of every phone number in the list, card, selected-profiles,
selected-contracts, contacts for pages and detail output, and SHALL remove
the spaces of prefix and number alike. The visible link text SHALL keep the
stored spelling without the prefix. With the setting empty, the link targets
SHALL be the stored numbers without spaces. This applies on TYPO3 v13 and
v14.

#### Scenario: Extension-only number with a prefix

- **WHEN** the prefix is set to `+49 6241 509` and a contract phone number is
  stored as `123`
- **THEN** the link target in the card and in the detail view is
  `tel:+496241509123`
- **AND** the link text reads `123`

#### Scenario: No prefix configured

- **WHEN** the prefix setting is empty and a contract phone number is stored
  as `+49 6241 509 123`
- **THEN** the link target is `tel:+496241509123`
