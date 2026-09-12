## Purpose

Defines how the program list plugin matches programs that are assigned to a
subcategory of a category selected by the editor or by a visitor, so that a
program needs to carry only its most specific category.

## ADDED Requirements

### Requirement: Editors can include subcategories in the list filter
The program list plugin SHALL offer an option to include subcategories when it
filters by category. The option SHALL be off for new and existing plugins.

#### Scenario: Existing plugin after the update
- **WHEN** a program list plugin was saved before the option existed
- **THEN** it lists exactly the programs it listed before

#### Scenario: Option off
- **WHEN** the option is off
- **AND** a visitor filters by the category "Bachelor"
- **THEN** the list shows only programs that carry "Bachelor" itself

### Requirement: A selected category matches its whole subtree
With the option on, the system SHALL list a program for a selected category
when the program carries that category or any category below it, at any
depth.

#### Scenario: Program carries only the child
- **WHEN** the option is on
- **AND** program A carries only "Bachelor of Science", a child of "Bachelor"
- **AND** a visitor filters by "Bachelor"
- **THEN** program A is listed

#### Scenario: Program carries a grandchild
- **WHEN** the option is on
- **AND** program B carries only a category two levels below "Bachelor"
- **AND** a visitor filters by "Bachelor"
- **THEN** program B is listed

#### Scenario: Program carries an unrelated sibling
- **WHEN** the option is on
- **AND** program C carries only "Master"
- **AND** a visitor filters by "Bachelor"
- **THEN** program C is not listed

### Requirement: Preselected categories follow the option
The system SHALL apply the option to the categories an editor preselects in
the plugin in the same way as to the categories a visitor selects.

#### Scenario: Editor preselects the parent
- **WHEN** the option is on
- **AND** the editor restricts the plugin to "Bachelor"
- **AND** program A carries only "Bachelor of Science"
- **THEN** program A is listed without any visitor selection

### Requirement: Several selections still all have to match
The system SHALL keep requiring every selected category to match. Including
subcategories SHALL widen each selection to its subtree and SHALL NOT turn
several selections into alternatives.

#### Scenario: Degree and location selected
- **WHEN** the option is on
- **AND** a visitor filters by "Bachelor" and by the location "Campus A"
- **THEN** only programs that carry a category of the "Bachelor" subtree and
  "Campus A" are listed

### Requirement: Only visible categories of the program group take part
The system SHALL consider only subcategories that are visible in the current
request and that belong to a category type of the programs group. A loop in
the category tree SHALL NOT prevent the list from rendering.

#### Scenario: Hidden subcategory
- **WHEN** the option is on
- **AND** "Bachelor of Science" is hidden
- **AND** a visitor filters by "Bachelor"
- **THEN** a program that carries only "Bachelor of Science" is not listed

#### Scenario: Category tree contains a loop
- **WHEN** the option is on
- **AND** two categories reference each other as parent
- **THEN** the list renders, and each of them matches programs that carry the
  other one

### Requirement: The filter form is unchanged
The system SHALL render the filter options and the selected value exactly as
without the option.

#### Scenario: Visitor selected the parent
- **WHEN** the option is on
- **AND** a visitor filtered by "Bachelor"
- **THEN** "Bachelor" is shown as the selected option of its filter
