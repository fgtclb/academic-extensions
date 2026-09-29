# academic-persons/visitor-list-filter Specification

## Purpose
Defines which profiles the persons list shows when a visitor filters it by
function type or organisational unit, and when such a filter is accepted.

## Requirements

### Requirement: Editors enable visitor filters per list
The list and listanddetail plugins SHALL offer an editor one option to allow
a visitor filter by function type and one to allow a visitor filter by
organisational unit. Both options SHALL be off by default, and a filter value
in the request MUST be ignored while its option is off. This applies on
TYPO3 v13 and v14 alike.

#### Scenario: Option off
- **WHEN** a request carries a function type filter value and the function
  type option of the plugin is off
- **THEN** the list shows the same profiles as without the value

#### Scenario: Card plugin
- **WHEN** an editor edits a card content element
- **THEN** neither visitor filter option is offered

### Requirement: A visitor filter narrows the list
With the option on, the list SHALL show only profiles that have a contract
carrying the requested function type or organisational unit. The pagination
SHALL count only those profiles.

#### Scenario: Filter by function type
- **WHEN** the function type option is on and a visitor requests the function
  type "Professor"
- **THEN** only profiles with a contract of function type "Professor" are
  listed

#### Scenario: Pagination of a filtered list
- **WHEN** a filtered list has more matching profiles than fit on one page
- **THEN** the number of pages is derived from the matching profiles only

#### Scenario: Navigation keeps the filter
- **WHEN** a visitor follows a page link or a letter link of a filtered list
- **THEN** the list they reach is filtered in the same way
- **AND** the letter navigation offers only letters that lead to filtered
  profiles

#### Scenario: Two matching contracts
- **WHEN** a profile has two contracts of the requested function type
- **THEN** that profile is listed once

### Requirement: Both filters apply to the same contract
When a visitor filters by function type and organisational unit at once, the
list SHALL show only profiles with one contract that carries both values.

#### Scenario: Values on different contracts
- **WHEN** a profile has one contract with the requested function type and
  another contract with the requested organisational unit
- **THEN** that profile is not listed

### Requirement: Only offered values are accepted
The system SHALL ignore a filter value that is not an integer, that names no
existing record, or that lies outside the function types or organisational
units an editor restricted the plugin to.

#### Scenario: Value outside the editor restriction
- **WHEN** the editor restricted the plugin to two function types and a
  visitor requests a third one
- **THEN** the list shows the same profiles as without the value

#### Scenario: Value that is not a number
- **WHEN** a visitor requests a function type filter of `professor`, `1.5` or
  a list of values
- **THEN** the page renders and the list shows the same profiles as without
  the value

#### Scenario: Hidden record
- **WHEN** a visitor requests a function type that is hidden
- **THEN** the list shows the same profiles as without the value

### Requirement: The list offers the options of each enabled filter
For each filter an editor enabled, the list template SHALL receive its
options: the records the plugin is restricted to, or all records while it is
restricted to none, without hidden records and ordered by name.

#### Scenario: Options ordered by name
- **WHEN** the function type filter is on and the plugin is not restricted
- **THEN** the template receives every visible function type, ordered by
  name

#### Scenario: Restricted list in a translation
- **WHEN** the editor restricted the plugin to two function types and the
  list renders in a translation, in the fallback, strict or free language
  mode of the site
- **THEN** the template receives the translations of those two function
  types, each known by its default language uid
- **AND** a filter value of one of them narrows the list as in the default
  language

### Requirement: A manual selection ignores visitor filters
The system SHALL ignore visitor filter values when the plugin shows a manual
selection of profiles.

#### Scenario: Manual selection with a filter value
- **WHEN** an editor selected profiles manually and a visitor requests a
  function type filter
- **THEN** all selected profiles are shown in their selection order
