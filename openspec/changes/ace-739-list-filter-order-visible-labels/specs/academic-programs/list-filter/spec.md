## Purpose

Defines which category filters the program list content element offers a
visitor, in which order, how many are visible at first, how their "all"
options are labelled and whether options without results are shown.

## ADDED Requirements

### Requirement: Integrators choose and order the filter types

The program filter SHALL offer the category types named in the setting for the
filter types, in the order given there, and SHALL ignore a name that is not a
registered program category type, and a type without any category. With the
setting empty, it SHALL offer every registered program category type that has
categories, in registry order, as before. This applies to TYPO3 v12 and v13
alike.

#### Scenario: Explicit order

- **WHEN** an integrator names two program category types in reverse
  registry order
- **THEN** the program filter shows them in the configured order and no
  other category filter

#### Scenario: No configuration

- **WHEN** neither filter types, a visible count nor the switch for options
  without results is configured
- **AND** the site overrides no label of the filter form
- **THEN** the program filter output is the same as before the change

#### Scenario: Project template written before the settings

- **WHEN** a project template renders the filter form of the program list
  without handing the resolved filter types on
- **THEN** its filter form shows the same selects in the same order as
  before, whatever filter types the site sets

### Requirement: Submitted filters of other types still apply

The program list SHALL keep applying a submitted filter value of a type the
form does not offer.

#### Scenario: Filter of a type that is not offered

- **WHEN** the site offers only the filter type `degree`
- **AND** a visitor submits a filter by a `location` category
- **THEN** the list shows only programs that carry that location

### Requirement: Only the first filters are visible

With a visible count greater than 0, the program filter SHALL show that many
filters directly and SHALL put the remaining filters into a "more filters"
disclosure that works without JavaScript. The disclosure SHALL be open when
one of the filters inside it has an active value. A visible count of 0 SHALL
show all filters directly, and so SHALL a visible count that covers every
offered filter.

#### Scenario: Visible count of one

- **WHEN** the visible count is 1 and two filters are offered
- **THEN** the first filter is shown directly and the second is inside a
  closed "more filters" disclosure

#### Scenario: Active filter behind the disclosure

- **WHEN** the visitor has selected a value in a filter inside the
  disclosure
- **THEN** the disclosure is rendered open

### Requirement: Each "all" option can be labelled per type

The "all" option of a filter SHALL use the label defined for its category type
when one exists, and SHALL fall back to the generic "all" label of the
extension otherwise. The extension SHALL ship no label per type.
A label a site sets in TypoScript for every plugin of the extension, or for
one of its plugins, SHALL reach the filter on TYPO3 v12 and v13 alike.

#### Scenario: No per-type label

- **WHEN** no label is defined for the "all" option of a type
- **THEN** that filter's "all" option shows the generic label

#### Scenario: Per-type label defined

- **WHEN** a label for the "all" option of the degree type is defined
- **THEN** the degree filter's "all" option shows that label, and
  other filters show the generic label

#### Scenario: Label set for the whole extension on TYPO3 v12 or v13
- **WHEN** a site on TYPO3 v12 or v13 sets the label of the "all" option of the
  degree type for every plugin of the extension
- **THEN** the degree filter's "all" option shows that label

### Requirement: Options without results can be hidden

With the setting to hide options without results enabled, the program filter
SHALL leave out options that no program of the current result carries, except
a selected one. A filter whose options are all left out SHALL still be
offered, with its "all" option only.

#### Scenario: Hide options without results

- **WHEN** the setting is enabled and a category has no program in the
  result
- **THEN** the filter renders no option for that category
