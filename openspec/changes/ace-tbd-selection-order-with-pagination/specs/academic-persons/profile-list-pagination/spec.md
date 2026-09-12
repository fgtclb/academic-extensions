## Purpose

Defines how the list and list-and-detail plugins of `academic_persons` split
the profiles they show into pages, for a query result and for a manual
selection alike.

## ADDED Requirements

### Requirement: A paginated manual selection keeps the editor's order

When an editor selects profiles manually and enables pagination, the system
SHALL render the selected profiles in the order of the selection, split into
pages of the configured size. This applies on TYPO3 v13 and v14.

#### Scenario: First page of a selection

- **WHEN** a list plugin selects the profiles 3, 1 and 2 in that order with
  pagination enabled and two results per page, and a visitor opens the list
- **THEN** the first page shows profile 3 followed by profile 1
- **AND** profile 2 is not shown

#### Scenario: Second page of a selection

- **WHEN** the visitor follows the link to page 2 of the same list
- **THEN** the page shows only profile 2

### Requirement: Every selected profile appears on exactly one page

The system MUST produce the same page contents for the same selection on every
supported database system, so that no selected profile is shown on two pages
or on none.

#### Scenario: Pages on PostgreSQL

- **WHEN** the paginated selection is rendered on PostgreSQL
- **THEN** the union of all pages contains each selected profile exactly once
- **AND** the pages match the pages rendered on SQLite

### Requirement: Lists without a selection paginate as before

The system SHALL paginate a list without a manual selection in the order
derived from the plugin's sorting and grouping settings, unchanged by this
requirement set.

#### Scenario: List sorted by last name

- **WHEN** a list plugin without a selection sorts by last name with pagination
  enabled
- **THEN** the pages show the profiles in last name order, as before
