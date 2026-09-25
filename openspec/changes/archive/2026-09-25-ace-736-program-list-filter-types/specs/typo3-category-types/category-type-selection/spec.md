## Purpose

Lets an integrator configure a backend select field that offers the category
types of one group, so that plugin settings can refer to category types
without a hard-coded item list.

## ADDED Requirements

### Requirement: A select field can offer the category types of a group
The system SHALL provide an items provider for backend select fields that
offers every registered category type of the group named in the field
configuration. Each item SHALL show the type title and icon and SHALL store
the type identifier.

#### Scenario: Field configured for the programs group
- **WHEN** an integrator configures a select field with the items provider
  and the group `programs`
- **THEN** an editor sees one item per category type of the programs group,
  with its title and icon
- **AND** saving stores the identifiers of the selected types

#### Scenario: Type added by a project
- **WHEN** a project package adds a category type to the programs group
- **THEN** the field offers that type as well

### Requirement: Items follow the type order of the group
The system SHALL offer the items in the type order of the group.

#### Scenario: Default order
- **WHEN** the field is rendered for the programs group
- **THEN** the items appear in the same order as the types of that group in
  the frontend

### Requirement: A missing or unknown group offers nothing
The system SHALL offer no items and SHALL NOT fail when the field names no
group or a group that is not registered.

#### Scenario: Unknown group
- **WHEN** an integrator configures the items provider with the group
  `unknown`
- **THEN** the field renders without items and without an error
