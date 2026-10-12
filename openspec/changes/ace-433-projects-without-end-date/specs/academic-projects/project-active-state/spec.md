## MODIFIED Requirements

### Requirement: The state agrees with the list filter
Every project that the list filter "Active" shows SHALL be in the state
active, and every project that the list filter "Completed" shows SHALL be in
the state completed. Every project in the state active SHALL be listed by the
"Active" filter, and every project in the state completed SHALL be listed by
the "Completed" filter, whether its end date was saved empty or was never
stored at all. This applies to TYPO3 v13 and v14 alike.

#### Scenario: Filtered list of active projects
- **WHEN** a visitor filters the project list by "Active"
- **THEN** every listed project is in the state active

#### Scenario: Filtered list of completed projects
- **WHEN** a visitor filters the project list by "Completed"
- **THEN** every listed project is in the state completed

#### Scenario: Project page without any stored end date
- **WHEN** a project page was created in the page tree, imported or switched
  to the project page type, and no editor has entered an end date since
- **AND** a visitor filters the project list by "Active"
- **THEN** the project is listed

#### Scenario: Project page without any stored end date in the completed list
- **WHEN** a project page has no stored end date
- **AND** a visitor filters the project list by "Completed"
- **THEN** the project is not listed

#### Scenario: Content element preset to active projects
- **WHEN** an editor presets a project list content element to active
  projects
- **THEN** a project page without any stored end date is part of the list
