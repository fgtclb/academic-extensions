## Purpose

Defines which icons the public profile detail view of `academic_persons`
shows, how an integrator replaces them, and which markup a site stylesheet can
rely on for them.

## ADDED Requirements

### Requirement: The public profile icons are frontend icons

The detail view SHALL render its seven icons, the email, phone, address, room
and office hours icons of the contact block and the plus and minus of the
fold-out entries, from the frontend icon registration of `academic_persons`
(`Configuration/FrontendIcons.php`), under the identifiers
`academic-persons-envelope`, `academic-persons-phone`,
`academic-persons-address`, `academic-persons-room`, `academic-persons-clock`,
`academic-persons-detail-plus` and `academic-persons-detail-minus`. A site
package that loads after `academic_persons` and registers one of these
identifiers in its own `Configuration/FrontendIcons.php` SHALL replace that
icon in the detail view without a template override. This SHALL apply on
TYPO3 v13 and v14.

#### Scenario: The shipped icons resolve

- **WHEN** a visitor opens the detail page of a profile with an email
  address, a phone number, an address, a room, office hours and a fold-out
  entry
- **THEN** each of the seven icons is shown with its own identifier
- **AND** the page contains no not-found icon

#### Scenario: A site package replaces an icon

- **WHEN** a site package that depends on `academic_persons` registers
  `academic-persons-envelope` with its own file in its
  `Configuration/FrontendIcons.php`
- **THEN** the email rows of the detail view show the drawing of that file

### Requirement: The public profile icons are no backend icons

The seven icons SHALL NOT be registered in TYPO3's backend icon registry, so a
registration of one of their identifiers in a `Configuration/Icons.php` SHALL
NOT change what the detail view shows. The record icons of the nine tables and
the plugin icon of `academic_persons` SHALL stay in the backend icon registry
and SHALL NOT be offered by the frontend icon registration. This SHALL apply on
TYPO3 v13 and v14.

#### Scenario: A replacement left in the backend file

- **WHEN** a site package registers `academic-persons-phone` with its own file
  in its `Configuration/Icons.php` only
- **THEN** the phone rows of the detail view show the shipped drawing

#### Scenario: An override that still asks the backend registry

- **WHEN** an overridden contact partial renders `academic-persons-envelope`
  through TYPO3's backend icon registry
- **THEN** that row shows TYPO3's not-found icon

#### Scenario: The backend keeps its icons

- **WHEN** an editor opens the list module on a folder with persons records,
  or the new content element wizard
- **THEN** the records and the persons plugins show the same icons as before

### Requirement: The markup of a public profile icon stays the same

Each of the seven icons SHALL be rendered as an element with the classes
`icon`, `icon-size-small`, `icon-state-default` and `icon-<identifier>`, the
attributes `data-identifier="<identifier>"` and `aria-hidden="true"`, and an
inner element with the class `icon-markup` that contains the drawing as an
inline SVG, exactly as before this change. This SHALL apply on TYPO3 v13 and
v14.

#### Scenario: A site stylesheet keeps applying

- **WHEN** a site stylesheet sizes the icons of the detail view through
  `.academic-persons-detail .icon-markup`
- **THEN** the rule still applies to every icon of the detail view

#### Scenario: The icon follows the text colour

- **WHEN** the detail view renders the room icon
- **THEN** its drawing is an inline `<svg>` drawn in `currentColor`, not an
  `<img>`
