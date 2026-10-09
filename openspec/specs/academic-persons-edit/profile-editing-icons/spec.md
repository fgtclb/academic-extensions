# academic-persons-edit/profile-editing-icons Specification

## Purpose
Defines which action icons the frontend profile editor of
`academic_persons_edit` shows, how an integrator replaces them, and which
markup the controls built in the browser and a site stylesheet can rely on.

## Requirements

### Requirement: The profile editing icons are frontend icons
The profile editor and the profile overview SHALL render their sixteen action
and state icons from the shared frontend icon set of `academic_base`, under the
identifiers `tx-academicbase-action-add`, `-back`, `-clear`, `-delete`,
`-drag`, `-edit`, `-help`, `-move-down`, `-move-up`, `-save`, `-undo`,
`-upload-image`, `-view` and `-view-close`, and `tx-academicbase-state-visible`
and `-hidden`. `academic_persons_edit` SHALL register no frontend icon of its
own, and the identifiers of 2.x and of the 3.0 development
(`academic-persons-edit-add`, `-back`, `-clear`, `-delete`, `-edit`, `-help`,
`-move-down`, `-move-up`, `-save`, `-sort-handle`, `-undo`, `-upload-image`,
`-view`, `-view-close`, `-visible` and `-hidden`) SHALL be registered nowhere,
without an alias. Every identifier a shipped template asks for SHALL be
registered, and the templates SHALL ask for exactly these sixteen. Each of them
SHALL be drawn in the colour of the surrounding text. A site package that loads
after `academic_base` and registers one of these identifiers in its own
`Configuration/FrontendIcons.php` SHALL replace that icon everywhere the editor
shows it, without a template override, and in every other academic extension
that shows the same identifier. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: The shipped icons resolve
- **WHEN** the owner of a profile opens the profile editor with documents,
  contracts and contacts
- **THEN** every action icon of the page is shown with its own identifier
- **AND** the page contains no not-found icon
- **AND** no icon of the page carries an `academic-persons-edit-` identifier

#### Scenario: A site package replaces an icon
- **WHEN** a site package that depends on `academic_base` registers
  `tx-academicbase-action-edit` with its own file in its
  `Configuration/FrontendIcons.php`
- **THEN** every edit button of the editor shows the drawing of that file

#### Scenario: An old identifier in a template override
- **WHEN** an overridden partial of the editor renders
  `academic-persons-edit-save`
- **THEN** that button shows the not-found icon

### Requirement: The profile editing icons are no backend icons
The sixteen action and state icons SHALL NOT be registered in TYPO3's backend
icon registry, so a registration of one of their identifiers in a
`Configuration/Icons.php` SHALL NOT change what the editor shows. The icon of
the profile editing content element SHALL be registered in the backend icon
registry only, under `tx-academicpersonsedit-plugin-profile-editing`, and the
page module and the new content element wizard SHALL show it for the profile
editing content element. It SHALL be drawn in the colour of the surrounding
text, so it follows the backend colour scheme. The former identifier
`persons_edit_icon` SHALL be registered nowhere. This SHALL apply on TYPO3 v13
and v14.

#### Scenario: A replacement left in the backend file
- **WHEN** a site package registers `tx-academicbase-action-delete` with its
  own file in its `Configuration/Icons.php` only
- **THEN** the delete buttons of the editor show the shipped drawing

#### Scenario: An override that still asks the backend registry
- **WHEN** an overridden partial of the editor renders
  `tx-academicbase-action-edit` through TYPO3's backend icon registry
- **THEN** that button shows TYPO3's not-found icon

#### Scenario: The content element keeps its backend icon
- **WHEN** an editor opens the new content element wizard or the page module
  with a profile editing content element
- **THEN** the content element shows the icon
  `tx-academicpersonsedit-plugin-profile-editing` in both places
- **AND** the icon takes the text colour of the backend colour scheme

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
- **WHEN** a site package replaces `tx-academicbase-action-edit` in its
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
