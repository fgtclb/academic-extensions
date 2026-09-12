## Purpose

Defines that the plugins of academic_persons show the header of their content
element as any standard content element does.

## ADDED Requirements

### Requirement: Persons plugins render the content element header
The profile list, card, detail, selected profiles and selected contracts
plugins SHALL render the header and subheader of their content element above
their output, in the header layout the editor chose, on TYPO3 v13 and v14.

#### Scenario: Profile list with a header
- **WHEN** an editor gives a profile list content element the header "Our
  professors" with the default layout
- **THEN** the visitor sees "Our professors" as a heading above the list

#### Scenario: Hidden header
- **WHEN** the editor sets the header layout to "Hidden"
- **THEN** no heading is rendered for the content element

#### Scenario: Selection without profiles
- **WHEN** a selected profiles content element has a header and no profile
  is selected
- **THEN** the header is still rendered
