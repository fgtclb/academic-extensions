## ADDED Requirements

### Requirement: A hidden part of the form keeps the preset of the content element

Where the content element hides the sorting, the partner list and map SHALL
sort by its default sorting, whatever a submission or a URL carries for it.
Where it hides the category filter, they SHALL show only partners of all its
default categories, and categories a submission or a URL carries SHALL narrow
the list further and SHALL NOT replace a default category. This applies on
TYPO3 v13 and v14. The redirect target of a submission SHALL carry the
resulting categories and sorting. A part the content element shows keeps the
selection of the visitor, as before.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the region "Europe"
- **AND** a visitor submits the sorting title, descending
- **THEN** the redirect target carries the region "Europe" and the sorting
  title, descending
- **AND** the list behind it shows only partners of the region "Europe", by
  title, descending

#### Scenario: Visitor filters a list whose sorting is hidden

- **WHEN** the content element hides the sorting, shows the category filter
  and presets the sorting title, descending
- **AND** a visitor submits the region "Europe"
- **THEN** the redirect target carries the region "Europe" and the sorting
  title, descending
- **AND** the list behind it shows the partners of the region "Europe" by
  title, descending

#### Scenario: A URL carries a category for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  region "Europe"
- **AND** a visitor opens the list with a URL that carries the partner type
  "University"
- **THEN** the list shows only the European universities

#### Scenario: A URL carries a category for a hidden filter without defaults

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the region
  "Americas"
- **THEN** the list shows only partners of the region "Americas"

#### Scenario: A submission carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a submission or a URL carries the sorting title, ascending
- **THEN** the list is sorted by title, descending

#### Scenario: Visitor sorts a map whose category filter is hidden

- **WHEN** the content element of a partner map hides the category filter,
  shows the sorting and presets the region "Europe"
- **AND** a visitor submits a sorting
- **THEN** the map behind the redirect shows only partners of the region
  "Europe"

### Requirement: A filter keeps the defaults of the category types it does not offer

A submission of the partner list or map form SHALL replace the default
categories of the category types the form offers, and SHALL keep the default
categories of every other type, on TYPO3 v13 and v14. Choosing "all" in a
select SHALL clear the default categories of its type. A URL that carries a
category filter SHALL remain the whole selection.

#### Scenario: The site offers the partner type only

- **WHEN** the site offers only the partner type as a filter and the content
  element presets the region "Europe"
- **AND** a visitor submits the partner type "University"
- **THEN** the redirect target carries "Europe" and "University"
- **AND** the list behind it shows only the European universities

#### Scenario: The form offers the type of the default

- **WHEN** the form offers the region and the partner type and the content
  element presets the region "Europe"
- **AND** a visitor submits the region "all" and the partner type "University"
- **THEN** the list behind the redirect shows the universities of every region
