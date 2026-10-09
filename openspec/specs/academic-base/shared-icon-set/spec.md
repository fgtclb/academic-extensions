# academic-base/shared-icon-set Specification

## Purpose
Gives the academic extensions and site packages one set of frontend icons for
the actions of a control, the states a control shows and the glyphs in front of
a piece of information, so an icon that means the same is drawn once and
replaced once.

## Requirements

### Requirement: academic_base ships a shared set of frontend icons
`academic_base` SHALL register 38 icons in its frontend icon registry under
identifiers of the form `tx-academicbase-<group>-<name>`, in the groups
`action` (add, back, clear, close, collapse, delete, drag, edit, expand, help,
move-down, move-up, save, undo, upload-image, view, view-close), `state`
(hidden, visible) and `info` (calendar, company, contract, degree, department,
email, employment, information, international, link, location, partnership,
person, phone, recommendation, role, room, sector, time). Each SHALL draw the
file `Resources/Public/Icons/<group>/<name>.svg` of `academic_base`. A frontend
template of any extension or site package SHALL be able to render each of them
with the icon view helper of `academic_base`. The icon registry of the TYPO3
backend SHALL NOT know them, so the backend shows its not-found icon for
them. This applies on TYPO3 v13 and v14.

#### Scenario: A template renders a shared icon
- **WHEN** a frontend template renders `tx-academicbase-info-phone` with the
  icon view helper of `academic_base`
- **THEN** the page shows the phone icon, marked
  `data-identifier="tx-academicbase-info-phone"` and with the class
  `icon-tx-academicbase-info-phone`
- **AND** the page does not contain `default-not-found`

#### Scenario: A shared icon in the backend
- **WHEN** the TYPO3 backend renders `tx-academicbase-info-phone`
- **THEN** it shows its own not-found icon, because the icon is a frontend
  icon only

### Requirement: A shared icon follows the text colour and the font size
Every icon of the shared set SHALL render as the SVG file inlined into the page,
with or without the `inline` alternative markup, drawn in `currentColor` and
sized `1em` by `1em`, so it takes the colour and the size of the text around it
on a page without a stylesheet for icons. The markup SHALL carry no hardcoded
colour, no `id` attribute, no `style` attribute and no `<style>` element, and
SHALL NOT contain the attribution comment of the file. This applies on TYPO3
v13 and v14.

#### Scenario: An icon inside a coloured link
- **WHEN** a frontend template renders `tx-academicbase-action-edit` inside a
  link whose text colour is red, without asking for the `inline` markup
- **THEN** the page holds an inlined `<svg>` with `width="1em"`,
  `height="1em"` and `fill="currentColor"`, and no `<img>`, so the icon is
  drawn in red at the size of the link text

### Requirement: A site package replaces a shared icon everywhere
A site package that loads after `academic_base` SHALL replace an icon of the
shared set by registering the same identifier in its own
`Configuration/FrontendIcons.php`, and every frontend template of every
extension that renders the identifier SHALL show the replacement after the
system caches are flushed. An entry for the identifier in
`Configuration/Icons.php` SHALL NOT change the frontend. This applies on TYPO3
v13 and v14.

#### Scenario: A site package replaces the phone icon
- **WHEN** a site package that depends on `academic_base` registers
  `tx-academicbase-info-phone` with a file of its own in its
  `Configuration/FrontendIcons.php`, and the system caches are flushed
- **THEN** every frontend page that renders `tx-academicbase-info-phone` shows
  the file of the site package

### Requirement: The shared set carries its licence attribution
`academic_base` SHALL ship, next to the icons, a notice that names Font Awesome
Free, its version, its creator and the Creative Commons Attribution 4.0
license, states how the files were modified, and lists every SVG file of the
set with the name of its Font Awesome icon. Every file of the set SHALL be
listed there, and every listed file SHALL exist.

#### Scenario: An integrator looks up the origin of an icon
- **WHEN** an integrator opens `Resources/Public/Icons/LICENSE-font-awesome.txt`
  of `academic_base`
- **THEN** it lists `info/time.svg` as the Font Awesome icon `clock`, under the
  attribution and license the set is shipped with
