# academic-persons-edit/profile-editing-icons Specification

## Purpose
Defines which action icons the frontend profile editor of
`academic_persons_edit` shows, how an integrator replaces them, and which
markup the controls built in the browser and a site stylesheet can rely on.

## Requirements

### Requirement: The profile editing icons are frontend icons
The profile editor and the profile overview SHALL render their sixteen action
icons from the frontend icon registration of `academic_persons_edit`
(`Configuration/FrontendIcons.php`), under the identifiers
`academic-persons-edit-add`, `-back`, `-clear`, `-delete`, `-edit`, `-help`,
`-move-down`, `-move-up`, `-save`, `-sort-handle`, `-undo`, `-upload-image`,
`-view`, `-view-close`, `-visible` and `-hidden`. Every identifier a shipped
template asks for SHALL be registered there, and every registered action icon
SHALL be used by a shipped template. A site package that loads after
`academic_persons_edit` and registers one of these identifiers in its own
`Configuration/FrontendIcons.php` SHALL replace that icon everywhere the editor
shows it, without a template override. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: The shipped icons resolve
- **WHEN** the owner of a profile opens the profile editor with documents,
  contracts and contacts
- **THEN** every action icon of the page is shown with its own identifier
- **AND** the page contains no not-found icon

#### Scenario: A site package replaces an icon
- **WHEN** a site package that depends on `academic_persons_edit` registers
  `academic-persons-edit-edit` with its own file in its
  `Configuration/FrontendIcons.php`
- **THEN** every edit button of the editor shows the drawing of that file

### Requirement: The profile editing icons are no backend icons
The sixteen action icons SHALL NOT be registered in TYPO3's backend icon
registry, so a registration of one of their identifiers in a
`Configuration/Icons.php` SHALL NOT change what the editor shows. The icon of
the profile editing content element SHALL stay in the backend icon registry and
SHALL NOT be offered by the frontend icon registration. This SHALL apply on
TYPO3 v13 and v14.

#### Scenario: A replacement left in the backend file
- **WHEN** a site package registers `academic-persons-edit-delete` with its own
  file in its `Configuration/Icons.php` only
- **THEN** the delete buttons of the editor show the shipped drawing

#### Scenario: An override that still asks the backend registry
- **WHEN** an overridden partial of the editor renders
  `academic-persons-edit-edit` through TYPO3's backend icon registry
- **THEN** that button shows TYPO3's not-found icon

#### Scenario: The content element keeps its backend icon
- **WHEN** an editor opens the new content element wizard or the page module
  with a profile editing content element
- **THEN** the content element shows the same icon as before

### Requirement: Controls built in the browser carry the same icons
The controls the editor builds in the browser from the markup the page ships,
the help button of a field, the add control of a contact section, the
visibility, view, move, delete and edit controls of a contact row, and the edit
button of a newly created document row or field, SHALL show the same icons as
the controls the server renders, including an icon a site package replaced.
This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A contact row added in the browser
- **WHEN** the owner of a profile adds a contact to a contract in the editor
- **THEN** the new row shows its visibility, view, move, delete and edit
  controls with their icons
- **AND** none of them shows a not-found icon

#### Scenario: A replaced icon in a built control
- **WHEN** a site package replaces `academic-persons-edit-edit` in its
  `Configuration/FrontendIcons.php` and the owner adds a document row
- **THEN** the edit button of the new row shows the drawing of the site
  package

### Requirement: The markup of a profile editing icon stays the same
Each action icon SHALL be rendered as an element with the classes `icon`,
`icon-size-<size>`, `icon-state-default` and `icon-<identifier>`, the attributes
`data-identifier="<identifier>"` and `aria-hidden="true"`, and an inner element
with the class `icon-markup` that contains the drawing as an inline SVG,
exactly as before this change. The size SHALL be `small` wherever it was
`small` before. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A site stylesheet keeps applying
- **WHEN** a site stylesheet styles the editor's icons through `.icon-markup
  svg`
- **THEN** the rule still applies to every action icon of the editor

#### Scenario: The visibility glyphs switch as before
- **WHEN** the owner hides a contact row
- **THEN** the hidden glyph is shown and the visible glyph is hidden, as
  before this change
