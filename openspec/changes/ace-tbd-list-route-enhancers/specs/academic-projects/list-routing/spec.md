## Purpose

Gives integrators an importable route configuration that turns the project
list arguments into readable, translated URLs.

## ADDED Requirements

### Requirement: Every argument combination has a readable URL

When a site imports the shipped project route configuration, every
combination of category filter, sorting and active state SHALL generate a
readable path and SHALL resolve back to the same list, for both project list
content elements, on TYPO3 v13 and v14.

#### Scenario: Only an active state

- **WHEN** a visitor selects the active state "completed" without a filter
- **THEN** the URL is a readable path with the localised active state segment
- **AND** opening that path shows only completed projects instead of a 404

#### Scenario: Filter and active state

- **WHEN** a visitor filtered by a competence field and selected "active"
- **THEN** the URL carries both as path segments and resolves to that list

### Requirement: Import is opt-in

The route configuration SHALL take effect only when a site imports it; a
site that does not import it MUST keep its current URLs.

#### Scenario: Site without the import

- **WHEN** a site does not import the project route configuration
- **THEN** filtered project list URLs keep their query string arguments
