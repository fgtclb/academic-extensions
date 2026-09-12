## Purpose

Defines which contract data the public profile shows in its contact block and
position line, and which contract data the list items can show.

## ADDED Requirements

### Requirement: The contact block shows office hours
The detail contact block SHALL show the office hours of each contract that
has office hours, with a label and an icon, and SHALL show no office hours
row for a contract without them. This applies on TYPO3 v13 and v14.

#### Scenario: Contract with office hours
- **WHEN** a visitor opens the detail page of a profile whose contract has
  office hours
- **THEN** the contact block shows those office hours with the label "Office
  hours"

#### Scenario: Contract without office hours
- **WHEN** a profile's contract has no office hours
- **THEN** the contact block shows no office hours row for it

### Requirement: Office hours keep their formatting and stay safe
The system SHALL render the paragraphs, line breaks and links of office hours
entered in the editor, SHALL keep line breaks of plain text office hours, and
MUST NOT render script elements or event handler attributes.

#### Scenario: Plain text with line breaks
- **WHEN** office hours are stored as two lines of plain text
- **THEN** the two lines are shown on separate lines

#### Scenario: Unsafe markup
- **WHEN** office hours contain a script element
- **THEN** the page contains no script element from the office hours

### Requirement: Integrators choose what the position line shows
The position line SHALL show, for each contract and in the configured order,
the configured contract data: position, function type and organisational
unit. Without a configuration it SHALL show only the position, as before.

#### Scenario: Default configuration
- **WHEN** no position fields are configured
- **THEN** the position line shows only the contract's position

#### Scenario: Function type without a position
- **WHEN** the position line is configured to show position and function
  type, and a contract has a function type but no position
- **THEN** the position line shows the function type

### Requirement: The function type name follows the profile's gender
The function type SHALL be shown with the name maintained for the profile's
gender, and with its general name where the profile has no gender or no
gender-specific name is maintained.

#### Scenario: Female profile with a female name
- **WHEN** a profile with gender `ms` has a contract whose function type has
  a female name
- **THEN** the female name is shown

#### Scenario: Profile without gender
- **WHEN** a profile has no gender
- **THEN** the general name of the function type is shown

### Requirement: List items can show the function type
The list, listanddetail and card plugins SHALL offer the function type among
the fields to show, and SHALL then show its name, following the profile's
gender, for each contract.

#### Scenario: Function type selected for the card
- **WHEN** an editor selects the function type among the card's fields to
  show
- **THEN** each card shows the function type name of its contracts
