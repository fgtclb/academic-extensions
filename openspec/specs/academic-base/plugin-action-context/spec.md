# academic-base/plugin-action-context Specification

## Purpose
Defines what an event listener can rely on from the plugin action context the
academic plugins hand to their events.

## Requirements

### Requirement: Reading the content element never fails
The plugin action context SHALL provide the content element that holds the
plugin, and SHALL provide none, without an error, when the request it was
built from carries no content element or carries something else under that
name, on TYPO3 v13 and v14.

#### Scenario: Plugin rendered as a content element
- **WHEN** a listener reads the content element from the context of a plugin
  rendered as a content element
- **THEN** it receives that content element

#### Scenario: Something else stored under the content element's name
- **WHEN** a listener reads the content element from a context whose request
  carries a value of another kind under that name
- **THEN** it receives none and no error is raised

### Requirement: One context per plugin rendering
Every event a plugin action of an academic extension dispatches while it
renders or handles one request SHALL receive the same plugin action context,
carrying the settings as the action uses them, on TYPO3 v13 and v14. This
covers the demand and list events of the partner, program and project lists
and of the program finder, the query and page title events of the persons
plugins and the plugin view event.

#### Scenario: Partner list events share their context
- **WHEN** listeners of the demand event, the list event and the plugin view
  event record the context they receive while one partner list renders
- **THEN** all three received the same context

#### Scenario: Persons list under a letter
- **WHEN** a visitor filters a paginated persons list by a letter and
  listeners of the profile query event and the plugin view event record the
  context they receive
- **THEN** both received the same context, and its settings show the
  pagination switched off

#### Scenario: Program finder events share their context
- **WHEN** listeners of the demand event, the list event and the plugin view
  event record the context they receive while one program finder renders
- **THEN** all three received the same context

### Requirement: Reading the site and the language never fails
The plugin action context SHALL provide the site and the site language of the
request it was built from, and SHALL provide none, without an error, when the
request carries no site or no site language, or carries something else under
those names, on TYPO3 v13 and v14. A request outside any site, for which
TYPO3 stores a placeholder that is not a real site, counts as carrying no
site.

#### Scenario: Plugin rendered on a page of a site
- **WHEN** a listener reads the site and the language from the context of a
  plugin rendered on a page that belongs to a site
- **THEN** it receives that site and the language the page is rendered in

#### Scenario: Request outside any site
- **WHEN** a listener reads the site from a context whose request carries the
  placeholder TYPO3 uses for "no site"
- **THEN** it receives none and no error is raised

#### Scenario: Something else stored under the language's name
- **WHEN** a listener reads the language from a context whose request carries
  a value of another kind under that name
- **THEN** it receives none and no error is raised

### Requirement: The application type fails only for a request no TYPO3 application handled
The plugin action context SHALL provide the application type of every request
a TYPO3 application handled, on TYPO3 v13 and v14. For a request that carries
no application type, reading it SHALL raise TYPO3's own error for that case,
and the developer documentation of the context SHALL name this getter as the
one that can raise.

#### Scenario: Plugin rendered in the frontend
- **WHEN** a listener reads the application type from the context of a plugin
  the frontend rendered
- **THEN** it receives the frontend application type

#### Scenario: Context built from a request created by hand
- **WHEN** code reads the application type from a context built from a
  request that no TYPO3 application handled
- **THEN** TYPO3's error for a request without an application type is raised

#### Scenario: Integrator looks up what the context hands over
- **WHEN** an integrator reads the extension points page of
  `academic_base` and the interface of the context
- **THEN** both state that reading the application type raises an error for a
  request without one, and that every other getter answers with none
