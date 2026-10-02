# academic-programs/list-routing Specification

## Purpose
Gives integrators an importable route configuration that turns the program
list arguments into readable, translated URLs.

## Requirements

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

### Requirement: Sorting values follow the site language

Sorting values SHALL be rendered in the language of the site, and a value of
another language MUST NOT resolve.

#### Scenario: German site

- **WHEN** a visitor on a German site sorts the program list by title
  descending
- **THEN** the path carries `titel/absteigend`
- **AND** the path `title/desc` answers 404 on that site

### Requirement: The former route file keeps working

A site that imports the former program route file SHALL get the same routes
as a site that imports the new one, and its page limits for the enhancer
SHALL still apply.

#### Scenario: Site importing the former file

- **WHEN** a site imports the former program route file and the visitor
  filters by a degree and sorts by the last update
- **THEN** the URL carries both as path segments and resolves to that list

### Requirement: Import is opt-in

The route configuration SHALL take effect only when a site imports it, and a
site that does not import it MUST keep its current URLs.

#### Scenario: Site without the import

- **WHEN** a site does not import the program route configuration
- **THEN** filtered program list URLs keep their query string arguments
