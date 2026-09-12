## Purpose

Defines that the contacts plugin of academic_contacts4pages shows the header
of its content element as any standard content element does.

## ADDED Requirements

### Requirement: The contacts plugin renders the content element header
The contacts list plugin SHALL render the header and subheader of its content
element above its output, in the header layout the editor chose, on TYPO3 v13
and v14.

#### Scenario: Contacts list with a header
- **WHEN** an editor gives a contacts list content element the header
  "Contact" with the default layout
- **THEN** the visitor sees "Contact" as a heading above the contacts

#### Scenario: Hidden header
- **WHEN** the editor sets the header layout to "Hidden"
- **THEN** no heading is rendered for the content element

### Requirement: Role headings do not repeat the subheader
The contacts list SHALL keep rendering one heading per contact role in the
chosen header layout, and SHALL render the subheader of the content element
only once, below its header.

#### Scenario: Contacts of two roles with a subheader
- **WHEN** a contacts list with a header and a subheader shows contacts of two
  roles
- **THEN** the subheader appears once, and each role heading shows only the
  role name
