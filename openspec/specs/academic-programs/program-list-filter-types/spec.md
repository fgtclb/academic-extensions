# academic-programs/program-list-filter-types Specification

## Purpose
Defines which category filter selects the program list plugin offers to a
visitor and in which order, as chosen by the editor per list or by the
integrator for the whole site, how many are visible at first, how their "all"
options are labelled and whether options without results are shown.

## Requirements

### Requirement: Editors choose the filter types of a program list
The program list plugin SHALL offer a field in which the editor selects
category types of the programs group as filters and orders them. The field
SHALL offer every registered type of that group and SHALL be empty for new
and existing plugins.

#### Scenario: Editor opens the plugin
- **WHEN** an editor opens the settings of a program list plugin
- **THEN** the filter types field offers the category types of the programs
  group by their titles

#### Scenario: Type removed by the project
- **WHEN** a project removed the type `costs` from the programs group
- **THEN** the filter types field does not offer `costs`

### Requirement: The chosen filter types are offered in the chosen order
With filter types chosen, the system SHALL render a filter select for exactly
those types, in the order the editor chose them.

#### Scenario: Two types chosen
- **WHEN** the editor chose the filter types `location` and `degree`, in
  that order
- **AND** the listed programs carry categories of both types
- **THEN** the filter form shows the location select followed by the degree
  select, and no other category select

#### Scenario: Element choice overrides the site-wide filter types
- **WHEN** the site-wide filter types of the program list name only `degree`
- **AND** the editor chose the filter types `location` and `degree` for one
  element
- **THEN** that element shows the location select followed by the degree
  select

### Requirement: An empty choice falls back to the site-wide filter types
With no filter type chosen in the element, the system SHALL offer the filter
types of the site-wide filter type setting of the program list, in their
configured order. With that setting empty as well, the system SHALL render a
filter select for every type of the programs group that has at least one
category, in the type order of the group.

#### Scenario: Site-wide filter types apply
- **WHEN** the site-wide filter types of the program list name only `degree`
- **AND** the editor chose no filter type for the element
- **THEN** the filter form shows only the degree select

#### Scenario: Site-wide filter types set as a site setting
- **WHEN** a site that uses the site set of the extension sets the filter
  types of the program list to `program_type` and `degree`
- **AND** the editor chose no filter type for the element
- **THEN** the filter form shows the program type select followed by the
  degree select

#### Scenario: Existing plugin after the update
- **WHEN** a program list plugin was saved before the field existed
- **AND** the site sets no filter types
- **THEN** its filter form shows the same selects in the same order as before

#### Scenario: Project template written before the filter types
- **WHEN** a project template renders the filter form of the program list
  without handing the resolved filter types on
- **THEN** its filter form shows the same selects in the same order as before,
  whatever filter types the element or the site set

### Requirement: A chosen type without categories is left out
The system SHALL NOT render a select for a chosen type that has no category
at all, and SHALL ignore a chosen type that is no longer registered. A chosen
type with categories SHALL be offered even when none of them is carried by a
listed program: with those categories as disabled options, as without a
choice, or with its "all" option only when the site hides options without
results.

#### Scenario: Chosen type has no categories
- **WHEN** the editor chose the filter types `teaching_language` and `degree`
- **AND** no category of the type `teaching_language` exists
- **THEN** the filter form shows only the degree select

#### Scenario: Chosen type has categories on no listed program
- **WHEN** the editor chose the filter types `degree` and `costs`
- **AND** a `costs` category exists, but none of the listed programs carries it
- **AND** the site does not hide options without results
- **THEN** the filter form shows the degree select followed by the costs select
- **AND** the costs category is a disabled option

#### Scenario: Chosen type was removed later
- **WHEN** the editor chose the filter type `costs`
- **AND** the project later removed the type `costs`
- **THEN** the list renders without a `costs` select and without an error

### Requirement: Submitted filters of other types still apply
The system SHALL keep applying a submitted filter value of a type that is not
offered in the form.

#### Scenario: Link with a filter that is not offered
- **WHEN** the editor chose only the filter type `degree`
- **AND** a visitor opens a link that filters by a `location` category
- **THEN** the list shows only programs that carry that location

### Requirement: Only the first filters are visible

With a visible count greater than 0, the program list SHALL show that many of
its offered filters directly and SHALL put the remaining ones into a "more
filters" disclosure that works without JavaScript. The disclosure SHALL be
open when one of the filters inside it has an active value. A visible count
of 0, or one that covers every offered filter, SHALL show all filters directly
and render no disclosure. The visible count is set for the whole site. This
applies to TYPO3 v13 and v14 alike.

#### Scenario: Visible count of one

- **WHEN** the filter types are `location`, `degree` and `program_type` and
  the visible count is 1
- **THEN** the location filter is shown directly and the degree and program
  type filters are inside a closed "more filters" disclosure

#### Scenario: Active filter behind the disclosure

- **WHEN** the visitor filtered the list by a degree, and the degree filter is
  inside the disclosure
- **THEN** the disclosure is rendered open

#### Scenario: Visible count covering every filter

- **WHEN** the visible count is 4 and four filters are offered
- **THEN** all four filters are shown directly and no disclosure is rendered

#### Scenario: No configuration

- **WHEN** neither a visible count nor the switch for options without results
  is configured
- **AND** the site overrides no label of the filter form
- **THEN** the program filter output is the same as before the change

### Requirement: Each "all" option can be labelled per type

The "all" option of a program filter SHALL use the label defined for its
category type when one exists, and SHALL fall back to the generic "all" label
of the extension otherwise. The extension SHALL ship no label per type.
A label a site sets in TypoScript for every plugin of the extension, or for
one of its plugins, SHALL reach the filter on TYPO3 v13 and v14 alike.

#### Scenario: Per-type label defined

- **WHEN** a site defines a label for the "all" option of the degree type
- **THEN** the degree filter's "all" option shows that label, and the location
  filter's shows the generic label

#### Scenario: Label set for the whole extension on TYPO3 v13
- **WHEN** a site on TYPO3 v13 sets the label of the "all" option of the
  degree type for every plugin of the extension
- **THEN** the degree filter's "all" option shows that label, as on TYPO3 v14

### Requirement: Options without results can be hidden

With the setting to hide options without results enabled, the program list
SHALL leave out options that no program of the current result carries, except
a selected one. A filter whose options are all left out SHALL still be
offered, with its "all" option only.

#### Scenario: Hide options without results

- **WHEN** the setting is enabled and the tuition fee is the only category of
  its type and no listed program carries it
- **THEN** the costs filter renders its "all" option and no option for the
  tuition fee
