# academic-programs/program-finder Specification

## Purpose
Defines the program finder content element: a compact form of category
selects that opens a program list page with the chosen filter applied.

## Requirements

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
that carries the program list, and SHALL render no form without a target page
it can link, on TYPO3 v13 and v14.

#### Scenario: Saving without a target page
- **WHEN** an editor saves a program finder without a target page in the
  backend form
- **THEN** the form marks the field as required and does not save the record

#### Scenario: A finder stored without a target page
- **WHEN** a finder record without a target page reaches the frontend, for
  example written by an import
- **THEN** the finder renders no form

#### Scenario: The target page is hidden later
- **WHEN** the target page of a finder is hidden after the finder was saved
- **THEN** the finder renders no form

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
- **WHEN** the editor chooses the types topic and degree, in that order
- **AND** the site-wide filter types of the program list name location and
  degree
- **THEN** the finder shows a topic select followed by a degree select and no
  other select

#### Scenario: No offered type has a category
- **WHEN** none of the category types the finder offers has a category
- **THEN** the finder renders no form

### Requirement: Options follow the programs in storage
Each select SHALL offer the categories of its type, and SHALL disable every
option that no program in the element's storage carries. When the site hides
options without results, the select SHALL leave those options out instead, as
the filter of the program list does. This applies to TYPO3 v13 and v14 alike.

#### Scenario: A degree without programs
- **WHEN** no program in the finder's storage carries the degree "Diploma"
- **AND** the site does not hide options without results
- **THEN** the option "Diploma" is shown disabled

#### Scenario: A degree without programs, options without results hidden
- **WHEN** the site hides options without results, and no program in the
  finder's storage carries the degree "Diploma"
- **THEN** the degree select of the finder offers no "Diploma"

### Requirement: Preselected categories are selected
The program finder SHALL show a category the editor preselected as the
selected option of its select when the page loads.

#### Scenario: Bachelor preselected
- **WHEN** the editor preselected the degree "Bachelor"
- **THEN** the degree select shows "Bachelor" as selected

#### Scenario: Two preselected categories of one type
- **WHEN** the editor preselected the degrees "Master" and "Bachelor", and
  "Master" is higher in the category tree
- **THEN** the degree select shows "Master" as selected

#### Scenario: A preselection the finder cannot show
- **WHEN** the editor preselected a category of a type the finder does not
  offer, or a category no program in the finder's storage carries
- **THEN** no option is selected for it

### Requirement: Submitting opens the list pre-filtered
Submitting the program finder SHALL open the target page, whose program list
SHALL show only the programs matching every selected category, in the
sorting configured for that list, and SHALL show the selection in its own
filter unless the list hides it.

#### Scenario: Visitor searches for Master programs
- **WHEN** a visitor selects the degree "Master" and submits the finder
- **THEN** the target page opens, its program list shows only Master
  programs, and its degree filter shows "Master"

#### Scenario: The target list is sorted by title, descending
- **WHEN** the target list is configured to sort by title, descending, and a
  visitor submits the finder
- **THEN** the list shows the matching programs by title, descending

### Requirement: Stored finder elements keep working
Content elements an installation stored as program finders with a target list
page before this change SHALL render with the upstream finder, targeting the
stored page, once the installation's own registration is removed.

#### Scenario: Project-registered finder after the update
- **WHEN** an installation removes its own finder registration after the
  update
- **THEN** its existing finder elements render and submit to the target page
  they stored

### Requirement: Each "all" option can be labelled per type
The "all" option of each finder select SHALL use the label the site defines
for its category type when there is one, and SHALL fall back to the generic
"all" label otherwise, as the filter of the program list does.

#### Scenario: Per-type label defined
- **WHEN** a site defines a label for the "all" option of the degree type
- **THEN** the degree select of the finder shows that label as its first
  option, and the topic select the generic one

### Requirement: The finder shows every select
The program finder SHALL show each of its selects directly, without a "more
filters" disclosure, whatever the visible count of the program list.

#### Scenario: Visible count of the list
- **WHEN** the visible count of the program list is 1
- **THEN** the finder still shows each of its selects directly, without a
  "more filters" disclosure
