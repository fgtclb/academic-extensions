## Purpose

Lets the category type select show readable group headings declared in
`CategoryTypes.yaml`, and lets a project name or relabel the groups it uses.

## ADDED Requirements

### Requirement: A declared group title heads its types
The type select of a category SHALL show the title declared for a group, in
the editor's backend language, as the heading of the types of that group, on
TYPO3 v13 and v14.

#### Scenario: Programs installed
- **WHEN** an editor opens the type select of a category with the programs
  extension installed
- **THEN** the programs types appear below the programs group title instead of
  the heading `programs`

#### Scenario: Partners installed
- **WHEN** an editor opens the type select of a category with the partners
  extension installed
- **THEN** the partners types appear below the partners group title

### Requirement: An undeclared group shows its identifier
A group that types use but no package declares SHALL be shown with its
identifier as the heading.

#### Scenario: Project group without a declaration
- **WHEN** a project declares types in the group `news` without declaring the
  group
- **THEN** the type select shows those types below the heading `news`

### Requirement: A later declaration of a group wins
When two packages declare the same group, the title and icon of the package
loaded later SHALL be used.

#### Scenario: Project relabels a shipped group
- **WHEN** a project that requires the programs extension declares the group
  `programs` with its own title
- **THEN** the type select shows the project's title for the programs group

### Requirement: A declared group icon is available to integrators
The icon declared for a group SHALL be available under the icon identifier
`category_types.group.<identifier>`.

#### Scenario: Programs group icon
- **WHEN** an integrator renders the icon `category_types.group.programs`
- **THEN** the icon declared for the programs group is shown
