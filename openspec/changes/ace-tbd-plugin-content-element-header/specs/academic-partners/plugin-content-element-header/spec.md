## Purpose

Defines that the plugins of academic_partners show the header of their
content element as any standard content element does.

## ADDED Requirements

### Requirement: Partners plugins render the content element header
The partner list, map, partnerships list and partnerships teaser plugins SHALL
render the header and subheader of their content element above their output,
in the header layout the editor chose, on TYPO3 v13 and v14.

#### Scenario: Partner map with a header
- **WHEN** an editor gives a partner map content element a header with the
  default layout
- **THEN** the visitor sees the header as a heading above the map

#### Scenario: Hidden header
- **WHEN** the editor sets the header layout to "Hidden"
- **THEN** no heading is rendered for the content element

### Requirement: Partnership role headings do not repeat the subheader
The partnerships list and teaser SHALL keep rendering one heading per
partnership role in the chosen header layout, and SHALL render the subheader
of the content element only once, below its header.

#### Scenario: Partnerships list with two roles and a subheader
- **WHEN** a partnerships list with a header and a subheader shows partners
  of two roles
- **THEN** the subheader appears once, and each role heading shows only the
  role name
