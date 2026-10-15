# academic-projects/list-filter Specification

## Purpose
Defines which category filters the two project list content elements offer a
visitor, in which order, how many are visible at first, how their "all"
options are labelled and whether options without results are shown, and which
projects the active state filter lists by their end date.

## Requirements

### Requirement: Integrators choose and order the filter types

The project filter SHALL offer the category types named in the setting for the
filter types, in the order given there, and SHALL ignore a name that is not a
registered project category type, and a type without any category. With the
setting empty, it SHALL offer every registered project category type that has
categories, in registry order, as before. This applies to TYPO3 v12 and v13
alike.

#### Scenario: Explicit order

- **WHEN** an integrator names two project category types in reverse
  registry order
- **THEN** the project filter shows them in the configured order and no
  other category filter

#### Scenario: No configuration

- **WHEN** neither filter types, a visible count nor the switch for options
  without results is configured
- **AND** the site overrides no label of the filter form
- **THEN** the project filter output is the same as before the change

### Requirement: Only the first filters are visible

With a visible count greater than 0, the project filter SHALL show that many
filters directly and SHALL put the remaining filters into a "more filters"
disclosure that works without JavaScript. The disclosure SHALL be open when
one of the filters inside it has an active value. A visible count of 0 SHALL
show all filters directly, and so SHALL a visible count that covers every
offered filter.

#### Scenario: Visible count of one

- **WHEN** the visible count is 1 and two filters are offered
- **THEN** the first filter is shown directly and the second is inside a
  closed "more filters" disclosure

#### Scenario: Active filter behind the disclosure

- **WHEN** the visitor has selected a value in a filter inside the
  disclosure
- **THEN** the disclosure is rendered open

### Requirement: Each "all" option can be labelled per type

The "all" option of a filter SHALL use the label defined for its category type
when one exists, and SHALL fall back to the generic "all" label of the
extension otherwise. The extension SHALL ship no label per type.
A label a site sets in TypoScript for every plugin of the extension, or for
one of its plugins, SHALL reach the filter on TYPO3 v12 and v13 alike.

#### Scenario: No per-type label

- **WHEN** no label is defined for the "all" option of a type
- **THEN** that filter's "all" option shows the generic label

#### Scenario: Per-type label defined

- **WHEN** a label for the "all" option of the competence field type is
  defined
- **THEN** the competence field filter's "all" option shows that label, and
  other filters show the generic label

#### Scenario: Label set for the whole extension on TYPO3 v12 or v13
- **WHEN** a site on TYPO3 v12 or v13 sets the label of the "all" option of the
  competence field type for every plugin of the extension
- **THEN** the competence field filter's "all" option shows that label

### Requirement: Options without results can be hidden

With the setting to hide options without results enabled, the project filter
SHALL leave out options that no project of the current result carries, except
a selected one. A filter whose options are all left out SHALL still be
offered, with its "all" option only.

#### Scenario: Hide options without results

- **WHEN** the setting is enabled and a category has no project in the
  result
- **THEN** the filter renders no option for that category

### Requirement: The active state filter lists projects by their end date

The "Active" filter of the project list SHALL list every project without an
end date and every project whose end date is still ahead, and the "Completed"
filter SHALL list every project whose end date has passed. A project page
without any stored end date SHALL count as a project without an end date,
exactly like one whose end date was saved empty, so it is listed by "Active"
and never by "Completed". A content element preset to active or completed
projects SHALL list the same projects as the filter. This applies to TYPO3 v12
and v13 alike.

#### Scenario: Project page without any stored end date

- **WHEN** a project page was created in the page tree, imported or switched
  to the project page type, and no editor has entered an end date since
- **AND** a visitor filters the project list by "Active"
- **THEN** the project is listed

#### Scenario: Project page without any stored end date in the completed list

- **WHEN** a project page has no stored end date
- **AND** a visitor filters the project list by "Completed"
- **THEN** the project is not listed

#### Scenario: Project whose end date has passed

- **WHEN** the end date of a project page has passed
- **THEN** the "Completed" filter lists it and the "Active" filter does not

#### Scenario: Content element preset to active projects

- **WHEN** an editor presets a project list content element to active
  projects
- **THEN** a project page without any stored end date and one whose end date
  was saved empty are part of the list
- **AND** a project whose end date has passed is not

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
