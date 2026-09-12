## Purpose

Lets an integrator add variables to the view of any academic plugin through
one event listener, without copying, subclassing or replacing a controller,
and replaces the per-action view events of the jobs and persons plugins.

## ADDED Requirements

### Requirement: Every academic plugin view can be extended by a listener
The persons, jobs, partners, programs, projects, contacts for pages and BITE
jobs plugins SHALL let an event listener add variables to the view of every
action that renders one, on TYPO3 v13 and v14. A variable added this way SHALL
be available to the plugin's templates, partials and sections.

#### Scenario: Variable added to the persons list
- **WHEN** a listener adds the variable `probe` and the persons list plugin
  renders
- **THEN** a template override of that plugin can output the value of `probe`

#### Scenario: Variable added to the partners map
- **WHEN** a listener adds the variable `probe` and the partners map plugin
  renders
- **THEN** the map template receives `probe`

### Requirement: The listener runs exactly once per rendering
The event SHALL be dispatched exactly once each time an action renders its
view, including the renderings that show an empty or not-found state.

#### Scenario: Job detail without a job
- **WHEN** the job detail plugin renders without a job to show
- **THEN** the listener is called once

#### Scenario: Selected profiles without a selection
- **WHEN** the selected profiles plugin renders without any selected profile
- **THEN** the listener is called once

### Requirement: The listener knows which plugin renders
The event SHALL give the listener the plugin name, the controller and action
names, the extension, the plugin settings, the request, the site and site
language, and the content element that holds the plugin.

#### Scenario: Listener distinguishes two plugins
- **WHEN** one listener handles both the program list and the program details
  plugin
- **THEN** it can tell them apart by plugin and action name, and reads the
  settings and the content element of the plugin being rendered

### Requirement: The replaced view events are no longer dispatched
The jobs new job form view event and the persons list, detail, selected
profiles and selected contracts events SHALL no longer be dispatched. A
listener still registered for one of them SHALL NOT be called, and SHALL NOT
prevent the site from rendering.

#### Scenario: Leftover listener of the removed persons list event
- **WHEN** a project still registers a listener for the removed persons list
  event and the persons list plugin renders
- **THEN** the page renders without error and that listener is not called

#### Scenario: Migrated listener
- **WHEN** a project moves a listener from the removed new job form event to
  the plugin view event and checks the action name
- **THEN** the new job form receives the variable the listener adds

### Requirement: The detail page title stays configurable
The persons detail plugin SHALL keep building the page title from the plugin
setting for the title format when it is set, and from the default format
otherwise.

#### Scenario: Title format from the plugin setting
- **WHEN** a detail content element sets a page title format
- **THEN** the page title of a profile detail follows that format

### Requirement: Protected variables stay protected
A variable that an action assigns after its extension points on purpose SHALL
NOT be replaceable through the plugin view event.

#### Scenario: New job form validations
- **WHEN** a plugin view event listener assigns the variable `validations`
  while the new job form renders
- **THEN** the form still carries the validations configured for jobs
