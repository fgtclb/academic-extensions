## Purpose

Defines that the plugins of academic_programs show the header of their
content element as any standard content element does.

## ADDED Requirements

### Requirement: Programs plugins render the content element header
The program list and program details plugins SHALL render the header and
subheader of their content element above their output, in the header layout
the editor chose, on TYPO3 v13 and v14.

#### Scenario: Program list with a header
- **WHEN** an editor gives a program list content element a header with the
  default layout
- **THEN** the visitor sees the header as a heading above the list

#### Scenario: Hidden header
- **WHEN** the editor sets the header layout to "Hidden"
- **THEN** no heading is rendered for the content element
