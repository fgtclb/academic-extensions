## MODIFIED Requirements

### Requirement: Options follow the programs in storage
Each select SHALL offer the categories of its type, and SHALL disable every
option that no program in the element's storage carries. With the option to
include subcategories on, a program SHALL count as carrying a category when it
carries the category or any visible subcategory of it in the programs group.
When the site hides options without results, the select SHALL leave those
options out instead, as the filter of the program list does. Which programs
the target page lists after a submission SHALL be decided by the program list
on that page and its own option. This applies to TYPO3 v13 and v14 alike.

#### Scenario: A degree without programs
- **WHEN** no program in the finder's storage carries the degree "Diploma"
- **AND** the site does not hide options without results
- **THEN** the option "Diploma" is shown disabled

#### Scenario: A degree without programs, options without results hidden
- **WHEN** the site hides options without results, and no program in the
  finder's storage carries the degree "Diploma"
- **THEN** the degree select of the finder offers no "Diploma"

#### Scenario: A degree whose subcategory is carried
- **WHEN** the finder includes subcategories
- **AND** a program in its storage carries "Bachelor of Science", a child of
  "Bachelor", and none carries "Bachelor" itself
- **THEN** "Bachelor" is a selectable option of the degree select

#### Scenario: A degree whose subcategory is carried, option off
- **WHEN** the finder does not include subcategories
- **AND** the site does not hide options without results
- **AND** a program in its storage carries "Bachelor of Science", a child of
  "Bachelor", and none carries "Bachelor" itself
- **THEN** "Bachelor" is shown as a disabled option, as before

### Requirement: Preselected categories are selected
The program finder SHALL show a category the editor preselected as the
selected option of its select when the page loads. With the option to include
subcategories on, a preselected category that no program carries itself SHALL
be selected when a program in the finder's storage carries one of its
subcategories.

#### Scenario: Bachelor preselected
- **WHEN** the editor preselected the degree "Bachelor"
- **THEN** the degree select shows "Bachelor" as selected

#### Scenario: Two preselected categories of one type
- **WHEN** the editor preselected the degrees "Master" and "Bachelor", and
  "Master" is higher in the category tree
- **THEN** the degree select shows "Master" as selected

#### Scenario: A preselection of a type the finder does not offer
- **WHEN** the editor preselected a category of a type the finder does not
  offer
- **THEN** no option is selected for it

#### Scenario: A preselection the finder cannot show
- **WHEN** the editor preselected a category no program in the finder's
  storage carries
- **AND** the finder does not include subcategories, or no program in its
  storage carries a subcategory of the preselected category
- **THEN** no option is selected for it

#### Scenario: A preselected parent whose subcategory is carried
- **WHEN** the finder includes subcategories
- **AND** the editor preselected the degree "Master"
- **AND** a program in the finder's storage carries "Master of Science", a
  child of "Master", and none carries "Master" itself
- **THEN** the degree select shows "Master" as selected
