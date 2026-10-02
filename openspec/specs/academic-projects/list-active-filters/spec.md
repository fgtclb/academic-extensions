# academic-projects/list-active-filters Specification

## Purpose
Shows visitors of the project lists which filters are active, lets them drop
one or all of them with a link, and tells them how many projects match.

## Requirements

### Requirement: Active filters are shown as removable tags

When the integrator enables active filter tags, both project lists SHALL
render one tag per selected filter category, and one tag for an active state
other than the default. Each tag SHALL link to the same list with exactly
that selection removed and every other selection kept.

#### Scenario: Category and active state are selected

- **WHEN** active filter tags are enabled and a visitor selected a
  competence field and the active state "completed"
- **THEN** a tag for the competence field and a tag for "completed" are shown
- **AND** the "completed" tag links to the list with the competence field kept
  and the default active state

#### Scenario: The state select is hidden

- **WHEN** active filter tags are enabled and the content element hides the
  state select and presets the state "completed"
- **THEN** no tag is shown for the state
- **AND** a category tag keeps the state "completed" in its link

### Requirement: A reset link clears every filter

When the integrator enables the reset link, the list shows a selection the
visitor made and a category or an active state other than "all" is selected or
preset by the content element, the project lists SHALL render a link to the
same page without any filter argument.

#### Scenario: Visitor resets the filters

- **WHEN** the reset link is enabled and the visitor follows it
- **THEN** the project list is shown as the content element presets it,
  categories and active state included

#### Scenario: Nothing to reset

- **WHEN** the reset link is enabled, the content element presets no category
  and the state "all", and the visitor changed the sorting only
- **THEN** no reset link is shown

#### Scenario: The list shows the preset state

- **WHEN** the reset link is enabled and a visitor opens a list whose content
  element presets the state "completed", without selecting anything
- **THEN** a tag "Completed" is shown and no reset link

### Requirement: The number of results is shown

When the integrator enables the result count, the project lists SHALL render
the total number of matching projects with a singular or plural label.

#### Scenario: No project matches

- **WHEN** the result count is enabled and no project matches
- **THEN** the list shows the count "0" with the plural label

### Requirement: The additions are off by default

Without explicit configuration the project lists MUST render none of the
tags, the reset link and the count.

#### Scenario: Site without the new settings

- **WHEN** a site does not set any of the three new settings
- **THEN** neither tags, reset link nor result count are rendered
