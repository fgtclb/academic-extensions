## ADDED Requirements

### Requirement: A hidden part of the form keeps the preset of the content element

Where the content element hides the category filter and has default categories,
both project lists SHALL show only projects of those categories, where it hides
the sorting, they SHALL sort by its default sorting, and where it hides the
state select, they SHALL show the projects of its active state, for every
request. A sorting or an active state a submission or a URL carries for a
hidden part SHALL be ignored. Categories a submission or a URL carries for an
element that hides the category filter SHALL be added to the default
categories, so they narrow the list further and never replace a default
category. A part the content element shows SHALL keep the selection of the
visitor, as before. This applies to TYPO3 v12 and v13 alike.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the project category "Energy Research"
- **AND** a visitor submits the sorting title, descending
- **THEN** the list shows only projects of that category, by title, descending

#### Scenario: Visitor sorts selected projects whose category filter is hidden

- **WHEN** the selected projects element hides the category filter, shows the
  sorting and presets the project category "Energy Research"
- **AND** a visitor submits the sorting title, descending
- **THEN** the element shows only the selected projects of that category, by
  title, descending

#### Scenario: A URL narrows a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  project category "Energy Research"
- **AND** a visitor opens the list with a URL that carries the cooperation
  "Industry"
- **THEN** the list shows only the industry projects of "Energy Research"

#### Scenario: A URL carries another category for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  project category "Energy Research"
- **AND** a visitor opens the list with a URL that carries the project
  category "Quantum Physics"
- **THEN** the list shows no project, since none carries both categories

#### Scenario: A URL filters a list whose category filter is hidden without default categories

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the project
  category "Quantum Physics"
- **THEN** the list shows only projects of that category, as before

#### Scenario: Visitor filters a list whose category filter is shown

- **WHEN** the content element shows the category filter and presets the
  project category "Energy Research"
- **AND** a visitor submits the project category "Quantum Physics"
- **THEN** the list shows only projects of "Quantum Physics"

#### Scenario: Visitor filters a list whose sorting is hidden

- **WHEN** the content element hides the sorting, shows the category filter
  and presets the sorting title, descending
- **AND** a visitor submits a project category
- **THEN** the list shows the projects of that category by title, descending

#### Scenario: Visitor filters a list whose state select is hidden

- **WHEN** the content element hides the state select, shows the category
  filter and presets the active state "active"
- **AND** a visitor submits a project category
- **THEN** the list shows only active projects of that category

#### Scenario: A URL carries another state for a list whose state select is hidden

- **WHEN** the content element hides the state select and presets the active
  state "active"
- **AND** a visitor opens the list with a URL that carries the active state
  "completed"
- **THEN** the list shows only active projects
