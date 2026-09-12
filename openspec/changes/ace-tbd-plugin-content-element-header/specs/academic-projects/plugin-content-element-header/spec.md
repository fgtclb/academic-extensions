## Purpose

Defines that the plugin of academic_projects shows the header of its content
element as any standard content element does.

## ADDED Requirements

### Requirement: The projects plugin renders the content element header
The project list plugin SHALL render the header and subheader of its content
element above its output, in the header layout the editor chose, on TYPO3 v13
and v14.

#### Scenario: Project list with a header
- **WHEN** an editor gives a project list content element a header with the
  default layout
- **THEN** the visitor sees the header as a heading above the list

#### Scenario: Hidden header
- **WHEN** the editor sets the header layout to "Hidden"
- **THEN** no heading is rendered for the content element
