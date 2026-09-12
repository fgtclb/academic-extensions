## Purpose

Defines which of a profile's contracts the profile views of `academic_persons`
show, as configured by the editor per content element and by the integrator
for the detail view.

## ADDED Requirements

### Requirement: All contracts are shown by default

Without further configuration the system SHALL show every contract of a profile
in the list, list-and-detail, card and selected-profiles plugins and in the
detail view, in the editor's contract order, as before. This applies on TYPO3
v13 and v14.

#### Scenario: Plugin with default settings

- **WHEN** a profile has two contracts and the plugin keeps its default
  settings
- **THEN** both contracts are shown in the order the editor sorted them

### Requirement: Editors can show only the first contract

The system SHALL offer editors of the list, list-and-detail, card and
selected-profiles plugins the choice between all contracts and the first
contract only. With "first", the system SHALL show exactly one contract per
profile, the first in the editor's contract order among the contracts left
after the other contract options apply.

#### Scenario: First contract only

- **WHEN** a list plugin shows the first contract only and a profile has the
  contracts "Professor" and "Dean" in that order
- **THEN** only "Professor" is shown for that profile

### Requirement: Editors can show only contracts matching the plugin filter

The system SHALL offer an option that limits the shown contracts to those of the
organisational units and function types the plugin is restricted to. Without
such a restriction the option SHALL have no effect.

#### Scenario: Plugin restricted to one unit

- **WHEN** a list plugin is restricted to the unit "Computer Science", the
  option is on, and a profile has contracts in "Mathematics" and "Computer
  Science"
- **THEN** only the "Computer Science" contract is shown

#### Scenario: First contract combined with the unit filter

- **WHEN** the same plugin also shows the first contract only, and the
  "Mathematics" contract comes first in the editor's order
- **THEN** the "Computer Science" contract is shown

### Requirement: Editors can show only contracts valid today

The system SHALL offer an option that limits the shown contracts to those whose
validity period includes the date the page is rendered for. A missing start
date and a missing end date SHALL each count as open-ended.

#### Scenario: Expired and current contract

- **WHEN** the option is on and a profile has one contract that ended last year
  and one without an end date
- **THEN** only the contract without an end date is shown

#### Scenario: Contract without an end date

- **WHEN** the option is on and a contract started last year and has no end
  date
- **THEN** the contract is shown

### Requirement: Integrators choose the contracts of the detail view

The system SHALL let an integrator choose all contracts or the first contract
only, separately for the position block and the contact block of the detail
view. The default SHALL be all. The system SHALL also let the integrator
limit each of the two blocks to contracts valid today, off by default.

#### Scenario: First contract in the contact block

- **WHEN** the integrator configures the contact block to show the first
  contract only, and a profile with two contracts is opened
- **THEN** the contact block shows the contact data of the first contract only
- **AND** the position block still shows both positions

#### Scenario: Only valid contracts in the position block

- **WHEN** the integrator limits the position block to contracts valid today,
  and a profile has one expired and one current contract
- **THEN** the position block shows only the current contract's position

### Requirement: Validity takes effect on the day it changes

While the output depends on contracts being valid today, the system SHALL
cache the page no longer than until the next day on which a shown contract
ends or a contract left out because it has not started yet begins. Without a
validity option the page cache lifetime SHALL stay unchanged.

#### Scenario: Shown contract ends tomorrow

- **WHEN** a list plugin shows only contracts valid today and a listed
  contract ends today
- **THEN** the page is not served from the cache after the end of today

#### Scenario: Contract starts tomorrow

- **WHEN** a list plugin shows only contracts valid today and a contract
  starts tomorrow
- **THEN** the page rendered tomorrow shows that contract

#### Scenario: No validity option

- **WHEN** no validity option applies to any contract on the page
- **THEN** the page cache lifetime is the one the site configures

### Requirement: A chosen contract is shown as chosen

The system SHALL keep showing exactly the chosen contract where an editor
selected contracts rather than profiles, regardless of the options above.

#### Scenario: Selected-contracts plugin

- **WHEN** an editor selects a contract that has expired
- **THEN** the selected-contracts plugin still shows that contract
