## Purpose

Shows visitors of the project lists which filters are active, lets them drop
one or all of them with a link, and tells them how many projects match.

## ADDED Requirements

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

### Requirement: A reset link clears every filter

When the integrator enables the reset link and at least one selection
differs from the default, the project lists SHALL render a link to the same
list without any filter argument.

#### Scenario: Visitor resets the filters

- **WHEN** the reset link is enabled and the visitor follows it
- **THEN** the project list is shown as the content element presets it, with
  the default active state

### Requirement: The number of results is shown

When the integrator enables the result count, the project lists SHALL render
the total number of matching projects with a singular or plural label.

#### Scenario: No project matches

- **WHEN** the result count is enabled and no project matches
- **THEN** the list shows the count "0" with the plural label

### Requirement: The additions are off by default

Without explicit configuration the project lists MUST render exactly the
markup they rendered before this change.

#### Scenario: Site without the new settings

- **WHEN** a site does not set any of the three new settings
- **THEN** neither tags, reset link nor result count are rendered
