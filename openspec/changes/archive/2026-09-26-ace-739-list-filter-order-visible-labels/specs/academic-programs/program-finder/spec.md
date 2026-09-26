## MODIFIED Requirements

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

## ADDED Requirements

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
