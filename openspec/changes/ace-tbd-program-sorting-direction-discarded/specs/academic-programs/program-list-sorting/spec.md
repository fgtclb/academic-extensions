## Purpose

Defines which orderings the program list offers to editors and site visitors,
and that every ordering the list offers is the ordering it renders.

## ADDED Requirements

### Requirement: Every offered field and direction combination is honoured

The program list SHALL render programs in the ordering a visitor selects in
the sort field and sort direction selects, for every combination of a field
and a direction those selects offer. The list MUST NOT silently fall back to
another ordering for a combination it offers. This applies to TYPO3 v13 and
v14 alike.

#### Scenario: Visitor reverses the manual order

- **WHEN** a visitor selects the sort field "manual order" and the direction
  "descending" on a list of three manually sorted programs
- **THEN** the list renders the three programs in the reverse of their manual
  order
- **AND** the direction select shows "descending" as selected

#### Scenario: Visitor picks the ascending manual order

- **WHEN** a visitor selects the sort field "manual order" and the direction
  "ascending"
- **THEN** the list renders the programs in their manual order

#### Scenario: Programs share a sort value

- **WHEN** two programs are equal in the selected ordering
- **THEN** they are rendered in the same relative order on every request

### Requirement: Editors can configure the reversed manual order

The program list content element SHALL offer "manual order, descending" as a
default ordering next to the existing orderings.

#### Scenario: Editor configures the reversed manual order

- **WHEN** an editor sets the default ordering of a program list content
  element to "manual order, descending"
- **THEN** a visitor who has not changed the sorting sees the programs in the
  reverse of their manual order

#### Scenario: Existing content element keeps its ordering

- **WHEN** a program list content element was saved with any previously
  available ordering
- **THEN** it renders that ordering unchanged
