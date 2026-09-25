# typo3-category-types/filter-select Specification

## Purpose
Defines which options the category filter form field renders for the
categories of a list filter, as an integrator configures it in a template.

## Requirements

### Requirement: Options without results can be left out

The category filter form field SHALL offer an opt-in switch. With the switch
on, an option for a category that no entry of the current result carries
SHALL be left out instead of being rendered as a disabled option. With the
switch off, the output SHALL be the same as without the switch. This applies
to TYPO3 v13 and v14 alike.

#### Scenario: Switch on

- **WHEN** an integrator enables the switch and one category of the filter
  has no result and is not selected
- **THEN** the filter renders no option for that category

#### Scenario: Every option without results

- **WHEN** the switch is on, the filter has an "all" option in front, and no
  category of the filter has a result
- **THEN** the "all" option is still rendered, as the way back to every
  result

#### Scenario: Template renders the options itself

- **WHEN** an integrator enables the switch and renders the options in the
  template instead of letting the form field render them
- **THEN** the options the template receives lack the categories without
  results, exactly as the rendered options would

#### Scenario: Switch off

- **WHEN** the switch is not set
- **THEN** the category without results is rendered as a disabled option, as
  before

### Requirement: A selected option is always kept

With the switch on, an option SHALL still be rendered when the visitor has
selected it - the value the filter is set to - even when its category has no
result. A preselection of every option while nothing is selected is no such
selection.

#### Scenario: Selected category without results

- **WHEN** the switch is on and the visitor's active filter is a category
  without results
- **THEN** that option is rendered, selected, so the visitor can see and
  change the active filter

#### Scenario: Selected by default only

- **WHEN** the switch is on, a multiple select preselects every option while
  nothing is selected, and one category has no result
- **THEN** that option is left out: nothing is selected, so there is no active
  filter to keep

### Requirement: The parent hierarchy stays intact

With the switch on and options grouped by parent category, a parent category
without results SHALL be kept as a disabled option while at least one of its
descendants is rendered.

#### Scenario: Parent without results, child with results

- **WHEN** the switch is on, options are grouped by parent, the parent has no
  result and one child has results
- **THEN** the parent is rendered as a disabled option and the child is
  rendered below it

#### Scenario: Parent and child without results

- **WHEN** the switch is on, options are grouped by parent, and neither the
  parent nor its child has a result
- **THEN** neither is rendered

#### Scenario: Not grouped

- **WHEN** the switch is on, options are not grouped, the parent has no result
  and one child has results
- **THEN** only the child is rendered: without grouping no option is shown
  below another
