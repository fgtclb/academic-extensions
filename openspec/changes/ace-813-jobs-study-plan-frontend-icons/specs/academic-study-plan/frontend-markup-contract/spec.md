## ADDED Requirements

### Requirement: Control icons come from the frontend icon registration

The study plan SHALL render the plus and the minus glyph of a semester header
and the close glyph of a module dialog from the frontend icon registration,
under `academic-study-plan-plus`, `academic-study-plan-minus` and
`academic-study-plan-close`, inlined and with the same markup as before this
change.
A site package that registers one of the three identifiers in its frontend
icon registration SHALL see its own glyph in the study plan. A registration
of the same identifier in the backend icon registration SHALL NOT change what
the study plan renders, and the backend icon registry SHALL NOT know the
three identifiers. The content element icon and the category, semester and
module record icons SHALL stay in the backend icon registry. This applies on
TYPO3 v13 and v14 alike.

#### Scenario: Stock installation

- **WHEN** a visitor opens a page with a study plan element
- **THEN** every semester header carries the plus and the minus glyph and
  every module dialog the close glyph
- **AND** the page shows no "icon not found" placeholder

#### Scenario: A site package replaces the close glyph

- **WHEN** a site package registers its own drawing for
  `academic-study-plan-close` in its frontend icon registration
- **THEN** every module dialog shows that drawing in its close button

#### Scenario: A replacement in the backend registration

- **WHEN** a site package registers its own drawing for
  `academic-study-plan-plus` in its backend icon registration only
- **THEN** the semester headers show the plus glyph the extension ships

#### Scenario: An override still asks the backend registry

- **WHEN** a template override of the semester part renders the plus glyph
  through the backend icon registry
- **THEN** it shows the "icon not found" placeholder

#### Scenario: The backend icons stay

- **WHEN** an editor opens the new content element wizard and the record list
  of a folder with study plan records
- **THEN** the study plan element, its categories, semesters and modules show
  their icons as before

### Requirement: The semester glyph switch keeps working

The plus and the minus glyph of a semester header SHALL carry the classes
`icon-academic-study-plan-plus` and `icon-academic-study-plan-minus` and the
class `icon`, which the shipped stylesheet selects, so that a closed semester
shows the plus glyph, an open one the minus glyph, and a wide viewport
neither. This SHALL hold for an override of the semester part that renders
the two identifiers from the frontend icon registration.

#### Scenario: Narrow viewport

- **WHEN** a visitor on a narrow viewport opens a semester of the study plan
- **THEN** its header shows the minus glyph instead of the plus glyph

#### Scenario: Wide viewport

- **WHEN** a visitor views the study plan on a wide viewport
- **THEN** no semester header shows a glyph

#### Scenario: Override of the semester part

- **WHEN** an integrator overrides the semester part and renders the two
  glyphs from the frontend icon registration under their identifiers
- **THEN** the glyph switch works as with the shipped part
