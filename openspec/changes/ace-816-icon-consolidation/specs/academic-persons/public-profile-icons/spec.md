## MODIFIED Requirements

### Requirement: The public profile icons are frontend icons
The detail view SHALL render its seven icons, the email, phone, address, room
and office hours icons of the contact block and the expand and collapse icons
of the fold-out entries, from the frontend icon registration of
`academic_base`, under the shared identifiers `tx-academicbase-info-email`,
`tx-academicbase-info-phone`, `tx-academicbase-info-location`,
`tx-academicbase-info-room`, `tx-academicbase-info-time`,
`tx-academicbase-action-expand` and `tx-academicbase-action-collapse`.
`academic_persons` SHALL register no frontend icon of its own, and the
identifiers it used during the 3.0 development (`academic-persons-envelope`,
`academic-persons-phone`, `academic-persons-address`, `academic-persons-room`,
`academic-persons-clock`, `academic-persons-detail-plus`,
`academic-persons-detail-minus`) SHALL resolve to nothing. A site package that
loads after `academic_base` and registers one of the shared identifiers in its
own `Configuration/FrontendIcons.php` SHALL replace that icon in the detail
view without a template override. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: The shipped icons resolve
- **WHEN** a visitor opens the detail page of a profile with an email
  address, a phone number, an address, a room, office hours and a fold-out
  entry
- **THEN** each of the seven icons is shown with its shared identifier
- **AND** the page contains no not-found icon

#### Scenario: A site package replaces an icon
- **WHEN** a site package that depends on `academic_base` registers
  `tx-academicbase-info-email` with its own file in its
  `Configuration/FrontendIcons.php`
- **THEN** the email rows of the detail view show the drawing of that file

#### Scenario: A shipped icon is a Font Awesome drawing
- **WHEN** the detail view renders the phone icon
- **THEN** its drawing is the Font Awesome Free solid phone of
  `academic_base`, not the Bootstrap Icons drawing of the 3.0 development

### Requirement: The public profile icons are no backend icons
The seven icons SHALL NOT be registered in TYPO3's backend icon registry, so a
registration of one of their identifiers in a `Configuration/Icons.php` SHALL
NOT change what the detail view shows. The record icons of the nine tables and
the icons of the six persons content elements SHALL stay in the backend icon
registry, under the identifiers `tx-academicpersons-record-<name>` and
`tx-academicpersons-plugin-<name>`, drawn in the text colour of the backend,
and SHALL NOT be offered by the frontend icon registration. Each content
element SHALL show the same icon in the new content element wizard and in the
page module: the list, list and detail and detail elements
`tx-academicpersons-plugin-persons`, the profile card
`tx-academicpersons-plugin-card`, and the selected profiles and the selected
contracts `tx-academicpersons-plugin-selected-profiles`. The 2.x identifiers
(`tx_academicpersons_domain_model_<table>`, `persons_icon`) SHALL resolve to
nothing. This SHALL apply on TYPO3 v13 and v14.

#### Scenario: A replacement left in the backend file
- **WHEN** a site package registers `tx-academicbase-info-phone` with its own
  file in its `Configuration/Icons.php` only
- **THEN** the phone rows of the detail view show the shipped drawing

#### Scenario: An override that still asks the backend registry
- **WHEN** an overridden contact partial renders `tx-academicbase-info-email`
  through TYPO3's backend icon registry
- **THEN** that row shows TYPO3's not-found icon

#### Scenario: The backend keeps its icons
- **WHEN** an editor opens the list module on a folder with persons records,
  or the new content element wizard
- **THEN** the records and the persons content elements show their
  `tx-academicpersons-record-<name>` and `tx-academicpersons-plugin-<name>`
  icons, inlined and drawn in the text colour of the backend colour scheme

#### Scenario: Wizard and page module agree
- **WHEN** an editor adds a profile card, a selected profiles or a selected
  contracts element through the new content element wizard
- **THEN** the wizard shows the icon of that element, not a core icon
- **AND** the page module shows the same icon for the element

#### Scenario: A 2.x identifier in a site package
- **WHEN** a site package names `persons_icon` in its TSconfig or replaces
  `tx_academicpersons_domain_model_profile` in its `Configuration/Icons.php`
- **THEN** the backend shows TYPO3's not-found icon for that TSconfig entry
- **AND** the profile records keep the shipped icon
