## ADDED Requirements

### Requirement: A hidden category filter keeps the default categories

Where the content element hides the category filter and has default categories,
the partner list and the partner map SHALL show only partners of those
categories for every request. Categories a submission or a URL carries for such
an element SHALL be added to the default categories, so they narrow the list
further and never replace a default category. Where the content element shows
the category filter, the selection of the visitor SHALL replace the default
categories, as before. This applies to TYPO3 v12 and v13 alike.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the region "Europe"
- **AND** a visitor submits the sorting title, descending
- **THEN** the list shows only partners of the region "Europe", by title,
  descending

#### Scenario: A URL narrows a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  region "Europe"
- **AND** a visitor opens the list with a URL that carries the partner type
  "University"
- **THEN** the list shows only the universities of the region "Europe"

#### Scenario: A URL carries another region for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  region "Europe"
- **AND** a visitor opens the list with a URL that carries the region
  "Americas"
- **THEN** the list shows no partner, since none is in both regions

#### Scenario: A URL filters a list whose category filter is hidden without default categories

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the region
  "Americas"
- **THEN** the list shows only partners of the region "Americas", as before

#### Scenario: Visitor filters a list whose category filter is shown

- **WHEN** the content element shows the category filter and presets the
  region "Europe"
- **AND** a visitor submits the region "Americas"
- **THEN** the list shows only partners of the region "Americas"

#### Scenario: Visitor sorts a map whose category filter is hidden

- **WHEN** the content element of a partner map hides the category filter,
  shows the sorting and presets the region "Europe"
- **AND** a visitor submits a sorting
- **THEN** the map shows only partners of the region "Europe"
