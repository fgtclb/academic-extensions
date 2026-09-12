## Purpose

Lets integrators change what the project lists query and render through event
listeners, without replacing the plugin controller.

## ADDED Requirements

### Requirement: A listener can change the project demand

Both project list plugins SHALL notify registered listeners after the demand
is built from the content element settings and the request, and before the
projects are queried. The demand a listener hands back SHALL be the one that
is queried and assigned to the view.

#### Scenario: Listener forces the active state

- **WHEN** an integrator's listener sets the demand's active state to
  "active"
- **THEN** the project list shows only active projects

#### Scenario: No listener is registered

- **WHEN** no listener is registered
- **THEN** the project list renders exactly as before this change

### Requirement: A listener can change the project result and view

Both project list plugins SHALL notify registered listeners after the query
and before rendering, with the projects, the applicable categories, the
demand and the view. A listener SHALL be able to replace the projects and
the categories and to assign additional view variables.

#### Scenario: Listener assigns related data

- **WHEN** a listener assigns an additional variable to the view and an
  overridden template renders it
- **THEN** the rendered list contains the variable's value

### Requirement: Listeners know which plugin fired

Both notifications SHALL give the listener the request, the content element
data and the plugin settings, so a listener can tell the two project list
plugins apart.

#### Scenario: Listener acts on one plugin only

- **WHEN** a listener changes the demand only for the single-selection
  project list
- **THEN** the regular project list is unaffected
