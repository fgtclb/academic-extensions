## Purpose

Defines the program finder content element: a compact form of category
selects that opens a program list page with the chosen filter applied.

## ADDED Requirements

### Requirement: Editors can add a program finder where the site enables it
The program finder SHALL be offered to editors on sites that include its
component set or the aggregate set of the extension, and SHALL be hidden on
other sites, on TYPO3 v13 and v14.

#### Scenario: Site with the finder set
- **WHEN** a site depends on the program finder set and an editor creates a
  content element
- **THEN** "Program finder" is offered in the academic group

#### Scenario: Site without the finder set
- **WHEN** a site depends on neither the finder set nor the aggregate set
- **THEN** "Program finder" is not offered

### Requirement: A target list page is required
The program finder SHALL require the editor to choose exactly one target page
that carries the program list.

#### Scenario: Saving without a target page
- **WHEN** an editor saves a program finder without a target page
- **THEN** the backend refuses the record and marks the field as required

### Requirement: The finder offers the configured category types in order
The program finder SHALL render one select per configured category type, in
the configured order. When the editor configured none, it SHALL offer the
site-wide filter types of the program list, and the degree and the topic, in
that order, when those are not set either.

#### Scenario: Default configuration
- **WHEN** an editor adds a finder and leaves the category types empty
- **AND** the site sets no filter types for the program list
- **THEN** the finder shows a degree select followed by a topic select

#### Scenario: Site-wide filter types
- **WHEN** an editor leaves the category types of a finder empty
- **AND** the site-wide filter types of the program list name location and
  degree, in that order
- **THEN** the finder shows a location select followed by a degree select

#### Scenario: Configured types
- **WHEN** the editor chooses the types location and degree, in that order
- **THEN** the finder shows a location select followed by a degree select and
  no other select

### Requirement: Options follow the programs in storage
Each select SHALL offer the categories of its type, and SHALL disable every
option that no program in the element's storage carries.

#### Scenario: A degree without programs
- **WHEN** no program in the finder's storage carries the degree "Diploma"
- **THEN** the option "Diploma" is shown disabled

### Requirement: Preselected categories are selected
The program finder SHALL show a category the editor preselected as the
selected option of its select when the page loads.

#### Scenario: Bachelor preselected
- **WHEN** the editor preselected the degree "Bachelor"
- **THEN** the degree select shows "Bachelor" as selected

### Requirement: Submitting opens the list pre-filtered
Submitting the program finder SHALL open the target page, whose program list
SHALL show only the programs matching every selected category and SHALL show
the selection in its own filter.

#### Scenario: Visitor searches for Master programs
- **WHEN** a visitor selects the degree "Master" and submits the finder
- **THEN** the target page opens, its program list shows only Master
  programs, and its degree filter shows "Master"

### Requirement: Stored finder elements keep working
Content elements an installation stored as program finders with a target list
page before this change SHALL render with the upstream finder, targeting the
stored page, once the installation's own registration is removed.

#### Scenario: Project-registered finder after the update
- **WHEN** an installation removes its own finder registration after the
  update
- **THEN** its existing finder elements render and submit to the target page
  they stored
