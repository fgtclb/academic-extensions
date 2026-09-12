## Purpose

Shows visitors of the program list which filters are active, lets them drop
one or all of them with a link, and tells them how many programs match.

## ADDED Requirements

### Requirement: Active filters are shown as removable tags

When the integrator enables active filter tags, the program list SHALL render
one tag per selected filter category. Each tag SHALL link to the same list
with exactly that category removed and every other selection kept.

#### Scenario: Degree and teaching language are selected

- **WHEN** active filter tags are enabled and a visitor selected a degree and
  a teaching language
- **THEN** one tag per selection is shown
- **AND** the degree tag links to the list filtered by the teaching language
  only

### Requirement: A reset link clears every filter

When the integrator enables the reset link and at least one filter is active,
the program list SHALL render a link to the same list without any filter
argument.

#### Scenario: Visitor resets the filters

- **WHEN** the reset link is enabled and the visitor follows it
- **THEN** the program list is shown as the content element presets it,
  without any visitor selection

### Requirement: The number of results is shown

When the integrator enables the result count, the program list SHALL render
the total number of matching programs with a singular or plural label.

#### Scenario: Several programs match

- **WHEN** the result count is enabled and 37 programs match
- **THEN** the list shows the count "37" with the plural label

### Requirement: The additions are off by default

Without explicit configuration the program list MUST render exactly the
markup it rendered before this change.

#### Scenario: Site without the new settings

- **WHEN** a site does not set any of the three new settings
- **THEN** neither tags, reset link nor result count are rendered
