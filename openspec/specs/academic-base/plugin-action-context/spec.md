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
covers the demand and list events of the partner and project lists, the query
and page title events of the persons plugins and the plugin view event.

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
