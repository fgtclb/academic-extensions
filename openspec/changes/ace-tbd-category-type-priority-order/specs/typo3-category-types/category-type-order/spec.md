## Purpose

Defines the order in which the category types of a group are presented in the
frontend and the backend, and how an integrator changes that order through the
category type configuration of a package.

## ADDED Requirements

### Requirement: Types of a group are ordered by priority
The system SHALL present the category types of a group ordered by their
configured priority, the highest priority first. A type without a configured
priority SHALL have the priority `0`.

#### Scenario: Three types with different priorities
- **WHEN** a group holds the types `a` with priority `0`, `b` with priority `10`
  and `c` with priority `5`
- **THEN** the types of that group are presented in the order `b`, `c`, `a`

#### Scenario: A negative priority moves a type to the end
- **WHEN** a group holds the types `a` and `b` with priority `0` and `c` with
  priority `-10`
- **THEN** `c` is presented after `a` and `b`

### Requirement: Equal priorities keep the declaration order
The system SHALL keep the order in which types of equal priority were declared,
that is the loading order of their packages and their position within each
configuration file.

#### Scenario: No priority configured anywhere
- **WHEN** no category type of a group configures a priority
- **THEN** the types of that group are presented in the same order as before
  this change

#### Scenario: Two types share a priority
- **WHEN** the types `a` and `b` both have the priority `10` and `a` is
  declared before `b`
- **THEN** `a` is presented before `b`

### Requirement: An override can change the priority of a shipped type
The system SHALL apply a priority set by an override of an existing type, so
that an integrator can reorder the types an extension ships without
redeclaring them. The override SHALL keep every other property of the type.

#### Scenario: Project moves a shipped type to the front
- **WHEN** a project package that loads after `academic_programs` overrides the
  type `degree` of the group `programs` with `useExisting` and the priority
  `100`
- **AND** no other type of that group has a priority above `0`
- **THEN** `degree` is the first type of the group `programs`
- **AND** its title and icon are the ones the shipping extension declared

### Requirement: Program outputs follow the type order
The system SHALL use the type order of the group `programs` wherever
`academic_programs` lists category types: the facts of a program page, the
facts of the program details plugin, the filter selects of the program list
plugin and the category summary of a program page in the backend.

#### Scenario: Program page facts
- **WHEN** a program page carries categories of the types `location` and
  `degree`
- **AND** `degree` has a higher priority than `location`
- **THEN** a visitor sees the degree before the location in the facts of the
  program page and of the program details plugin

#### Scenario: List filter selects
- **WHEN** the program list plugin renders its filter form
- **AND** `degree` has a higher priority than `location`
- **THEN** the degree select is rendered before the location select

#### Scenario: Backend category summary
- **WHEN** the page module renders the category summary of a program page
- **AND** `degree` has a higher priority than `location`
- **THEN** the category summary of that page lists the degree before the
  location

### Requirement: The backend type select follows the type order
The system SHALL offer the category types in the type select of a category
record in the type order of their group.

#### Scenario: Editor edits a category
- **WHEN** an editor opens the type select of a category record
- **AND** `degree` has a higher priority than `location`
- **THEN** `degree` is offered before `location`
