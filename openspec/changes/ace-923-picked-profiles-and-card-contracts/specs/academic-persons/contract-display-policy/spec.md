## MODIFIED Requirements

### Requirement: Editors can show only contracts matching the plugin filter

The system SHALL offer editors of the list and list-and-detail plugins an option
that limits the shown contracts to those of the organisational units and
function types the plugin is restricted to. Without such a restriction the
option SHALL have no effect. The card and selected-profiles plugins select their
profiles by hand, apply no such restriction and do not offer the option. A
value of the option, and a restriction to organisational units or function
types, stored while a card plugin was a list SHALL have no effect on the
contracts the card shows. This applies on TYPO3 v13 and v14.

#### Scenario: Plugin restricted to one unit

- **WHEN** a list plugin is restricted to the unit "Computer Science", the
  option is on, and a profile has contracts in "Mathematics" and "Computer
  Science"
- **THEN** only the "Computer Science" contract is shown

#### Scenario: First contract combined with the unit filter

- **WHEN** the same plugin also shows the first contract only, and the
  "Mathematics" contract comes first in the editor's order
- **THEN** the "Computer Science" contract is shown

#### Scenario: Card that was a list

- **WHEN** a list plugin restricted to the unit "Computer Science" with the
  option on is switched to a card, and a picked profile has contracts in
  "Mathematics" and "Computer Science"
- **THEN** the card shows both contracts

#### Scenario: Card that was a list shows the first contract only

- **WHEN** the same card shows the first contract only, and the "Mathematics"
  contract comes first in the editor's order
- **THEN** the card shows the "Mathematics" contract
