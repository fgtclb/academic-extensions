## MODIFIED Requirements

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
and the class `icon`, which the shipped stylesheet selects, so that a closed
semester shows the expand glyph, an open one the collapse glyph, and a wide
viewport neither. This SHALL hold for an override of the semester part that
renders the two identifiers from the frontend icon registration. The shipped
stylesheet SHALL size every icon of the study plan, so a replacement drawing
without a size of its own stays visible.

#### Scenario: Narrow viewport

- **WHEN** a visitor on a narrow viewport opens a semester of the study plan
- **THEN** its header shows the collapse glyph instead of the expand glyph

#### Scenario: Wide viewport

- **WHEN** a visitor views the study plan on a wide viewport
- **THEN** no semester header shows a glyph

#### Scenario: Override of the semester part

- **WHEN** an integrator overrides the semester part and renders the two
  glyphs from the frontend icon registration under their identifiers
- **THEN** the glyph switch works as with the shipped part

#### Scenario: A replacement drawing without a size

- **WHEN** a site package replaces the close glyph with a drawing that has no
  width and no height of its own
- **THEN** the close button of a module dialog still shows it, in the size
  of the other icons
