# academic-study-plan/frontend-markup-contract Specification

## Purpose
Defines the markup the study plan script relies on, how an integrator
replaces part of the study plan markup without losing its interaction, and the
optional collapsible filter.

## Requirements

### Requirement: The interaction relies on documented data attributes
The study plan SHALL provide the category filter, the highlighting of
modules, the opening and closing of semesters and the module dialogs for any
markup that carries the documented data attributes, whatever its class names
and element structure.

#### Scenario: Override with its own classes
- **WHEN** an integrator overrides the module markup with other class names
  and keeps the documented data attributes
- **THEN** filtering, highlighting, semester toggling and the module dialogs
  work, by mouse and by keyboard

### Requirement: One part can be overridden on its own
The study plan markup SHALL be split into separately overridable parts for
the filter, a semester, a module and a module dialog, so that overriding one
part leaves the others upstream.

#### Scenario: Only the module part is overridden
- **WHEN** an integrator overrides only the module part
- **THEN** the filter, the semesters and the dialogs render from the
  extension and the interaction still works

#### Scenario: The module element itself is the dialog trigger
- **WHEN** an integrator's module override marks the module element itself as
  the dialog trigger instead of an element inside it
- **THEN** activating that module opens the dialog of the same module, and no
  other module's dialog

### Requirement: Markup of 3.0 keeps working until 4.0
Markup that identifies its parts only by the class names of version 3.0 SHALL
keep working in every 3.x release.

#### Scenario: A 3.0 template override without data attributes
- **WHEN** an installation overrides the template with the class-based markup
  of 3.0
- **THEN** filtering, highlighting, semester toggling and the dialogs work as
  before

### Requirement: The filter can be collapsed
When the integrator switches the collapsible filter on, the study plan SHALL
render the category filter collapsed behind a toggle button that states
whether it is expanded and works by keyboard. With the switch off, which is
the default, the filter SHALL render expanded as before.

#### Scenario: Collapsible filter switched on
- **WHEN** the site setting for the collapsible filter is on and a visitor
  activates the toggle with the keyboard
- **THEN** the filter expands and the toggle reports itself as expanded

#### Scenario: Default configuration
- **WHEN** the site configures nothing
- **THEN** the filter renders expanded and no toggle is shown

### Requirement: The default output is unchanged
Without an override and with the default settings, the study plan SHALL show
visitors the same content and behave the same by mouse and keyboard as before
this change.

#### Scenario: Upgrade without an override
- **WHEN** an installation without a study plan override updates
- **THEN** visitors see the same study plan and its interaction is unchanged

### Requirement: Control icons come from the frontend icon registration

The study plan SHALL render the expand and the collapse glyph of a semester
header and the close glyph of a module dialog as the shared action icons of
`academic_base`, under `tx-academicbase-action-expand`,
`tx-academicbase-action-collapse` and `tx-academicbase-action-close`, from the
frontend icon registration, inlined, drawn in the colour of the surrounding
text and with a visible size. The study plan SHALL register no frontend icon
of its own.
A site package that registers one of the three identifiers in its frontend
icon registration SHALL see its own glyph in the study plan, and in every
other academic extension that renders the same identifier. A registration
of the same identifier in the backend icon registration SHALL NOT change what
the study plan renders, and the backend icon registry SHALL NOT know the
three identifiers. The content element icon, `tx-academicstudyplan-plugin-study-plan`,
and the category, semester and module record icons,
`tx-academicstudyplan-record-category`, `tx-academicstudyplan-record-semester`
and `tx-academicstudyplan-record-module`, SHALL stay in the backend icon
registry only and follow the backend colour scheme. This applies on TYPO3 v13
and v14 alike.

#### Scenario: Stock installation

- **WHEN** a visitor opens a page with a study plan element
- **THEN** every semester header carries the expand and the collapse glyph and
  every module dialog the close glyph
- **AND** each glyph is drawn in the text colour and with a visible size, and
  the close button of a module dialog is visible
- **AND** the page shows no "icon not found" placeholder

#### Scenario: A site package replaces the close glyph

- **WHEN** a site package registers its own drawing for
  `tx-academicbase-action-close` in its frontend icon registration
- **THEN** every module dialog shows that drawing in its close button
- **AND** every other academic extension that renders the close glyph shows
  it too

#### Scenario: A replacement in the backend registration

- **WHEN** a site package registers its own drawing for
  `tx-academicbase-action-expand` in its backend icon registration only
- **THEN** the semester headers show the expand glyph `academic_base` ships

#### Scenario: An override still asks the backend registry

- **WHEN** a template override of the semester part renders the expand glyph
  through the backend icon registry
- **THEN** it shows the "icon not found" placeholder

#### Scenario: The backend icons stay

- **WHEN** an editor opens the new content element wizard and the record list
  of a folder with study plan records
- **THEN** the study plan element, its categories, semesters and modules show
  their icons in the text colour of the backend colour scheme
- **AND** the wizard shows the same icon for the study plan element as the
  page module

### Requirement: The semester glyph switch keeps working

The expand and the collapse glyph of a semester header SHALL carry the classes
`icon-tx-academicbase-action-expand` and `icon-tx-academicbase-action-collapse`
and the class `icon`, which a site stylesheet selects, so that with a
stylesheet like the one of the development instances a closed semester shows
the expand glyph, an open one the collapse glyph, and a wide viewport neither.
This SHALL hold for an override of the semester part that renders the two
identifiers from the frontend icon registration. A stylesheet like that one
sizes every icon of the study plan, so a replacement drawing without a size of
its own stays visible.

#### Scenario: Narrow viewport

- **WHEN** a visitor on a narrow viewport opens a semester of the study plan
  on a site whose stylesheet switches the glyphs by those classes
- **THEN** its header shows the collapse glyph instead of the expand glyph

#### Scenario: Wide viewport

- **WHEN** a visitor views the study plan on a wide viewport of such a site
- **THEN** no semester header shows a glyph

#### Scenario: Override of the semester part

- **WHEN** an integrator overrides the semester part and renders the two
  glyphs from the frontend icon registration under their identifiers
- **THEN** the glyph switch works as with the shipped part

#### Scenario: A replacement drawing without a size

- **WHEN** a site package of such a site replaces the close glyph with a
  drawing that has no width and no height of its own
- **THEN** the close button of a module dialog still shows it, in the size
  of the other icons

### Requirement: A module dialog closes in every expected way

A module dialog SHALL close when a visitor activates its close button, clicks
on its backdrop or presses Escape, and closing it in any of these ways SHALL
stop the audio of the dialog and rewind it to its start. A click inside the
dialog, and a text selection that starts inside the dialog and is released
over the backdrop, SHALL leave it open. This applies on TYPO3 v13 and v14
alike, and to a module override that makes the module element itself the
dialog trigger.

#### Scenario: A click on the backdrop

- **WHEN** a visitor clicks on the backdrop of an open module dialog
- **THEN** the dialog closes and its audio stops

#### Scenario: Escape while the audio plays

- **WHEN** a visitor plays the audio of a module dialog and presses Escape
- **THEN** the dialog closes and its audio stops

#### Scenario: A selection released over the backdrop

- **WHEN** a visitor selects text inside a module dialog and releases the
  pointer over the backdrop
- **THEN** the dialog stays open

#### Scenario: The module element is the trigger

- **WHEN** a module override makes the module element itself the dialog
  trigger and a visitor clicks on the backdrop of its open dialog
- **THEN** the dialog closes and does not open again
