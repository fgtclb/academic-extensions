## Purpose

Shows visitors of the partner list and map which filters are active, lets them
drop one or all of them with a link, and tells them how many partners match.

## ADDED Requirements

### Requirement: Active filters are shown as removable tags

When the integrator enables active filter tags, the partner list and map SHALL
render one tag per selected filter category, labelled with the category title
in the current language. Each tag SHALL link to the same list with exactly
that category removed and every other selection kept.

#### Scenario: Two filters are active

- **WHEN** active filter tags are enabled and a visitor filtered by the region
  "Europe" and the partner type "University"
- **THEN** two tags "Europe" and "University" are shown
- **AND** the "Europe" tag links to the list filtered by "University" only

#### Scenario: No filter is active

- **WHEN** active filter tags are enabled and no filter is selected
- **THEN** no tag line is rendered

### Requirement: A reset link clears every filter

When the integrator enables the reset link and at least one filter is active,
the partner list and map SHALL render a link to the same list without any
filter argument.

#### Scenario: Visitor resets the filters

- **WHEN** the reset link is enabled, two filters are active and the visitor
  follows the reset link
- **THEN** the partner list is shown as the content element presets it,
  without any visitor selection

### Requirement: The number of results is shown

When the integrator enables the result count, the partner list SHALL render
the total number of matching partners with a singular or plural label.

#### Scenario: Twelve partners match

- **WHEN** the result count is enabled and twelve partners match the filter
- **THEN** the list shows "12 partners found"

#### Scenario: One partner matches

- **WHEN** the result count is enabled and one partner matches the filter
- **THEN** the list shows the singular label

### Requirement: The additions are off by default

Without explicit configuration the partner list and map MUST render exactly
the markup they rendered before this change.

#### Scenario: Site without the new settings

- **WHEN** a site does not set any of the three new settings
- **THEN** neither tags, reset link nor result count are rendered
