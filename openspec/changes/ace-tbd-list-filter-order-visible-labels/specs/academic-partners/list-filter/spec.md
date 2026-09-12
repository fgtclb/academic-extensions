## Purpose

Defines which category filters the partner list and map content elements
offer a visitor, in which order, how many are visible at first and how their
"all" options are labelled.

## ADDED Requirements

### Requirement: Integrators choose and order the filter types

The partner filter SHALL offer the category types named in the setting for
the filter types, in the order given there, and SHALL ignore a name that is
not a registered partner category type. With the setting empty, it SHALL
offer every registered partner category type that has categories, in
registry order, as before. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Explicit order

- **WHEN** an integrator sets the filter types to `sdg,region`
- **THEN** the partner filter shows the SDG filter before the region filter
  and no other category filter

#### Scenario: No configuration

- **WHEN** no filter types are configured
- **THEN** the partner filter output is the same as before the change

### Requirement: Only the first filters are visible

With a visible count greater than 0, the partner filter SHALL show that many
filters directly and SHALL put the remaining filters into a "more filters"
disclosure that works without JavaScript. The disclosure SHALL be open when
one of the filters inside it has an active value. A visible count of 0 SHALL
show all filters directly.

#### Scenario: Visible count of one

- **WHEN** the visible count is 1 and three filters are offered
- **THEN** the first filter is shown directly and the other two are inside a
  closed "more filters" disclosure

#### Scenario: Active filter behind the disclosure

- **WHEN** the visitor has selected a value in a filter inside the
  disclosure
- **THEN** the disclosure is rendered open

### Requirement: Each "all" option can be labelled per type

The "all" option of a filter SHALL use the label defined for its category type
when one exists, and SHALL fall back to the generic "all" label of the
extension otherwise.

#### Scenario: Per-type label defined

- **WHEN** a label for the "all" option of the region type is defined
- **THEN** the region filter's "all" option shows that label, and other
  filters show the generic label

### Requirement: Options without results can be hidden

With the setting to hide options without results enabled, the partner filter
SHALL leave out options that no partner of the current result carries,
except the selected one.

#### Scenario: Hide options without results

- **WHEN** the setting is enabled and a region has no partner in the result
- **THEN** the region filter renders no option for that region
