## ADDED Requirements

### Requirement: A hidden part of the form keeps the preset of the content element

Where the content element hides the sorting, both project lists SHALL sort by
its default sorting, and where it hides the state select, they SHALL show the
projects of its active state, whatever a submission or a URL carries for that
part. Where it hides the category filter, they SHALL show only projects of all
its default categories, and categories a submission or a URL carries SHALL
narrow the list further and SHALL NOT replace a default category. This applies
on TYPO3 v13 and v14. The redirect target of a submission SHALL carry the
resulting values. A part the content element shows keeps the selection of the
visitor, as before.

#### Scenario: Visitor sorts a list whose category filter is hidden

- **WHEN** the content element hides the category filter, shows the sorting
  and presets the project category "Energy Research"
- **AND** a visitor submits the sorting title, descending
- **THEN** the redirect target carries the project category "Energy Research"
  and the sorting title, descending
- **AND** the list behind it shows only projects of that category, by title,
  descending

#### Scenario: Visitor filters a list whose sorting is hidden

- **WHEN** the content element hides the sorting, shows the category filter
  and presets the sorting title, descending
- **AND** a visitor submits a project category
- **THEN** the redirect target carries that category and the sorting title,
  descending
- **AND** the list behind it shows the projects of that category by title,
  descending

#### Scenario: Visitor filters a list whose state select is hidden

- **WHEN** the content element hides the state select, shows the category
  filter and presets the active state "active"
- **AND** a visitor submits a project category
- **THEN** the redirect target carries that category and the active state
  "active"
- **AND** the list behind it shows only active projects of that category

#### Scenario: Visitor sorts a list whose state select is hidden

- **WHEN** the content element hides the state select, shows the sorting and
  presets the active state "active"
- **AND** a visitor submits the sorting title, descending
- **THEN** the redirect target carries the active state "active"
- **AND** the list behind it shows only active projects

#### Scenario: A submission carries a sorting for a list whose sorting is hidden

- **WHEN** the content element hides the sorting and presets the sorting
  title, descending
- **AND** a submission or a URL carries the sorting title, ascending
- **THEN** the list is sorted by title, descending

#### Scenario: A URL carries a category for a list whose category filter is hidden

- **WHEN** the content element hides the category filter and presets the
  project category "Energy Research"
- **AND** a visitor opens the list with a URL that carries the cooperation
  "Industry"
- **THEN** the list shows only the energy research projects with industry

#### Scenario: A URL carries a category for a hidden filter without defaults

- **WHEN** the content element hides the category filter and presets no
  category
- **AND** a visitor opens the list with a URL that carries the project
  category "Quantum Physics"
- **THEN** the list shows only projects of that category

#### Scenario: A URL carries another state for a list whose state select is hidden

- **WHEN** the content element hides the state select and presets the active
  state "active"
- **AND** a visitor opens the list with a URL that carries the active state
  "completed"
- **THEN** the list shows only active projects

### Requirement: A filter keeps the defaults of the category types it does not offer

A submission of the form of a project list SHALL replace the preset categories
of the category types the form offers, and SHALL keep the preset categories of
every other type, on TYPO3 v13 and v14. Choosing "all" in a select SHALL clear
the preset categories of its type. A URL that carries a category filter SHALL
remain the whole selection.

#### Scenario: The site offers the cooperation only

- **WHEN** the site offers only the cooperation as a filter and the content
  element presets the project category "Energy Research"
- **AND** a visitor submits the cooperation "Industry"
- **THEN** the redirect target carries "Energy Research" and "Industry"
- **AND** the list behind it shows only the energy research projects with
  industry

#### Scenario: The form offers the type of the default

- **WHEN** the form offers the project category and the cooperation and the
  content element presets the project category "Energy Research"
- **AND** a visitor submits the project category "all" and the cooperation
  "Industry"
- **THEN** the list behind the redirect shows every project with industry
