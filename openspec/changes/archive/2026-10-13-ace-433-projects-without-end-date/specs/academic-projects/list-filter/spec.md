## ADDED Requirements

### Requirement: The active state filter lists projects by their end date

The "Active" filter of the project list SHALL list every project without an
end date and every project whose end date is still ahead, and the "Completed"
filter SHALL list every project whose end date has passed. A project page
without any stored end date SHALL count as a project without an end date,
exactly like one whose end date was saved empty, so it is listed by "Active"
and never by "Completed". A content element preset to active or completed
projects SHALL list the same projects as the filter. This applies to TYPO3 v12
and v13 alike.

#### Scenario: Project page without any stored end date

- **WHEN** a project page was created in the page tree, imported or switched
  to the project page type, and no editor has entered an end date since
- **AND** a visitor filters the project list by "Active"
- **THEN** the project is listed

#### Scenario: Project page without any stored end date in the completed list

- **WHEN** a project page has no stored end date
- **AND** a visitor filters the project list by "Completed"
- **THEN** the project is not listed

#### Scenario: Project whose end date has passed

- **WHEN** the end date of a project page has passed
- **THEN** the "Completed" filter lists it and the "Active" filter does not

#### Scenario: Content element preset to active projects

- **WHEN** an editor presets a project list content element to active
  projects
- **THEN** a project page without any stored end date and one whose end date
  was saved empty are part of the list
- **AND** a project whose end date has passed is not
