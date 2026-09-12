## Purpose

Defines which category filters the project list content element offers a
visitor, in which order, how many are visible at first and how their "all"
options are labelled.

## ADDED Requirements

### Requirement: Integrators choose and order the filter types

The project filter SHALL offer the category types named in the setting for
the filter types, in the order given there, and SHALL ignore a name that is
not a registered project category type. With the setting empty, it SHALL
offer every registered project category type that has categories, in
registry order, as before. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Explicit order

- **WHEN** an integrator names two project category types in reverse
  registry order
- **THEN** the project filter shows them in the configured order and no
  other category filter

#### Scenario: No configuration

- **WHEN** no filter types are configured
- **THEN** the project filter output is the same as before the change

### Requirement: Only the first filters are visible

With a visible count greater than 0, the project filter SHALL show that many
filters directly and SHALL put the remaining filters into a "more filters"
disclosure that works without JavaScript. The disclosure SHALL be open when
one of the filters inside it has an active value. A visible count of 0 SHALL
show all filters directly.

#### Scenario: Visible count of one

- **WHEN** the visible count is 1 and two filters are offered
- **THEN** the first filter is shown directly and the second is inside a
  closed "more filters" disclosure

### Requirement: Each "all" option can be labelled per type

The "all" option of a filter SHALL use the label defined for its category type
when one exists, and SHALL fall back to the generic "all" label of the
extension otherwise.

#### Scenario: No per-type label

- **WHEN** no label is defined for the "all" option of a type
- **THEN** that filter's "all" option shows the generic label

### Requirement: Options without results can be hidden

With the setting to hide options without results enabled, the project filter
SHALL leave out options that no project of the current result carries,
except the selected one.

#### Scenario: Hide options without results

- **WHEN** the setting is enabled and a category has no project in the
  result
- **THEN** the filter renders no option for that category
