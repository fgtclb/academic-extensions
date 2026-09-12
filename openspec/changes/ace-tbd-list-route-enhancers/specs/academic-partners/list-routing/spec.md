## Purpose

Gives integrators an importable route configuration that turns the partner
list and map arguments into readable, translated URLs.

## ADDED Requirements

### Requirement: Every argument combination has a readable URL

When a site imports the shipped partner route configuration, every
combination of category filter, sorting and page SHALL generate a readable
path and SHALL resolve back to the same list, on TYPO3 v13 and v14.

#### Scenario: Only a filter on page one

- **WHEN** a visitor filtered the partner list by the region "Europe" and
  stays on page one
- **THEN** the URL is a readable path such as `…/filter/europe-12`
- **AND** opening that path shows the region-filtered list instead of a 404

#### Scenario: Only a sorting

- **WHEN** a visitor sorted the list by title descending without a filter
- **THEN** the URL is a readable path with the localised sorting segment and
  it resolves to the sorted list

#### Scenario: Filter, sorting and page

- **WHEN** a visitor filtered, sorted and moved to page two
- **THEN** the URL carries all three as path segments and resolves to page
  two of that list

### Requirement: Path keys follow the site language

Static path keys and sorting values SHALL be rendered in the language of the
site: German sites SHALL use German keys, English sites English keys.

#### Scenario: German site

- **WHEN** the partner list of a German site is paged
- **THEN** the page key in the path is `seite`

### Requirement: Import is opt-in

The route configuration SHALL take effect only when a site imports it; a
site that does not import it MUST keep its current URLs.

#### Scenario: Site without the import

- **WHEN** a site does not import the partner route configuration
- **THEN** filtered partner list URLs keep their query string arguments
