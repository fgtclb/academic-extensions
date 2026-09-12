## Purpose

Defines how `academic_projects` determines whether a project is active or
completed, how templates read that state, and when the project card shows it.

## ADDED Requirements

### Requirement: The project state follows the end date
Every project SHALL be in the state active when it has no end date or its end
date has not passed yet, and in the state completed when its end date has
passed. Templates SHALL be able to read that state for every project, and it
SHALL never be empty.

#### Scenario: Project without end date
- **WHEN** a project has no end date
- **THEN** its state is active

#### Scenario: Project ending in the future
- **WHEN** a project's end date is tomorrow
- **THEN** its state is active

#### Scenario: Project that has ended
- **WHEN** a project's end date was yesterday
- **THEN** its state is completed

### Requirement: The state agrees with the list filter
Every project that the list filter "Active" shows SHALL be in the state
active, and every project that the list filter "Completed" shows SHALL be in
the state completed.

#### Scenario: Filtered list of active projects
- **WHEN** a visitor filters the project list by "Active"
- **THEN** every listed project is in the state active

#### Scenario: Filtered list of completed projects
- **WHEN** a visitor filters the project list by "Completed"
- **THEN** every listed project is in the state completed

### Requirement: The project card can show the state
The project list content element SHALL offer an editor option to show the
state on every project card. The option SHALL be off for new content elements
and for content elements saved before it existed. With the option on, every
card SHALL show the translated label of its state.

#### Scenario: Option off
- **WHEN** an editor does not switch the option on
- **THEN** the project cards render without a state label, as before

#### Scenario: Option on
- **WHEN** an editor switches the option on and a listed project has ended
- **THEN** that project's card shows the label "Completed" in the language of the site language
