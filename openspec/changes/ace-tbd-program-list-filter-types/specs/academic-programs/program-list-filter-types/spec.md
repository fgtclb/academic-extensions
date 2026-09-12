## Purpose

Defines which category filter selects the program list plugin offers to a
visitor and in which order, as chosen by the editor per list.

## ADDED Requirements

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
filter select for every type of the programs group that has categories among
the listed programs, in the type order of the group.

#### Scenario: Site-wide filter types apply
- **WHEN** the site-wide filter types of the program list name only `degree`
- **AND** the editor chose no filter type for the element
- **THEN** the filter form shows only the degree select

#### Scenario: Existing plugin after the update
- **WHEN** a program list plugin was saved before the field existed
- **AND** the site sets no filter types
- **THEN** its filter form shows the same selects in the same order as before

### Requirement: A chosen type without categories is left out
The system SHALL NOT render a select for a chosen type that has no categories
among the listed programs, and SHALL ignore a chosen type that is no longer
registered.

#### Scenario: Chosen type has no categories
- **WHEN** the editor chose the filter types `degree` and `costs`
- **AND** none of the listed programs carries a `costs` category
- **THEN** the filter form shows only the degree select

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
