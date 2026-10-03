## MODIFIED Requirements

### Requirement: A later declaration of a group wins
When two packages declare the same group, the values the package loaded later
declares SHALL replace the earlier ones, and the values it leaves out SHALL be
kept. A later declaration that names a new `frontendIcon` without
`frontendInlineIcon` SHALL show that file in the frontend as an image rather
than inherit the flag the earlier declaration set for its own frontend file.
A later declaration that names a new `icon` without `inlineIcon` keeps the
earlier `inlineIcon`, as before. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: Project relabels a shipped group
- **WHEN** a project that requires the programs extension declares the group
  `programs` with its own title and no icon
- **THEN** the type select shows the project's title for the programs group
- **AND** the programs group keeps the icon the programs extension declares

#### Scenario: Project replaces the frontend icon of an inlined group
- **WHEN** the programs extension declares the group `programs` with a
  `frontendIcon` and `frontendInlineIcon: true`
- **AND** a project that requires it declares the group `programs` again with
  its own `frontendIcon` file only
- **THEN** the frontend shows the project's file as an image
- **AND** the backend icon of the group is unchanged

### Requirement: A declared group icon is available to integrators
The icon declared for a group SHALL be available under the icon identifier
`category_types_group.<identifier>`, in the backend and to frontend
templates that render icons from the frontend icon registry of
`academic_base`, on TYPO3 v13 and v14. A group icon identifier SHALL never equal the icon identifier of a
category type, whatever the group and the type are named. A group declared
without an icon SHALL provide none.

#### Scenario: Programs group icon
- **WHEN** an integrator renders the icon `category_types_group.programs`, in
  a backend view or from the frontend icon registry
- **THEN** the icon declared for the programs group is shown

#### Scenario: A group named group
- **WHEN** a project declares a group `group` with the type `programs`, both
  with an icon, next to the shipped group `programs`
- **THEN** the type icon `category_types.group.programs` shows the type's file
- **AND** the group icon `category_types_group.programs` shows the programs
  group's file
