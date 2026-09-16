## Purpose

Defines which orderings the program list offers to editors and site visitors,
and that every ordering the list offers is the ordering it renders.

## ADDED Requirements

### Requirement: Every offered field and direction combination is honoured

The program list SHALL render programs in the ordering a visitor selects in
the sort field and sort direction selects, for every combination of a field
and a direction those selects offer. The list MUST NOT silently fall back to
another ordering for a combination it offers. This applies to TYPO3 v12 and
v13 alike.

#### Scenario: Visitor reverses the manual order

- **WHEN** a visitor selects the sort field "Page sorting" and the direction
  "Descending"
- **THEN** the list renders the programs in the reverse of their manual order
- **AND** the direction select shows "Descending" as selected

#### Scenario: Visitor picks the ascending manual order

- **WHEN** a visitor selects the sort field "Page sorting" and the direction
  "Ascending"
- **THEN** the list renders the programs in their manual order

#### Scenario: An ordering reached through the shipped route enhancer

- **WHEN** a visitor opens a list URL whose path carries a sort field and a
  sort direction the enhancer maps
- **THEN** the list renders that ordering and the two selects show it as
  selected

### Requirement: Editors can configure the reversed manual order

The program list content element SHALL offer "Page sorting, reversed" as a
default ordering next to the existing orderings, on both supported TYPO3
versions.

#### Scenario: Editor configures the reversed manual order

- **WHEN** an editor sets the default ordering of a program list content
  element to "Page sorting, reversed"
- **THEN** a visitor who has not changed the sorting sees the programs in the
  reverse of their manual order

#### Scenario: Existing content element keeps its ordering

- **WHEN** a program list content element was saved with any previously
  available ordering
- **THEN** it renders that ordering unchanged
