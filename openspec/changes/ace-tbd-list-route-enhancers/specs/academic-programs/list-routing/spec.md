## Purpose

Gives integrators an importable route configuration that turns the program
list arguments into readable, translated URLs.

## ADDED Requirements

### Requirement: Every argument combination has a readable URL

When a site imports the shipped program route configuration, the
combinations "filter only", "sorting only" and "filter and sorting" SHALL
each generate a readable path and SHALL resolve back to the same list, on
TYPO3 v13 and v14.

#### Scenario: Only a filter

- **WHEN** a visitor filtered the program list by a degree
- **THEN** the URL is a readable path with the degree's segment and it
  resolves to the filtered list instead of a 404

#### Scenario: Filter and sorting

- **WHEN** a visitor filtered by a degree and sorted by title descending
- **THEN** the URL carries both as path segments and resolves to that list

### Requirement: Import is opt-in

The route configuration SHALL take effect only when a site imports it; a
site that does not import it MUST keep its current URLs.

#### Scenario: Site without the import

- **WHEN** a site does not import the program route configuration
- **THEN** filtered program list URLs keep their query string arguments
