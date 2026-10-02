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

#### Scenario: The filter is hidden

- **WHEN** active filter tags are enabled and the content element hides the
  filter
- **THEN** no tag is shown, whatever the list is filtered by

### Requirement: A reset link clears every filter

When the integrator enables the reset link, the list shows a selection the
visitor made and a category is selected or preselected by the content element,
the program list SHALL render a link to the same page without any filter
argument. Where the content element hides the filter, no reset link SHALL be
rendered.

#### Scenario: Visitor resets the filters

- **WHEN** the reset link is enabled and the visitor follows it
- **THEN** the program list is shown as the content element presets it,
  without any visitor selection

#### Scenario: Nothing to reset

- **WHEN** the reset link is enabled, the content element preselects nothing
  and the visitor changed the sorting only
- **THEN** no reset link is shown

### Requirement: The number of results is shown

When the integrator enables the result count, the program list SHALL render
the total number of matching programs with a singular or plural label.

#### Scenario: Several programs match

- **WHEN** the result count is enabled and 37 programs match
- **THEN** the list shows the count "37" with the plural label

### Requirement: The additions are off by default

Without explicit configuration the program list MUST render none of the
tags, the reset link and the count.

#### Scenario: Site without the new settings

- **WHEN** a site does not set any of the three new settings
- **THEN** neither tags, reset link nor result count are rendered
