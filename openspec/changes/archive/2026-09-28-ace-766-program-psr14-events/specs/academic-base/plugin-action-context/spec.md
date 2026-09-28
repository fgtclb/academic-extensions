## MODIFIED Requirements

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
