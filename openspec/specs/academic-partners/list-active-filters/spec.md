# academic-partners/list-active-filters Specification

## Purpose
Shows visitors of the partner list and map which filters are active, lets them
drop one or all of them with a link, and tells them how many partners match.

## Requirements

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

#### Scenario: The editor preselected a category

- **WHEN** active filter tags are enabled and a visitor opens a list whose
  content element preselects the region "Europe"
- **THEN** a tag "Europe" is shown
- **AND** it links to the list without any category

#### Scenario: The filter is hidden

- **WHEN** active filter tags are enabled and the content element hides the
  filter
- **THEN** no tag is shown, whatever the list is filtered by

#### Scenario: A tag on the map

- **WHEN** active filter tags are enabled on the partner map
- **THEN** each tag links back to the map

### Requirement: A reset link clears every filter

When the integrator enables the reset link, the list shows a selection the
visitor made and a category is selected or preselected by the content element,
the partner list and map SHALL render a link to the same page without any
filter argument. Where the content element hides the filter, no reset link
SHALL be rendered.

#### Scenario: Visitor resets the filters

- **WHEN** the reset link is enabled, two filters are active and the visitor
  follows the reset link
- **THEN** the partner list is shown as the content element presets it,
  without any visitor selection

#### Scenario: The list shows the preselection

- **WHEN** the reset link is enabled and a visitor opens a list whose content
  element preselects a category, without selecting anything
- **THEN** no reset link is shown

#### Scenario: Nothing to reset

- **WHEN** the reset link is enabled, the content element preselects nothing
  and the visitor changed the sorting only or went to another page
- **THEN** no reset link is shown

### Requirement: The number of results is shown

When the integrator enables the result count, the partner list and map SHALL
render the total number of matching partners with a singular or plural label,
in a paginated list the number of all of them, not of one page.

#### Scenario: Twelve partners match

- **WHEN** the result count is enabled and twelve partners match the filter
- **THEN** the list shows "12 partners found"

#### Scenario: One partner matches

- **WHEN** the result count is enabled and one partner matches the filter
- **THEN** the list shows the singular label

### Requirement: The additions are off by default

Without explicit configuration the partner list and map MUST render none of
the tags, the reset link and the count.

#### Scenario: Site without the new settings

- **WHEN** a site does not set any of the three new settings
- **THEN** neither tags, reset link nor result count are rendered
