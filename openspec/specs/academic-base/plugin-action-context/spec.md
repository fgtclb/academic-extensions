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
