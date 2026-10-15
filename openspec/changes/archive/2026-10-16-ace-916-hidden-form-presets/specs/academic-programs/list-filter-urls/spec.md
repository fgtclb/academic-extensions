## ADDED Requirements

### Requirement: A hidden part of the form keeps the preset of the content element

Where the content element hides the category filter, the program list SHALL
show only programs of its default categories, and where it hides the sorting,
it SHALL sort by its default sorting, whatever a submission or a URL carries
for the sorting, on TYPO3 v13 and v14. Categories a submission or a URL
carries for a list whose category filter is hidden SHALL narrow the list
further and SHALL NOT replace a default category. The redirect target of a
submission SHALL carry the resulting categories and sorting. A part the
content element shows keeps the selection of the visitor, as before.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the degree "Bachelor of Science"
- **AND** a visitor submits the sorting title, descending
- **THEN** the redirect target carries the degree "Bachelor of Science" and
  the sorting title, descending
- **AND** the list behind it shows only programs with that degree, by title,
  descending

#### Scenario: A submission carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a submission of its filter carries the sorting title, ascending
- **THEN** the redirect target carries the sorting title, descending
- **AND** the list behind it is sorted by title, descending

#### Scenario: A URL carries another degree for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  degree "Bachelor of Science"
- **AND** a visitor opens the list with a URL that carries the degree "Master
  of Science"
- **THEN** the list shows no program, since none carries both degrees

#### Scenario: A URL carries a category for a hidden filter without defaults

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the degree "Master
  of Science"
- **THEN** the list shows only the Master of Science programs

#### Scenario: A URL carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a visitor opens the list with a URL that carries the sorting title,
  ascending
- **THEN** the list is sorted by title, descending

### Requirement: A filter keeps the defaults of the category types it does not offer

A submission of the program list form SHALL replace the default categories of
the category types the form offers, and SHALL keep the default categories of
every other type, on TYPO3 v13 and v14. Choosing "all" in a select SHALL clear
the default categories of its type. A URL that carries a category filter SHALL
remain the whole selection.

#### Scenario: The filter types leave out the type of a default

- **WHEN** the filter types of the element offer the degree only and the
  content element presets the location "Berlin"
- **AND** a visitor submits the degree "Master of Science"
- **THEN** the list behind the redirect shows only Master of Science programs
  in Berlin

#### Scenario: The form offers the type of the default

- **WHEN** the form offers the location and the content element presets the
  location "Berlin"
- **AND** a visitor submits the location "all"
- **THEN** the list behind the redirect shows the programs of every location
