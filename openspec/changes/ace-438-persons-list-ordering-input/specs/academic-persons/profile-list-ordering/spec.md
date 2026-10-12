## Purpose

Defines the ordering the persons list of `academic_persons` applies for the
sort field, group field and sort direction an integrator or editor configures,
and how the list treats empty or unknown values of those settings.

## ADDED Requirements

### Requirement: The list orders by the configured field and direction

When a persons list is not a manual selection, the system SHALL render its
profiles grouped by the configured group field, then ordered by the configured
sort field in the configured direction, with profiles that are equal in all of
them in the same relative order on every request. This applies on TYPO3 v13
and v14 and on every supported database system.

#### Scenario: Last names in descending order

- **WHEN** a list element sorts by last name in descending direction, and the
  profiles were created in an order that is not their last name order
- **THEN** the list renders the profiles by last name from Z to A

#### Scenario: No sort field selected

- **WHEN** a list element has "none" selected as its sort field and as its
  group field
- **THEN** the list renders the profiles in the order they were created

### Requirement: An empty entry of the configured sort or group values is no option

The system SHALL ignore every empty entry of the extension configuration that
lists the allowed sort values and the allowed group values, whether it comes
from an emptied setting, a trailing comma or two commas in a row. The sort and
group selects of the list element MUST then offer "none" once and every
configured value, and a list on "none" MUST render without an error. This
applies on TYPO3 v13 and v14 and on every supported database system.

#### Scenario: Trailing comma in the allowed sort values

- **WHEN** an integrator configures the allowed sort values with a trailing
  comma, and an editor opens a persons list element
- **THEN** the sort select offers "none" and the configured fields, and no
  item without a label

#### Scenario: List on "none" with an empty entry configured

- **WHEN** the allowed sort values and the allowed group values each contain
  an empty entry, and a visitor opens a list whose element has "none"
  selected for both
- **THEN** the list renders its profiles in the order they were created
- **AND** no database error is raised

#### Scenario: Allowed sort values emptied

- **WHEN** an integrator empties the allowed sort values
- **THEN** the sort select offers only "none"
- **AND** every persons list renders its profiles in the order they were
  created

### Requirement: Configured values without a label or without a value

The system SHALL label a configured sort or group value that has no label with
the value itself, and SHALL ignore an entry that has a label but no value,
without raising a PHP warning.

#### Scenario: Value with an empty label

- **WHEN** an integrator configures the allowed sort values as
  `firstName=,lastName=Last name`
- **THEN** the sort select offers "firstName" and "Last name"

#### Scenario: Label without a value

- **WHEN** an integrator configures the allowed sort values as
  `=First name,lastName=Last name`
- **THEN** the sort select offers "none" and "Last name" only

### Requirement: An unreadable sort or group configuration does not break the list

When the extension configuration of the allowed sort values or the allowed
group values cannot be read, the system SHALL treat the list as empty: the
select offers only "none" and the persons list renders in the order the
profiles were created, instead of failing with an error.

#### Scenario: Configuration path cannot be restored

- **WHEN** the allowed sort values are missing from the stored extension
  configuration and the system cannot restore them, and a visitor opens a
  persons list
- **THEN** the list renders its profiles in the order they were created

### Requirement: The sort direction is read in any letter case

The system SHALL accept the sort direction `asc` or `desc` in any letter case,
from the content element, from a site setting, from a TypoScript constant and
from an event listener that changes the list demand. An unknown or empty
direction MUST keep the direction set before, which is ascending unless
something set descending, so the list keeps its ordering by the sort field.
This applies on TYPO3 v13 and v14.

#### Scenario: Uppercase direction from a site setting

- **WHEN** a site sets the persons sort direction setting to `DESC`, and a
  list element that stores no sort direction of its own sorts by last name
- **THEN** the list renders the profiles by last name from Z to A

#### Scenario: Unknown direction from a site setting

- **WHEN** a site sets the persons sort direction setting to `down`, and a
  list element that stores no sort direction of its own sorts by last name
- **THEN** the list renders the profiles by last name from A to Z

#### Scenario: Listener sets an unknown direction after a valid one

- **WHEN** a list element sorts by last name in descending direction, and an
  event listener that changes the list demand sets the direction `reverse`
- **THEN** the list renders the profiles by last name from Z to A
