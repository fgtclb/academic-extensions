## ADDED Requirements

### Requirement: A hidden category filter keeps the default categories

Where the content element hides the category filter and has default categories,
the program list SHALL show only programs of those categories for every
request. Categories a submission or a URL carries for such an element SHALL be
added to the default categories, so they narrow the list further and never
replace a default category. Where the content element shows the category
filter, the selection of the visitor SHALL replace the default categories, as
before. This applies to TYPO3 v12 and v13 alike.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the degree "Bachelor of Science"
- **AND** a visitor submits the sorting title, descending
- **THEN** the list shows only programs with that degree

#### Scenario: A URL narrows a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  degree "Bachelor of Science"
- **AND** a visitor opens the list with a URL that carries the topic
  "Engineering"
- **THEN** the list shows only the engineering programs with that degree

#### Scenario: A URL carries another degree for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  degree "Bachelor of Science"
- **AND** a visitor opens the list with a URL that carries the degree "Master
  of Science"
- **THEN** the list shows no program, since none carries both degrees

#### Scenario: A URL filters a list whose category filter is hidden without default categories

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the degree "Master
  of Science"
- **THEN** the list shows only programs with that degree, as before

#### Scenario: Visitor filters a list whose category filter is shown

- **WHEN** the content element shows the category filter and presets the
  degree "Bachelor of Science"
- **AND** a visitor submits the degree "Master of Science"
- **THEN** the list shows only programs with the degree "Master of Science"
