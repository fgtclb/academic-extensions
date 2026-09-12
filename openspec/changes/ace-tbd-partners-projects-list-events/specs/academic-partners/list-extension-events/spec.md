## Purpose

Lets integrators change what the partner list and map query and render
through event listeners, without replacing the plugin controller.

## ADDED Requirements

### Requirement: A listener can change the partner demand

The partner list and map SHALL notify registered listeners after the demand
is built from the content element settings and the request, and before the
partners are queried. The demand a listener hands back SHALL be the one that
is queried and assigned to the view.

#### Scenario: Listener adds a category filter

- **WHEN** an integrator's listener restricts the demand to the region
  "Europe" on a list without a visitor filter
- **THEN** the partner list shows only partners of that region

#### Scenario: No listener is registered

- **WHEN** no listener is registered
- **THEN** the partner list renders exactly as before this change

### Requirement: A listener can change the partner result and view

The partner list and map SHALL notify registered listeners after the query
and before rendering, with the partners, the applicable categories, the
demand and the view. A listener SHALL be able to replace the partners and
the categories and to assign additional view variables.

#### Scenario: Listener assigns a view variable

- **WHEN** a listener assigns a variable to the view and an overridden
  template renders it
- **THEN** the rendered list contains the variable's value

#### Scenario: Listener replaces the result

- **WHEN** a listener replaces the partners with a subset
- **THEN** only that subset is rendered

### Requirement: Listeners know which plugin fired

Both notifications SHALL give the listener the request, the content element
data, the plugin settings and whether the list or the map is rendering.

#### Scenario: Listener acts on the map only

- **WHEN** a listener changes the demand only when the map is rendering
- **THEN** the partner list on the same page is unaffected

### Requirement: The map still skips partners without coordinates

The partner map MUST NOT draw partners without coordinates, even when a demand
listener changes the demand.

#### Scenario: Listener widens the demand on the map

- **WHEN** a listener replaces the map's demand with a fresh one
- **THEN** partners without coordinates are still not drawn
