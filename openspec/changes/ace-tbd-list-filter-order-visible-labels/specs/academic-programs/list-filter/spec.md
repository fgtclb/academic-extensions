## Purpose

Defines which category filters the program list content element offers a
visitor, in which order, how many are visible at first and how their "all"
options are labelled.

## ADDED Requirements

### Requirement: Integrators choose and order the filter types

The program filter SHALL offer the category types named in the setting for
the filter types, in the order given there, and SHALL ignore a name that is
not a registered program category type. With the setting empty, it SHALL
offer every registered program category type that has categories, in
registry order, as before. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Curated subset

- **WHEN** an integrator names two of the registered program category types
- **THEN** the program filter shows only those two, in the configured order

#### Scenario: No configuration

- **WHEN** no filter types are configured
- **THEN** the program filter output is the same as before the change

### Requirement: Only the first filters are visible

With a visible count greater than 0, the program filter SHALL show that many
filters directly and SHALL put the remaining filters into a "more filters"
disclosure that works without JavaScript. The disclosure SHALL be open when
one of the filters inside it has an active value. A visible count of 0 SHALL
show all filters directly.

#### Scenario: Visible count larger than the number of filters

- **WHEN** the visible count is 5 and three filters are offered
- **THEN** all three filters are shown directly and no disclosure is rendered

### Requirement: Each "all" option can be labelled per type

The "all" option of a filter SHALL use the label defined for its category type
when one exists, and SHALL fall back to the generic "all" label of the
extension otherwise.

#### Scenario: Per-type label defined

- **WHEN** a label for the "all" option of one program category type is
  defined
- **THEN** that filter's "all" option shows it

### Requirement: Options without results can be hidden

With the setting to hide options without results enabled, the program filter
SHALL leave out options that no program of the current result carries,
except the selected one.

#### Scenario: Hide options without results

- **WHEN** the setting is enabled and a category has no program in the result
- **THEN** the filter renders no option for that category
